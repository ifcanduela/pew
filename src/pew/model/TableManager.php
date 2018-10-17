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
    protected $defaultConnection;

    /** @var static  */
    protected static $instance;

    public function __construct()
    {
    }

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

    public function setDefaultConnection(string $connectionName)
    {
        $this->defaultConnection = $connectionName;
    }

    /**
     * Set a database connection.
     *
     * @param string $connectionName
     * @param Database|callable $databaseConnection
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
     */
    public function create(string $recordClass, $connectionName = null)
    {
        # Check if the information is cached
        if (!isset($this->tableNames[$recordClass])) {
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
        $db = static::getConnection($connectionName);

        # Cache the table definition for future uses
        if (!isset($this->cachedTableDefinitions[$tableName])) {
            $this->cachedTableDefinitions[$tableName] = new Table($tableName, $db, $recordClass);
        }

        return clone $this->cachedTableDefinitions[$tableName];
    }

    /**
     * Guess the table name based on a class name.
     *
     * @param string $className
     * @return string
     */
    public static function guessTableName(string $className)
    {
        $reflectionClass = new ReflectionClass($className);
        $classShortName = $reflectionClass->getShortName();

        return Stringy::create($classShortName)
            ->removeRight("Model")
            ->underscored() . "s";
    }
}
