<?php

use \pew\libs\FileCache;

class FileCacheTest extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        parent::__construct();

        if (file_exists(__DIR__.'/new_folder')) {
            if (file_exists(__DIR__.'/new_folder/foo')) {
                unlink(__DIR__.'/new_folder/foo');
            }
            
            if (file_exists(__DIR__.'/new_folder/foo.gz')) {
                unlink(__DIR__.'/new_folder/foo.gz');
            }

            rmdir(__DIR__.'/new_folder');
        }
    }

    public function testCachingMethods()
    {
        $fc = new \pew\libs\FileCache;

        $fc->save('foo', 'bar');
        $this->assertEquals('bar', $fc->load('foo'));

        $this->assertTrue($fc->cached('foo'));
        $this->assertFalse($fc->cached('spam'));

        try {
            $fc->load('spam');
            $this->fail('Exception expected');
        } catch (RuntimeException $e) {
            $this->assertEquals('RuntimeException', get_class($e));
        }

        $fc->delete('foo');
        $this->assertFalse($fc->cached('foo'));

        $this->assertFalse($fc->cached('dummy'));
        $fc->delete('dummy');
        $this->assertFalse($fc->cached('dummy'));
    }

    public function testMagicInterface()
    {
        $fc = new \pew\libs\FileCache;

        $fc->magic_foo = 'magic_bar';
        $this->assertEquals('magic_bar', $fc->magic_foo);

        $this->assertTrue(isSet($fc->magic_foo));
        $this->assertFalse(isSet($fc->magic_spam));

        $this->assertNull($fc->magic_spam);

        unset($fc->magic_foo);
        $this->assertNull($fc->magic_foo);
    }

    public function testConfig()
    {
        $fc = new \pew\libs\FileCache(120, __DIR__.'/new_folder');

        $fc->save('foo', 'bar');
        $this->assertEquals('bar', $fc->load('foo'));

        $fc->gzip(true);
        $this->assertTrue($fc->gzip());
        $fc->gzip(false);
        $this->assertFalse($fc->gzip());

        $this->assertEquals('.gz', $fc->gzip_suffix());
        $fc->gzip_suffix('g_zip');
        $this->assertEquals('g_zip', $fc->gzip_suffix());

        $this->assertEquals(120, $fc->interval());
        $fc->interval(24 * 60 * 60);
        $this->assertEquals(24 * 60 * 60, $fc->interval());

        $fc->delete('foo');
    }
}
