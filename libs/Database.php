<?php

namespace pew\libs;

/**
 * The Database class encapsulates database access.
 *
 * PewDatabase implements PHP Data Objects (PDO) to provide a homogeneous
 * interface for multiple Relational Database Management Systems. Currently
 * available are SQLite and MySQL. Configuration is defined via a simple
 * associative array passed into the constructor.
 *
 * The methods contained within this class are aimed to simplify basic database
 * operations, such as simple selects, inserts and updates.
 *
 * One of the ways of using this class is the following:
 *
 *      $pdb = new \pew\libs\Database($config);
 *      $my_cat = $pdb->where(array('name' => 'Cuddles'))->single('cats');
 *
 * There are more examples at the bottom of this file.
 * 
 * @package pew\libs
 * @author ifcanduela <ifcanduela@gmail.com>
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
    public $fetch_mode = \PDO::FETCH_ASSOC;

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
    private $tags = array();
    
    /**
     * @var int Number of tagged parameters in a prepared statement.
     */
    protected static $tag_count = 0;
    
    /**
     * @var array Kag/value pairs for WHERE clauses in prepared statements.
     */
    private $where_tags = array();
    
    /**
     * @var array Key/value pairs for SET clauses in prepared statements.
     */
    private $set_tags = array();
    
    /**
     * @var array Key/value pairs for use in prepared statements with INSERT.
     */
    private $insert_tags = array();
    
    /**
     * @var array Key/value pairs for HAVING clauses in prepared statements.
     */
    private $having_tags = array();
    
    /**
     * Build the connection string and connect to the selected database engine.
     *
     * Connects to the specified database engine and sets PDO error mode to
     * ERRMODE_EXCEPTION.
     * 
     * @param mixed $config A PDO object or an array
     * @throws InvalidArgumentException If the DB engine is not selected
     */
    public function __construct($config = null)
    {
        if (is_array($config)) {
            if (!isset($config['engine'])) {
                throw new \InvalidArgumentException('Database engine was not selected');
            }

            $this->config = $config;
            
            $this->connect($config);
        } elseif (is_object($config)) {
            $this->pdo($config);
        }
    }
    
    /**
     * Connects to the configured database provider.
     *
     * @return bool True if the connection was successful, false otherwise
     */
    public function connect($config)
    {
        if (!$this->is_connected) {
            extract($config);
            
            try {
                switch ($engine) {
                    case self::SQLITE:
                        $this->pdo = new \PDO($engine . ':' . $file);
                        
                        # check if file and containing folder are writable
                        $this->is_writable = is_writable(dirname($file)) && is_writable($file);
                    break;
                    
                    case self::MYSQL:
                    default:
                        $this->pdo = new \PDO(
                            $engine . ':dbname=' . $name . ';host=' . $host,
                            $user, $pass, array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'")
                        );
                }
                
                $this->is_connected = true;
            } catch (\PDOException $e) {
                $this->is_connected = false;
                throw $e;
            }
         }
        
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

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
    public function pdo(\PDO $pdo = null)
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
     * @return \pew\libs\Database The Database object
     */
    public function from($from)
    {
        $this->from = $from;
        return $this;
    }
    
    /**
     * Sets the INTO field for INSERT queries.
     *
     * This function is an alias for PewDatabase::from()
     *
     * @param string $from The list of tables against which to perform the query
     * @return \pew\libs\Database The Database object
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
     * @return PewDataabse The PewDatabase object
     */
    public function fields($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Sets the WHERE field and its values for prepared statements.
     *
     * @param string $from The list of tables against which to perform the query
     * @return \pew\libs\Database The Database object
     */
    public function where($conditions)
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
     * @return \pew\libs\Database The Database object
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
     * @param string $conditions An array of field/value pairs
     * @return \pew\libs\Database The Database object
     */
    public function having($conditions)
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
     * @return \pew\libs\Database The Database object
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
     * E.G.: Use "1" to return one row or "4,1" to return the fourth row.
     *
     * @param string $limit Either "row_count", or "offset, row_count"
     * @return \pew\libs\Database The Database object
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
     * @param string $set An array of field/value pairs
     * @return \pew\libs\Database The Database object
     */
    public function set($set)
    {
        list($this->set_tags, $this->set) = $this->build_tags($set, 's_', ' SET ', ', ');
        
        return $this;
    }
    
    /**
     * Sets the INTO and VALUES fields and their values for an INSERT prepared
     * statement.
     *
     * @param string $values An array of field/value pairs
     * @return \pew\libs\Database The Database object
     */
    public function values($values)
    {
        list($this->insert_tags) = $this->build_tags($values, 'i_');
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
     * @param string $table_name Name of the table in the database
     * @param bool $as_array Return multiple keys as an array (default is true)
     * @return mixed A comma-separated string with the primary key fields or an
     *               array if $as_array is true
     */
    public function get_pk($table, $as_array = false)
    {
        if (!$this->is_connected) {
            throw new \PDOException;
        }
        
        $pk = array();
        
        try {
            $sql = "SHOW COLUMNS FROM {$table}";
            $primary_key_index = 'Key';
            $primary_key_value = 'PRI';
            $table_name_index = 'Field';
            $stm = $this->pdo->query($sql);
            $r = $stm->fetchAll();
        } catch (\PDOException $e) {
            try {
                $sql = "PRAGMA table_info({$table})";
                $primary_key_index = 'pk';
                $primary_key_value = 1;
                $table_name_index = 'name';
                $stm = $this->pdo->query($sql);
                $r = $stm->fetchAll();
            } catch (\PDOException $e) {
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
            throw new \PDOException;
        }
        
        $cols = array();
        
        try {
            $sql = "SHOW COLUMNS FROM {$table}";
            $table_name_index = 'Field';

            # Get all columns from a selected table
            $r = $this->pdo->query($sql)->fetchAll();
        } catch (\PDOException $e) {
            try {
                $sql = "PRAGMA table_info({$table})";
                $table_name_index = 'name';
                
                # Get all columns from a selected table
                $r = $this->pdo->query($sql)->fetchAll();
            } catch (\PDOException $e) {
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
        $exists = false;
        
        try {
            $this->pdo->prepare("SELECT 1 FROM $table");
            $exists = true;
        } catch (\PDOException $e) {
            $exists = false;
        }
        
        return $exists;
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
            return array(array(), '');
        }
        
        $where = '';
        $atoms = array();
        $tags = array();
        
        if (count($conditions) > 0) {
            foreach ($conditions as $k => $v) {
                if (is_numeric($k) && is_string($v)) {
                    # If the key is numeric, the value is a string with the
                    # condition; There is nothing else to do
                    $atoms[] = $v;
                } else {
                    # If the key is a table field, use PDO parameters
                    ++self::$tag_count;
                    # Build a tag as :PREFIX_fieldname_TAGCOUNT
                    $tag = str_replace('.', '_', $k);
                    $tag = ':' . $prefix . $tag . '_' . self::$tag_count;
                    
                    if (is_array($v)) {
                        # The comparison operator is provided
                        if (strtoupper($v[0]) == 'IN') {
                            # Tags are not supported for IN lists
                            $atoms[] = "$k IN ({$v[1]})";
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
                        # The comparison operator defaults to '='
                        $atoms[] = "$k = $tag";
                        $tags[$tag] = $v;
                    }
                }
            }
            
            $where_string = " $clause " . join($separator, $atoms);
        }
        
        return array($tags, $where_string, 'tags' => $tags, 'clause' => $where_string);
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
            throw new \PDOException;
        }

        # Try to prepare the statement
        try {
            $stm = $this->pdo->prepare($query);
        } catch (\PDOException $e) {
            throw new \PDOException("Query could not be prepared: $query");
        }
        
        # Execute the prepared statement
        try {
            $stm->execute($this->tags);
        } catch(\PDOException $e) {
            throw new \PDOException("Query could not be executed: $query");
        }
        
        # Everything's OK, return the complete statement
        return $stm;
    }

    /**
     * Selects the first column from the first row in a query.
     *
     * @param string $table_name The table name
     * @param array $conditions Conditions
     * @return int Number of rows deleted
     * @throws Exception Exception thrown if no table is set
     */
    public function cell($fields = null, $table = null)
    {
        if (isset($table)) {
            $this->from = $table;
        } else {
            if (!isset($this->from)) {
                throw new \InvalidArgumentException("No table provided for method PewDatabase::cell()");
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
     * @param string $table_name The table name
     * @param array $conditions Conditions
     * @return int Number of rows deleted
     * @throws InvalidArgumentException Exception thrown if no table is set
     */
    public function single($table = null, $fields = null)
    {
        if (isset($table)) {
            $this->from = $table;
        } else {
            if (!isset($this->from)) {
                throw new \InvalidArgumentException("No table provided for method PewDatabase::single()");
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
     * @param string $table_name The table name
     * @param array $conditions Conditions
     * @return array Indexed array with the resulting rows
     * @throws InvalidArgumentException If no table is set
     */
    public function select($table = null, $fields = null)
    {
        if (isset($table)) {
            $this->from = $table;
        } else {
            if (!isset($this->from)) {
                throw new \InvalidArgumentException("No table provided for method PewDatabase::select()");
            }
        }
        
        if (isset($fields)) {
            $this->fields = $fields;
        }
        
        $query = $this->get_query('select', $this->from);
        $stm = $this->run_query($query);
        $this->reset();

        if ($this->fetch_class) {
            $stm->setFetchMode(\PDO::FETCH_CLASS, $this->fetch_class);
        } else {
            $stm->setFetchMode($this->fetch_mode);
        }

        return $stm->fetchAll();
    }

    /**
     * Inserts a row in a table.
     *
     * @param string $table_name The table name
     * @param array $data Values to modify
     * @param array $conditions Conditions
     * @return int Primary key value of the last inserted element
     * @throws InvalidArgumentException If no table is set
     */
    public function insert($table = null)
    {
        if (isset($table)) {
            $this->from = $table;
        } else {
            if (!isset($this->from)) {
                throw new \InvalidArgumentException("No table provided for method PewDatabase::insert()");
            }
        }
        
        $query = $this->get_query('INSERT', $this->from);
        
        $stm = $this->run_query($query);
        
        $this->reset();
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Updates rows in a table.
     *
     * @param string $table_name The table name
     * @param array $data Values to modify
     * @param array $conditions Conditions
     * @return int Number of rows affected
     * @throws InvalidArgumentException If no table is set
     */
    public function update($table = null)
    {
        if (isset($table)) {
            $this->from = $table;
        } else {
            if (!isset($this->from)) {
                throw new \InvalidArgumentException("No table provided for method PewDatabase::update()");
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
     * @param string $table_name The table name
     * @param array $conditions
     * @return int Number of rows deleted
     * @throws InvalidArgumentException If no table is set
     */
    public function delete($table = null)
    {
        if (isset($table)) {
            $this->from = $table;
        } else {
            if (!isset($this->from)) {
                throw new \InvalidArgumentException("No table provided for method PewDatabase::delete()");
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
     *                      PewDatabase::from() and PewDatabase::into()
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
     * @return PewDatabase Returns the PewDatabase object
     */
    public function reset()
    {
        $this->from =       $this->where =       $this->order_by =
        $this->group_by =   $this->having =      $this->limit =
        $this->where =      $this->set =         null;
        
        $this->tags =       $this->where_tags =  $this->having_tags =
        $this->set_tags =   $this->insert_tags = array();
        
        $this->fields = '*';
        
        self::$tag_count = 0;
        
        return $this;
    }
}

/**
 * $pdb = new \pew\libs\Database(['engine' => 'sqlite', 'file' => 'db.sqlite']);
 *
 * $pdb = new PewDatabase();
 *
 * $eighties_movies = $pdb->where(array('year' => array('between', 1980, 1990)))->select('movies', 'title, year, director');
 *
 * $how_many_kubrick_movies = $pdb->fields('count(*)')->where(array('director_name' => 'Stanley Kubrick'))->cell('movies');
 *
 * $new_id = $pdb->values(array('title' => 'The Dark Knight Rises', 'director' => 'Christopher Nolan'))->insert('movies');
 *
 * $modified_studios = $pdb->set(array('country' => 51))->where(array('country' => null))->update('studios');
 *
 * $all_movies = $pdb->select('movies');
 *
 * $all_black_and_white_movies = $pdb->pdo->query("SELECT id, name, year FROM movies WHERE color = FALSE");
 */
