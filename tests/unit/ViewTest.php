<?php

use pew\View;

function rn($text) {
    return str_replace("\r", "", $text);
}

class ViewTest extends PHPUnit\Framework\TestCase
{
    protected function setUp()
    {

    }

    public function testVoidConstructor()
    {
        $v = new View();

        $this->assertEquals(getcwd(), $v->folder());
    }

    public function testBasics()
    {
        $v = new View(__DIR__ . '/../fixtures/views');

        $this->assertEmpty($v->title());

        $this->assertTrue($v->exists('view1'));
        $this->assertFalse($v->exists('nope'));

        $result = $v->render('view1', ['parameter' => 'PARAMETER', 'property' => 'PROPERTY']);

        $this->assertEquals(rn('<div>PARAMETER</div>
<div>PROPERTY</div>
<div>PROPERTY</div>
'), rn($result));

        $v->layout('layout');
        $v->title('test');
        $result = $v->render('view1', ['parameter' => 'PARAMETER', 'property' => 'PROPERTY']);

        $this->assertEquals(rn('<title>test</title>
<div>PARAMETER</div>
<div>PROPERTY</div>
<div>PROPERTY</div>
'), rn($result));
    }

    public function testFluentInterface()
    {
        $v = new View(__DIR__ . "/../fixtures/views");

        $result = $v
            ->set('parameter', 'PARAMETER')
            ->set('property', 'PROPERTY')
            ->title("test title")
            ->template("view1")
            ->layout("layout")
            ->render();

        $this->assertEquals(rn('<title>test title</title>
<div>PARAMETER</div>
<div>PROPERTY</div>
<div>PROPERTY</div>
'), rn($result));
    }
}
