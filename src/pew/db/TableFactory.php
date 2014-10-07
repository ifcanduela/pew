<?php

namespace pew\db;

use pew\libs\Str;
use pew\db\Database;
use pew\db\Table;
use pew\db\TableInterface;

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
	protected $namespaces = [];

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
	 * @param string  $table_name
	 * @param boolean $fallback If false an Exception will be raised if the Table cannot be instantiated
	 * @return TableInterface
	 */
	public function create($table_name, $fallback = true)
	{
		$class_base_name = Str::camel_case($table_name);

		foreach ($this->namespaces as $ns) {
			$class_name = $ns[0] . $class_base_name . $ns[1];

			if (class_exists($class_name)) {
				if (!is_a($class_name, '\\pew\\db\\TableInterface', true)) {
					throw new InvalidTableClassException("Table class {$class_name} must implement TableInterface");
				}

				$obj = new $class_name($this->db, $table_name);

			 	return $obj;
			}
		}

		if ($fallback === false) {
			throw new TableClassNotFoundException("Class for table {$table_name} could not be found");
		}

		return new Table($this->db, $table_name);
	}

	/**
	 * Registers a new namespace for model search.
	 * 
	 * @param string $namespace
	 * @param string $class_suffix
	 */
	public function register_namespace($namespace, $class_suffix)
	{
		$this->namespaces[] = [rtrim($namespace, '\\') . '\\', $class_suffix];
	}
}
