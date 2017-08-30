<?php

use pew\View;

class ViewTest extends PHPUnit\Framework\TestCase
{
    protected function setUp()
    {

    }

    public function testBasics()
    {
        $v = new View(__DIR__ . '/../assets/views');

        $this->assertEmpty($v->title());

        $this->assertTrue($v->exists('view1'));
        $this->assertFalse($v->exists('nope'));

        $v->template('view1');
        $result = $v->render(['parameter' => 'PARAMETER', 'property' => 'PROPERTY']);

        $this->assertEquals('<div>PARAMETER</div>
<div>PROPERTY</div>
<div>PROPERTY</div>
', $result);

        $v->layout('layout');
        $v->title('test');
        $result = $v->render(['parameter' => 'PARAMETER', 'property' => 'PROPERTY']);

        $this->assertEquals('<title>test</title>
<div>PARAMETER</div>
<div>PROPERTY</div>
<div>PROPERTY</div>
', $result);
    }
}
