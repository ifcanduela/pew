<?php

use pew\App;

require_once __DIR__ . '/../fixtures/controllers/TestController.php';

class AppTest extends PHPUnit\Framework\TestCase
{
    protected function setUp()
    {

    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The app path does not exist:
     */
    public function testAppRequiresExistingFolder()
    {
        $app = new App('./non-existing-folder', 'test');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage must return an array
     */
    public function testAppRequiresExistingConfigFileReturnArray()
    {
        $app = new App(__DIR__ . '/../fixtures/', 'bad-config');
    }

    public function testInitializeApp()
    {
        $app = new App(__DIR__ . '/../fixtures/', 'test');

        $this->assertInstanceOf(App::class, $app);
    }

    public function testTemplateResponse()
    {
        $app = new App(__DIR__ . '/../fixtures/', 'test');
        $app->set('path', '/test/template-response');
        $app->set('app_namespace', '\\tests\\fixtures\\');
        $request = $app->get('request');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals("<h1>world</h1>", trim($response));
    }

    public function testJsonResponse()
    {
        $app = new App(__DIR__ . '/../fixtures/', 'test');
        $app->set('path', '/test/json-response');
        $app->set('app_namespace', '\\tests\\fixtures\\');
        $request = $app->get('request');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('{"hello":"world"}', $response);
    }

    public function testStringResponse()
    {
        $app = new App(__DIR__ . '/../fixtures/', 'test');
        $app->set('path', '/test/string-response');
        $app->set('app_namespace', '\\tests\\fixtures\\');
        $request = $app->get('request');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('response', $response);
    }

    public function testFalseResponse()
    {
        $app = new App(__DIR__ . '/../fixtures/', 'test');
        $app->set('path', '/test/false-response');
        $app->set('app_namespace', '\\tests\\fixtures\\');
        $request = $app->get('request');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('', $response);
    }

    public function testCallback()
    {
        $app = new App(__DIR__ . '/../fixtures/', 'test');
        $app->set('path', '/callback');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('callback response', $response);
    }

    public function testConfigurationValues()
    {
        $app = new App(__DIR__ . '/../fixtures/', 'test');

        $this->assertEquals(realpath(__DIR__ . '/../fixtures/'), $app->get("app_path"));

        $wwwPath = realpath(dirname(dirname(__DIR__)));
        $rootPath = dirname($wwwPath);

        $this->assertEquals("\\app\\", $app->get("app_namespace"));
        $this->assertEquals(15 * 60, $app->get("cache_duration"));
        $this->assertEquals("config", $app->get("config_folder"));
        $this->assertEquals(false, $app->get("debug"));
        $this->assertEquals("index", $app->get("default_action"));
        $this->assertEquals("dev", $app->get("env"));
        $this->assertEquals(["\\", ".", "|"], $app->get("ignore_url_separator"));
        $this->assertEquals(["json", "html", "php"], $app->get("ignore_url_suffixes"));
        $this->assertEquals(300, $app->get("log_level"));
        $this->assertEquals($rootPath, $app->get("root_path"));
        $this->assertEquals($wwwPath, $app->get("www_path"));
    }
}
