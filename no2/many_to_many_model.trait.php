<?php
/**
 * Implement has_and_belongs_to_many low level methods by handling the links
 * manipulation.
 *
 * NOTE: Should only be `use'd by a subclass of No2_AbstractModel.
 *
 * @author
 *   Belkacem Alidra <belkacem.alidra@netoxygen.ch>
 */

trait No2_ManyToManyModel
{
    /**
     * Links this model to the others: handles all the stuff about the join table.
     *
     * @param
     *  array $relation: The relation to use
     *
     *  Define the relation with an array containing 3 values:
     *    - The target key: the column linked to this model in the join table
     *    - The join table name
     *    - The linked key: the column linked to the other model in the join table
     *
     * @param
     *  array $others: An array of Models or of ids
     *
     * @throws
     *  InvalidArgumentException when the relation is not well defined
     *  LogicException when $this is a new record
     *
     * @return
     *  boolean: true on success, false otherwise
     */
    protected function many_to_many_set($relation, $others = null)
    {
        if ($this->is_new_record())
            throw new LogicException('many_to_many_set called on a new record');
        if (!is_array($relation) || count($relation) < 3)
            throw new InvalidArgumentException('The relation is not defined');

        list($target_key, $join_table, $linked_key) = $relation;

        $delete = 'DELETE FROM {join_table} WHERE {target_key} = :id';
        $insert = 'INSERT INTO {join_table} ({target_key}, {linked_key}) VALUES';
        $insert_params = $delete_params = [
            '{join_table}' => $join_table,
            '{target_key}' => $target_key,
            '{linked_key}' => $linked_key,
            ':id'          => $this->id,
        ];

        // build the VALUES (...) for the INSERT statment
        $values = [];
        if (is_array($others) && !empty($others)) {
            for ($i = 0, $n = count($others); $i < $n; $i++) {
                $other    = $others[$i];
                $label    = ":val_$i";
                $values[] = "(:id, $label)";
                $insert_params[$label] = (
                    is_object($other) && isset($other->id) ? $other->id : $other
                );
            }
        }
        $values = join(', ', $values);

        $profile = $this->__db_profile;
        $options = ['profile' => $profile];
        $success = false;
        if (empty($values)) { // we only need to execute the DELETE query
            $success = (No2_SQLQuery::execute($delete, $delete_params, $options) !== false);
        } else {
            $transaction = false; // true if we started the transaction, false otherwise.
            if (!No2_SQLQuery::_inTransaction()) { // start our own transaction
                $transaction = No2_SQLQuery::_beginTransaction($profile);
                if (!$transaction) {
                    No2_Logger::err(get_class($this) . '->many_to_many_set: could not start a transaction');
                    return false;
                }
            }
            // from here on, we are in a transaction

            // do the work
            $success = (
                No2_SQLQuery::execute($delete, $delete_params, $options) !== false &&
                No2_SQLQuery::execute("$insert $values", $insert_params, $options) !== false
            );

            // terminate the transaction if we started it.
            if ($transaction) {
                if ($success)
                    $success = No2_SQLQuery::_commitTransaction($profile);
                else
                    No2_SQLQuery::_rollBackTransaction($profile);
            }
        }

        return $success;
    }

    /**
     * Get the linked models as an SQL query on which you can easily call select().
     *
     * @param
     *  array $relation: The relation to use
     *
     *  Define the relation with an array containing 3 values:
     *    - The target key: the column linked to this model in the join table
     *    - The join table name
     *    - The linked key: the column linked to the other model in the join table
     *
     * @param
     *  No2_SQLQuery $query: The linked model query to update
     *
     * @throws
     *  InvalidArgumentException: if the $relation do not contains the 3 mandatory values
     *
     * @return
     *  No2_SQLQuery: The parameter query updated by adding an INNER JOIN clause
     */
    protected function many_to_many_get($relation, $linked_query)
    {
        if (!is_array($relation) || count($relation) < 3)
            throw new InvalidArgumentException('The relation is not defined');

        list($target_key, $join_table, $linked_key) = $relation;

        $linked_class = $linked_query->klass;

        return $linked_query->join(
            "INNER JOIN {__m2m_join_table} AS __m2m_join_table
            ON  __m2m_join_table.$target_key = :__m2m_id
            AND __m2m_join_table.$linked_key = {__m2m_linked_table}.id",
            [
                ':__m2m_id'            => $this->id,
                '{__m2m_join_table}'   => $join_table,
                '{__m2m_linked_table}' => $linked_class::$table
            ]
        );
    }
}
