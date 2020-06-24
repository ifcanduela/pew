<?php

use Symfony\Component\HttpFoundation\Session\Session;

$session = new Session();
$session->start();

use pew\App;
use pew\request\Request;

class FunctionsTest extends \PHPUnit\Framework\TestCase
{
    public $app;

    public function setUp(): void
    {
        $this->app = new App(__DIR__ . '/../fixtures/', 'test');
    }

    public function makeRequest($get = [], $post = [])
    {
        return new Request(
            $get,
            $post,
            [],
            [],
            [],
            [
                "REQUEST_TIME_FLOAT" => microtime(true),
                "REQUEST_TIME" => time(),
                "SERVER_NAME" => "localhost",
                "SERVER_ADDR" => "::1",
                "SERVER_PORT" => "80",
                "REMOTE_ADDR" => "::1",
                "DOCUMENT_ROOT" => "  /var/www",
                "REQUEST_SCHEME" => " http",
                "SERVER_ADMIN" => "webmaster@localhost",
                "SCRIPT_FILENAME" => "/var/www/www/index.php",
                "REMOTE_PORT" => "60034",
                "SERVER_PROTOCOL" => "HTTP/1.1",
                "REQUEST_METHOD" => " GET",
                "REQUEST_URI" => "/index.php",
                "SCRIPT_NAME" => "/index.php",
            ],
            ""
        );
    }

    public function testRootFunction()
    {
        $this->assertEquals($this->app->get("root_path"), root());
        $this->assertEquals(
            $this->app->get("root_path")
            . DIRECTORY_SEPARATOR . "data"
            . DIRECTORY_SEPARATOR . "info.txt"
        , root("data", "info.txt"));
    }

    public function testUrlFunction()
    {
        $this->app->set("request", $this->makeRequest());

        $this->assertEquals("http://localhost/", url());
        $this->assertEquals("http://localhost/css/styles.css", url("css/styles.css"));
        $this->assertEquals("http://localhost/js/app.js", url("js", "app.js"));

        $this->assertEquals("http://localhost/js/app.js?a=1", url("js", ["a" => 1], "app.js"));
    }

    public function testHereFunction()
    {
        $r = $this->makeRequest(["a" => 1]);
        $this->app->set("request", $r);

        $this->assertEquals("http://localhost/?a=1", here());
    }

    public function testArraypathFunction()
    {
        $array = [
            (object) [],
            (object) ["count" => 2],
        ];

        $this->assertEquals(2, array_path($array, "1.count"));
        $this->assertEquals(null, array_path($array, "999.missing"));
    }

    public function testSessionFunction()
    {
        $s = new Session();
        $s->set("alpha", "ALPHA");
        $s->set("beta", ["gamma" => "GAMMA"]);

        $this->assertEquals("ALPHA", session("alpha"));
        $this->assertEquals("GAMMA", session("beta.gamma"));

        $this->assertEquals([
            "alpha" => "ALPHA",
            "beta" => ["gamma" => "GAMMA"],
        ], session());
    }
}
