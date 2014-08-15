<?php

use pew\libs\Session;

class TestSession extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $a = [];
        $this->session = new Session($a);
    }

    public function testSessionIsOpen()
    {
        $this->assertTrue($this->session->is_open());
    }

    public function testReadingWritingAndDeletingValues()
    {
        $this->session->foo = 'bar';
        $this->assertEquals('bar', $this->session->foo);
        $this->assertEquals('bar', $this->session->get('foo'));

        $this->session->set('spam', 'bacon');
        $this->assertEquals('bacon', $this->session->spam);
        $this->assertEquals('bacon', $this->session->get('spam'));

        $this->session->delete('foo');
        unset($this->session->spam);

        $this->assertFalse($this->session->exists('foo'));
        $this->assertFalse(isSet($this->session->spam));
    }

    public function testGettingSessionData()
    {
        $this->assertEquals([], $this->session->get());

        $this->session->fooBar = 'Bacon and Eggs';

        $this->assertEquals(['fooBar' => 'Bacon and Eggs'], $this->session->get());
    }

    public function testReadingDefaultValues()
    {
        $this->assertEquals($this->session->get('nothing', 'default value'), 'default value');
        $this->assertNull($this->session->get('nothing'));
    }

    public function testCsrfTokens()
    {
        $this->assertFalse($this->session->check_token(md5(uniqid())));

        $token = $this->session->get_token();

        $this->assertTrue($this->session->check_token($token));
        $token[0] = '!';
        $this->assertFalse($this->session->check_token($token));
    }

    public function testFlashData()
    {
        $this->assertFalse($this->session->has_flash());
        $this->assertEquals([], $this->session->flash_data());
        $this->assertNull($this->session->flash('nothing', 'nothing here for now'));
        $this->assertNull($this->session->flash('nothing'));
    }

    public function testSessionClose()
    {
        $this->session->close();

        $this->assertFalse($this->session->is_open());
    }
}
