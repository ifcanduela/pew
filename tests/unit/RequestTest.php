<?php

use pew\request\Request;

class RequestTest extends PHPUnit\Framework\TestCase
{
    public function testAppUrl()
    {
        $request = Request::createFromGlobals();
        $this->assertEquals($request->getSchemeAndHttpHost() . $request->getBaseUrl(), $request->appUrl());
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
