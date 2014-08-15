<?php

use pew\View;

class TestView extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $view = new View;
        $this->assertEquals(getcwd(), $view->folder());
    }

    public function testAddFolders()
    {
        $view = new View('tests/assets');
        $this->assertEquals('tests/assets', $view->folder());

        $view->folder('app/views');
        $this->assertEquals('app/views', $view->folder());
    }

    public function testTemplateResolution()
    {
        $view = new View('app/views');

        $this->assertFalse($view->exists('view1'));
        $this->assertFalse($view->exists('view8'));

        $view->extension('.tpl');
        $this->assertFalse($view->exists('view2'));

        $view->folder('tests/assets');
        $view->extension('.php');

        $this->assertTrue($view->exists('view1'));
        $this->assertFalse($view->exists('view8'));

        $view->extension('.tpl');
        $this->assertTrue($view->exists('view2'));
    }

    public function testRender()
    {
        $view = new View('tests/assets');
        $view['property'] = 'Property';
        $view->template('view1');
        $output = $view->render(['parameter' => 'Parameter']);

        $result = '<div>Parameter</div>
<div>Property</div>
<div>Property</div>
<div>Property</div>
';

        $this->assertEquals($result, $output);
    }

    public function testRenderLayout()
    {
        $view = new View('tests/assets');
        $view['property'] = 'Property';
        $view['title'] = 'Page Title';
        $view->template('view1');
        $view->layout('layout');
        $output = $view->render(['parameter' => 'Parameter']);

        $this->assertRegexp('/<!DOCTYPE/', $output);
        $this->assertRegexp('/<title>Page Title<\/title>/', $output);
        $this->assertRegexp('/<div>Parameter<\/div>/', $output);
        $this->assertRegexp('/<div>Property<\/div>/', $output);
    }
}
