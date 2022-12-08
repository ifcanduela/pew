<?php

use ifcanduela\router\Route;
use ifcanduela\router\Router;
use Monolog\Level;
use pew\App;
use pew\request\Request;

class AppTest extends PHPUnit\Framework\TestCase
{
    public $appFolder;

    protected function setUp(): void
    {
        $this->appFolder = __DIR__ . "/../fixtures/";
    }

    public function testAppRequiresExistingFolder()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The app path does not exist:");
        $app = new App('./non-existing-folder', "test");
    }

    public function testAppRequiresExistingConfigFileReturnArray()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("must return an array");
        $app = new App($this->appFolder, 'bad-config');
    }

    public function testInitializeApp()
    {
        $app = new App($this->appFolder, "test");

        $this->assertInstanceOf(App::class, $app);
    }

    public function testTemplateResponse()
    {
        $app = new App($this->appFolder, "test");
        $app->set(Request::class, Request::create('/test/template-response'));

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals("<h1>world</h1>", trim($response));
        $this->assertEquals("<h1>world</h1>", trim($response));
    }

    public function testJsonResponse()
    {
        $app = new App($this->appFolder, "test");
        $app->set(Request::class,  Request::create('/test/json-response'));

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('{"hello":"world"}', $response);
    }

    public function testStringResponse()
    {
        $app = new App($this->appFolder, "test");
        $app->set(request::class, Request::create('/test/string-response'));

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('response', $response);
    }

    public function testFalseResponse()
    {
        $app = new App($this->appFolder, "test");
        $app->set(Request::class, Request::create('/test/false-response'));

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('', $response);
    }

    public function testCallback()
    {
        $app = new App($this->appFolder, "test");
        $app->set(Request::class, Request::create('/callback'));

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
        $app = new App($this->appFolder, "test");

        $this->assertEquals(realpath($this->appFolder), $app->get("app_path"));

        $rootPath = getcwd();
        $wwwPath = $rootPath . DIRECTORY_SEPARATOR . "www";

        $this->assertEquals("testValue", $app->get("testKey"));
        $this->assertEquals("\\app\\", $app->get("app_namespace"), "App Namespace");
        $this->assertEquals(15 * 60, $app->get("cache_duration"), "Cache Duration");
        $this->assertEquals("config", $app->get("config_folder"), "Config Folder");
        $this->assertEquals(false, $app->get("debug"), "Debug Mode");
        $this->assertEquals("index", $app->get("default_action"), "Default Action");
        $this->assertEquals("test", $app->get("env"), "Current Environment");
        $this->assertEquals(["\\", ".", "|"], $app->get("ignore_url_separator"), "URL Separators to Ignore");
        $this->assertEquals(["json", "html", "php"], $app->get("ignore_url_suffixes"), "URL Suffixes to Ignore");
        $this->assertEquals(Level::Warning, $app->get("log_level"), "Log Level");
        $this->assertEquals($rootPath, $app->get("root_path"), "Root Path");
        $this->assertEquals($wwwPath, $app->get("www_path"), "Public Path");

        $this->assertEquals([
            "test" => [
                "engine" => "sqlite",
                "file" => ":memory:",
            ],
        ], $app->get("db_config"));
        $this->assertInstanceOf(\ifcanduela\db\Database::class, $app->get("db"));
    }

    public function testRouteNotFound()
    {
        $app = new App($this->appFolder, "test");
        $app->set('path', '/not-found');

        ob_start();
        $app->run();
        $response = ob_get_clean();

        $this->assertEquals('Route not found', $response);

        $app = new App($this->appFolder, "test");
        $app->set('path', '/not-found');
        $app->set('debug', true);

        try {
            $app->run();
        } catch (\ifcanduela\router\exception\RouteNotFound $e) {
            $this->assertEquals("Route not found", $e->getMessage());
        }
    }

    public function testMiddleware()
    {
        $app = new App($this->appFolder, "test");

        $r = Route::from("/test/string-response")->to("test@stringResponse");
        $r->before(\app\services\MiddlewareTest::class);
        $r->after(\app\services\MiddlewareTest::class);

        $router = new Router();
        $router->addRoute($r);
        $app->set(Router::class, $router);
        $app->set(Request::class, Request::create("/test/string-response"));

        ob_start();
        $app->run();
        $html = ob_get_clean();
        $this->assertEquals("beforeresponse", $html);
    }

    public function testAfterMiddleware()
    {
        $app = new App($this->appFolder, "test");

        $r = Route::from("/test/string-response")->to("test@stringResponse");
        $r->after(\app\services\MiddlewareTest::class);

        $router = new Router();
        $router->addRoute($r);
        $app->set(Router::class, $router);
        $app->set(Request::class, Request::create("/test/string-response"));

        ob_start();
        $app->run();
        $html = ob_get_clean();
        $this->assertEquals("noneresponse", $html);
    }
}
