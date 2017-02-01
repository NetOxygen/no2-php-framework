<?php
/**
 * This class goal is to make SQL query in PHP less painful by providing a DSL
 * with chaining methods of objects. As a general SQL query method, the static
 * function execute() can also be used.
 *
 * @note
 *   - Most method return a modified clone <code>$this</code>, only
 *     restrict_to() can change the object's state.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
class No2_SQLQuery
{
    /*
     * Theses constant are used as "hint" for a SELECT instruction. If
     * EXPECT_MANY is given the the returned value is always an array.
     * The values are choosen so bitwise operation are possible.
     */
    const SURPRISE    = -1; /**< don't make any expectations       */
    const EXPECT_ZERO =  1; /**< expect no result (strange choice) */
    const EXPECT_ONE  =  2; /**< expect exactly one result         */
    const EXPECT_MANY =  6; /**< expect many results, a collection (can be 0 or 1) */

    const INSERT = 'INSERT'; /**< INSERT instruction string */
    const SELECT = 'SELECT'; /**< SELECT instruction string */
    const UPDATE = 'UPDATE'; /**< UPDATE instruction string */
    const DELETE = 'DELETE'; /**< DELETE instruction string */

    /**
     * Array of database handlers (No2_Database objects), see
     * No2_SQLQuery::setup.
     */
    protected static $databases = [];

    /**
     * the default database profile key
     */
    const DEFAULT_PROFILE = 'default';

    /**
     * setup a database link.
     *
     * @param $str
     *   A database link setup description. See Examples.
     *
     * @code
     *   pgsql://user:password@hostname/database
     *      PostgreSQL driver
     *   mysql://user:password@hostname/database
     *      MySQL driver using the (new) PDO interface.
     *   _mysql://user:password@hostname/database
     *      MySQL driver using the (old) mysql interface (deprecated).
     *   sqlite:///path/to/project.db
     *      SQLite driver (not implemented yet!).
     *   _wpdb
     *      When embeded into a Wordpress blog, it will use the global $wpdb
     *      object.
     *
     * @param $profile
     *   A key for the configured link. When performing a query, the user can
     *   specify which profile to use.
     *
     * @return
     *   The configured database handler. If the configuration string can't be
     *   parsed, an Exception is thrown.
     */
    public static function setup($str, $profile = self::DEFAULT_PROFILE)
    {
        $matches = [];
        if (preg_match('#^mysql://([^:]+):([^@]+)@([^/]+)/([^\s]+)$#', $str, $matches)) {
            // default MySQL driver using the PDO interface.
            $user     = $matches[1];
            $password = $matches[2];
            $hostname = $matches[3];
            $database = $matches[4];

            require_once(NO2DIR . '/pdo_mysql.class.php');
            $handle = new No2_PDO_MySQL($hostname, $database, $user, $password);
        } else if (preg_match('#^pgsql://([^:]+):([^@]+)@([^/]+)/([^\s]+)$#', $str, $matches)) {
            $user     = $matches[1];
            $password = $matches[2];
            $hostname = $matches[3];
            $database = $matches[4];

            require_once(NO2DIR . '/pdo_postgresql.class.php');
            $handle = new No2_PDO_PostgreSQL($hostname, $database, $user, $password);
        } else if (preg_match('#^_mysql://([^:]+):([^@]+)@([^/]+)/([^\s]+)$#', $str, $matches)) {
            // the old mysql interface is still supported, although you must
            // "force" it with _mysql as driver.
            $user     = $matches[1];
            $password = $matches[2];
            $hostname = $matches[3];
            $database = $matches[4];

            require_once(NO2DIR . '/mysql.class.php');
            $handle = new No2_MySQL($hostname, $database, $user, $password);
        } else if (preg_match('#^_wpdb$#', $str, $matches)) {
            // We're embeded into a wordpress blog.
            require_once(NO2DIR . '/wpdb.class.php');
            $handle = new No2_WPDB();
        } else {
            throw new Exception(get_called_class() . '::setup: ' .
                'bad setup parameter: ' . $str);
        }

        static::$databases[$profile] = $handle;
        return $handle;
    }

    /**
     * public database handler accessor.
     *
     * @param $profile
     *   The database profile wanted.
     *
     * @return
     *   The No2_Database instance matching the requested profile, or null.
     */
    public static function database($profile)
    {
        return (array_key_exists($profile, static::$databases) ?
            static::$databases[$profile] : null
        );
    }

    /**
     * return a database link or throw an InvalidArgumentException.
     *
     * Ugly but somehow more useful than the public accessor.
     */
    protected static function _database_or_throw($profile)
    {
        $db = static::database($profile);
        if (!($db instanceof No2_Database)) {
            throw new InvalidArgumentException(
                get_called_class() . '::_database_or_throw: ' .
                "$profile: invalid database profile (not configured?)"
            );
        }
        return $db;
    }

    /**
     * execute a SQL query. This static method can be called when a complex SQL
     * query has to be executed.
     *
     * If the database link is not valid, an exception is thrown.
     *
     * @see No2_Database::execute()
     *
     * @param $q
     *   The SQL query. see No2_Database::execute() for the format.
     *
     * @param $arguments
     *   The parameters to $q as an array of key to values of replacement.
     *   *ALL* match of "key" will be replaced by the value. It should be used
     *   by prefixing keys with a colon `:' like <code>:tag</code> or enclosing
     *   with curly braces `{}' like <code>{tag}</code>. see
     *   No2_Database::execute() for a complete description of the format. All
     *   argument beggining with two underscore `__' are reserved for the
     *   implementation.
     *
     * @param $options
     *   An array of options. The following keys are valid:
     *   - factory:
     *      a class used to create instances from a SELECT query, should have a
     *      load() static method.
     *   - return_as_collection:
     *      if true an array with the result will always be returned, even if
     *      there is no or one element(s). If not set or set to false the
     *      return values will be decided as follow:
     *      - when there is no result, null is returned.
     *      - when there is only one result, the object is returned.
     *      - when there is many results, an array of objects is returned.
     *   - profile:
     *     The database profile to use. see No2_SQLQuery::setup().
     *
     * @throw
     *   InvalidArgumentException if a bad database profile (see options) is
     *   given.
     *
     * @return
     *   if the instruction is one of INSERT, UPDATE or DELETE it returns true
     *   on success, false otherwise. If the instruction is a SELECT it depends
     *   on the 'return_as_collection' key of the <code>$options</code>
     *   parameter.
     */
    public static function execute($q, $arguments = [], $options = [])
    {
        $profile = (array_key_exists('profile', $options) ?
            $options['profile'] : static::DEFAULT_PROFILE
        );
        $db = static::_database_or_throw($profile);
        $dbres = $db->execute($q, $arguments);

        if (!is_object($dbres) && !is_resource($dbres))
            return $dbres;

        $objects = [];
        if (array_key_exists('factory', $options) && class_exists($options['factory'])) {
            $klass = $options['factory'];
            while ($properties = $db->fetch_assoc($dbres))
                $objects[] = $klass::load($properties, $profile);
        } else {
            while ($array = $db->fetch_assoc($dbres))
                $objects[] = $array;
        }

        if (array_key_exists('return_as_collection', $options) &&
                $options['return_as_collection']) {
            $results = $objects;
        } else {
            switch (count($objects)) {
            case 0:
                $results = null;
                break;
            case 1:
                $results = $objects[0];
                break;
            default: // more than one
                $results = $objects;
                break;
            }
        }

        return $results;
    }

    /*
     * A bunch of transaction related stuff. Notice that:
     * XXX: thoses functions begin with an underscore means unstable API. They:
     *   1. only work with PDO based handlers for now,
     *   2. are very ugly, the profile should be saved somehow, the transaction
     *      should be represented as an object of something.
     *
     * Use at your own risks.
     */

    /**
     * Start a Transaction.
     */
    public static function _beginTransaction($profile = self::DEFAULT_PROFILE)
    {
        return static::_database_or_throw($profile)->_beginTransaction();
    }

    /**
     * Test if the given profile is in a transaction.
     */
    public static function _inTransaction($profile = self::DEFAULT_PROFILE)
    {
        return static::_database_or_throw($profile)->_inTransaction();
    }

    /**
     * Commit a Transaction.
     */
    public static function _commitTransaction($profile = self::DEFAULT_PROFILE)
    {
        return static::_database_or_throw($profile)->_commit();
    }

    /**
     * Rollback a Transaction.
     */
    public static function _rollBackTransaction($profile = self::DEFAULT_PROFILE)
    {
        return static::_database_or_throw($profile)->_rollBack();
    }

    /**
     * Lock Tables.
     */
    public static function _lockTables($tables, $profile = self::DEFAULT_PROFILE)
    {
        return static::_database_or_throw($profile)->_lockTables($tables);
    }

    /**
     * Unlock Tables.
     */
    public static function _unlockTables($profile = self::DEFAULT_PROFILE)
    {
        return static::_database_or_throw($profile)->_unlockTables();
    }

    /**
     * same as select, but add a 'FOR UPDATE' clause (used inside a
     * transaction).
     */
    public function _select4update($expr = null, $arguments = [])
    {
        $this->select4 = 'FOR UPDATE';
        return $this->select($expr, $arguments);
    }

    /**
     * Convert an array of values into the (...) part of a SQL IN clause.
     * FIXME: the profile is needed but it's ugly.
     *
     * @throw
     *   InvalidArgumentException if a bad database profile is given.
     */
    static function array_to_IN_clause($array, $profile = self::DEFAULT_PROFILE)
    {
        $quoted = [];
        $db     = static::_database_or_throw($profile);
        foreach ($array as $element) {
            if (is_null($element))
                $v = 'NULL';
            else if (is_bool($element))
                $v = ($element ? 'TRUE' : 'FALSE');
            else if (is_int($element) || is_float($element))
                $v = $element;
            else
                $v = $db->escape($element);
            $quoted[] = $v;
        }
        return '(' . join(', ', $quoted) . ')';
    }

    /**
     * a No2_SQLQuery from which this was formed.
     */
    protected $_parent = null;

    /**
     * The number of parents, used to generate uniq tags.
     */
    protected $_height = 0;

    /**
     * called to create a clone of this and setting the _parent and _height
     * properties properly.
     *
     * @return
     *   A new clone of this object.
     */
    protected function specialize()
    {
        $klone = clone $this;
        $klone->_parent = $this;
        $klone->_height = $this->_height + 1;

        return $klone;
    }

    /**
     * Class used to create new instances when the query is a SELECT query.
     * It is also used to find the table name, so it should have public
     * static $table property. It should be a subclass of No2_AbstractModel.
     */
    public $klass;

    /**
     * what to expect as result of a SELECT instruction. see EXPECT_* and
     * SURPRISE constants.
     */
    protected $hint;

    /**
     * Types of instruction that are valid for this object. When method like
     * where() will change the state of this object, it will restrict the
     * possible instruction types.
     *
     * @see restrict_to()
     */
    protected $types;

    /**
     * arguments for the query.
     */
    protected $arguments = [];

    /**
     * JOIN clause(s) of the SQL query.
     */
    protected $join = '';

    /**
     * WHERE clause of the SQL query.
     */
    protected $where = '';

    /**
     * SET clause of the SQL query.
     */
    protected $set = '';

    /**
     * ORDER BY clause of the SQL query.
     */
    protected $order_by = '';

    /**
     * GROUP BY clause of the SQL query.
     */
    protected $group_by = '';

    /**
     * LIMIT clause of the SQL query.
     */
    protected $limit = '';

    /**
     * Locking part of a select, like 'FOR UPDATE'.
     */
    protected $select4 = '';

    /**
     * The database profile name for this query.
     */
    protected $profile = self::DEFAULT_PROFILE;

    /**
     * @param $klass
     *   The model class that will be used to get the table name and, if
     *   select() is called, used to create instances.
     *
     * @param $hint
     *   a hint of how many results to expect. This flag is only used by the
     *   select() method. if EXPECT_MANY is given, the result is returned as an
     *   array regardless of the count.
     */
    public function __construct($klass, $hint = self::SURPRISE)
    {
        $this->klass = $klass;
        $this->hint  = intval($hint);
        $this->types = [self::INSERT, self::SELECT, self::UPDATE, self::DELETE];
    }

    /**
     * this method allow model classes to define scope method, prefixed by
     * `scope_'.
     *
     * The model class scope_* method should take one argument that is "this",
     * a No2_SQLQuery instance. It will modify it (like calling where()) and
     * should return the result.
     */
    public function __call($name, $arguments)
    {
        $method_name = 'scope_' . $name;
        try {
            $reflect = new ReflectionMethod($this->klass, $method_name);
            if ($reflect->isPublic() && $reflect->isStatic()) {
                array_unshift($arguments, null /* static method */, $this);
                return call_user_func_array([$reflect, 'invoke'], $arguments);
            }
        } catch (Exception $e) {
            // ignore because the codepath after the try block trigger_error anyway
        }
        trigger_error('Call to undefined method ' .
            get_class($this) . '::' . $name . '()', E_USER_ERROR);
    }

    /**
     * set the database profile to use for this query.
     *
     * @throw
     *   InvalidArgumentException if a bad database profile is given.
     */
    public function query_on($profile)
    {
        No2_SQLQuery::_database_or_throw($profile); // checking validity

        $profiled = $this->specialize();
        $profiled->profile = $profile;

        return $profiled;
    }

    /**
     * Add a JOIN clause.
     *
     * @note
     *   Several join can be chained.
     *
     * @param $join
     *   a partial SQL string.
     *
     * @param $arguments
     *   arguments to <code>$condition</code>.
     *
     * @return
     *   a modified clone of <code>$this</code>, allowing to to chain
     *   methods.
     */
    public function join($join, $arguments = [])
    {
        $joined = $this->specialize();

        if (!empty($joined->join))
            $joined->join .= ' ';

        $joined->join     .= $join;
        $joined->arguments = array_merge($joined->arguments, $arguments);
        return $joined;
    }

    /**
     * set the WHERE clause to the instruction.
     *
     * @note
     *   Several where can be chained. When chaining the conditions will be
     *   AND'ed.
     *
     * @param $conditions
     *   a partial SQL condition string.
     *
     * @param $arguments
     *   arguments to <code>$condition</code>.
     *
     * @return
     *   a modified clone of <code>$this</code>, allowing to to chain
     *   methods.
     */
    public function where($conditions, $arguments = [])
    {
        $whereized = $this->specialize();
        $whereized->restrict_to([self::SELECT, self::UPDATE, self::DELETE], true);

        if (empty($whereized->where)) {
            /* first where call */
            $whereized->where  = 'WHERE ';
        } else {
            /* chain given where condition with the previous with AND */
            $whereized->where .= ' AND ';
        }

        $whereized->where    .= "({$conditions})";
        $whereized->arguments = array_merge($whereized->arguments, $arguments);

        return $whereized;
    }

    /**
     * set the ORDER BY clause to the SELECT instruction. If this method is
     * called more than once, only the last call will be taken into account.
     *
     * @param $expr
     *   a list of expr either as a string or an array.
     *
     * @param $arguments
     *   arguments to <code>$condition</code>.
     *
     * @return
     *   a modified clone of <code>$this</code>, allowing to to chain
     *   methods.
     */
    public function order_by($expr, $arguments = [])
    {
        $ordered = $this->specialize();
        $ordered->restrict_to(self::SELECT, true);

        $ordered->order_by = 'ORDER BY ';
        if (is_array($expr))
            $ordered->order_by .= join(',', $expr);
        else
            $ordered->order_by .= $expr;
        $ordered->arguments = array_merge($ordered->arguments, $arguments);

        return $ordered;
    }

    /**
     * set the GROUP BY clause to the SELECT instruction. If this method is
     * called more than once, only the last call will be taken into account.
     *
     * @param $expr
     *   a list of expr either as a string or an array.
     *
     * @param $arguments
     *   arguments to <code>$condition</code>.
     *
     * @return
     *   a modified clone of <code>$this</code>, allowing to to chain
     *   methods.
     */
    public function group_by($expr, $arguments = [])
    {
        $grouped = $this->specialize();
        $grouped->restrict_to(self::SELECT, true);

        $grouped->group_by = 'GROUP BY ';
        if (is_array($expr))
            $grouped->group_by .= join(',', $expr);
        else
            $grouped->group_by .= $expr;
        $grouped->arguments = array_merge($grouped->arguments, $arguments);

        return $grouped;
    }

    /**
     * set the LIMIT clause of the SELECT instruction. If this method is called
     * more than once, only the last call will be taken into account.
     *
     * @param $off
     *   The offset (first argument to limit, starting point).
     *
     * @param $count
     *   The number of results to return (second argument to limit, duration).
     *
     * @return
     *   a modified clone of <code>$this</code>, allowing to to chain
     *   methods.
     */
    public function limit($off, $count)
    {
        $limited = $this->specialize();
        $limited->restrict_to(self::SELECT, true);

        $ioff   = intval($off);
        $icount = intval($count);

        if ($ioff === 0)
            $limited->limit = "LIMIT $icount";
        else
            $limited->limit = "LIMIT $ioff, $icount";
        return $limited;
    }

    /**
     * sexy helper for the LIMIT clause.
     *
     *   first() will set a LIMIT clause starting at 0 and returning a given
     *   number of objects (default is 1).
     *
     * @note
     *   This method is mostly designed to restrict the result to only one.
     *   When $n is 1, it will modify the hint accordingly (using EXPECT_ONE).
     *   There are cases when you'll set $n to 1 but still want an array as
     *   result (for exemple when $n is user provided). If so, use limit(0, $n)
     *   instead because limit() will not change the hint.
     *
     * @param $n
     *   The number of objects wanted.
     *
     * @return
     *   a modified clone of <code>$this</code>, allowing to to chain
     *   methods.
     */
    public function first($n = 1)
    {
        $limited = $this->limit(0, $n);
        if ($n == 1)
            $limited->hint = self::EXPECT_ONE;
        return $limited;
    }

    /**
     * set the SET clause of the instruction. If this method is called more
     * than once, only the last call will be taken into account.
     *
     * @param $columns
     *   an associative array of key/values. The keys should be safely escaped
     *   for SQL query.
     *
     * @return
     *   a modified clone of <code>$this</code>, allowing to to chain
     *   methods.
     */
    public function set($columns = [])
    {
        if (empty($columns))
            return $this;

        $setted = $this->specialize();
        $setted->restrict_to(self::UPDATE, true);

        $tag_prefix = "__set{$this->_height}";

        $setted->set = [];
        foreach ($columns as $name => $value) {
            $field_name_tag = "{{$tag_prefix}_$name}";
            $setted->arguments[$field_name_tag] = $name;
            if (is_null($value)) {
                $field_value_tag = "{{$tag_prefix}_{$name}_value}";
                $setted->arguments[$field_value_tag] = 'NULL';
            } else {
                $field_value_tag = ":{$tag_prefix}_{$name}_value";
                $setted->arguments[$field_value_tag] = $value;
            }
            $setted->set[] = "$field_name_tag = $field_value_tag";
        }
        $setted->set = 'SET ' . join(', ', $setted->set);

        return $setted;
    }

    /**
     * execute an INSERT instruction to insert exactly one row.
     *
     * @return
     *   null on error, the inserted row on success.
     */
    public function insert1($columns = [])
    {
        $id_tag = null;
        $this->restrict_to(self::INSERT);

        // build (...) VALUES (...)
        $fields = [];
        $values = [];
        foreach ($columns as $name => $value) {
            $field_name_tag = "{_$name}";
            $this->arguments[$field_name_tag] = $name;
            $fields []= $field_name_tag;
            if (is_null($value)) {
                $field_value_tag = "{_{$name}_value}";
                $this->arguments[$field_value_tag] = 'NULL';
            } else {
                $field_value_tag = ":_{$name}_value";
                $this->arguments[$field_value_tag] = $value;
            }
            $values []= $field_value_tag;
            // we need to store the id if set, because MySQL won't set
            // LAST_INSERT_ID() if it has not been generated by MySQL.
            if ($name === 'id')
                $id_tag = $field_name_tag;
        }
        $fields = join(', ', $fields);
        $values = join(', ', $values);

        $klass     = $this->klass;
        $arguments = array_merge($this->arguments, ['{__table}' => $klass::$table]);
        $options   = ['profile' => $this->profile];
        $inserted  = false;
        $db = static::_database_or_throw($this->profile);
        if ($db->has_returning()) {
            $sql      = "INSERT INTO {__table} ($fields) VALUES ($values) RETURNING *";
            $inserted = static::execute($sql, $arguments, $options);
        } else { // fallback to that *ugly* "id is AUTO_INCREMENT" MySQL stuff.
            $sql = "INSERT INTO {__table} ($fields) VALUES ($values)";
            if (static::execute($sql, $arguments, $options) !== false) {
                if ($id_tag)
                    $inserted = static::execute("SELECT * FROM {__table} WHERE id = $id_tag", $arguments);
                else
                    $inserted = static::execute('SELECT * FROM {__table} WHERE id = LAST_INSERT_ID()', $arguments);
            }
        }

        if ($inserted !== false) {
            return $inserted;
        } else {
            No2_Logger::warn(get_class($this) . '::insert: ' .
                "SQL query returned FALSE: $sql");
            No2_Logger::warn('error message: ' . $db->error());
            return null;
        }
    }

    /**
     * execute a SELECT instruction. Optionally the methods where() and
     * order_by() or even group_by() could have been called before.
     *
     * @param $expr
     *   a list of expr either as a string or an array.
     *
     * @param $arguments
     *   arguments to <code>$condition</code>.
     *
     * @return
     *   if the flag hint is set to EXPECT_MANY, an array is returned with all
     *   the selected elements. Otherwise, there are three possible case:
     *   - when there is no result, null is returned.
     *   - when there is only one result, the object is returned.
     *   - when there is many results, an array of objects is returned.
     */
    public function select($expr = null, $arguments = [])
    {
        $this->restrict_to(self::SELECT);

        $select = 'SELECT ';
        if (is_null($expr)) {
            /* no argument to select(), assume all */
            $select .= '{__table}.*';
        } else {
            /* one argument to select, either a raw string of an array of string */
            if (is_array($expr))
                $select .= join(', ', $expr);
            else
                $select .= $expr;
        }

        /* prepare the query and arguments */
        $klass     = $this->klass;
        $arguments = array_merge($this->arguments, $arguments, ['{__table}' => $klass::$table]);
        $options   = [
            'profile'              => $this->profile,
            'factory'              => $klass,
            'return_as_collection' => ($this->hint == self::EXPECT_MANY),
        ];
        $q = "{$select} FROM {__table} {$this->join} {$this->where} {$this->group_by} {$this->order_by} {$this->limit} {$this->select4}";
        $result = static::execute($q, $arguments, $options);

        if ($result === false) {
            No2_Logger::warn(get_class($this) . '::select: ' .
                "SELECT query returned FALSE: $q");
            No2_Logger::warn('error message: ' .
                static::_database_or_throw($this->profile)->error());
            return false;
        }
        /* sanity check. If only one result is expected but the query has
            returned many, issue a warning. */
        if ($this->hint == self::EXPECT_ONE && is_array($result)) {
            No2_Logger::warn(get_class($this) . '::select: ' .
                'SELECT query expect one result but got an array of length=' . count($result));
        }

        return $result;
    }

    /**
     * This function is a helper for a bunch of SQL aggregate functions (like
     * SUM, MIN/MAX, COUNT etc.).
     */
    protected function _sql_aggregate_func($func, $expr)
    {
        $this->restrict_to(self::SELECT);

        /* prepare the query and arguments */
        $klass     = $this->klass;
        $arguments = array_merge($this->arguments, [
            '{__func_expr}' => $expr,
            '{__table}'     => $klass::$table,
        ]);
        $options   = ['profile' => $this->profile];
        $q = "SELECT $func({__func_expr}) AS value FROM {__table} {$this->join} {$this->where}";

        $result = static::execute($q, $arguments, $options);
        if ($result === false) {
            No2_Logger::warn(get_class($this) . "::$func:: SELECT query returned FALSE: $q");
            No2_Logger::warn('error message: ' .
                static::_database_or_throw($this->profile)->error());
            return false;
        }

        return $result['value'];
    }

    /**
     * shortcut for a SELECT COUNT(something). Will honor previous where() calls.
     *
     * @return
     *   an integer with the count value or false on error.
     */
    public function count($expr = '*')
    {
        return intval($this->_sql_aggregate_func('COUNT', $expr));
    }

    /**
     * shortcut for a SELECT AVG(something). Will honor previous where() calls.
     *
     * @return
     *   a double with the average value or false on error.
     */
    public function average($expr)
    {
        return doubleval($this->_sql_aggregate_func('AVG', $expr));
    }

    /**
     * shortcut for a SELECT SUM(something). Will honor previous where() calls.
     *
     * @return
     *   a string with the sum value or false on error.
     */
    public function sum($expr)
    {
        return $this->_sql_aggregate_func('SUM', $expr);
    }

    /**
     * shortcut for a SELECT MIN(something). Will honor previous where() calls.
     *
     * @return
     *   a string with the max value or false on error.
     */
    public function min($expr)
    {
        return $this->_sql_aggregate_func('MIN', $expr);
    }

    /**
     * shortcut for a SELECT MAX(something). Will honor previous where() calls.
     *
     * @return
     *   a string with the max value or false on error.
     */
    public function max($expr)
    {
        return $this->_sql_aggregate_func('MAX', $expr);
    }

    /**
     * execute an UPDATE instruction. set() should have been called before.
     *
     * @param $mysql_id_hack
     *   Since MySQL doesn't have RETURNING, we need the id to get back the
     *   updated row. If the id field is updated, the "new" id must be
     *   provided.
     * @return
     *   false on error, true otherwise.
     */
    public function update($mysql_id_hack = null)
    {
        $this->restrict_to(self::UPDATE);

        if (empty($this->set)) {
            No2_Logger::warn(get_class($this) . '::update: ' .
                'called without previous set() call.');
        }

        $klass = $this->klass;
        $klass_arguments = ['{__table}' => $klass::$table];
        $arguments = array_merge($this->arguments, $klass_arguments);
        $options   = ['profile' => $this->profile];
        $db        = static::_database_or_throw($this->profile);
        $updated   = false;
        $sql       = "UPDATE {__table} {$this->set} {$this->where}";
        if ($db->has_returning()) {
            $sql    .= ' RETURNING *';
            $updated = static::execute($sql, $arguments, $options);
        } else { // XXX: TOCTOU, hacky.
            if (static::execute($sql, $arguments, $options) !== false) {
                $sql       = 'SELECT * FROM {__table} WHERE id = :mysql_id_hack';
                $arguments = array_merge(
                    [':mysql_id_hack' => $mysql_id_hack],
                    $klass_arguments
                );
                $updated   = static::execute($sql, $arguments, $options);
            }
        }

        if ($updated !== false) {
            return $updated;
        } else {
            No2_Logger::warn(get_class($this) . '::update: ' .
                "SQL query returned FALSE: $sql");
            No2_Logger::warn('error message: ' . $db->error());
            return null;
        }
    }

    /**
     * execute a DELETE instruction. It is wise to call where() before in orde
     * to avoid to clear the whole table.
     *
     * @return
     *   false on error, true otherwise.
     */
    public function delete()
    {
        $this->restrict_to(self::DELETE);

        $klass     = $this->klass;
        $arguments = array_merge($this->arguments, ['{__table}' => $klass::$table]);
        $options   = ['profile' => $this->profile];
        $q = "DELETE FROM {__table} {$this->where}";

        $result = static::execute($q, $arguments, $options);
        return ($result === false ? false : true);
    }

    /**
     * Restrict the No2_SQLQuery to be of a given set of instruction.
     *
     * This method is used to avoid construction of malformed query, like a
     * SELECT with a SET clause. It is used by all DSL methods and instructions
     * methods. Since it is a public method, it can also be used by the user to
     * force a type of query.
     *
     * @param $restrictions
     *   one of INSERT, SELECT, UPDATE or DELETE. An array can be given if many
     *   values are desired. If the restrictions can not be honored,
     *   restrict_to() will throw an exception.
     *
     * @param $change
     *   if true, the restrictions of this object will change according to
     *   $restrictions and the current one. If false, only a check is
     *   performed.
     */
    public function restrict_to($restrictions, $change = false)
    {
        if (is_array($restrictions))
            $given = $restrictions;
        else
            $given = [$restrictions];
        $current = $this->types;
        $future  = array_intersect($current, $given);
        if (empty($future)) {
            throw new Exception(get_class($this) . '::restrict_to: ' .
                'invalid restriction given: ' . join(',', $given) . ', expected: ' .
                join(',', $current));
        }
        if ($change) {
            /* set the new restrictions */
            $this->types = $future;
        }
    }
}
