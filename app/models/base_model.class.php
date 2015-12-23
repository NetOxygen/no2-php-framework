<?php
/**
 * Parent of all Model. Usefull to add stuff specific to the application here.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
abstract class BaseModel extends No2_AbstractModel
{
    /**
     * @override
     *
     * provide database fields types translators
     */
    public function __set_translators()
    {
        return array_merge(parent::__set_translators(), [
            'integer' => 'intval',
            'float'   => 'floatval',
            'double'  => 'doubleval',
            'boolean' => function ($val) {
                return (empty($val) ? false : true);
            },
            'datetime' => function ($val) {
                if ($val instanceof DateTime)
                    return $val;
                // try to convert using strtotime(), this is expect to work for
                // database formated dates.
                $ret = null;
                $t = strtotime($val);
                if ($t) {
                    $ret = new DateTime();
                    $ret->setTimestamp($t);
                } else {
                    $ret = s_to_datetime($val);
                }
                if (!$ret)
                    throw new InvalidArgumentException("$val: could not translate into datetime");
                return $ret;
            },
            'json' => function ($val) {
                if (!is_string($val))
                    $val = json_encode($val);
                return json_decode($val);
            },
            'uuidv4' => function ($val) {
                return (is_uuidv4(trim($val)) ? strtolower(trim($val)) : null);
            },
        ]);
    }

    /**
     * @override
     *
     * provide database fields types translators
     */
    public function massage_for_storage_translators()
    {
        return array_merge(parent::massage_for_storage_translators(), [
            'boolean' => function ($val) {
                // using '1' and '0' should be ok for both MySQL and PostgreSQL
                return ($val ? '1' : '0');
            },
            'datetime' => function ($val) {
                if ($val instanceof DateTime)
                    return $val->format(DateTime::ISO8601);
                return $val;
            },
            'json' => function ($val) {
                if (is_object($val) || is_array($val))
                    return json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                return $val;
            },
        ]);
    }

    /**
     * override save() in order to set some "magic" fields.
     */
    public function save($do_validate = true)
    {
        $now      = new DateTime();
        $new      = $this->is_new_record();
        $db_infos = $this->db_infos();
        $id_is_uuidv4   = (array_key_exists('type', $db_infos['id']) && $db_infos['id']['type'] === 'uuidv4');
        $has_created_at = array_key_exists('created_at', $db_infos);
        $has_updated_at = array_key_exists('updated_at', $db_infos);
        $has_created_by = array_key_exists('created_by', $db_infos);
        $has_updated_by = array_key_exists('updated_by', $db_infos);

        if ($new && $id_is_uuidv4)
            $this->id = uuidv4();
        if ($new && $has_created_at) {
            $saved_created_at = $this->created_at;
            $this->created_at = $now;
        }
        if ($new && $has_created_by) {
            $saved_created_by = $this->created_by;
            $this->created_by = current_user()->id;
        }
        if ($has_updated_at) {
            $saved_updated_at = $this->updated_at;
            $this->updated_at = $now;
        }
        if ($has_updated_by) {
            $saved_updated_by = $this->updated_by;
            $this->updated_by = current_user()->id;
        }

        $success = parent::save($do_validate);

        // if save() has failed, restore the previous values.
        if (!$success) {
            if ($new && $id_is_uuidv4)
                unset($this->id);
            if ($new && $has_created_at)
                $this->created_at = $saved_created_at;
            if ($new && $has_created_by)
                $this->created_by = $saved_created_by;
            if ($has_updated_at)
                $this->updated_at = $saved_updated_at;
            if ($has_updated_by)
                $this->updated_by = $saved_updated_by;
        }

        return $success;
    }

    /**
     * string convertion method, mostly used for debugging.
     */
    public function __toString()
    {
        /* show the class and loop through db_infos values */
        $klass  = get_class($this);
        $fields = "";
        foreach ($this->db_infos() as $key => $meta) {
            $value = $this->$key;
            if (is_null($value))
                $value = 'null';
            elseif (array_key_exists('type', $meta)) {// this field has a cast type
                switch ($meta['type']) {
                case 'boolean': /* FALLTHROUGH */
                case 'integer': /* FALLTHROUGH */
                case 'float':   /* FALLTHROUGH */
                case 'double':  /* FALLTHROUGH */
                    break;
                case 'datetime':
                    $value = $value->format(DateTime::ISO8601);
                    break;
                case 'json':
                    if (is_object($value) || is_array($value))
                        $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    break;
                default: // string
                    $value = "\"$value\"";
                }
            } else // assume string
                $value = "\"$value\"";
            $fields .= sprintf(" %s=%s", $key, $value);
        }
        return "#<$klass:$fields>";
    }
}
