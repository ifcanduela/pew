<?php

use \pew\libs\Registry;

class RegistryTest extends PHPUnit_Framework_TestCase
{
    public function testInstatiation()
    {
        $r = new Registry;
        $instance_1 = Registry::instance();
        $instance_2 = Registry::instance();
        $instance_3 = new Registry;

        $this->assertEquals($instance_1, $instance_2);
        $this->assertEquals($instance_1, Registry::instance());
        $this->assertFalse($instance_1 === $r);
        $this->assertFalse($instance_2 === $r);
        $this->assertFalse($instance_3 === $instance_2);
        $this->assertFalse($instance_3 === $r);
        $this->assertFalse($instance_3 === (new Registry));
    }

    public function testImport()
    {
        $r = new Registry;
        $this->assertEquals(0, $r->count());

        $r->import([
                'foo' => 12345,
                'bar' => 'Amsterdam',
                'baz' => new stdClass
            ]);

        $this->assertEquals(3, $r->count());
    }

    public function testExportAndKeys()
    {
        $r = new Registry;
        
        $r->foo = 12345;
        $r->offsetSet('bar', 'Amsterdam');
        $r['baz'] = new stdClass;

        $keys = $r->keys();
        $export = $r->export();

        $this->assertEquals(array_keys($export), $keys);

        $this->assertTrue(in_array('foo', $keys));
        $this->assertTrue(array_key_exists('foo', $export));
        $this->assertEquals(12345, $export['foo']);

        $this->assertTrue(in_array('bar', $keys));
        $this->assertTrue(array_key_exists('bar', $export));
        $this->assertEquals('Amsterdam', $export['bar']);

        $this->assertTrue(in_array('baz', $keys));
        $this->assertTrue(array_key_exists('baz', $export));
        $this->assertEquals('stdClass', get_class($export['baz']));
    }

    public function testCountableInterface()
    {
        $r = new Registry;
        $this->assertEquals(0, count($r));
        $r->foo = 'bar';
        $this->assertEquals(1, count($r));
        unset($r->foo);
        $this->assertEquals(0, count($r));
    }

    public function testArrayInterface()
    {
        $r = new Registry;
        $this->assertFalse(isSet($r['foo']));

        $r->import([
                'foo' => 12345,
                'bar' => 'Amsterdam',
                'baz' => new stdClass
            ]);
        
        $this->assertTrue(isSet($r['foo']));
        $this->assertTrue(isSet($r['bar']));
        $this->assertTrue(isSet($r['baz']));
        $this->assertEquals(12345, $r['foo']);
        $this->assertEquals('Amsterdam', $r['bar']);
        $this->assertTrue(get_class($r['baz']) === 'stdClass');

        unset($r['foo']);
        $this->assertFalse(isSet($r['foo']));
        
        unset($r['bar']);
        $this->assertFalse(isSet($r['bar']));
    }

    public function testObjectInterface()
    {
        $r = new Registry;
        $this->assertFalse(isSet($r['foo']));

        $r->import([
                'foo' => 12345,
                'bar' => 'Amsterdam',
                'baz' => new stdClass
            ]);

        $this->assertTrue(isSet($r->foo));
        $this->assertTrue(isSet($r->bar));
        $this->assertTrue(isSet($r->baz));
        $this->assertEquals(12345, $r->foo);
        $this->assertEquals('Amsterdam', $r->bar);
        $this->assertTrue(get_class($r->baz) === 'stdClass');

        unset($r->foo);
        $this->assertFalse(isSet($r->foo));
        
        unset($r->bar);
        $this->assertFalse(isSet($r['bar']));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The key nothing_here is not defined
     */
    public function testBadIndexAccess()
    {
        $r = new Registry;

        $r->nothing_here;
    }

    public function testFactory()
    {
        $r = new Registry;
        $this->assertFalse(isSet($r['zip']));

        $r->zip = function($r) {
            return new \ZipArchive;
        };

        $r->registryWithZip = function ($r) {
            $f = new Registry;
            $f->zipArchive = $r->zip;
            return $f;
        };

        $this->assertTrue(isSet($r->zip));
        $this->assertTrue(isSet($r->registryWithZip));
        $this->assertTrue(isSet($r->registryWithZip->zipArchive));
        $this->assertEquals($r->zip, $r->registryWithZip->zipArchive);
    }

    public function testFactoryWithSingleton()
    {
        $r = Registry::instance();
        $this->assertFalse(isSet($r['zip']));

        $r->callback = function ($r) {
            return get_class($r) == 'Registry';
        };

        $this->assertTrue(isSet($r->callback));
        $this->assertTrue(true, $r->callback);
    }
}
