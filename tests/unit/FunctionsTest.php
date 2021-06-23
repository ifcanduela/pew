<?php

use Symfony\Component\HttpFoundation\Session\Session;

$session = new Session();
$session->start();

use pew\App;
use pew\request\Request;

use function pew\root;
use function pew\session;
use function pew\url;
use function pew\here;
use function pew\array_path;
use function pew\array_find_key;
use function pew\array_find_value;
use function pew\file_get_json;
use function pew\file_put_json;
use function pew\slug;

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

    public function testJsonFunctions()
    {
        $data = file_get_json(__DIR__ . '/../fixtures/json1.json');
        $this->assertEquals([1, 2, 3, 4], $data);

        $data = file_get_json(__DIR__ . '/../fixtures/json2.json');
        $this->assertIsArray($data);
        $this->assertArrayHasKey("numbers", $data);
        $this->assertArrayHasKey("letters", $data);

        $data = file_get_json(__DIR__ . '/../fixtures/json2.json', false);
        $this->assertIsObject($data);
        $this->assertObjectHasAttribute("numbers", $data);
        $this->assertObjectHasAttribute("letters", $data);

        $t = time();
        file_put_json(__DIR__. "/../fixtures/json_{$t}.json", [1.1, 2.2, 3.3]);
        $data = file_get_json(__DIR__ . "/../fixtures/json_{$t}.json");

        $this->assertEquals([1.1, 2.2, 3.3], $data);

        unlink(__DIR__ . "/../fixtures/json_{$t}.json");

        try {
            file_get_json(__DIR__ . "/../fixtures/json3.json");
        } catch (\Exception $e) {
            $this->assertEquals("JSON decoding error: Syntax error", $e->getMessage());
        }

        try {
            $t = time();
            file_put_json(__DIR__ . "/../fixtures/json_{$t}.json", fopen(__DIR__ . "/../fixtures/nofile", "w"));
        } catch (\Exception $e) {
            $this->assertEquals("JSON encoding error: Type is not supported", $e->getMessage());
            unlink(__DIR__ . "/../fixtures/nofile");
        }
    }

    public function testArrayFindValue()
    {
        $value = array_find_value([1, 2, 3, 4], function ($v, $i) {
            return $i > 0 && $v % 2 == 1;
        });

        $this->assertEquals(3, $value);

        $value = array_find_value([1, 2, 3, 4], function ($v, $i) {
            return $v > 10;
        });

        $this->assertNull($value);
    }

    public function testArrayFindKey()
    {
        $key = array_find_key([1, 2, 3, 4], function ($v) {
            return $v === 4;
        });

        $this->assertEquals(3, $key);

        $key = array_find_key([1, 2, 3, 4], function ($v, $i) {
            return $i > 0 && $v % 2 == 1;
        });

        $this->assertEquals(2, $key);

        $key = array_find_key([1, 2, 3, 4], function ($v, $i) {
            return $v > 10;
        });

        $this->assertNull($key);
    }

    public function testSlug()
    {
        $strings = [
            "2021-06-01 12:56:51" => "2021-06-01-12-56-51",
            "Name MiddleName LastName" => "name-middle-name-last-name",
            "MrWorld1999" => "mr-world-1999",
        ];

        foreach ($strings as $string => $expected) {
            $this->assertEquals($expected, slug($string));
        }
    }
}
