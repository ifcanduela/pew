<?php

use pew\lib\Url;
use pew\request\Request;

class UrlTest extends PHPUnit\Framework\TestCase
{
    public function testBasics()
    {
        $url = new Url();
        $this->assertEquals("/", (string) $url);
        $this->assertEquals("http://example.com/", (string) $url->setHost("example.com"));

        $url = new Url("/original/path", Request::create('http://localhost/original/path'));
        $this->assertEquals("http://localhost/original/path", (string) $url);

        $url->setPath('/path/to/route');
        $this->assertEquals("http://localhost/path/to/route", (string) $url);

        $url->setScheme("ftp");
        $this->assertEquals("ftp://localhost/path/to/route", (string) $url);

        $url->setHost("example.com");
        $this->assertEquals("ftp://example.com/path/to/route", (string) $url);

        $url->setAuth("testuser1");
        $this->assertEquals("ftp://testuser1@example.com/path/to/route", (string) $url);

        $url->setAuth("testuser2", "testpass");
        $this->assertEquals("ftp://testuser2:testpass@example.com/path/to/route", (string) $url);

        $url->setPort(21);
        $this->assertEquals("ftp://testuser2:testpass@example.com:21/path/to/route", (string) $url);
        $this->assertEquals(21, $url->getPort(true));
    }

    public function testUrlToPath()
    {
        $this->assertEquals("http://localhost/other/route", (string) Url::to('/other/route')->setHost("localhost"));
    }

    public function testQueryParameters()
    {
        $url = new Url("/path/to/route?one=1", Request::create(""));

        $this->assertEquals(["one" => "1"], $url->getQuery());
        $this->assertEquals("1", $url->getQueryParam("one"));

        $url->setQueryParam("two", 2);
        $this->assertEquals(["one" => "1", "two" => "2"], $url->getQuery());
        $this->assertEquals("2", $url->getQueryParam("two"));

        $url->mergeQueryParams(["two" => "dos", "three" => "tres"]);
        $this->assertEquals(["one" => "1", "two" => "dos", "three" => "tres"], $url->getQuery());
    }

    public function testAddAndRemovePathSegments()
    {
        $url = new Url("https://example.com/first/second/third");

        $url->addPath("fourth");
        $this->assertEquals("/first/second/third/fourth", $url->getPath());

        $url->removePath("second");
        $this->assertEquals("/first/third/fourth", $url->getPath());
    }

    public function testScheme()
    {
        $url = new Url("http://localhost/path/to/route?return=https://localhost/return/");

        $this->assertEquals("http://", $url->getScheme());
        $this->assertEquals("http", $url->getScheme(false));

        $url = $url->setScheme("https://example.com?scheme=scp://");
        $this->assertEquals("https", $url->getScheme(false));
    }

    public function testUrlFragment()
    {
        $url = new Url("https://example.com/index.php#alpha");

        $this->assertEquals("#alpha", $url->getFragment());

        $url->setFragment("");
        $this->assertEquals("", $url->getFragment());

        $url->setFragment("beta");
        $this->assertEquals("#beta", $url->getFragment());
    }

    public function testQueryString()
    {
        $url = new Url("http://example.org/index.php?alpha=a&beta=b");

        $allQueryParams= $url->getQuery();
        $onlyBetaQueryParam = $url->getQuery(["beta"]);

        $this->assertEquals(["alpha" => "a", "beta" => "b"], $allQueryParams);
        $this->assertEquals(["beta" => "b"], $onlyBetaQueryParam);

        $url->setQueryParam("delta", "d");

        $this->assertEquals("d", $url->getQueryParam("delta"));
        $this->assertNull($url->getQueryParam("gamma"));

        $url->setQueryString("eta=e&iota=i");
        $this->assertNull($url->getQueryParam("alpha"));
        $this->assertEquals("e", $url->getQueryParam("eta"));
        $this->assertEquals("i", $url->getQueryParam("iota"));

        $url->setQuery(["kappa" => "k"]);
        $this->assertNull($url->getQueryParam("iota"));
        $this->assertEquals("k", $url->getQueryParam("kappa"));
    }
}
