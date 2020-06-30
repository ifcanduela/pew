<?php

use pew\response\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\Cookie;

class ResponseTest extends PHPUnit\Framework\TestCase
{
    public function testConstructor()
    {
        $response = new Response();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf(SymfonyResponse::class, $response->getResponse());
    }

    public function testSetResponseCode()
    {
        $response = new Response();
        $response->code(501);

        $r = $response->getResponse();
        $this->assertEquals(501, $r->getStatusCode());
    }

    public function testSetCookie()
    {
        $response = new Response();
        $response->cookie("my_cookie_1", 1);
        $r = $response->getResponse();
        $c = $r->headers->getCookies();
        $this->assertEquals("my_cookie_1", $c[0]->getName());
        $this->assertEquals("1", $c[0]->getValue());

        $response = new Response();
        $response->cookie(new Cookie("my_cookie_2", 2));
        $r = $response->getResponse();
        $c = $r->headers->getCookies();
        $this->assertEquals("my_cookie_2", $c[0]->getName());
        $this->assertEquals("2", $c[0]->getValue());
    }
}
