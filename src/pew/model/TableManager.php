<?php

namespace pew\model;

use ifcanduela\db\Database;
use Stringy\Stringy;
use ReflectionClass;

class TableManager
{
    /** @var array */
    protected $connections = [];

    /** @var array */
    protected $connectionCallbacks = [];

    /** @var array */
    protected $cachedTableDefinitions = [];

    /** @var array */
    protected $cachedRecordClasses = [];

    /** @var string */
    protected $defaultConnection = "default";

    /** @var static  */
    protected static $instance;

    /**
     * Get a singleton instance of the TableManager.
     *
     * @param TableManager|null $instance
     * @return static
     */
    public static function instance(TableManager $instance = null)
    {
        if (isset($instance)) {
            static::$instance = $instance;
        } elseif (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Set the default database connection to use.
     *
     * @param string $connectionName
     * @return void
     */
    public function setDefaultConnection(string $connectionName)
    {
        $this->defaultConnection = $connectionName;
    }

    /**
     * Get the default database connection to use.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->defaultConnection;
    }

    /**
     * Set a database connection.
     *
     * @param string $connectionName
     * @param Database|callable $databaseConnection
     * @return void
     */
    public function setConnection(string $connectionName, $databaseConnection)
    {
        if (is_callable($databaseConnection)) {
            $this->connectionCallbacks[$connectionName] = $databaseConnection;
        } else {
            $this->connections[$connectionName] = $databaseConnection;
        }
    }

    /**
     * Get one of the configured database connections.
     *
     * @param string $connectionName
     * @return Database
     */
    public function getConnection(string $connectionName)
    {
        # Check if the connection has been initialized
        if (isset($this->connections[$connectionName])) {
            return $this->connections[$connectionName];
        }

        # Check if the connection can be initialized
        if (isset($this->connectionCallbacks[$connectionName])) {
            $callback = $this->connectionCallbacks[$connectionName];
            # Initialize the connection
            $this->connections[$connectionName] = $callback();

            return $this->connections[$connectionName];
        }

        throw new \InvalidArgumentException("Connection `{$connectionName}` not found");
    }

    /**
     * Create a table factory for a specific database table.
     *
     * @param string $recordClass
     * @param string|null $connectionName
     * @return Table
     * @throws \ReflectionException
     */
    public function create(string $recordClass, $connectionName = null)
    {
        # Check if the information is cached
        if (!isset($this->cachedRecordClasses[$recordClass])) {
            # Fetch default properties (tableName and connectionName)
            $reflectionClass = new ReflectionClass($recordClass);
            $defaultProperties = $reflectionClass->getDefaultProperties();

            # Cache the properties
            $this->cachedRecordClasses[$recordClass] = [
                $defaultProperties["tableName"] ?? static::guessTableName($recordClass),
                $defaultProperties["connectionName"] ?? $connectionName ?? $this->defaultConnection,
            ];
        }

        # Fetch tableName and connectionName
        list($tableName, $connectionName) = $this->cachedRecordClasses[$recordClass];

        # Fetch the connection
        $db = $this->getConnection($connectionName);

        # Cache the table definition for future uses
        if (!isset($this->cachedTableDefinitions[$tableName])) {
            $this->cachedTableDefinitions[$tableName] = new Table($tableName, $db, $recordClass);
        }

        return clone $this->cachedTableDefinitions[$tableName];
    }

    /**
     * Make a naive guess on a table name based on the class name.
     *
     * @param string $className
     * @return string
     */
    public static function guessTableName(string $className)
    {
        # Split $className into namespaces and class name.
        $segments = explode("\\", $className);
        # Get the last item
        $classShortName = array_pop($segments);

        # Transform the class name into an underscored version and make it plural
        return Stringy::create($classShortName)->underscored() . "s";
    }
}
