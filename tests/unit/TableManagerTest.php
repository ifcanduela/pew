<?php

require_once dirname(__DIR__) . '/fixtures/models/Project.php';
require_once dirname(__DIR__) . '/fixtures/models/User.php';

use pew\model\TableManager;
use ifcanduela\db\Database;
use tests\fixtures\models\Project;
use tests\fixtures\models\User;

class TableManagerTest extends PHPUnit\Framework\TestCase
{
    public $db;

    protected function setUp()
    {
        $db = Database::sqlite(':memory:');

        $db->run('CREATE TABLE projects (id INTEGER PRIMARY KEY, name TEXT)');
        $db->run('INSERT INTO projects (name) VALUES ("Project Alpha")');
        $db->run('INSERT INTO projects (name) VALUES ("Project Beta")');
        $db->run('INSERT INTO projects (name) VALUES ("Project Gamma")');

        $db->run('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, project_id INTEGER)');
        $db->run('INSERT INTO users (username, project_id) VALUES ("User 1", 1)');
        $db->run('INSERT INTO users (username, project_id) VALUES ("User 2", 1)');
        $db->run('INSERT INTO users (username, project_id) VALUES ("User 3", 2)');
        $db->run('INSERT INTO users (username, project_id) VALUES ("User 4", 2)');
        $db->run('INSERT INTO users (username, project_id) VALUES ("User 5", 3)');

        $this->db = $db;

        TableManager::instance()->setConnection("default", $db);
    }

    public function testSetAndGetConnections()
    {
        $tm = new TableManager();

        $this->assertInstanceOf(TableManager::class, $tm);

        $conn = null;

        try {
            $conn = $tm->getConnection("nothing here");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("Connection `nothing here` not found", $e->getMessage());
            $this->assertNull($conn);
        }
    }

    public function testInstance()
    {
        $tm1 = TableManager::instance();
        $tm1->setConnection("one", Database::sqlite(':memory:'));
        $tm1->setConnection("two", Database::sqlite(':memory:'));

        $this->assertInstanceOf(TableManager::class, $tm1);

        $tm2 = TableManager::instance();

        $this->assertNotNull($tm2->getConnection("one"));
        $this->assertNotNull($tm2->getConnection("two"));

        $tm = new TableManager();

        try {
            $conn = $tm->getConnection("one");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("Connection `one` not found", $e->getMessage());
        }

        TableManager::instance($tm);

        $tm3 = TableManager::instance();

        try {
            $conn = $tm->getConnection("one");
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("Connection `one` not found", $e->getMessage());
        }
    }

    // __construct
    // instance
    // setDefaultConnection
    // setConnection
    // getConnection
    // create
    // guessTableName
}
