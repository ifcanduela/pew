<?php

namespace pew\model;

use Stringy\StaticStringy as Str;

use pew\libs\Database;

class TableClassNotFoundException extends \Exception {}
class InvalidTableClassException extends \Exception {}

class TableFactory
{
    /**
     * @var \pew\db\Database
     */
    protected $db;

    /**
     * @var array
     */
    // protected $namespaces = [];

    protected static $connections = [];

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
     * @param string  $tableName
     * @param string  $connectionName
     * @return TableInterface
     */
    public static function create($tableName, $recordClass, $connectionName = 'default')
    {
        $db = static::getConnection($connectionName);

        return new Table($tableName, $db, $recordClass);
    }

    // /**
    //  * Registers a new namespace for model search.
    //  * 
    //  * @param string $namespace
    //  * @param string $class_suffix
    //  */
    // public function register_namespace($namespace, $class_suffix)
    // {
    //     $this->namespaces[] = [rtrim($namespace, '\\') . '\\', $class_suffix];
    // }
}
