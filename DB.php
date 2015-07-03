<?php
class DB {
    /**
     * Host of the database.
     * @var string
     */
    private static $_HOST = '';

    /**
     * Name of the database.
     * @var string
     */
    private static $_DB = '';

    /**
     * User registered in MySQL server.
     * @var string
     */
    private static $_USER = '';

    /**
     * Password of the user registered in MySQL server.
     * @var string
     */
    private static $_PASS = '';

    # NOTE: static property, just one point of connection.
    /**
     * Connecion.
     * @var resource
     */
    private static $_connection = null;

    /**
     * Begins the connection to the database.
     */
    public function __construct () {
        if ( is_null( self::$_connection ) ) {
            self::$_connection = mysqli_connect( self::$_HOST, self::$_USER,
                self::$_PASS, self::$_DB );
            # Checking for errors
            if ( mysqli_errno( self::$_connection ) ) {
                throw new Exception( 'Can\'t connect to database' );
            }
        }
    }

    /**
     * Close connection when object is destroyed.
     */
    public function __destruct () {
        if ( !is_null( self::$_connection ) ) {
            mysqli_close( self::$_connection );
            self::$_connection = null;
        }
    }

    /**
     * Returns the instance of the connection.
     * @return resource mysqli connection.
     */
    public function getConnection () {
        return mysqli_connect( self::$_HOST, self::$_USER, self::$_PASS,
            self::$_DB );
    }

    /**
     * Executes a custom statement.
     * @param  string $statement Insert, update, delete or query clause.
     * @return mixed            Result of executing the statement.
     */
    public function customStatement ( $statement ) {
        $correct = mysqli_real_escape_string( self::$_connection, $statement );
        return $this->_executeStatement( $correct );
    }

    /**
     * Executes a custom query and returns an array of associative arrays
     * with the data.
     * @param  string $query Query clause.
     * @return array        Array with the data.
     */
    public function customQuery ( $query = '' ) {
        $statement = mysqli_real_escape_string( self::$_connection, $query );
        return $this->_fetchResult( $this->_executeStatement( $statement ) );
    }

    /**
     * Executes a simple query.
     * @param  string $table   Tabla to search data.
     * @param  array  $fields  Array of fields.
     * @param  array  $filters Associative array with the filters.
     * @return array          Fetched data.
     */
    public function simpleQuery ( $table = '', $fields = array( '*' ),
        $filters = array() ) {
        # Build the query
        $statement = 'SELECT '.implode( ',', $fields ).' FROM '.$table;

        # Optional filters
        if ( count( $filters ) > 0 ) {
            $where = array();   # Parsed filters

            foreach ( $filters as $key => $value ) {
                $where[] = $key.'='.$this->_parseValue( $value );
            }
            $statement .= ' WHERE '.implode( ' AND ', $where );
        }

        # Execute and return
        return $this->_fetchResult( $this->_executeStatement( $statement ) );
    }

    /**
     * Executes an insert clause.
     * @param  string $table Table where the data is inserted.
     * @param  array  $data  Associative array 'field name' => 'value'.
     * @return boolean       Returns true if the data is correctly inserted.
     */
    public function insert ( $table = '', $data = array() ) {
        $keys = array();            # Keys of the array.
        $correctValues = array();   # Parsed values of the array.

        foreach ( $data as $key => $value ) {
            $keys[] = $key;
            $correctValues = $this->_parseValue( $value );
        }

        # Build the insert statement
        $statement = 'INSERT INTO '.$table.' ('.implode( ',', $keys ).') '
            .' VALUES ('.implode( ',', $correctValues ).')';

        # Execute and return
        return $this->_executeStatement( $statement );
    }

    /**
     * Executes an update clause.
     * @param  string $table   Table that will be updated.
     * @param  array  $data    Associative array 'field name' => 'value'.
     * @param  array  $filters Associative array 'field name' => 'value'.
     * @return boolean          Returns true if the data is correctly updated.
     */
    public function update ( $table = '', $data = array(),
        $filters = array() ) {
        $sets = array();    # Array of sets statement (SET field=value)

        foreach ( $data as $key => $value ) {
            $sets[] = $key.'='.$this->_parseValue( $value );
        }

        # Build the update statement
        $statement = 'UPDATE '.$table.' SET '.implode( ',', $sets );

        if ( count( $filters ) > 0 ) {
            $where = array();   # Array of filters

            foreach ( $filters as $key => $value ) {
                $where[] = $key.'='.$this->_parseValue( $value );
            }
            $statement .= ' WHERE '.implode( ' AND ', $where );
        }

        # Execute and return
        return $this->_executeStatement( $statement );
    }

    /**
     * Executes a delete clause.
     * @param  string $table   Table from which data is deleted.
     * @param  array  $filters Associative array 'field name' => 'value'.
     * @return boolean          Returns true if the data is correctly removed.
     */
    public function delete ( $table = '', $filters = array() ) {
        # Build the delete statement
        $statement = 'DELETE FROM '.$table;

        # Optional filters
        if ( count( $filters ) > 0 ) {
            $where = array();   # Array with the filters

            foreach ( $filters as $key => $value ) {
                $where[] = $key.'='.$this->_parseValue( $value );
            }
            $statement .= ' WHERE '.implode( ' AND ', $where );
        }

        # Execute and return
        return $this->_executeStatement( $statement );
    }

    /**
     * Returns the correct value of the value. Parses the string to avoid
     * SQL and HTML injection.
     * @param  mixed $value Value to parse.
     * @return mixed        The parsed value.
     */
    private function _parseValue ( $value ) {
        if ( is_string( $value ) ) {
            $mysqlParsed = mysqli_real_escape_string( self::$_connection, $value );
            $htmlParsed = htmlentities( $mysqlParsed );
            $correct = '\''.$htmlParsed.'\'';     # Strings are placed between ''
        } else {
            $correct = $value;
        }
        return $correct;
    }

    /**
     * Executes the statement and returns the results.
     * @param  string $statement Statement to execute.
     * @return mixed            Result of executing the statement.
     */
    private function _executeStatement ( $statement ) {
        return mysqli_query( self::$_connection, $statement );
    }

    /**
     * Builds an array of associative arrays with the result of a query.
     * @param  mixed $result Result of executing a query.
     * @return array         Array with the data.
     */
    private function _fetchResult ( $result ) {
        $data = array();
        while ( $row = mysqli_fetch_assoc( $result ) ) {
            $data[] = $row;
        }
        return $data;
    }
}
