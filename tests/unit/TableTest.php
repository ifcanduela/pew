<?php

use ifcanduela\db\Database;
use pew\model\TableManager;
use pew\model\Table;
use app\models\Project;
use app\models\User;

class TableTest extends \PHPUnit\Framework\TestCase
{
    public $db;

    public function makeDatabase()
    {
        $db = Database::sqlite(':memory:');

        $db->run('CREATE TABLE projects (id INTEGER PRIMARY KEY, name TEXT)');
        $db->run('INSERT INTO projects (name) VALUES ("Project Alpha")');
        $db->run('INSERT INTO projects (name) VALUES ("Project Beta")');
        $db->run('INSERT INTO projects (name) VALUES ("Project Gamma")');

        $db->run('CREATE TABLE users (
            id INTEGER PRIMARY KEY,
            username TEXT NOT NULL,
            project_id INTEGER NOT NULL,
            created_at INTEGER NULL,
            updated_at INTEGER NULL
        )');
        $db->run('INSERT INTO users (username, project_id) VALUES ("User 1", 1)');
        $db->run('INSERT INTO users (username, project_id) VALUES ("User 2", 1)');
        $db->run('INSERT INTO users (username, project_id) VALUES ("User 3", 2)');
        $db->run('INSERT INTO users (username, project_id) VALUES ("User 4", 2)');
        $db->run('INSERT INTO users (username, project_id) VALUES ("User 5", 3)');

        TableManager::instance()->setConnection("test", $db);

        return $db;
    }

    protected function setUp(): void
    {
        $this->db = $this->makeDatabase();
    }

    public function testTableInstantiation()
    {
        $t = new Table("users", $this->db);
        $count = $t->count();

        $this->assertEquals(5, $count);
    }

    public function testCreateSelectQuery()
    {
        $t = new Table("users", $this->db);

        $t->createSelect()->andWhere(["id" => [">", 1]])
            ->andWhere(["id" => ["<", 5]]);
        $this->assertInstanceOf(Table::class, $t);
        $result = $t->all();

        $this->assertEquals(3, count($result));
    }

    public function testFailInit()
    {
        try {
            $t = new Table("dogs", $this->db);
        } catch (\pew\model\exception\TableNotFoundException $e) {
            $this->assertInstanceOf('\pew\model\exception\TableNotFoundException', $e);
            $this->assertEquals('Table `dogs` not found', $e->getMessage());
        }
    }

    public function testTableName()
    {
        $t = new Table("users", $this->db);

        $this->assertEquals("users", $t->tableName());

        $t->tableName("projects");
        $this->assertEquals("projects", $t->tableName());

        $t->tableName("");
        $this->assertEquals("projects", $t->tableName());
    }

    public function testPrimaryKey()
    {
        $users = new Table("users", $this->db);
        $this->assertEquals("id", $users->primaryKey());

        $projects = new Table("projects", $this->db);
        $this->assertEquals("id", $projects->primaryKey());
    }

    public function testColumnNames()
    {
        $users = new Table("users", $this->db);
        $expected = [
            "id" => null,
            "username" => null,
            "project_id" => null,
            "created_at" => null,
            "updated_at" => null,
        ];
        $this->assertEquals($expected, $users->columnNames());
        $this->assertEquals(array_keys($expected), $users->columnNames(false));

        $projects = new Table("projects", $this->db);
        $expected = ["id" => null, "name" => null];
        $this->assertEquals($expected, $projects->columnNames());
        $this->assertEquals(array_keys($expected), $projects->columnNames(false));
    }

    public function testHasColumn()
    {
        $users = new Table("users", $this->db);

        $this->assertTrue($users->hasColumn("project_id"));
        $this->assertFalse($users->hasColumn("project"));
    }

    public function testRecordClass()
    {
        $users = new Table("users", $this->db);
        $this->assertNull($users->recordClass());

        $users->recordClass(User::class);
        $this->assertEquals(User::class, $users->recordClass());

        $projects = new Table("projects", $this->db, \app\models\Project::class);
        $this->assertEquals(\app\models\Project::class, $projects->recordClass());
    }

    public function testSelectOperations()
    {
        $users = new Table("users", $this->db);

        $user_1 = $users->createSelect()
            ->where(["id" => 1])
            ->one();

        $this->assertEquals("User 1", $user_1["username"]);
    }

    public function testUpdateRecord()
    {
        $users = new Table("users", $this->db, User::class);
        $user = $users->createSelect()->where(["id" => 1])->one();
        $user->username = "Updated user";
        $user->save();

        $user = $users->createSelect()->where(["id" => 1])->one();
        $this->assertEquals("Updated user", $user->username);
    }

    public function testUpdateOperations()
    {
        $users = new Table("users", $this->db);

        $updatedCount = $users->createUpdate()
            ->where(["id" => [">", 1]])
            ->set(["project_id" => 99])
            ->run();

        $this->assertEquals(4, $updatedCount);
    }

    public function testInsertOperations()
    {
        $users = new Table("users", $this->db);

        $insertCount = $users->createInsert()
            ->values(["username" => "Test User 1", "project_id" => "99"])
            ->run();

        $this->assertEquals(1, $insertCount);
        $this->assertEquals(6, $users->lastInsertId());
    }

    public function testQuery()
    {
        $t = new Table("users", $this->db);

        $result = $t->query("SELECT * FROM users WHERE id >= :id", ["id" => 3]);
        $this->assertEquals(3, count($result));

        $result = $t->query("UPDATE users SET project_id = RANDOM()");
        $this->assertEquals(5, $result);

        try {
            $result = $t->query("SELECT _nothing_ FROM users", []);
            $this->assertEquals(3, count($result));
        } catch (\Exception $e) {
            $this->assertInstanceOf(\PdoException::class, $e);
        }
    }
/*
    public function testCreateDelete()
    {

    }

    public function testSave()
    {

    }

    public function testDelete()
    {

    }

    public function testLastInsertId()
    {

    }

    public function testTransactions()
    {
        // Table::begin();
        // Table::commit();
        // Table::rollback();
    }

    public function testRun()
    {

    }

    public function test__call()
    {

    }

    public function testWith()
    {

    }
*/
}
