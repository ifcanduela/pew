<?php 

namespace testApp\models {
    use \pew\db\Table;

    class Projects extends Table {}

    class Users extends Table {
        public $table = 'users_table';
    }
    
    class GroupsModel extends Table {
        public function table_name() { return 'groups'; }
    }
}

namespace {
    use \pew\db\Table;
    use \pew\db\Database;

    class TableFactoryTest extends PHPUnit_Framework_TestCase
    {
        public function get_db()
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

            return $db;
        }

        public function testCreateFactory()
        {
            $db = $this->get_db();
            $f = new testApp\models\Projects($db);
            
            $this->assertEquals('projects', $f->table_name());
        }
    }
}
