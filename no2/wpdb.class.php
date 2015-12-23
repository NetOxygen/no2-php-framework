<?php
/**
 * Wordpress $wpdb as a No2 Database driver.
 *
 * @see No2_Database
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
class No2_WPDB implements No2_Database
{
    /**
     * a pointer the Wordpress's $wpdb
     */
    public $wpdb = null;

    /**
     * We simulate a result cache from the db to match the fetch_assoc()
     * interface.
     */
    protected $results = null;

    /**
     * create a new database connection.
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        // index 0 is null, to make results start at 1.
        $this->results = [null];
    }

    /**
     * make a SQL query to the database. the query should be properly escaped.
     *
     * @see http://php.net/manual/en/function.mysql-query.php
     */
    public function query($query)
    {
        global $wpdb;

        if (preg_match('/\s*select/i', $query)) {
            No2_Logger::debug('$wpdb->get_results: ' . preg_replace('/\s+/m', ' ', $query));
            $result = $wpdb->get_results($query, ARRAY_A);
            if (is_array($result)) {
                $index = count($this->results);
                $this->results[$index] = $result;
                $result = (object)array($index); // return the index as an object.
            }
        } else {
            No2_Logger::debug('$wpdb->query: ' . preg_replace('/\s+/m', ' ', $query));
            $result = $wpdb->query($query);
        }

        return $result;
    }

    /*
     * @see Database::execute()
     */
    public function execute($query, $arguments=[])
    {
        $split = preg_split('/(:\w+|{\w+})/', $query, -1, PREG_SPLIT_DELIM_CAPTURE);
        $count = count($split);
        for ($i = 1; $i < $count; $i += 2) {
            $key = $split[$i];
            if (array_key_exists($key, $arguments)) {
                // found a replacement in $arguments.
                if ($key[0] == ':') {
                    // A pattern like :tag, we enclose it with simple quotes.
                    $split[$i] = "'" .  $this->escape($arguments[$key]) . "'";
                } else {
                    // A pattern like {tag}, just add it.
                    $split[$i] = $arguments[$key];
                }
            }
        }

        $sql = join($split);
        $result = $this->query($sql);
        return $result;
    }

    /**
     * wrapper around mysql_error.
     *
     * @see http://php.net/manual/en/function.mysql-error.php
     */
    public function error()
    {
         return $this->wpdb->last_error;
    }

    /**
     * wrapper around mysql_fetch_assoc.
     *
     * @see http://php.net/manual/en/function.mysql-fetch-assoc.php
     */
    public function fetch_assoc($handle)
    {
        $handle_as_array = (array)$handle;
        $index           = (int)$handle_as_array[0];
        if (!array_key_exists($index, $this->results))
            return null;
        $result = array_shift($this->results[$index]);
        return $result;
    }

    /**
     * wrapper around mysql_insert_id.
     *
     * @see http://php.net/manual/en/function.mysql-insert-id.php
     */
    public function _insert_id()
    {
        return $this->wpdb->insert_id;
    }

    /**
     * escape properly a string.
     */
    public function escape($s)
    {
        return $this->wpdb->escape($s);
    }

    /**
     * Wordpress only works with MySQL.
     */
    public function has_returning()
    {
        return false;
    }
}
