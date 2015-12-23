<?php
/**
 * Handle database link and operations.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
interface No2_Database
{
    /**
     * Make a query from the database.
     *
     * @param $q
     *   the query to perform, for example SQL code.
     *
     * @return
     *   the result of the query, false on error.
     */
    public function query($q);

    /**
     * construct and execute a query to the database.
     *
     * This method's goal is to make queries safer and easier. It use a custom
     * format allowing safe insertion of parameters in the query. The format is
     * defined as follow:<br />
     *   Two type of tags are allowed as parameters:<br />
     *   - <code>:tag</code> a colon followed by a name:<br />
     *     This kind of tag is used for database values, like 42, "John" etc.
     *     It is replaced by <code>$arguments[':tag']</code> escaped and
     *     quoted.
     *   - <code>{tag}</code> a name surounded by curly braces:<br />
     *     This kind of tag is used for database fields, like id, name etc. It
     *     is replaced by <code>$arguments['{tag}']</code> and is *NOT*
     *     escaped.
     *
     * <b>Example</b>
     * @code
     * No2_Database::execute(
     *   'SELECT * FROM {some_table} WHERE id = :target_id',
     *   ['{some_table}' => 'users', ':id' => 42]
     * );
     * // will execute: SELECT * FROM users WHERE id = '42'
     * @endcode
     *
     * @param $query
     *   a query string with optional tags.
     *
     * @param $arguments
     *   an array of keys/values where keys match some tag in the query string
     *   and values are the data used as replacement.
     *
     * @return
     *   the result of the query, false on error.
     */
    public function execute($query, $arguments);

    /**
     * escape properly a string.
     *
     * @note
     *   Using this function and Database::query() is not recommanded. You
     *   should use Database::execute() instead. This function might not work
     *   under some circumstances (like using PDO_ODBC).
     *
     * @param $s
     *   the string to escape
     *
     * @return
     *   an escaped (safe) string.
     */
    public function escape($s);

    /**
     * Declare if the database engine support PostgreSQL RETURNING (on INSERT
     * and UPDATE). Basically only PostgreSQL drivers would return true here.
     */
    public function has_returning();
}
