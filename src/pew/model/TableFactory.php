<?php

namespace pew\model;

use Stringy\StaticStringy as Str;
use pew\libs\Database;

class TableFactory
{
    /**
     * @var \pew\db\Database
     */
    protected $db;

    /**
     * @var array
     */
    protected static $connections = [];

    /**
     * @var array
     */
    protected static $connectionCallbacks = [];

    /**
     * Creates a new TableFactory.
     *
     * @param Database $db
     * @param array $namespaces Array of namespace and suffix
     */
    public function __construct(Database $db, array $namespaces = [])
    {
        $this->db = $db;

        foreach ($namespaces as $namespace) {
            $this->register_namespace($namespace[0], $namespace[1]);
        }
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
    public static function getConnection(string $connectionName): Database
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
     * @param string  $tableName
     * @param string  $connectionName
     * @return TableInterface
     */
    public static function create($tableName, $recordClass, $connectionName = 'default')
    {
        $db = static::getConnection($connectionName);

        return new Table($tableName, $db, $recordClass);
    }
}
