<?php

use pew\libs\FileCache;

class FileCacheTest extends PHPUnit\Framework\TestCase
{
    public function testBasics()
    {
        $c = new FileCache(10, __DIR__ . '/../cache');
        $this->assertFalse($c->cached('not-cached'));
    }

    public function testSetFolder()
    {
        $c = new FileCache(10);
        $this->assertEquals('cache', $c->folder());
        $c->folder(__DIR__ . '/../cache');
        $this->assertEquals( __DIR__ . '/../cache', $c->folder());
    }
}
