<?php
/**
 * PostgreSQL implementation of the No2_Database abstract class, using the PDO
 * PostgreSQL interface.
 *
 * @see No2_Database
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
class No2_PDO_PostgreSQL implements No2_Database
{
    /**
     * link to the PostgreSQL database (PDO instance).
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
            "pgsql:host=$server;dbname=$database",
            $user,
            $password
        );
    }

    /**
     * make a SQL query to the database. the query should be properly escaped.
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
    public function execute($query, $arguments=[])
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
     * @return the last error.
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

    /*
     * wrapper.
     */
    public function fetch_assoc($handle)
    {
        return $handle->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * escape properly a string.
     */
    public function escape($s)
    {
        return $this->link->quote($s);
    }

    /**
     * see http://www.postgresql.org/docs/9.3/static/sql-insert.html
     * and http://www.postgresql.org/docs/9.3/static/sql-update.html
     */
    public function has_returning()
    {
        return true;
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

    /**
     * This method will lock every tables in the default lock mode (ACCESS
     * EXCLUSIVE).
     *
     * PostgreSQL explicit locking mechanisms are more powerful and complex
     * than this interface. For example, if you want to lock two tables in
     * different mode, you have to issue two LOCK TABLE instructions.
     *
     * @see
     *   http://www.postgresql.org/docs/9.3/static/sql-lock.html
     *   http://www.postgresql.org/docs/9.3/static/explicit-locking.html
     */
    public function _lockTables($tables)
    {
        $sql = 'LOCK TABLE ' . join(', ', array_keys($tables));
        $success = ($this->link->exec($sql) !== false);
        return $success;
    }

    /*
     * This function is a noop.
     *
     * "There is no UNLOCK TABLE command; locks are always released at
     * transaction end.
     *
     * see http://www.postgresql.org/docs/9.3/static/sql-lock.html
     */
    public function _unlockTables()
    {
        /*
         * NOTE: The current implementation is a no-op,
         *
         * We could track the transaction status (in transaction, not in
         * transaction), or maybe the status is available in the link. When in
         * transaction we could return false and/or rollBack().
         */
        return true;
    }
}
