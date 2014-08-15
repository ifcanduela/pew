<?php

use \pew\libs\Env;

class EnvTest extends PHPUnit_Framework_TestCase
{
    public $_server;

    public $SERVER_1 = [
        'HTTPS' => 'on',
        'SERVER_NAME' => 'localhost_ssl',
        'SERVER_PORT' => '443',
        'REQUEST_METHOD' => 'GET',
        'REMOTE_ADDR' => '22.44.66.88',
        'SCRIPT_NAME' => 'subfolder/service.php',
        'REQUEST_URI' => '/subfolder/service.php',
    ];

    public $SERVER_2 = [
        'HTTPS' => '',
        'SERVER_NAME' => 'some_proxy_server',
        'SERVER_PORT' => '80',
        'REQUEST_METHOD' => 'POST',
        'REMOTE_ADDR' => '1.2.3.4',
        'SCRIPT_NAME' => '/index.php',
        'REQUEST_URI' => '/index.php?p=/controller/action/arg1',
    ];

    public $SERVER_3 = [
        'HTTPS' => '',
        'SERVER_NAME' => 'some_proxy_server',
        'SERVER_PORT' => '80',
        'REQUEST_METHOD' => 'GET',
        'REMOTE_ADDR' => '1.2.3.4',
        'SCRIPT_NAME' => '/index.php',
        'PATH_INFO' => '/controller/action/arg1',
        'REQUEST_URI' => '/a/b/c/d?a=b&c=d'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->_server = $_SERVER;
    }

    public function setUp()
    {
        $_SERVER = $this->_server;
    }

    public function testMethod()
    {
        $env = new Env;
        $this->assertEquals('', $env->method);
    }

    public function testHeaders()
    {
        $env = new Env;
        $this->assertEquals('', $env->headers);
    }

    public function testScheme()
    {
        $env = new Env;
        $this->assertEquals('', $env->scheme);
    }

    public function testHost()
    {
        $env = new Env;
        $this->assertEquals('', $env->host);
    }

    public function testPort()
    {
        $env = new Env;
        $this->assertEquals('', $env->port);
    }

    public function testPath()
    {
        $env = new Env;
        $this->assertEquals(dirname($_SERVER['SCRIPT_NAME']), $env->path);
    }

    public function testScript()
    {
        $env = new Env;
        $this->assertEquals(basename($_SERVER['SCRIPT_NAME']), $env->script);
    }

    public function testSegments()
    {
        $env = new Env;
        $this->assertEquals('/', $env->segments);
    }

    public function testGet()
    {
        $env = new Env;
        $this->assertEquals([], $env->get);
    }

    public function testPost()
    {
        $env = new Env;
        $this->assertEquals([], $env->post);
    }

    public function testFiles()
    {
        $env = new Env;
        $this->assertEquals([], $env->files);
    }

    public function testCookie()
    {
        $env = new Env;
        $this->assertEquals([], $env->cookie);
    }

    public function testLocal()
    {
        $env = new Env;
        $this->assertTrue($env->local);
    }

    public function testServerConfig1()
    {
        $_SERVER = $this->SERVER_1;
        $env = new Env;

        $this->assertEquals('https://', $env->scheme);
        $this->assertEquals('localhost_ssl', $env->host);
        $this->assertEquals('443', $env->port);
        $this->assertEquals('GET', $env->method);
        $this->assertEquals('subfolder/', $env->path);
        $this->assertEquals('service.php', $env->script);
        $this->assertEquals('/', $env->segments);
    }

    public function testServerConfig2()
    {
        $_SERVER = $this->SERVER_2;
        $env = new Env;

        $this->assertEquals('http://', $env->scheme);
        $this->assertEquals('some_proxy_server', $env->host);
        $this->assertEquals('80', $env->port);
        $this->assertEquals('POST', $env->method);
        $this->assertEquals('/', $env->path);
        $this->assertEquals('index.php', $env->script);
        $this->assertEquals('/', $env->segments);
    }

    public function testServerConfig3()
    {
        $_SERVER = $this->SERVER_3;
        $env = new Env;

        if (!function_exists('getAllHeaders')) {
            function getAllHeaders() { return []; }
        }

        $this->assertEquals('http://', $env->scheme);
        $this->assertEquals('some_proxy_server', $env->host);
        $this->assertEquals('80', $env->port);
        $this->assertEquals('GET', $env->method);
        $this->assertEquals('/', $env->path);
        $this->assertEquals('index.php', $env->script);
        $this->assertEquals('/controller/action/arg1', $env->segments);
    }
}
