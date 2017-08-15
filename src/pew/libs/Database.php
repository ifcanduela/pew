<?php

namespace pew\libs;

use PDO;
use PDOStatement;
use PDOException;

/**
 * The Database class encapsulates database access.
 *
 * Database implements PHP Data Objects (PDO) to provide a homogeneous
 * interface for multiple Relational Database Management Systems. Currently
 * available are SQLite and MySQL. Configuration is defined via a simple
 * associative array passed into the constructor.
 *
 * The methods contained within this class are aimed to simplify basic database
 * operations, such as simple selects, inserts and updates.
 */
class Database
{
    /*
     * Database engines.
     */
    const MYSQL  = 'mysql';
    const SQLITE = 'sqlite';

    /**
     * @var \PDO PHP Data Object for database access.
     */
    private $pdo = null;

    /**
     * @var int PDO statement fetch mode.
     */
    public $fetch_mode = PDO::FETCH_ASSOC;

    /**
     * @var string PDO statement fetch class.
     */
    public $fetch_class;

    /**
     * @var bool Connection established flag.
     */
    private $is_connected = false;

    /**
     * @var array Configuration parameters.
     */
    private $config;

    /**
     * @var boolean Check if SQLite file is writable.
     */
    public $is_writable = true;

    /**
     * @var string Last query run.
     */
    public $last_query = null;

    /**
     * @var string List of tables for FROM clause.
     */
    private $from = null;

    /**
     * @var string list of fields for SELECT or INSERT clauses.
     */
    private $fields = '*';

    /**
     * @var array SQL-formatted WHERE clause.
     */
    private $where = null;

    /**
     * @var string SQL-formatted LIMIT clause.
     */
    private $limit = null;

    /**
     * @var string SQL-formatted GROUP BY clause.
     */
    private $group_by = null;

    /**
     * @var array SQL-formatted HAVING clause.
     */
    private $having = null;

    /**
     * @var string SQL-formatted ORDER BY clause.
     */
    private $order_by = null;

    /**
     * @var array SQL-formatted VALUES clause.
     */
    private $values = null;

    /**
     * @var array SQL-formatted SET clause.
     */
    private $set = null;

    /**
     * @var array Key/value pairs for prepared statements.
     */
    private $tags = [];

    /**
     * @var int Number of tagged parameters in a prepared statement.
     */
    protected static $tag_count = 0;

    /**
     * @var array Key/value pairs for WHERE clauses in prepared statements.
     */
    private $where_tags = [];

    /**
     * @var array Key/value pairs for SET clauses in prepared statements.
     */
    private $set_tags = [];

    /**
     * @var array Key/value pairs for use in prepared statements with INSERT.
     */
    private $insert_tags = [];

    /**
     * @var array Key/value pairs for HAVING clauses in prepared statements.
     */
    private $having_tags = [];

    /**
     * Build the connection string and connect to the selected database engine.
     *
     * Connects to the specified database engine and sets PDO error mode to
     * ERRMODE_EXCEPTION.
     *
     * @param mixed $config A PDO object or an array
     * @throws \InvalidArgumentException If the DB engine is not selected
     */
    public function __construct($config = null)
    {
        if (is_array($config)) {
            if (!isset($config['engine'])) {
                throw new \InvalidArgumentException('Database engine was not selected');
            }

            $this->config = $config;

            $this->connect($config);
        } elseif ($config instanceof PDO) {
            $this->pdo($config);
        }
    }

    /**
     * Connects to the configured database provider.
     *
     * @param array $config
     * @return bool True if the connection was successful, false otherwise
     */
    protected function connect($config)
    {
        if (!$this->is_connected) {
            try {
                $engine = $config['engine'];
                switch ($engine) {
                    case self::SQLITE:
                        $file = $config['file'];
                        $this->pdo = new PDO($engine . ':' . $file);

                        # check if file and containing folder are writable
                        $this->is_writable = is_writable(dirname($file)) && is_writable($file);

                        break;

                    case self::MYSQL:
                    default:
                        $name = $config['name'];
                        $host = $config['host'];
                        $user = $config['user'];
                        $pass = $config['pass'];

                        $dsn = $engine . ':dbname=' . $name . ';host=' . $host;
                        $options = [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"];

                        $this->pdo = new PDO($dsn, $user, $pass, $options);
                }

                $this->is_connected = true;
            } catch (PDOException $e) {
                $this->is_connected = false;
                throw $e;
            }

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $this->is_connected;
    }

    /**
     * Destroy the PDO connection.
     *
     * @return null
     */
    public function disconnect()
    {
        $this->pdo = null;
        $this->is_connected = false;
    }

    /**
     * Set and retrieves the PDO instance in use.
     *
     * @param PDO $pdo Set a PDO instance for the wrapper.
     * @return PDO The PDO instance
     */
    public function pdo(PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
            $this->is_connected = true;
        }

        return $this->pdo;
    }

    /**
     * Sets the FROM field for subsequent queries.
     *
     * @param string $from The list of tables against which to perform the query
     * @return Database The Database object
     */
    public function from($from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Sets the INTO field for INSERT queries.
     *
     * This function is an alias for Database::from()
     *
     * @param string $into Table name
     * @return Database The Database object
     */
    public function into($into)
    {
        $this->from = $into;
        return $this;
    }

    /**
     * Sets the fields to return in SELECT queries.
     *
     * @param string $fields A SQl-formatted field list
     * @return Database The Database object
     */
    public function fields($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Sets the WHERE field and its values for prepared statements.
     *
     * @param array $conditions The list of comparisons
     * @return Database The Database object
     */
    public function where(array $conditions)
    {
        list($this->where_tags, $this->where) = $this->build_tags($conditions, 'w_');

        return $this;
    }

    /**
     * Sets the GROUP BY field and their values for an INSERT prepared
     * statement.
     *
     * Don't add 'GROUP BY' to the $order_by parameter.
     *
     * @param string $group_by SQL-formatted list of grouping fields
     * @return Database The Database object
     */
    public function group_by($group_by)
    {
        if ($group_by = trim($group_by)) {
            $this->group_by = " GROUP BY $group_by ";
        }

        return $this;
    }

    /**
     * Sets the HAVING field and its values for prepared statements.
     *
     * @param array $conditions An array of field/value pairs
     * @return Database The Database object
     */
    public function having(array $conditions)
    {
        list($this->having_tags, $this->having) = $this->build_tags($conditions, 'h_', ' HAVING ');

        return $this;
    }

    /**
     * Sets the ORDER BY field and their values for an INSERT prepared
     * statement.
     *
     * Don't add 'ORDER BY' to the $order_by parameter.
     *
     * @param string $order_by A SQL-formatted list of sorting fields
     * @return Database The Database object
     */
    public function order_by($order_by)
    {
        if ($order_by = trim($order_by)) {
            $this->order_by = " ORDER BY $order_by ";
        }

        return $this;
    }

    /**
     * Sets the LIMIT clause for a prepared statement.
     *
     * E.g.: Use "1" to return one row or "4,1" to return the fourth row.
     *
     * @param string $limit Either "row_count", or "offset, row_count"
     * @return Database The Database object
     */
    public function limit($limit)
    {
        if ($limit = trim($limit)) {
            $this->limit = " LIMIT $limit ";
        }

        return $this;
    }

    /**
     * Sets the SET field and its values for UPDATE prepared statements.
     *
     * @param array $set An array of field/value pairs
     * @return Database The Database object
     */
    public function set(array $set)
    {
        list($this->set_tags, $this->set) = $this->build_tags($set, 's_', ' SET ', ', ');

        return $this;
    }

    /**
     * Sets the INTO and VALUES fields and their values for an INSERT prepared
     * statement.
     *
     * @param array $values An array of field/value pairs
     * @return Database The Database object
     */
    public function values(array $values)
    {
        list($this->insert_tags) = $this->build_tags($values, 'i_', 'VALUES');

        $this->fields = join(', ', array_keys($values));
        $this->values = ' VALUES (' . join(', ', array_keys($this->insert_tags)) . ') ';

        return $this;
    }

    /**
     * Finds the Primary Key fields of a table.
     *
     * The most common return is a string with the name of the primary key
     * column (for example, "id"). If the primary key is composite, this
     * method will return all primary keys in a comma-separated string, except
     * if the second parameter is specified as true, in which case the return
     * will be an array.
     *
     * @param string $table Name of the table in the database
     * @param bool $as_array Return multiple keys as an array (default is true)
     * @return mixed A comma-separated string with the primary key fields or an
     *               array if $as_array is true
     */
    public function get_pk($table, $as_array = false)
    {
        if (!$this->is_connected) {
            throw new PDOException;
        }

        $pk = [];

        try {
            $sql = "SHOW COLUMNS FROM {$table}";
            $primary_key_index = 'Key';
            $primary_key_value = 'PRI';
            $table_name_index = 'Field';
            $stm = $this->pdo->query($sql);
            $r = $stm->fetchAll();
        } catch (PDOException $e) {
            try {
                $sql = "PRAGMA table_info({$table})";
                $primary_key_index = 'pk';
                $primary_key_value = 1;
                $table_name_index = 'name';
                $stm = $this->pdo->query($sql);
                $r = $stm->fetchAll();
            } catch (PDOException $e) {
                throw $e;
            }
        }

        # Search all columns for the Primary Key flag
        foreach ($r as $col) {
            if (($col[$primary_key_index] == $primary_key_value)) {
                # Add this column to the primary keys list
                $pk[] = $col[$table_name_index];
            }
        }

        # if the return value is preferred as string
        if (!$as_array) {
            $pk = join(',', $pk);
        }

        return $pk;
    }

    /**
     * Get a list of the table fields.
     *
     * @param string $table Table name
     * @return array List of the table fields
     */
    public function get_cols($table)
    {
        if (!$this->is_connected) {
            throw new PDOException;
        }

        $cols = [];

        try {
            $sql = "SHOW COLUMNS FROM {$table}";
            $table_name_index = 'Field';

            # Get all columns from a selected table
            $r = $this->pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            try {
                $sql = "PRAGMA table_info({$table})";
                $table_name_index = 'name';

                # Get all columns from a selected table
                $r = $this->pdo->query($sql)->fetchAll();
            } catch (PDOException $e) {
                throw $e;
            }
        }

        # Add column names to $cols array
        foreach ($r as $col) {
            $cols[] = $col[$table_name_index];
        }

        return $cols;
    }

    /**
     * Find out if the database contains a table.
     *
     * @param string $table Table name
     * @return boolean True if the table exists, false otherwise
     */
    public function table_exists($table)
    {
        try {
            $this->pdo->prepare("SELECT 1 FROM $table");
            return true;
        } catch (PDOException $e) {}

        return false;
    }

    /**
     * Builds lists for PDO prepared statements.
     *
     * This function returns a string for the WHERE and HAVING and SET clauses,
     * and an array of :field_tag => field_value pairs for the binding of
     * parameters to tags in PDO prepared statements.
     *
     * The IN and BETWEEN operators are not yet supported.
     *
     * @param array $conditions An array with the conditions
     * @param string $prefix A string to prepend to the tags after ':' and the
     *                       name of the field
     * @param string $clause Which clause to prepare the string for, either
     *                       'WHERE' (by default), 'HAVING' or 'SET'
     * @param string $separator A string to insert between the pairs, usually
     *                          and by default it's ' AND ', but should be ', '
     *                          if $clause is 'SET'
     * @return array An array with tag/value pairs in the index 0 and a string
     *               for use with the selected clause in the index 1
     */
    protected function build_tags($conditions, $prefix = '', $clause = 'WHERE', $separator = ' AND ')
    {
        # When no conditions are given, provide a neutral set of data
        if (count($conditions) == 0) {
            return [[], ''];
        }

        $where_string = '';
        $atoms = [];
        $tags = [];

        if (count($conditions) > 0) {
            foreach ($conditions as $k => $v) {
                if (is_numeric($k) && is_string($v)) {
                    # If the key is numeric, the value is a string with the
                    # condition; There is nothing else to do
                    $atoms[] = $v;
                } elseif (is_null($v) && $clause === 'WHERE') {
                    $atoms[] = "`$k` IS NULL";
                } else {
                    # If the key is a table field, use PDO parameters
                    ++self::$tag_count;
                    # Build a tag as :PREFIX_fieldname_TAGCOUNT
                    $tag = str_replace(['.', '*', '(', ')'], '_', $k);
                    $tag = ':' . $prefix . $tag . '_' . self::$tag_count;

                    if (is_array($v)) {
                        # The comparison operator is provided
                        if (strtoupper($v[0]) == 'IN') {
                            $l = [];
                            foreach ($v[1] as $i => $val) {
                                $t = "{$tag}_in_{$i}";
                                $l[] = $t;
                                $tags[$t] = $val;
                            }

                            $atoms[] = "$k IN (" . join(', ', $l) . ")";
                        } elseif (strtoupper($v[0]) == 'BETWEEN') {
                            # For BETWEEN, two tags must be used:
                            # :PREFIX_fieldname_TAGCOUNT_a and
                            # :PREFIX_fieldname_TAGCOUNT_b
                            $atoms[] = "$k BETWEEN {$tag}_a AND {$tag}_b";
                            $tags[$tag.'_a'] = $v[1];
                            $tags[$tag.'_b'] = $v[2];
                        } else {
                            $atoms[] = "$k {$v[0]} $tag";
                            $tags[$tag] = $v[1];
                        }
                    } else {
                        # The comparison operator defaults to '=' or IS
                        if (is_null($v) && $clause === 'WHERE') {
                            # this is duplicate, test a bit
                            $atoms[] = "$k IS NULL";
                        } else {
                            $atoms[] = "$k = $tag";
                        }

                        $tags[$tag] = $v;
                    }
                }
            }

            $where_string = " $clause " . join($separator, $atoms);
        }

        return [$tags, $where_string, 'tags' => $tags, 'clause' => $where_string];
    }

    /**
     * Runs a prepared statement.
     *
     * @param string $query The SQL query to run
     * @return PDOStatement The resulting PDO Statement object
     * @throws PDOException In case of preparation or execution error
     */
    protected function run_query($query)
    {
        if (!$this->is_connected) {
            throw new PDOException;
        }

        # Try to prepare the statement
        try {
            $stm = $this->pdo->prepare($query);
        } catch (PDOException $e) {
            throw new PDOException("Query could not be prepared: $query -- Original message is " . $e->getMessage());
        }

        # Execute the prepared statement
        try {
            $stm->execute($this->tags);
        } catch (PDOException $e) {
            throw new PDOException("Query could not be prepared: $query -- Original message is " . $e->getMessage());
        }

        # Everything's OK, return the complete statement
        return $stm;
    }

    /**
     * Selects the first column from the first row in a query.
     *
     * @param string $fields
     * @param string $table
     * @return int Number of rows deleted
     */
    public function cell($fields = null, $table = null)
    {
        if (isset($table)) {
            $this->from = $table;
        } else {
            if (!isset($this->from)) {
                throw new \InvalidArgumentException("No table provided for method Database::cell()");
            }
        }

        if (isset($fields)) {
            $this->fields = $fields;
        }

        if (!isset($this->limit)) {
            $this->limit(1);
        }

        $query = $this->get_query('SELECT');
        $stm = $this->run_query($query);
        $this->reset();

        return $stm->fetchColumn();
    }

    /**
     * Selects a single row in a table.
     *
     * @param string $table
     * @param string $fields
     * @return int Number of rows deleted
     */
    public function single($table = null, $fields = null)
    {
        if (isset($table)) {
            $this->from = $table;
        } else {
            if (!isset($this->from)) {
                throw new \InvalidArgumentException("No table provided for method Database::single()");
            }
        }

        if (isset($fields)) {
            $this->fields = $fields;
        }

        if (!isset($this->limit)) {
            $this->limit(1);
        }

        $query = $this->get_query('SELECT');
        $stm = $this->run_query($query);
        $this->reset();

        if ($this->fetch_class) {
            $stm->setFetchMode(\PDO::FETCH_CLASS, $this->fetch_class);
        } else {
            $stm->setFetchMode($this->fetch_mode);
        }

        return $stm->fetch();
    }

    /**
     * Selects rows from a table.
     *
     * @param string $table The table name
     * @param array $fields List of column names
     * @return array Indexed array with the resulting rows
     * @throws \InvalidArgumentException If no table is set
     */
    public function select($table = null, $fields = null)
    {
        if (isset($table)) {
            $this->from = $table;
        } else {
            if (!isset($this->from)) {
                throw new \InvalidArgumentException("No table provided for method Database::select()");
            }
        }

        if (isset($fields)) {
            $this->fields = $fields;
        }

        $query = $this->get_query('select', $this->from);
        $stm = $this->run_query($query);
        $this->reset();

        if ($this->fetch_class) {
            $stm->setFetchMode(PDO::FETCH_CLASS, $this->fetch_class);
        } else {
            $stm->setFetchMode($this->fetch_mode);
        }

        return $stm->fetchAll();
    }

    /**
     * Inserts a row in a table.
     *
     * @param string $table The table name
     * @return int Primary key value of the last inserted element
     * @throws \InvalidArgumentException If no table is set
     */
    public function insert($table = null)
    {
        if (!$this->is_writable) {
            throw new PDOException("Database is not writable.");
        }

        if (isset($table)) {
            $this->from = $table;
        } else {
            if (!isset($this->from)) {
                throw new \InvalidArgumentException("No table provided for method Database::insert()");
            }
        }

        $query = $this->get_query('INSERT', $this->from);
        $this->run_query($query);
        $this->reset();

        return $this->pdo->lastInsertId();
    }

    /**
     * Updates rows in a table.
     *
     * @param string $table The table name
     * @return int Number of rows affected
     * @throws \InvalidArgumentException If no table is set
     */
    public function update($table = null)
    {
        if (!$this->is_writable) {
            throw new PDOException("Database is not writable.");
        }

        if (isset($table)) {
            $this->from = $table;
        } else {
            if (!isset($this->from)) {
                throw new \InvalidArgumentException("No table provided for method Database::update()");
            }
        }

        $query = $this->get_query('UPDATE', $this->from);
        $stm = $this->run_query($query);
        $this->reset();

        return $stm->rowCount();
    }

    /**
     * Deletes rows in a table.
     *
     * @param string $table The table name
     * @return int Number of rows deleted
     * @throws \InvalidArgumentException If no table is set
     */
    public function delete($table = null)
    {
        if (!$this->is_writable) {
            throw new PDOException("Database is not writable.");
        }

        if (isset($table)) {
            $this->from = $table;
        } else {
            if (!isset($this->from)) {
                throw new \InvalidArgumentException("No table provided for method Database::delete()");
            }
        }

        $query = $this->get_query('DELETE', $this->from);
        $stm = $this->run_query($query);
        $this->reset();

        return $stm->rowCount();
    }

    /**
     * Builds a Select, Update, Insert or Delete query.
     *
     * This method updates the last_query property.
     *
     * @param string $type One of SELECT, UPDATE, INSERT or DELETE
     * @param string $table A table name list that overrides that of
     *                      Database::from() and Database::into()
     * @return string The sql statement
     */
    public function get_query($type, $table = null)
    {
        $sql = '';

        if (!isset($table)) {
            $table = $this->from;
        }

        switch (strtoupper($type)) {
            case 'SELECT':
                $sql = "SELECT $this->fields FROM $table $this->where $this->group_by $this->having $this->order_by $this->limit";
                $this->tags = array_merge($this->where_tags, $this->having_tags);
                break;
            case 'UPDATE':
                $sql = "UPDATE $table $this->set $this->where";
                $this->tags = array_merge($this->set_tags, $this->where_tags);
                break;
            case 'INSERT':
                $sql = "INSERT INTO $table ($this->fields) $this->values";
                $this->tags = $this->insert_tags;
                break;
            case 'DELETE':
                $sql = "DELETE FROM $table $this->where";
                $this->tags = $this->where_tags;
                break;
            default:
                throw new \RuntimeException("Unknown query mode: {$type}");
        }

        $sql = trim(preg_replace('/\s+/', ' ', $sql));
        $this->last_query = $sql;

        return $sql;
    }

    /**
     * Resets the data in the SQL clauses.
     *
     * @return Database Returns the Database object
     */
    public function reset()
    {
        $this->from =       $this->where =       $this->order_by =
        $this->group_by =   $this->having =      $this->limit =
        $this->where =      $this->set =         null;

        $this->tags =       $this->where_tags =  $this->having_tags =
        $this->set_tags =   $this->insert_tags = [];

        $this->fields = '*';

        self::$tag_count = 0;

        return $this;
    }

    /**
     * Prepare and execute a query.
     *
     * Examples:
     *
     *     # get an array of all rows that match the query
     *     $db->query("SELECT * FROM table1 WHERE name = ?", [$name]);
     *
     *     # get the resulting PDOStatement object
     *     $db->query("SELECT * FROM table1 LIMIT 100", [], true);
     *
     *     # get the count of affected rows
     *     $db->query("INSERT INTO table3 () VALLUES (:alpha, :beta, :gamma)", [
     *         ':alpha' => $alpha,
     *         ':beta' => $beta,
     *         ':gamma' => $gamma,
     *     ]);
     *
     * @param string $sql SQL Statement to execute
     * @param array $params Placeholder and value pairs
     * @param boolean $return_stm Return the PDOStatement object
     * @param int $fetch_mode One of the PDO fetch modes
     * @return mixed Number of affected rows or selected records
     */
    public function query($sql, array $params = [], $return_stm = false, $fetch_mode = PDO::FETCH_ASSOC)
    {
        $stm = $this->pdo->prepare($sql);
        $stm->execute($params);

        if ($return_stm) {
            return $stm;
        } elseif (substr(trim($sql), 0, 6) === 'SELECT') {
            return $stm->fetchAll($fetch_mode);
        } else {
            return $stm->rowCount();
        }
    }
}
