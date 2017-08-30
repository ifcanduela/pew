<?php

use pew\App;

require_once __DIR__ . '/../app/controllers/TestController.php';

class AppTest extends PHPUnit\Framework\TestCase
{
    protected function setUp()
    {

    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The app path does not exist: C:\Dropbox\sites\github\ifcanduela\pew\./non-existing-folder
     */
    public function testAppRequiresExistingFolder()
    {
        $app = new App('./non-existing-folder', 'test');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Configuration file C:\Dropbox\sites\github\ifcanduela\pew\tests\app/config/bad-config.php does not return an array
     */
    public function testAppRequiresExistingConfigFileReturnArray()
    {
        $app = new App(__DIR__ . '/../app/', 'bad-config');
    }

    public function testInitializeApp()
    {
        $app = new App(__DIR__ . '/../app/', 'test');

        $this->assertInstanceOf(App::class, $app);
    }

    public function testTemplateResponse()
    {
        $app = new App(__DIR__ . '/../app/', 'test');
        $app->container['path'] = '/test/template-response';
        $request = $app->get('request');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals("<h1>world</h1>", trim($response));
    }

    public function testJsonResponse()
    {
        $app = new App(__DIR__ . '/../app/', 'test');
        $app->container['path'] = '/test/json-response';
        $request = $app->get('request');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('{"hello":"world"}', $response);
    }

    public function testStringResponse()
    {
        $app = new App(__DIR__ . '/../app/', 'test');
        $app->container['path'] = '/test/string-response';
        $request = $app->get('request');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('response', $response);
    }

    public function testFalseResponse()
    {
        $app = new App(__DIR__ . '/../app/', 'test');
        $app->container['path'] = '/test/false-response';
        $request = $app->get('request');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('', $response);
    }

    public function testCallback()
    {
        $app = new App(__DIR__ . '/../app/', 'test');
        $app->container['path'] = '/callback';

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('callback response', $response);
    }
}
