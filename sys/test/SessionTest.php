<?php

require_once dirname(__FILE__) . '/../../session.class.php';

/**
 * Test class for Session.
 * Generated by PHPUnit on 2012-01-10 at 13:27:39.
 */
class SessionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Session
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        @ob_end_clean();
        $this->object = new Session(false);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        $this->object = null;
    }

    public function testOpen() {
        // this test case fails because session_open is called on bootstrapping
        //$this->assertFalse($this->object->is_open());
        $this->object->open();
        $this->assertTrue($this->object->is_open());
    }

    public function testClose() {
        $this->object->open();
        
        $this->assertTrue($this->object->is_open());
        $this->object->close();
        $this->assertFalse($this->object->is_open());
    }

    public function testWrite() {
        $this->object->open();
        
        $this->object->write('testWrite1', 1);
        $this->object->write('testWriteFalse', false);
        $this->object->write('testWriteString', 'String');
        
        $this->assertEquals(1, $_SESSION['test']['testWrite1']);
        $this->assertEquals(false, $_SESSION['test']['testWriteFalse']);
        $this->assertEquals('String', $_SESSION['test']['testWriteString']);
    }

    public function testRead() {
        $this->object->open();
        
        $_SESSION['test']['testRead2'] = 2;
        $_SESSION['test']['testReadTrue'] = true;
        $_SESSION['test']['testReadChars'] = 'Chars';
        
        $this->assertEquals(2, $this->object->read('testRead2'));
        $this->assertEquals(true, $this->object->read('testReadTrue'));
        $this->assertEquals('Chars', $this->object->read('testReadChars'));
        $this->assertNull($this->object->read('non-existant-key'));
        $this->assertEquals('alternate', $this->object->read('non-existant-key', 'alternate'));
    }

    /**
     * @todo Implement testExists().
     */
    public function testExists() {
        $this->object->open();
        
        $this->object->write('testExists0', 0);
        $this->object->write('testExistsFalse', false);
        $this->object->write('testExistsNull', null);
        $this->object->write('testExistsArray', array());
        
        $this->assertTrue($this->object->exists('testExists0'));
        $this->assertFalse($this->object->exists('testExists1'));
        $this->assertTrue($this->object->exists('testExistsFalse'));
        # changed isset() to array_key_exists()
        $this->assertTrue($this->object->exists('testExistsNull'));
        $this->assertTrue($this->object->exists('testExistsArray'));
    }

    /**
     * @todo Implement testDelete().
     */
    public function testDelete() {
        $this->object->open();
        $this->object->write('testDelete', 0);
        $this->object->delete('testDelete');
        $this->assertFalse($this->object->exists('testDelete'));
    }

    /**
     * @todo Implement testSet_flash().
     */
    public function testSet_flash() {
        $this->object->open();
        $this->object->set_flash('testSet_Flash');
        $this->assertEquals('testSet_Flash', $_SESSION['test']['flash']);
    }

    /**
     * @todo Implement testIs_flash().
     */
    public function testIs_flash() {
        $this->object->open();
        $_SESSION['test']['flash'] = 'testIs_Flash';
        $this->assertTrue($this->object->is_flash());
    }

    /**
     * @todo Implement testGet_flash().
     */
    public function testGet_flash() {
        $this->object->open();
        
        $_SESSION['test']['flash'] = "testGet_flash";
        
        $this->assertEquals("testGet_flash", $this->object->get_flash());
    }

    /**
     * @todo Implement test__set().
     */
    public function test__set() {
        $this->object->open();
        
        $this->object->test_set = 'test_set';
        $this->assertEquals('test_set', $_SESSION['test']['test_set']);
    }

    /**
     * @todo Implement test__get().
     */
    public function test__get() {
        $this->object->open();
        
        $_SESSION['test']['test_get'] = true;
        $this->assertEquals(true, $this->object->test_get);
    }

    /**
     * @todo Implement test__isset().
     */
    public function test__isset() {
        $this->object->open();
        
        $_SESSION['test']['test_isset'] = 1234;
        $this->assertTrue(isset($this->object->test_isset));
    }

    /**
     * @todo Implement test__unset().
     */
    public function test__unset() {
        $this->object->open();
        
        $_SESSION['test']['test_unset'] = 1234;
        unset($this->object->test_unset);
        $this->assertFalse(isset($this->object->test_isset));
    }

}

?>
