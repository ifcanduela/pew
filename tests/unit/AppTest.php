<?php

use pew\App;

class AppTest extends PHPUnit\Framework\TestCase
{
    public $appFolder;

    protected function setUp()
    {
        $this->appFolder = __DIR__ . "/../fixtures/";
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
        $app = new App($this->appFolder, 'bad-config');
    }

    public function testInitializeApp()
    {
        $app = new App($this->appFolder, 'test');

        $this->assertInstanceOf(App::class, $app);
    }

    public function testTemplateResponse()
    {
        $app = new App($this->appFolder, 'test');
        $app->set('path', '/test/template-response');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals("<h1>world</h1>", trim($response));
    }

    public function testJsonResponse()
    {
        $app = new App($this->appFolder, 'test');
        $app->set('path', '/test/json-response');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('{"hello":"world"}', $response);
    }

    public function testStringResponse()
    {
        $app = new App($this->appFolder, 'test');
        $app->set('path', '/test/string-response');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('response', $response);
    }

    public function testFalseResponse()
    {
        $app = new App($this->appFolder, 'test');
        $app->set('path', '/test/false-response');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('', $response);
    }

    public function testCallback()
    {
        $app = new App($this->appFolder, 'test');
        $app->set('path', '/callback');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('callback response', $response);
    }

    public function testNoAppConfigurationFile()
    {
        $app = new App($this->appFolder, 'none');

        $this->assertFalse($app->has("testKey"));
    }

    public function testConfigurationValues()
    {
        $app = new App($this->appFolder, 'test');

        $this->assertEquals(realpath($this->appFolder), $app->get("app_path"));

        $rootPath = getcwd();
        $wwwPath = $rootPath . DIRECTORY_SEPARATOR . "www";

        $this->assertEquals("testValue", $app->get("testKey"));
        $this->assertEquals("\\app\\", $app->get("app_namespace"), "App Namespace");
        $this->assertEquals(15 * 60, $app->get("cache_duration"), "Cache Duration");
        $this->assertEquals("config", $app->get("config_folder"), "Config Folder");
        $this->assertEquals(false, $app->get("debug"), "Debug Mode");
        $this->assertEquals("index", $app->get("default_action"), "Default Action");
        $this->assertEquals("dev", $app->get("env"), "Current Environment");
        $this->assertEquals(["\\", ".", "|"], $app->get("ignore_url_separator"), "URL Separators to Ignore");
        $this->assertEquals(["json", "html", "php"], $app->get("ignore_url_suffixes"), "URL Suffixes to Ignore");
        $this->assertEquals(300, $app->get("log_level"), "Log Level");
        $this->assertEquals($rootPath, $app->get("root_path"), "Root Path");
        $this->assertEquals($wwwPath, $app->get("www_path"), "Public Path");
    }

    public function testResolveController()
    {
        $r = new \pew\router\Route();
        $r->setHandler("test@index");

        $app = new App($this->appFolder, 'test');

        $controllerClass = $app->resolveController($r);
        $this->assertEquals("\\app\\controllers\\TestController", $controllerClass);
    }

    public function testResolveNamespacedController()
    {
        $r = new \pew\router\Route();
        $r->setHandler("admin@index");
        $r->setNamespace("admin");

        $app = new App($this->appFolder, 'test');

        $controllerClass = $app->resolveController($r);
        $this->assertEquals("\\app\\controllers\\admin\\AdminController", $controllerClass);

        $r = new \pew\router\Route();
        $r->setHandler("admin\\admin@index");

        $app = new App($this->appFolder, 'test');

        $controllerClass = $app->resolveController($r);
        $this->assertEquals("\\app\\controllers\\admin\\AdminController", $controllerClass);
    }

    public function testResolveControllerNotFound()
    {
        $r = new \pew\router\Route();
        $r->setHandler("admin\\admin@index");

        $app = new App($this->appFolder, 'test');

        $controllerClass = $app->resolveController($r);
        $this->assertEquals("\\app\\controllers\\admin\\AdminController", $controllerClass);
    }

    public function testMiddleware()
    {
        $app = new App($this->appFolder, 'test');

        $r = new \pew\router\Route();
        $r->setHandler("test@stringResponse");
        $r->before([
            \app\services\MiddlewareTest::class,
        ]);
        $r->after([
            \app\services\MiddlewareTest::class,
        ]);

        $app->set("route", $r);

        ob_start();
        $app->run();
        $html = ob_get_clean();
        $this->assertEquals('beforeresponse', $html);
    }

    public function testAfterMiddleware()
    {
        $app = new App($this->appFolder, 'test');

        $r = new \pew\router\Route();
        $r->setHandler("test@stringResponse");
        $r->after([
            \app\services\MiddlewareTest::class,
        ]);

        $app->set("route", $r);
        $action = (string) $app->resolveAction($r);
        $this->assertEquals("stringResponse", $action);

        // ob_start();
        // $app->run();
        // $html = ob_get_clean();
        // $this->assertEquals('noneresponse', $html);
    }
}
