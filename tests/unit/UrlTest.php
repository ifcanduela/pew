<?php

use pew\lib\Url;
use pew\request\Request;

class UrlTest extends PHPUnit\Framework\TestCase
{
    public function testBasics()
    {
        $url = new Url(Request::create('/original/path'));
        $this->assertEquals("http://localhost/original/path", (string) $url);
        $this->assertEquals("http://localhost", $url->base());

        $url = $url->setPath('/path/to/route');
        $this->assertEquals("http://localhost/path/to/route", (string) $url);

        $url = $url->setScheme("ftp");
        $this->assertEquals("ftp://localhost/path/to/route", (string) $url);

        $url = $url->setHost("example.com");
        $this->assertEquals("ftp://example.com/path/to/route", (string) $url);

        $url = $url->setAuth("testuser1");
        $this->assertEquals("ftp://testuser1@example.com/path/to/route", (string) $url);

        $url = $url->setAuth("testuser2", "testpass");
        $this->assertEquals("ftp://testuser2:testpass@example.com/path/to/route", (string) $url);

        $url = $url->setPort(21);
        $this->assertEquals("ftp://testuser2:testpass@example.com:21/path/to/route", (string) $url);
        $this->assertEquals(21, $url->getPort(true));
    }

    public function testUrlToPath()
    {
        $url = new Url(Request::create('/path/to/route'));
        $this->assertEquals("http://localhost/other/route", $url->to('/other/route'));
    }

    public function testQueryParameters()
    {
        $url = new Url(Request::create('/path/to/route?one=1'));

        $this->assertEquals(['one' => '1'], $url->getQuery());
        $this->assertEquals('1', $url->getQueryParam('one'));

        $url2 = $url->setQueryParam('two', 2);
        $this->assertEquals(['one' => '1', 'two' => '2'], $url2->getQuery());
        $this->assertEquals('2', $url2->getQueryParam('two'));

        $url3 = $url->mergeQueryParams(['two' => 'dos', 'three' => 'tres']);
        $this->assertEquals(['one' => '1', 'two' => 'dos', 'three' => 'tres'], $url3->getQuery());
    }
}
