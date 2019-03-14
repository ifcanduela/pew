<?php

use pew\request\Request;

class RequestTest extends PHPUnit\Framework\TestCase
{
    public function makeRequest($path = "/", $get = [], $post = [])
    {
        $server = [
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
            ];

        if ($path !== "/") {
            $server["PATH_INFO"] = $path;
        }

        return new Request(
            $get,
            $post,
            [],
            [],
            [],
            $server,
            ""
        );
    }
    public function testAppUrl()
    {
        $request = $this->makeRequest();

        $this->assertEquals("http://localhost/", $request->appUrl());
    }

    public function testIsGet()
    {
        $request = Request::createFromGlobals();
        $this->assertTrue($request->isGet());
    }

    public function testIsPost()
    {
        $request = Request::createFromGlobals();
        $this->assertFalse($request->isPost());
    }

    public function testMethod()
    {
        $request = Request::createFromGlobals();
        $this->assertEquals('GET', $request->method());

        $request = new Request([], ['_method' => 'PUT']);
        $this->assertEquals('PUT', $request->get('_method'));
        $this->assertEquals('PUT', $request->method());
    }

    public function testGetPostArgs()
    {
        $request = new Request(['foo' => 'bar'], ['_method' => 'PUT']);
        $this->assertEquals('bar', $request->get('foo'));
        $this->assertEquals('PUT', $request->post('_method'));
        $this->assertEquals(['_method' => 'PUT'], $request->post());
    }

    public function testIsJson()
    {
        $request = Request::create('/users/1|json');
        $this->assertTrue($request->isJson());

        $request = Request::create('/users/Somejson');
        $this->assertFalse($request->isJson());
    }
}
