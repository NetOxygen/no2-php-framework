<?php
/**
 * MySQL implementation of the No2_Database abstract class using the
 * old MySQL functions.
 *
 * @see No2_Database
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
class No2_MySQL implements No2_Database
{
    /**
     * link to the MySQL database. handler (resource) returned by
     * mysql_connect.
     */
    private $link;

    /**
     * create a new database connection.
     *
     * @param $server
     *   the server's hostname to connect to.
     *
     * @param $database
     *   the database to use.
     *
     * @param $user
     *   the username used for authorization.
     *
     * @param $password
     *   the password used for authorization.
     */
    public function __construct($server, $database, $user, $password)
    {
        $this->link = mysql_connect($server, $user, $password,
            /* ensure a new connection */ true);
        if (!$this->link)
            throw new Exception("Can't connect to MySQL ($server)");
        if (!mysql_select_db($database, $this->link))
            throw new Exception("Can't connect to database ($database)");
        if (!mysql_set_charset('utf8', $this->link))
            throw new Exception("Can't set charset to utf8 ($database)");
    }

    /**
     * make a SQL query to the database. the query should be properly escaped
     * since it will be directly passed to mysql_query().
     *
     * @see http://php.net/manual/en/function.mysql-query.php
     */
    public function query($query)
    {
        No2_Logger::debug('mysql_query: ' . preg_replace('/\s+/m', ' ', $query));
	    $result = mysql_query($query, $this->link);
        return $result;
    }

    /**
     * @see Database::execute()
     */
    public function execute($query, $arguments = [])
    {
        $split = preg_split('/(:\w+|{\w+})/', $query, -1, PREG_SPLIT_DELIM_CAPTURE);
        $count = count($split);
        for ($i = 1; $i < $count; $i += 2) {
            $key = $split[$i];
            if (array_key_exists($key, $arguments)) {
                // found a replacement in $arguments.
                if ($key[0] == ':') {
                    // A pattern like :tag, we enclose it with simple quotes.
                    $split[$i] = $this->escape($arguments[$key]);
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
        return mysql_error($this->link);
    }

    /**
     * wrapper around mysql_fetch_assoc.
     *
     * @see http://php.net/manual/en/function.mysql-fetch-assoc.php
     */
    public function fetch_assoc($result)
    {
        return mysql_fetch_assoc($result);
    }

    /**
     * wrapper around mysql_insert_id.
     *
     * @see http://php.net/manual/en/function.mysql-insert-id.php
     */
    public function _insert_id()
    {
        return mysql_insert_id($this->link);
    }

    /**
     * escape properly a string.
     */
    public function escape($s)
    {
        // http://stackoverflow.com/questions/1522313/php-mysql-real-escape-string-stripslashes-leaving-multiple-slashes
        // RT #36248
        $stripslashed = (get_magic_quotes_gpc() ? stripslashes($s) : $s);
        $escaped      = mysql_real_escape_string($stripslashed, $this->link);
        $quoted       = "'$escaped'";
        return $quoted;
    }

    /**
     * MySQL (sadly) doesn't know about RETURNING.
     */
    public function has_returning()
    {
        return false;
    }
}
