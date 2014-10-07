<?php 

namespace testApp\models {
    class ProjectsModel extends \pew\db\Table {}
}

namespace {
    use \pew\db\TableFactory;
    use \pew\db\Database;

    class TableFactoryTest extends PHPUnit_Framework_TestCase
    {
        public function testCreateFactory()
        {
            $db = new Database([
                    'engine' => 'sqlite',
                    'file' => ':memory:'
                ]);
            $db->query("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
            $db->query("INSERT INTO users (name) VALUES (?)", ['User1']);
            $db->query("INSERT INTO users (name) VALUES (?)", ['User2']);
            $db->query("INSERT INTO users (name) VALUES (?)", ['User3']);

            $db->query("CREATE TABLE projects (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
            
            $f = new TableFactory($db);
            
            $users_table = $f->create('users');
            $this->assertEquals('pew\\db\\Table', get_class($users_table));
            
            $users = $users_table->find_all();
            $this->assertEquals(3, count($users));

            $f->register_namespace('\\testApp\\models', 'Model');
            $projects_model = $f->create('projects');
            $this->assertEquals('testApp\\models\\ProjectsModel', get_class($projects_model));
        }
    }
}
