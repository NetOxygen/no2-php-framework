<?php
/**
 * MySQL implementation of the No2_Database abstract class, using the PDO MySQL
 * interface.
 *
 * @note
 *   - This file is an adaptation from no2/mysql.class.php which use the
 *   "old" mysql_* interface. Some of the comments link to the mysql_*
 *   documentation, and this implementation try to use PDO to honor the
 *   No2_MySQL interface. So they're still left here as reference
 *   (for argument / return values) but are not relevant for the
 *   implementation.
 *   - In the Future™ it should be refactored to support easily other database
 *   engines. It could either be a class handling all connection types
 *   (__construct might need a new parameter to determine which engine is
 *   needed) or become a base class (__construct would be moved in a MySQL
 *   specific subclass).
 *
 * @see No2_Database
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
class No2_PDO_MySQL implements No2_Database
{
    /**
     * link to the MySQL database (PDO instance).
     */
    private $link;

    /**
     * last statment. Used to retrieve the error code / message in some cases.
     */
    private $last_stmt;

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
        $this->last_stmt = null;
        $this->link      = new PDO(
            "mysql:host=$server;dbname=$database;charset=utf8",
            $user,
            $password,
            [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"] # fix http://stackoverflow.com/questions/4361459/php-pdo-charset-set-names
        );
    }

    /**
     * make a SQL query to the database. the query should be properly escaped.
     *
     * @see http://php.net/manual/en/function.mysql-query.php
     */
    public function query($query)
    {
        return $this->execute($query);
    }

    /*
     * @see Database::execute()
     *
     * {args} are handled by this function, and :args are handled and escaped
     * by PDO.
     */
    public function execute($query, $arguments = [])
    {
        $split = preg_split('/({\w+})/', $query, -1, PREG_SPLIT_DELIM_CAPTURE);
        $count = count($split);
        for ($i = 1; $i < $count; $i += 2) {
            $key = $split[$i];
            if (array_key_exists($key, $arguments)) {
                // found a replacement in $arguments.
                $split[$i] = $arguments[$key];
            }
        }

        $query = join($split);

        // create a PDO statment
        $this->last_stmt = $st = $this->link->prepare($query);

        if ($st === false) {
            $this->last_stmt = null;
            return false;
        }

        // forward :args to PDO.
        $pdo_args = [];
        foreach ($arguments as $key => $value) {
            if ($key[0] == ':')
                $pdo_args[$key] = $value;
        }

        No2_Logger::debug('PDO->execute: ' . preg_replace('/\s+/m', ' ', $query));
        return ($st->execute($pdo_args) ? $st : false);
    }


    /**
     * wrapper around mysql_error.
     *
     * @see http://php.net/manual/en/function.mysql-error.php
     */
    public function error()
    {
        $error = [];
        $msg   = '';
        if (!is_null($this->last_stmt))
            $error = $this->last_stmt->errorInfo();
        if (count($error) != 3)
            $error = $this->link->errorInfo();

        /*
         * $error is an array with 1 value (SQLSTATE=00000) if there is no
         * error. Otherwise there is 3 values (SQLSTATE, the error code, and
         * the error message).
         */
        if (count($error) == 3)
            $msg = sprintf("(%s:%s) %s", $error[0], $error[1], $error[2]);

        return $msg;
    }

    /**
     * wrapper around mysql_fetch_assoc.
     *
     * @see http://php.net/manual/en/function.mysql-fetch-assoc.php
     */
    public function fetch_assoc($handle)
    {
        return $handle->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * wrapper around mysql_insert_id.
     *
     * @see http://php.net/manual/en/function.mysql-insert-id.php
     */
    public function _insert_id()
    {
        return $this->link->lastInsertId();
    }

    /**
     * escape properly a string.
     */
    public function escape($s)
    {
        return $this->link->quote($s);
    }

    /**
     * MySQL (sadly) doesn't know about RETURNING.
     */
    public function has_returning()
    {
        return false;
    }

    /* transaction stuff */

    public function _beginTransaction()
    {
        return $this->link->beginTransaction();
    }

    public function _inTransaction()
    {
        return $this->link->inTransaction();
    }

    public function _commit()
    {
        return $this->link->commit();
    }

    public function _rollBack()
    {
        return $this->link->rollBack();
    }

    public function _lockTables($tables)
    {
        $q = 'LOCK TABLES ';
        foreach($tables as $table => $mode)
            $ts[] = $table . ' ' . ($mode === 'write' ? 'WRITE' : 'READ');
        $sql = $q . join(', ', $ts);
        $success = ($this->link->exec($sql) !== false);
        return $success;
    }

    public function _unlockTables()
    {
        $sql = 'UNLOCK TABLES';
        $success = ($this->link->exec($sql) !== false);
        return $success;
    }
}
