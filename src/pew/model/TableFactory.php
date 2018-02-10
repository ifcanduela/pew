<?php

namespace pew\model;

use ifcanduela\db\Database;

class TableFactory
{
    /** @var Database */
    protected $db;

    /** @var array */
    protected static $connections = [];

    /** @var array */
    protected static $connectionCallbacks = [];

    /** @var array */
    protected static $tableDefinitions = [];

    /**
     * Creates a new TableFactory.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Set a database connection.
     *
     * @param string $connectionName
     * @param Database|null $databaseConnection
     * @param \Closure|null $databaseConnectionCallback
     */
    public static function setConnection(string $connectionName, Database $databaseConnection = null, \Closure $databaseConnectionCallback = null)
    {
        if (!$databaseConnection && !$databaseConnectionCallback) {
            throw new \InvalidArgumentException("Invalid configuration for TableFactory: either a connection or a callback is required");
        }

        if ($databaseConnectionCallback) {
            static::$connectionCallbacks[$connectionName] = $databaseConnectionCallback;
        }

        if ($databaseConnection) {
            static::$connections[$connectionName] = $databaseConnection;
        }
    }

    /**
     * Get one of the configured database connections.
     *
     * @param string $connectionName
     * @return Database
     */
    public static function getConnection(string $connectionName)
    {
        if (isset(static::$connections[$connectionName])) {
            return static::$connections[$connectionName];
        }

        if (isset(static::$connectionCallbacks[$connectionName])) {
            $callback = static::$connectionCallbacks[$connectionName];
            static::$connections[$connectionName] = $callback();
            return static::$connections[$connectionName];
        }

        throw new \InvalidArgumentException("Connection {$connectionName} not found");
    }

    /**
     * Create a table factory for a specific database table.
     *
     * @param string $tableName
     * @param string $recordClass
     * @param string $connectionName
     * @return Table
     */
    public static function create($tableName, string $recordClass, $connectionName = 'default')
    {
        $db = static::getConnection($connectionName);

        if (!isset(static::$tableDefinitions[$tableName])) {
            static::$tableDefinitions[$tableName] = new Table($tableName, $db, $recordClass);
        }

        return clone static::$tableDefinitions[$tableName];
    }
}
