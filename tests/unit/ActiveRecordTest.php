<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use pew\model\Table;
use pew\model\TableManager;
use ifcanduela\db\Database;
use app\models\ParentModel;
use app\models\Project;
use app\models\User;
use app\models\Profile;
use app\models\Tag;
use app\models\ComplexTableName;

class ActiveRecordTest extends PHPUnit\Framework\TestCase
{
    public Database $db;

    protected function getLogger(): Logger
    {
        $logger = new Logger('SQL Logger');
        $logfile = __DIR__ . '/../app/logs/app.log';
        $logger->pushHandler(new StreamHandler($logfile, Logger::INFO));

        return $logger;
    }

    protected function setUp(): void
    {
        $db = Database::sqlite(':memory:');
        $db->setLogger($this->getLogger());

        $db->run('CREATE TABLE projects (
            id INTEGER PRIMARY KEY,
            name TEXT
        )');
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
        $db->run('INSERT INTO users (username, project_id, created_at, updated_at) VALUES ("User 1", 1, 10000000, 10000000)');
        $db->run('INSERT INTO users (username, project_id, created_at, updated_at) VALUES ("User 2", 1, 10000002, 10000002)');
        $db->run('INSERT INTO users (username, project_id, created_at, updated_at) VALUES ("User 3", 2, NULL, NULL)');
        $db->run('INSERT INTO users (username, project_id, created_at, updated_at) VALUES ("User 4", 2, NULL, NULL)');
        $db->run('INSERT INTO users (username, project_id, created_at, updated_at) VALUES ("User 5", 3, NULL, NULL)');

        $db->run('CREATE TABLE tags (id INTEGER PRIMARY KEY, name TEXT)');
        $db->run('INSERT INTO tags (name) VALUES ("Alpha")');
        $db->run('INSERT INTO tags (name) VALUES ("Beta")');
        $db->run('INSERT INTO tags (name) VALUES ("Gamma")');

        $db->run('CREATE TABLE projects_tags (id INTEGER PRIMARY KEY, project_id INTEGER NOT NULL, tag_id INTEGER NOT NULL)');
        $db->run('INSERT INTO projects_tags (project_id, tag_id) VALUES (1, 1)');
        $db->run('INSERT INTO projects_tags (project_id, tag_id) VALUES (1, 2)');
        $db->run('INSERT INTO projects_tags (project_id, tag_id) VALUES (2, 3)');

        $db->run('CREATE TABLE profiles (id INTEGER PRIMARY KEY, full_name TEXT NOT NULL,  user_id INTEGER NOT NULL)');
        $db->run('INSERT INTO profiles (full_name, user_id) VALUES ("One Alpha", 1)');
        $db->run('INSERT INTO profiles (full_name, user_id) VALUES ("Two Beta", 3)');
        $db->run('INSERT INTO profiles (full_name, user_id) VALUES ("Three Gamma", 4)');

        $db->run('CREATE TABLE complex_table_names (id INTEGER PRIMARY KEY)');

        $db->run('CREATE TABLE parent_models (id INTEGER PRIMARY KEY)');
        $db->run('CREATE TABLE child_models (id INTEGER PRIMARY KEY, parent_model_id INTEGER)');

        $this->db = $db;

        TableManager::instance()->setConnection("test", $db);
    }

    public function testNewRecord(): void
    {
        $model = new Project();

        $this->assertInstanceOf(Project::class, $model);
        // non-initialised table columns
        $this->assertEquals([
            'id' => null,
            'name' => null,
            'extraField' => 'extraValue',
        ], $model->attributes());

        $this->assertNull($model->id);
        $this->assertNull($model->name);

        // assign a value to a database field
        $model->name = 'Project Zeta';
        // save the record
        $this->assertTrue($model->save());
        // the primary key is now populated
        $this->assertNotNull($model->id);

        $loaded = Project::find()->where(["name" => "Project Zeta"])->one();
        $this->assertInstanceOf(Project::class, $loaded);

        $fields = [];

        foreach ($model as $field => $value) {
            $fields[] = $field;
        }

        // check the iterated fields
        $this->assertEquals(['extraField', 'id', 'name'], $fields);
        // check the fields serialized into JSON
        $serialized = json_decode(json_encode($model), true);
        $this->assertEquals("Project Zeta", $serialized["name"]);
        $this->assertEquals("extraValue", $serialized["extraField"]);
        // check the fields excluded from JSON serialization
        $model->doNotSerialize = ['name', 'id'];
        $this->assertEquals(["extraField" => "extraValue"], json_decode(json_encode($model), true));

        // delete the record we created
        $model->delete();
        $loaded = Project::find()->where(["name" => "Project Zeta"])->one();
        $this->assertNull($loaded);
    }

    public function testCreateRecordWithAttributes(): void
    {
        $p = new Project([
            "name" => "Project Eta",
            "extraField" => "someValue",
        ]);

        $this->assertEquals("Project Eta", $p->name);
        $this->assertEquals("someValue", $p->extraField);
    }

    public function testGuessRecordTableName(): void
    {
        $t = new Tag();
        $this->assertEquals("tags", $t->tableName);

        $ctn = new ComplexTableName();
        $this->assertEquals("complex_table_names", $ctn->tableName);
    }

    public function testRecordFromQuery(): void
    {
        // retrieve a list of records based on an SQL query
        $projects = Project::fromQuery('SELECT * FROM projects WHERE id > 1');

        $this->assertInstanceOf(\pew\model\Collection::class, $projects);
        $this->assertInstanceOf(Project::class, $projects[0]);
    }

    public function testOverloadFindStaticMethod(): void
    {
        $finder = User::find();

        $this->assertInstanceOf(Table::class, $finder);
        $this->assertEquals(2, count($finder->all()));
    }

    public function testRecordFromArray(): void
    {
        // create a new record using an array
        $model = Project::fromArray([
                'id' => 99,
                'name' => 'test project',
            ]);

        // record fields are populated
        $this->assertEquals(99, $model->id);
        $this->assertEquals('test project', $model->name);
    }

    public function testRecordToArray(): void
    {
        $model = Project::fromArray([
                "id" => 99,
                "name" => "test project",
            ]);

        $model->extraField = "isIncluded";

        $expected = [
                "id" => 99,
                "name" => "test project",
                "extraField" => "isIncluded",
            ];

        $this->assertEquals($expected, $model->toArray());
    }

    public function testJsonSerialization(): void
    {
        $model = Project::fromArray([
                "id" => 99,
                "name" => "test project",
            ]);

        $result = json_encode($model, JSON_PRETTY_PRINT);

        $this->assertStringContainsString('"extraField": "extraValue"', $result);
        $this->assertStringContainsString('"id": 99,', $result);
        $this->assertStringContainsString('"name": "test project"', $result);

        $model->doNotSerialize = ["extraField"];
        $result = json_encode($model, JSON_PRETTY_PRINT);

        $this->assertStringNotContainsString('"extraField": "extraValue"', $result);
        $this->assertStringContainsString('"id": 99,', $result);
        $this->assertStringContainsString('"name": "test project"', $result);

        $model->serialize = ["users"];
        $result = json_encode($model, JSON_PRETTY_PRINT);

        $this->assertStringNotContainsString('"extraField": "extraValue"', $result);
        $this->assertStringContainsString('"id": 99,', $result);
        $this->assertStringContainsString('"name": "test project"', $result);
        $this->assertStringContainsString('"users": []', $result);
    }

    public function testSetterAndGetterMethods(): void
    {
        $p = new Project();
        $p->privateField = "setter test";

        $this->assertEquals("setter test", $p->privateField);

        $p->name = "Project name";

        $this->assertEquals("Project name", $p->name);

        try {
            $p->missingProperty = true;
        } catch (\Exception $e) {
            $this->assertEquals("Record attribute `missingProperty` does not exist in `app\\models\\Project`", $e->getMessage());
        }

        try {
            $value = $p->missingProperty;
        } catch (\Exception $e) {
            $this->assertEquals("Record attribute `missingProperty` does not exist in `app\\models\\Project`", $e->getMessage());
        }

        $this->assertTrue(isset($p->name));
        $this->assertTrue(isset($p->extraField));
        $this->assertTrue(isset($p->privateField));
        $this->assertFalse(isset($p->missingProperty));

        unset($p->name);
        $this->assertFalse(isset($p->name));
    }

    public function testStaticMethods(): void
    {
        $project1 = Project::findOne(1);
        $projectAlpha = Project::findOneByName("Project Alpha");
        $projectAlphas = Project::findAllByName("Project Alpha");

        $this->assertTrue($project1->name === $projectAlpha->name);
        $this->assertTrue($project1->name === $projectAlphas->first()->name);

        try {
            Project::missingMethod();
        } catch (\Exception $e) {
            $this->assertEquals("Method `app\\models\\Project::missingMethod()` does not exist", $e->getMessage());
        }
    }

    public function testExplicitRelationship(): void
    {
        $model = Project::findOne(1);

        $this->assertEquals(2, count($model->explicitUsers));
    }

    public function testHasManyRelation(): void
    {
        $model = Project::findOne(1);

        $this->assertEquals(2, count($model->users));
    }

    public function testBelongsToRelation(): void
    {
        $model = User::findOne(1)->project;

        $this->assertInstanceOf(Project::class, $model);
    }

    public function testHasOneRelation(): void
    {
        $u1 = User::findOne(1);
        $u2 = User::findOne(2);

        $this->assertInstanceOf(Profile::class, $u1->profile);
        $this->assertNull($u2->profile);
    }

    public function testHasAndBelongsToManyRelation(): void
    {
        $p1 = Project::findOne(1);
        $p2 = Project::findOne(2);

        $p1tags = $p1->tags;
        $p2tags = $p2->tags;

        $this->assertInstanceOf(\pew\model\Collection::class, $p1tags);
        $this->assertEquals(2, $p1tags->count());

        $this->assertInstanceOf(\pew\model\Collection::class, $p2tags);
        $this->assertEquals(1, $p2tags->count());
    }

    public function testLoadRelationships(): void
    {
        $projects = Project::find()->with("users", "tags")->all();
        $this->assertEquals(4, $projects->count());

        $p1 = $projects->first();
        $this->assertEquals("Project Alpha", $p1->name);
        $this->assertEquals(2, $p1->users->count());
        $this->assertEquals(2, $p1->tags->count());

        $p2 = $projects[1];
        $this->assertEquals("Project Beta", $p2->name);
        $this->assertEquals(1, $p2->tags->count());
        // must be 0 because User has a constraint in created_at
        $this->assertEquals(0, $p2->users->count());
    }

    public function testDeleteRecord(): void
    {
        $model = new Project();
        // assign a value to a database field
        $model->name = 'Project Zeta';
        // save the record
        $model->save();

        // delete record #4, inserted in testNewRecord()
        $loaded = Project::findOne($model->id);
        $this->assertInstanceOf(Project::class, $loaded);
        $loaded->delete();

        $loaded = Project::findOne($model->id);
        $this->assertNull($loaded);
    }

    public function testRecordIsNew(): void
    {
        $new = new Project();
        $this->assertTrue($new->isNew);

        $old = Project::find()->one();
        $this->assertFalse($old->isNew);

        $new->save();
        $this->assertFalse($new->isNew);
    }

    public function testUpdateQueries(): void
    {
        User::update()
            ->set(["created_at" => 15000000])
            ->where(["id" => ["IN", [1, 2]]])
            ->run();

        $user = User::findOne(1);
        $this->assertEquals(15000000, $user->created_at);

        User::updateAll([
            "created_at" => 16000000,
        ], ["id" => ["IN", [1, 2]]]);

        $user = User::findOne(2);
        $this->assertEquals(16000000, $user->created_at);
    }

    public function testDeleteAll(): void
    {
        // delete one record in the table
        Project::deleteAll(['id' => ['>', 2]]);
        $this->assertEquals(2, Project::find()->count());

        // delete all records in the table
        Project::deleteAll();
        $this->assertEquals(0, Project::find()->count());
    }

    public function testChildModelWithSameGetter(): void
    {
        $p = new ParentModel();

        $this->assertEquals("ChildModel::\$value", $p->testValue);
    }
}
