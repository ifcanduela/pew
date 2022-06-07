<?php

declare(strict_types=1);

namespace pew\model;

use ifcanduela\db\Database;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use Doctrine\Inflector\InflectorFactory;

use function pew\str;

/**
 * Methods to handle named database connections and create Table gateway objects.
 *
 * To configure a TableManager, either instantiate it normally or use the
 * singleton.
 *
 * ```php
 * $tm = new TableManager();
 * $tm = TableManager::instance();
 * ```
 *
 * Setup database connections in your instance of the TableManager. Database
 * connections can be \ifcanduela\db\Database objects or callbacks.
 *
 * ```php
 * $tm->setConnection("default", Database::sqlite("dev.sqlite"));
 * $tm->setConnection("user_db", Database::sqlite("users.sqlite"));
 * $tm->setConnection("prod", function () {
 *     return new Database::mysql("localhost", "pew_prod", "db_username", "db_password);
 * });
 * ```
 *
 * The table manager will default to one of the configured connections, by default
 * called "default". The name of the default connection can be set:
 *
 * ```php
 * $tm->setDefaultConnectionName("prod");
 * ```
 *
 * Your models can specify connections using the Model::$connectionName string property.
 * If no connection is set, the table manager's default connection will be used, if
 * it exists.
 *
 * ```php
 * class User extends \pew\Model
 * {
 *     public string $connectionName = "user_db";
 * }
 * ```
 */
class TableManager
{
    protected array $connections = [];

    protected array $connectionCallbacks = [];

    protected array $cachedTableDefinitions = [];

    protected array $cachedRecordClasses = [];

    protected string $defaultConnectionName = "default";

    protected static TableManager $instance;

    /**
     * Get a singleton instance of the TableManager.
     *
     * @param TableManager|null $instance
     * @return static
     */
    public static function instance(TableManager $instance = null): TableManager
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
     * @param null|Database|callable $databaseConnection
     * @return void
     */
    public function setDefaultConnectionName(string $connectionName, $databaseConnection = null): void
    {
        $this->defaultConnectionName = $connectionName;

        if ($databaseConnection) {
            $this->setConnection($connectionName, $databaseConnection);
        }
    }

    /**
     * Get the default database connection to use.
     *
     * @return string
     */
    public function getDefaultConnectionName(): string
    {
        return $this->defaultConnectionName;
    }

    /**
     * Set a database connection.
     *
     * @param string $connectionName
     * @param Database|callable $databaseConnection
     * @return void
     */
    public function setConnection(string $connectionName, $databaseConnection): void
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
    public function getConnection(string $connectionName): Database
    {
        // Check if the connection has been initialized
        if (isset($this->connections[$connectionName])) {
            return $this->connections[$connectionName];
        }

        // Check if the connection can be initialized
        if (isset($this->connectionCallbacks[$connectionName])) {
            $callback = $this->connectionCallbacks[$connectionName];
            // Initialize the connection
            $this->connections[$connectionName] = $callback();

            return $this->connections[$connectionName];
        }

        throw new InvalidArgumentException("Connection `{$connectionName}` not found");
    }

    /**
     * Create a table factory for a specific database table.
     *
     * @param string $recordClass
     * @param ?string $connectionName
     * @return Table
     */
    public function create(string $recordClass, ?string $connectionName = null): Table
    {
        // Check if the information is cached
        if (!isset($this->cachedRecordClasses[$recordClass])) {
            // Fetch default properties (tableName and connectionName)
            try {
                $reflectionClass = new ReflectionClass($recordClass);
                $defaultProperties = $reflectionClass->getDefaultProperties();
            } catch (\ReflectionException $e) {
                throw new LogicException("Missing record class `${recordClass}`");
            }

            // Cache the properties
            $this->cachedRecordClasses[$recordClass] = [
                $defaultProperties["tableName"] ?? static::guessTableName($recordClass),
                $defaultProperties["connectionName"] ?? $connectionName ?? $this->defaultConnectionName,
            ];
        }

        // Fetch tableName and connectionName
        [$tableName, $connectionName] = $this->cachedRecordClasses[$recordClass];

        // Fetch the connection
        $db = $this->getConnection($connectionName);

        // Cache the table definition for future uses
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
    public static function guessTableName(string $className): string
    {
        // Split $className into namespaces and class name.
        $segments = explode("\\", $className);
        $shortName = array_pop($segments);

        // Split the class name into words
        $words = explode("_", (string) str($shortName)->snake());

        // Get the last word
        $lastWord = array_pop($words);

        // Find the plural of the last word
        $inflector = InflectorFactory::create()->build();
        $plural = $inflector->pluralize($lastWord);
        $words[] = $plural;

        // Build the underscored table name
        return mb_strtolower(implode("_", $words));
    }
}
