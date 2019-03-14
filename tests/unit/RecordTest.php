<?php

use pew\model\TableManager;
use ifcanduela\db\Database;
use app\models\Project;
use app\models\User;

class RecordTest extends PHPUnit\Framework\TestCase
{
    public $db;

    protected function getLogger()
    {
        $logger = new \Monolog\Logger('SQL Logger');
        $logfile = __DIR__ . '/../app/logs/app.log';
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($logfile, \Monolog\Logger::INFO));

        return $logger;
    }

    protected function setUp()
    {
        $db = Database::sqlite(':memory:');
        $db->setLogger($this->getLogger());

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

        TableManager::instance()->setConnection("test", $db);
    }

    public function testNewRecord()
    {
        $model = new Project;

        $this->assertInstanceOf(Project::class, $model);
        # non-initialised table columns
        $this->assertEquals([
                'id' => null,
                'name' => null,
                'extraField' => 'extraValue',
            ], $model->attributes());

        $this->assertNull($model->id);
        $this->assertNull($model->name);

        # assign a value to a database field
        $model->name = 'Project Zeta';
        # save the record
        $this->assertTrue($model->save());
        # the primary key is now populated
        $this->assertNotNull($model->id);

        $fields = [];

        foreach ($model as $field => $value) {
            $fields[] = $field;
        }

        # check the iterated fields
        $this->assertEquals(['id', 'name'], $fields);
        # check the fields serialized into JSON
        $this->assertEquals(["id" => "4", "name" => "Project Zeta", "extraField" => "extraValue"], json_decode(json_encode($model), true));
        # check the fields excluded from JSON serialization
        $model->doNotSerialize = ['name'];
        $this->assertEquals(["id" => "4", "extraField" => "extraValue"], json_decode(json_encode($model), true));
    }

    public function testRecordFromQuery()
    {
        # retrieve a list of records based on an SQL query
        $projects = Project::fromQuery('SELECT * FROM projects WHERE id > 1');

        $this->assertInstanceOf(\pew\model\Collection::class, $projects);
        $this->assertInstanceOf(Project::class, $projects[0]);
    }

    public function testRecordFromArray()
    {
        # create a new record using an array
        $model = Project::fromArray([
                'id' => 99,
                'name' => 'test project',
            ]);

        # record fields are populated
        $this->assertEquals(99, $model->id);
        $this->assertEquals('test project', $model->name);
    }

    public function testExplicitRelationship()
    {
        $model = Project::findOne(1);

        $this->assertEquals(2, count($model->explicitUsers));
    }

    public function testHasManyRelation()
    {
        $model = Project::findOne(1);

        $this->assertEquals(2, count($model->users));
    }

    public function testBelongsToRelation()
    {
        $model = User::findOne(1)->project;

        $this->assertInstanceOf(Project::class, $model);
    }

    public function testDeleteRecord()
    {
        # delete record #4, inserted in testNewRecord()
        $model = Project::findOne(4)->delete();
        $this->assertEquals(3, Project::find()->count());
    }

    public function testDeleteAll()
    {
        # delete one record in the table
        Project::deleteAll(['id' => ['>', 2]]);
        $this->assertEquals(2, Project::find()->count());

        # delete all records in the table
        Project::deleteAll();
        $this->assertEquals(0, Project::find()->count());
    }
}
