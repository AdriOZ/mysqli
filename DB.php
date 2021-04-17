<?php

/**
 * MySQL management.
 */
class DB
{
    /**
     * Connection to mysql.
     * @var mysqli
     */
    protected $connection;

    /**
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $db
     * @throws Exception
     */
    public function __construct(
        string $host,
        string $user,
        string $pass,
        string $db
    ) {
        $this->connection = mysqli_connect(
            $host,
            $user,
            $pass,
            $db
        );

        if (!$this->connection) {
            throw new Exception(mysqli_connect_error());
        }
    }

    /**
     * Closes the connection when the object is removed from memory.
     */
    public function __destruct()
    {
        if ($this->connection) {
            mysqli_close($this->connection);
        }
    }

    /**
     * Parses a value to be used in a SQL statement.
     * @param string $value The value to be parsed.
     * @return string The parsed value.
     */
    public function Parse(string $value)
    {
        return mysqli_real_escape_string($this->connection, (string) $value);
    }

    /**
     * Executes the statement and returns the result.
     * @param  string $stm Statement to be executed.
     * @return mysqli_result|bool Returns the result of the statement or false if there was an error.
     */
    public function Statement(string $stm): mysqli_result|bool
    {
        $tmp = mysqli_query($this->connection, $stm);
        return $tmp ? $tmp : false;
    }

    /**
     * Executes the query. Internally it uses sprintf, so the values starting % will be replaced.
     * @param string $query Query to be executed.
     * @param mixed $arguments Arguments to be replaced in the query
     * @return bool|array If the query is SELECT, it returns an array of objects. Oterwhise, it returns a boolean.
     */
    public function Query(string $query, ...$arguments): bool|array
    {
        $values = [];
        $result = false;

        foreach ($arguments as $a) {
            $values[] = $this->Parse($a);
        }
        $query = (string) call_user_func_array('vsprintf', [$query, $values]);

        if ('' !== $query) {
            $tmp = $this->Statement($query);

            if ($tmp instanceof mysqli_result) {
                $result = [];

                while ($fetch = mysqli_fetch_object($tmp)) {
                    $result[] = $fetch;
                }
            } else {
                $result = $tmp;
            }
        }
        return $result;
    }

    /**
     * Simple SELECT builder.
     * @param  string $table
     * @param  array  $fields
     * @param  array  $where
     * @param  string $orderBy
     * @param  string $orderType
     * @param  int    $limit
     * @return array
     */
    public function Select(
        string $table,
        array $fields = ['*'],
        array $where = [],
        string|null $orderBy = null,
        string $orderType = 'DESC',
        int $limit = -1
    ): array {
        $query = 'SELECT ' . implode(',', $fields) . ' FROM ' . $table;

        if (!empty($where)) {
            $query .= ' WHERE ';
            $parsedValues = [];

            foreach ($where as $key => $value) {
                if (is_null($value)) {
                    $parsedValues[] = $key . " IS NULL";
                } else {
                    $parsedValues[] = $key . "='" . $this->Parse($value) . "'";
                }
            }

            $query .= implode(' AND ', $parsedValues);
        }

        if (!is_null($orderBy)) {
            $query .= ' ORDER BY ' . $orderBy . ' ' . $orderType;
        }
        if ($limit !== -1) {
            $query .= ' LIMIT ' . $limit;
        }
        $tmp = $this->Statement($query);

        if ($tmp) {
            $result = [];

            while ($fetch = mysqli_fetch_object($tmp)) {
                $result[] = $fetch;
            }
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Shortcut for Select(table, [*], where)
     * @param string $table
     * @param array  $conditions
     * @return array
     */
    public function Where(string $table, array $conditions = []): array
    {
        return $this->Select($table, ['*'], $conditions);
    }

    /**
     * Shortcut for Select(table, ['*'], where), but only returns one object.
     * @param string $table
     * @param array  $conditions
     * @return object|null
     */
    public function Find(string $table = '', array $conditions = []): object|null
    {
        $tmp = $this->Select($table, ['*'], $conditions, null, 'DESC', 1);
        return !empty($tmp) ? $tmp[0] : null;
    }

    /**
     * Inserts data into the database.
     * @param  string $table Name of the table where the data will be inserted.
     * @param  array  $data  Associative array with the fields of the table as keys and the
     *                       values to be inserted as the values.
     * @return bool True if the data is correctly inserted.
     */
    public function Insert(string $table, array $data = []): bool
    {
        $values = array_values($data);
        $fields = array_keys($data);
        $correctValues = [];

        foreach ($values as $v) {
            if (is_null($v)) {
                $correctValues[] = "NULL";
            } else {
                $correctValues[] = "'" . $this->Parse($v) . "'";
            }
        }
        $insert = 'INSERT INTO ' . $table . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $correctValues) . ')';
        return (bool)$this->Statement($insert);
    }


    /**
     * Makes a bulk insert of data into a table.
     * @param string $table Name of the table.
     * @param array $fields Array of string containing the fields that will be inserted.
     * @param array $data Array of arrays containing the multiple inserts.
     * @return bool True if the data is correctly inserted.
     */
    public function Bulk(string $table, array $fields = [], array $data = [[]]): bool
    {
        $bulkValues = [];

        foreach ($data as $d) {
            $correctValues = [];
            foreach ($d as $v) {
                if (is_null($v)) {
                    $correctValues[] = "NULL";
                } else {
                    $correctValues[] = "'" . $this->Parse($v) . "'";
                }
            }
            $bulkValues[] = '(' . implode(',', $correctValues) . ')';
        }
        $insert = 'INSERT INTO ' . $table . ' (' . implode(',', $fields) . ') VALUES ' . implode(',', $bulkValues);
        return (bool)$this->Statement($insert);
    }

    /**
     * Updates the data of the database.
     * @param  string $table   Name of the table to be updated.
     * @param  array  $data    Associative array with the fields of the table as keys and the
     *                         values to be inserted as the values of the array.
     * @param array   $conditions Associative array withe the fields of the table as keys, and the
     *                         values of the filters as the values of the array.
     * @return boolean True if the data is correctly updated.
     */
    public function Update(string $table, array $data = [], array $conditions = []): bool
    {
        $update = 'UPDATE ' . $table . ' SET ';
        $parsedValues = [];

        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $parsedValues[] = $key . "=" . "NULL";
            } else {
                $parsedValues[] = $key . "='" . $this->Parse($value) . "'";
            }
        }
        $update .= implode(',', $parsedValues);

        if (!empty($conditions)) {
            $update .= ' WHERE ';
            $parsedFilters = [];

            foreach ($conditions as $key => $value) {
                $parsedFilters[] = $key . "='" . $this->Parse($value) . "'";
            }

            $update .= implode(' AND ', $parsedFilters);
        }
        return (bool)$this->Statement($update);
    }

    /**
     * Deletes data from the database.
     * @param  string $table   Affected table.
     * @param  array  $filters Associative array with the keys of the array
     *                         as the fields and the values of the arrays as the values used in the filter.
     * @return boolean True if the data is correctly removed.
     */
    public function Delete(string $table, array $filters = []): bool
    {
        $del = 'DELETE FROM ' . $table;

        if (!empty($filters)) {
            $del .= ' WHERE ';
            $parsedFilters = [];

            foreach ($filters as $key => $value) {
                $parsedFilters[] = $key . "='" . $this->Parse($value) . "'";
            }

            $del .= implode(' AND ', $parsedFilters);
        }
        return (bool)$this->Statement($del);
    }

    /**
     * Begins a transaction.
     * @return bool
     */
    public function Begin(): bool
    {
        return mysqli_begin_transaction($this->connection);
    }

    /**
     * Commits the transaction.
     * @return bool
     */
    public function Commit(): bool
    {
        return mysqli_commit($this->connection);
    }

    /**
     * Reverts the transaction.
     * @return bool
     */
    public function Rollback(): bool
    {
        return mysqli_rollback($this->connection);
    }

    /**
     * Returns the Id of the last inserted entity.
     * @return int|string
     */
    public function LastId(): int
    {
        return (int)mysqli_insert_id($this->connection);
    }

    /**
     * Returns the last error message.
     * @return string 
     */
    public function GetError(): string
    {
        return mysqli_error($this->connection);
    }
}
