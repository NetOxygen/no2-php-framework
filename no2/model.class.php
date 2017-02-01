<?php
/**
 * This base class handle primitive database object manipulation. It mean that
 * INSERT, SELECT, UPDATE and DELETE are handled via methods of this class.
 *
 * - INSERT and UPDATE are implemented via the save() method.
 * - SELECT is implemented via where(), all(), first() and find_*() static methods.
 * - DELETE is implemented via the _delete() and destroy() methods.
 *
 * Database fields are implemented using the __db_data property. The db_infos()
 * method "store" the fields list and logic.
 *
 * @note
 *   While the No2_SQLQuery class should allow all kind of query,
 *   No2_AbstractModel has its own philosophy. It want to make SELECT queries
 *   easier and fast. INSERT, UPDATE and DELETE are expected to have an
 *   overhead because of in-code validations for (INSERT and UPDATE) and
 *   cleanup (DELETE). As a result it provide a lot of helper functions to
 *   build No2_SQLQuery queries but will restrict them to be SELECT (for
 *   find(), where(), associations etc.) but only a few ways (preferably only
 *   one) to perform INSERT, UPDATE and DELETE operations.
 *
 * FIXME: this class should not be as thightly coupled to No2_SQLQuery. We
 * should write a generic No2_DatabaseQuery interface and let the model class
 * configure the type if needed (just like No2_AbstractController can choose
 * No2_View). While this would be quick to do here it is hard and time
 * consuming to define the interface.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
abstract class No2_AbstractModel
{
    /**
     * The table associated to this class. Has to be overrided by subclasses.
     */
    public static $table = 'without_doubt_an_invalid_table_name';

    /**
     * define database fields and options.
     *
     * No2_AbstractModel use the following properties:
     *  - type: used to convert when setting a new value
     *    (see __set_translators()) and when saving to the database
     *    (see massage_for_storage_translators()).
     *  - default: the default value on object creation
     *  - protected: if set to true, the field is not updated by
     *    update_properties(). Useful for fields that are not intended to be
     *    user writable, like creation/update timestamps, ids etc.
     *
     * More properties can be inserted and handlded at the subclasses level.
     */
    public function db_infos()
    {
        return [
            // 'field_name' => ['type' => 'integer', 'default' => 42, 'protected' => true],
        ];
    }

    /**
     * add a default scope for id.
     */
    public static function scope_id($q, $id)
    {
        return $q->where('id = :scope_id', [':scope_id' => $id]);
    }

    /**
     * contains values that should be saved / loaded from the database
     */
    protected $__db_data = [
        // 'id' => 42 ...
    ];

    /**
     * the database profile to use when save() / destroy() is called.
     */
    protected $__db_profile = No2_SQLQuery::DEFAULT_PROFILE;

    /**
     * array of database fields that mismatch the database's values.
     *
     * This array is used to know if save() should query the storage engine
     * and, if it needed, which fields need to be updated. Keys are fields name
     * and value usually true.
     */
    protected $__dirty_db_data = [
        // 'email' => true ...
    ];

    /**
     * true if this object is a new record (it does not exist in the database),
     * false otherwise.
     */
    protected $__is_new_record = true;

    /**
     * test if this object lives in the database.
     *
     * @return
     *   false if there is an entry in the storage engine  associated with this
     *   Object, true otherwise.
     */
    public function is_new_record()
    {
        return ($this->__is_new_record);
    }

    /**
     * check if this model has dirty properties.
     *
     * @return
     *   true if at least one property value differ from the db, false
     *   otherwise.
     */
    public function is_dirty()
    {
        return ($this->is_new_record() || !empty($this->__dirty_db_data));
    }

    /**
     * Overloaded in order to ensure to store and convert database fields
     * values into __db_data and update __dirty_db_data when appropriate.
     *
     * @param $name
     *   the name of the property to set.
     *
     * @param $value
     *   the new value to bind to $name.
     *
     * @see __set_translators()
     * @see http://www.php.net/manual/en/language.oop5.overloading.php
     */
    public function __set($name, $value)
    {
        $db_infos = $this->db_infos();

        if (!array_key_exists($name, $db_infos)) {
            $this->$name = $value;
        } else { // it is a database field
            if (!is_null($value) && array_key_exists('type', $db_infos[$name])) {
                // there is a value to cast and this field has a cast type
                $type        = $db_infos[$name]['type'];
                $translators = $this->__set_translators();
                if (array_key_exists($type, $translators)) {
                    // a translator exist for this type
                    $tr    = $translators[$type];
                    $value = $tr($value);
                } else {
                    No2_Logger::warn(
                        "BaseModel::__set(): invalid cast type for $name property: $type"
                    );
                }
            }

            // do nothing if the new value match the current one.
            if (array_key_exists($name, $this->__db_data) && $this->__db_data[$name] === $value)
                return $value;

            $this->__dirty_db_data[$name] = true;
            $this->__db_data[$name]       = $value;
        }
        return $value;
    }

    /**
     * __set() helper returning an array of type translators functions expected
     * to be overrided by subclasses.
     *
     * This implementation return an empty array and let the translator
     * implementation details to subclasses.
     *
     * @see __set()
     */
    public function __set_translators()
    {
        return [ ];
    }

    /**
     * Overloaded to return database fields by reference from the __db_data
     * array.
     *
     * @param $name
     *   the name of the property to get.
     *
     * @return
     *   null if there is no property $name, its value otherwise.
     *
     * @see http://www.php.net/manual/en/language.oop5.overloading.php
     */
    public function &__get($name)
    {
        /* see http://www.php.net/manual/en/language.references.return.php#102139 */
        static $null_guard = null;

        if (array_key_exists($name, $this->db_infos())) { // it is a database field
            if (array_key_exists($name, $this->__db_data))
                return $this->__db_data[$name];
            else
                return $null_guard;
        } else {
            return $this->$name;
        }
        /* NOTREACHED */
    }

    /**
     * Overloaded to handle special cases when $name is a database field.
     *
     * @param $name
     *   the name of the property to check.
     *
     * @see http://www.php.net/manual/en/language.oop5.overloading.php
     */
    public function __isset($name)
    {
        if (array_key_exists($name, $this->db_infos())) { // it is a database field
            return array_key_exists($name, $this->__db_data);
        } else {
            return isset($this->$name);
        }
        /* NOTREACHED */
    }

    /**
     * Overloaded to handle special cases when $name is a database field.
     *
     * @param $name
     *   the name of the property to unset.
     *
     * @see http://www.php.net/manual/en/language.oop5.overloading.php
     */
    public function __unset($name)
    {
        if (array_key_exists($name, $this->db_infos())) { // it is a database field
            unset($this->__db_data[$name]);
        } else {
            unset($this->$name);
        }
    }

    /**
     * Ctor used by the database.
     *
     * When an object is loaded from the database, all its fields should be
     * set and clean (i.e. not dirty). This ctor just ensure that all fields
     * are marked clean because __construct() will assign and mark them
     * dirty.
     *
     * @note
     *   __construct() and load() doesn't check any values and will set private
     *   / protected properties since the code is executed in the model's class
     *   context. As a result, the $properties argument should be considered
     *   safe BEFORE being passed to theses methods. If $properties is not
     *   safe, update_properties() should be used instead.
     */
    public static function load($properties = [], $profile)
    {
        $instance = new static($properties);
        $instance->__db_profile    = $profile;
        $instance->__dirty_db_data = [];
        $instance->__is_new_record = false;

        return $instance;
    }

    /**
     * This constructor should be used for new database row. To find a Model,
     * use where() or find() static methods.
     *
     * @param $properties
     *   array of initialized properties. It can be used to set object
     *   properties of this model and / or database field.
     *
     * @note
     *   __construct() and load() doesn't check any values and will set private
     *   / protected properties since the code is executed in the model's class
     *   context. As a result, the $properties argument should be considered
     *   safe BEFORE being passed to theses methods. If $properties is not
     *   safe, update_properties() should be used instead.
     */
    public function __construct($properties = [])
    {
        $this->__validation_errors = [];
        $this->__dirty_db_data     = [];

        /*
         * this check is needed because $properties are often given directly
         * from $_REQUEST. It can easily be null.
         */
        if (is_array($properties)) {
            foreach ($properties as $name => $value)
                $this->$name = $value;
        }

        /*
         * set each default values if needed.
         */
        foreach ($this->db_infos() as $field => $infos) {
            if (array_key_exists('default', $infos) && !isset($this->$field))
                $this->$field = $infos['default'];
        }
    }

    /**
     * return a collection of all this model.
     *
     * By calling this method it means that you expect many results. As such a
     * select() will always return an array (empty when there is no results)
     * unless you've modified the expectation (for exemple by calling first()
     * on the selector).
     *
     * @return
     *   a No2_SQLQuery object.
     */
    public static function all()
    {
        $query = new No2_SQLQuery(get_called_class(), No2_SQLQuery::EXPECT_MANY);
        $query->restrict_to(No2_SQLQuery::SELECT, true);
        return $query;
    }

    /**
     * return a defined number of entries.
     *
     * @return
     *   a No2_SQLQuery object.
     */
    public static function first($n = 1)
    {
        return static::all()->first($n);
    }

    /**
     * This method is a shortcut to SELECT a row given an id.
     *
     * @note
     *   This method will always use the 'default' database profile. Consider
     *   using ::first()->query_on($profile)->id($id)->select() when a
     *   non-default database profile is needed.
     *
     * @param $id
     *   the id value to find.
     *
     * @return
     *   An instance of static a matching row is found, null otherwise.
     */
    public static function find($id)
    {
        return static::first()->id($id)->select();
    }

    /**
     * Find row(s) with an SQL statment.
     *
     * Allow to create a complexe SELECT query that will return this type of
     * Model.
     *
     * @see
     *   No2_SQLQuery::execute(), No2_Database::execute()
     *
     * @return
     *  - null if there is no row matching
     *  - An instance if there is only one row matching
     *  - An array of instances if many of them match
     */
    public static function find_by_sql($sql, $arguments = [], $options = [])
    {
        return No2_SQLQuery::execute($sql, $arguments, array_merge($options, [
            'factory' => get_called_class(),
        ]));
    }

    /**
     * Find many rows with an SQL statment.
     *
     * Allow to create a complexe SELECT query that will return this type of
     * Model. This method is a wrapper around find_by_sql() which always return
     * an array.
     *
     * @see
     *   No2_SQLQuery::execute(), No2_Database::execute()
     *
     * @return
     *  An array with all instances found (one or zero results will still be
     *  returned in a array).
     */
    public static function find_all_by_sql($sql, $arguments = [], $options = [])
    {
        return No2_SQLQuery::execute($sql, $arguments, array_merge($options, [
            'factory'              => get_called_class(),
            'return_as_collection' => true,
        ]));
    }

    /**
     * errors on this model that prevent it to be saved. This is an associative
     * array with field as key, and array of error message corresponding to the
     * field. This variable is private in order to avoid it being set by
     * __construct().
     *
     * @see errors(), error_add()
     */
    private $__validation_errors;

    /**
     * get the errors on this model from the last validation.
     */
    public function errors($field = null)
    {
        if (is_null($field))
            return $this->__validation_errors;
        if (array_key_exists($field, $this->__validation_errors))
            return $this->__validation_errors[$field];
        return null;
    }

    /**
     * add an error to a field or this object. Used by validate().
     *
     * @param $field
     *   the field where the error is on. As a convention, if you need to set a
     *   global error on the object (that isn't related to a database field
     *   use 0 as $field argument.
     *
     * @param $message
     *   a description of the error.
     */
    protected function error_add($field, $message)
    {
        if (!isset($this->__validation_errors[$field]))
            $this->__validation_errors[$field] = [];
        array_push($this->__validation_errors[$field], $message);
    }

    /**
     * This method is intended to be overrided in subclasses to support the
     * validation logic of each model. If an error is detected, error_add()
     * should be used.
     *
     * @return
     *   nothing. All errors should be reported via the error_add() method.
     */
    protected function validate()
    {
        /* do nada. */
    }

    /**
     * test if the current object is valid. Being valid means that it can be
     * saved in the database in its current state.
     *
     * @return
     *   true if this is valid, false otherwise.
     */
    public function is_valid()
    {
        $this->__validation_errors = []; // reset the error array
        $this->validate();
        return (empty($this->__validation_errors));
    }

    /**
     * update this model's properties.
     *
     * This method fiter some properties given in order to avoid user injection
     * of internal data field (like id). The 'protected' db_infos() property is
     * used to protect fields that should be filtered by this function.
     *
     * @param $properties
     *   An array of new properties.
     */
    public function update_properties($properties)
    {
        $db_infos = $this->db_infos();
        $reflect  = new ReflectionClass($this);

        foreach ($properties as $name => $val) {
            /*
             * first try to set $name as a db field. It is expected to match
             * most of the time so it would be costly to test the property
             * reflection first (which would be throw happy).
             */
            if (array_key_exists($name, $db_infos)) {
                $field_infos = $db_infos[$name];
                // filter out protected properties.
                if (array_key_exists('protected', $field_infos) && $field_infos['protected']) {
                    No2_Logger::warn(get_class($this) . '#update_properties: ' .
                        "filtering out $name (protected)"
                    );
                } else {
                    $this->$name = $val; // see __set()
                }
            } else {
                $p = $reflect->getProperty($name);
                if ($p->isPublic() && !$p->isStatic())
                    $p->setValue($this, $val);
                else {
                    $msg = 'Undefined property ' . get_class($this) . "::\$$name";
                    throw new InvalidArgumentException($msg);
                }
            }
        }
    }

    /**
     * prepare a value to be inserted into the database.
     *
     * Use the same kind of mechanism as __set() using
     * massage_for_storage_translators() instead of __set_translators().
     *
     * @param $key
     *   The property's name
     *
     * @param $value
     *   The value to be prepared.
     *
     * @return
     *   The value to be inserted in the database.
     *
     * @see massage_for_storage_translators()
     */
    protected function massage_for_storage($name, $value)
    {
        $db_infos = $this->db_infos();

        if (
            !is_null($value) // there is a value to cast
            && array_key_exists($name,  $db_infos) // it is a database field
            && array_key_exists('type', $db_infos[$name]) // this field has a cast type
        ) {
            $type        = $db_infos[$name]['type'];
            $translators = $this->massage_for_storage_translators();
            if (array_key_exists($type, $translators)) { // a translator exist for this type
                $tr   = $translators[$type];
                $value = $tr($value);
            }
        }
        return $value;
    }

    /**
     * massage_for_storage() helper returning an array of type translators
     * functions expected to be overrided by subclasses.
     *
     * This implementation return an empty array and let the translator
     * implementation details to subclasses.
     *
     * @see massage_for_storage()
     */
    public function massage_for_storage_translators()
    {
        return [ ];
    }

    /**
     * Save the current Object. If the object was loaded by the database,
     * update() will be called and otherwise insert() will be called. If the
     * do_validate param evaluate to true, the return value of is_valid() will
     * be checked before any attempt to save this.
     *
     * @param $do_validate
     *   Control if the object's state should be validated (calling and
     *   checking the return value of is_valid()). If $do_validate evaluate to
     *   true, is_valid() is called and false will be returned on validation
     *   failure.
     *
     * @return
     *   false on error, true otherwise.
     */
    public function save($do_validate = true)
    {
        $is_new_record = $this->is_new_record();
        if ($do_validate && !$this->is_valid())
            return false;

        // filter to only SET dirty properties.
        $properties = [];
        foreach ($this->__dirty_db_data as $prop => $dirty) {
            if ($dirty)
                $properties[$prop] = $this->massage_for_storage($prop, $this->__db_data[$prop]);
        }

        if (!$is_new_record && empty($properties)) {
            // We're doing an UPDATE with no new values. Don't commit anything
            // to the db and return true to notice the caller that somehow,
            // save was a success.
            $success = true;
            $rows    = [];
        } else {
            $query = new No2_SQLQuery(get_class($this));
            $query->query_on($this->__db_profile);
            if ($is_new_record)
                $rows = $query->insert1($properties);
            else
                $rows = $query->id($this->id)->set($properties)->update($this->id);
            $success = !is_null($rows);
        }

        if (!$success) {
            /*
             * this is strange because we passed through is_valid(). There is
             * some inconsistancies between programmed validation and
             * database validation.
             */
            No2_Logger::err(get_class($this) . '::save: ' .
                'database failed to save me:' .  print_r($this, true));
        } else { // the database query was successful.
            // we're now a saved record, congrats.
            $this->__is_new_record = false;
            // load the row properties from the database response. This is
            // needed for database generated fields.
            foreach ($rows as $name => $value)
                $this->$name = $value;
            // reset the dirty properties, because the db now match them.
            $this->__dirty_db_data = [];
        }

        return $success;
    }

    /**
     * Perform a DELETE instruction to remove the associated row with this
     * object in the database.
     *
     * This function doesn't check for association, it doesn't cleanup
     * dependencies, and doesn't throw an error when it fails. For all theses
     * reasons direct use of this method is highly discouraged, destroy()
     * should be called instead. As a result it is protected and prefixed by an
     * underline. A subclass that *really* want to make this method available
     * (meaning bypassing destroy()) would have to define public delete()
     * method forwarding the call to _delete():
     * @code
     *   public function delete()
     *   {
     *     return $this->_delete();
     *   }
     * @endcode
     *
     * @return
     *   false on error, true otherwise
     */
    protected function _delete()
    {
        $query = new No2_SQLQuery(get_class($this));
        $query->query_on($this->__db_profile);
        return $query->id($this->id)->delete();
    }

    /**
     * Destroy the current Object in the database and cleanup association etc.
     * It is intended to be overrided by subclasses. It is prefered over
     * _delete() because it will cleanup more than just its entry in the
     * database and it will throw an exception on error preventing further
     * execution.
     *
     * @return
     *   nothing. On error, destroy() will throw an Exception.
     *
     * @note
     *   on success is_new_record() will return true after this method returns.
     */
    public function destroy()
    {
        if ($this->is_new_record())
            return;

        $success = $this->_delete();
        if (!$success) {
            throw new Exception(sprintf(
                'Could not destroy %s(id=%s)', get_called_class(), $this->id
            ));
        }
        $this->__is_new_record = true;
    }
}
