<?php

define('USEAUTH', false);
define('USESESSION', false);
define('USEDB', false);

require_once dirname(__FILE__) . '/../functions.php';
require_once dirname(__FILE__) . '/../app.class.php';

/**
 * Test class for App.
 * Generated by PHPUnit on 2012-01-11 at 12:46:29.
 */
class AppTest extends PHPUnit_Framework_TestCase {

    /**
     * @var App
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->app = new App;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        $this->app = null;
    }

    /**
     * @todo Implement testRun().
     */
    public function testRun() {
        $this->markTestIncomplete("The App::run() method is untestable for the moment.");
        
        $_GET['url'] = 'controller/action/page:1/2/3';
        
        /*
         * App::run() requires class Pew, which in turn requires sys/config, 
         * which in turn requires app/config, which in turn...
         */
        $this->app->run();
    }

    public function testGet_segments_GET() {
        $segments = $this->app->get_segments('kittens/feed/page:1/2/catnip');
        $this->assertEquals('kittens/feed/page:1/2/catnip', $segments['uri']);
        $this->assertEquals('kittens', $segments['controller']);
        $this->assertEquals('feed', $segments['action']);
        $this->assertEquals('1', $segments['named']['page']);
        $this->assertEquals('page:1', $segments['segments'][2]);
        $this->assertEquals('2', $segments['passed'][1]);
        $this->assertEquals('catnip', $segments['passed'][2]);
        $this->assertEquals(2, $segments['id']);
    }
    
    public function testGet_segments_POST() {
        $_SERVER['REQUEST_METHOD'] = 'post';
        
        $_POST = $data = array(
            'dog_name' => 'Truffles',
            'street_address' => 'Main st.',
            'owner_id' => '1'
        );
        
        $segments = $this->app->get_segments('goggies/walk/1');
        $this->assertEquals($data, $segments['form']);
        $this->assertEquals($data['street_address'], $segments['form']['street_address']);
    }
    
    public function testFunc_class_name_to_file_name()
    {
        $this->assertEquals('pew_class_name', class_name_to_file_name('PewClassName'));
        $this->assertEquals('pewclass_name', class_name_to_file_name('PewclassName'));
        $this->assertEquals('p_e_w_class_name', class_name_to_file_name('PEWClassName'));
        $this->assertEquals('pewclassname', class_name_to_file_name('Pewclassname'));
        $this->assertEquals('pew_class_name', class_name_to_file_name('pewClassName'));
        $this->assertEquals('pewclassname', class_name_to_file_name('pewclassname'));
    }
    
    public function testFunc_file_name_to_class_name()
    {
        $this->assertEquals('PewClassName', file_name_to_class_name('pew_class_name'));
        $this->assertEquals('PewclassName', file_name_to_class_name('pewclass_name'));
        $this->assertEquals('PEWClassName', file_name_to_class_name('p_e_w_class_name'));
        $this->assertEquals('Pewclassname', file_name_to_class_name('pewclassname'));
        $this->assertEquals('PewClassName', file_name_to_class_name('pew_class_name'));
        $this->assertEquals('Pewclassname', file_name_to_class_name('pewclassname'));
    }
    
    public function testFunc_array_flatten()
    {
        $array1 = array(
          array(1, 2, 3, 4, 5),
          array( 'string', array(12, 34, 56 ,78), false),
          array('uno' => 'one', 'dos' => 'two', 'tres' => 'three')
        );
        
        $result1 = array(1, 2, 3, 4, 5, 'string', 12, 34, 56, 78, false, 'one', 'two', 'three');
        
        $this->assertEquals($result1, array_flatten($array1));
        
        $array2 = array(
          array(1, 2, 3, 4, 5),
          array(1, 2, 3, 4, 5),
          array(1, 2, 3, 4, 5),
        );
        
        $result2 = array(1, 2, 3, 4, 5, 1, 2, 3, 4, 5, 1, 2, 3, 4, 5);
        
        $this->assertEquals($result2, array_flatten($array2));
        
        $array3 = array(
          array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5),
          array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5),
          array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5),
        );
        
        $result3 = array(1, 2, 3, 4, 5, 1, 2, 3, 4, 5, 1, 2, 3, 4, 5);
        
        $this->assertEquals($result3, array_flatten($array3));
    }
    
    public function testFunc_array_reap()
    {
        $array = array(
            array(1, 2, 3, 4, 5),
            array('string1', 'string2', 'string3', 'str4' => 'string4'),
            array('uno' => 'one', 'dos' => 'two', 'tres' => 'three'),
            'PEW' => true
        );

        $result1 = array(1 => array('str4' => 'string4'), 2 => array('uno' => 'one', 'dos' => 'two', 'tres' => 'three'));
        $result2 = array(2 => array('uno' => 'one'));
        $result3 = array(array(1, 2, 3, 4, 5), array('string1', 'string2', 'string3'));
        $result4 = array('PEW' => true);
        $result5 = array(1 => array(2 => 'string3'));
        $this->assertEquals($result1, array_reap($array, '#:$'));
        $this->assertEquals($result2, array_reap($array, '#:uno'));
        $this->assertEquals($result3, array_reap($array, '#:#'));
        $this->assertEquals($result4, array_reap($array, '$'));
        $this->assertEquals($result5, array_reap($array, '1:2'));
    }
    
    public function testFunc_to_underscores()
    {
        $this->assertEquals('______', to_underscores('- _-- '));
    }
    
    public function testFunc_check_dir()
    {
        if (is_dir('testFunc_check_dir')) {
            rmdir('testFunc_check_dir');
        }
        $this->assertFalse(is_dir('testFunc_check_dir'));
        $this->assertTrue(check_dirs('testFunc_check_dir'));
        $this->assertTrue(is_dir('testFunc_check_dir'));
    }
    
    public function testFunc_clean_array_data()
    {
        $this->markTestIncomplete();
    }
    
    public function testFunc_config()
    {
        $this->markTestIncomplete();
    }
    
    public function testFunc_deref()
    {
        $this->markTestIncomplete();
    }
}

?>
