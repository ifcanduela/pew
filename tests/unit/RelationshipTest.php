<?php

use ifcanduela\db\Database;
use pew\model\RecordCollection;
use pew\model\Table;
use pew\model\TableManager;
use pew\model\relation\BelongsTo;
use pew\model\relation\HasMany;
use pew\model\relation\HasOne;
use pew\model\relation\HasAndBelongsToMany;

class RelationshipTest extends \PHPUnit\Framework\TestCase
{
    public $db;

    public function makeDatabase()
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

        $db->run('CREATE TABLE profiles (id INTEGER PRIMARY KEY, full_name TEXT, user_id INTEGER)');
        $db->run('INSERT INTO profiles (full_name, user_id) VALUES ("User One", 1)');
        $db->run('INSERT INTO profiles (full_name, user_id) VALUES ("User Two", 2)');

        $db->run('CREATE TABLE tags (id INTEGER PRIMARY KEY, name TEXT)');
        $db->run('INSERT INTO tags (name) VALUES ("alpha")');
        $db->run('INSERT INTO tags (name) VALUES ("beta")');

        $db->run('CREATE TABLE projects_tags (id INTEGER PRIMARY KEY, project_id INTEGER, tag_id INTEGER)');
        $db->run('INSERT INTO projects_tags (project_id, tag_id) VALUES (1, 1)');
        $db->run('INSERT INTO projects_tags (project_id, tag_id) VALUES (1, 2)');
        $db->run('INSERT INTO projects_tags (project_id, tag_id) VALUES (2, 1)');
        $db->run('INSERT INTO projects_tags (project_id, tag_id) VALUES (2, 2)');

        TableManager::instance()->setConnection("test", $db);

        return $db;
    }

    protected function setUp(): void
    {
        $this->db = $this->makeDatabase();
    }

    public function testBelongsTo()
    {
        $projects = new Table("projects", $this->db);

        $b = new BelongsTo($projects->createSelect(), "project_id", "id", 1);
        $related = $b->fetch();
        $this->assertEquals("Project Alpha", $related["name"]);
    }

    public function testBelongsToBatch()
    {
        $projects = new Table("projects", $this->db);

        $b = new BelongsTo($projects->createSelect(), "project_id", "id", 1);
        $all = $b->find([1, 2, 3])->toArray();
        $this->assertEquals(3, count($all));
    }

    public function testHasMany()
    {
        $users = new Table("users", $this->db);

        $b = new HasMany($users->createSelect(), "project_id", "id", 1);
        $related = $b->fetch();

        $this->assertInstanceOf(RecordCollection::class, $related);
        $this->assertEquals("User 1", $related[0]["username"]);
    }

    public function testHasManyBatch()
    {
        $users = new Table("users", $this->db);

        $b = new HasMany($users->createSelect(), "project_id", "id", 1);
        $related = $b->find([1, 2, 3, 4]);

        $this->assertEquals(4, $related->count());
        $this->assertInstanceOf(RecordCollection::class, $related);
        $this->assertEquals("User 1", $related[1][0]["username"]);
    }

    public function testHasAndBelongsToMany()
    {
        $tags = new Table("projects", $this->db);

        $b = (new HasAndBelongsToMany($tags->createSelect(), "id", "tag_id",1))
            ->through("projects_tags", [
                "projects_tags.project_id" => "projects.id"]);

        $related = $b->fetch();

        $this->assertEquals("Project Alpha",  $related[0]["name"]);
    }

    public function testHasAndBelongsToManyBatch()
    {
        $tags = new Table("projects", $this->db);

        $b = (new HasAndBelongsToMany($tags->createSelect(), "id", "tag_id",1))
            ->through("projects_tags", [
                "projects_tags.project_id" => "projects.id"]);

        $related = $b->find([1, 2]);

        $this->assertEquals(2, count($related));
        $this->assertTrue(isset($related[1]));
        $this->assertTrue(isset($related[2]));
    }

    public function testHasOne()
    {
        $profiles = new Table("profiles", $this->db);

        $b = new HasOne($profiles->createSelect(), "user_id", "id", 1);
        $related = $b->fetch();

        $this->assertEquals("User One", $related["full_name"]);
    }

    public function testHasOnBatch()
    {
        $profiles = new Table("profiles", $this->db);

        $b = new HasOne($profiles->createSelect(), "user_id", "id", 1);
        $related = $b->find([2, 3]);

        $this->assertEquals(1, $related->count());
        $this->assertEquals("User Two", $related[2]["full_name"]);
    }
}
