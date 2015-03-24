<?php

use \pew\libs\Registry;

class RegistryTest extends PHPUnit_Framework_TestCase
{
    public function testRegistry()
    {
        $void = new Registry;
        $data = new Registry([
                'value1' => 1,
                'value2' => 2,
            ]);
        $fact = new Registry([
                'value3' => 3,
                'fact' => function ($c) { return $c['value3']; },
            ]);

        $this->assertInstanceOf('\pew\libs\Registry', $void);
        $this->assertInstanceOf('\pew\libs\Registry', $data);
        $this->assertInstanceOf('\pew\libs\Registry', $fact);

        $this->assertEquals(1, $data->value1);
        $this->assertEquals(2, $data['value2']);
        $this->assertEquals(3, $fact->fact);
    }

    public function testImport()
    {
        $r = new Registry;
        
        $r->import([
                'foo' => 12345,
                'bar' => 'Amsterdam',
                'baz' => [1, 2, 3],
                'qux' => function () { return 'QUUX'; },
            ]);

        $this->assertEquals(12345, $r->foo);
        $this->assertEquals('Amsterdam', $r['bar']);
        $this->assertEquals(3, $r['baz'][2]);
        $this->assertEquals('QUUX', $r['qux']);
    }

    public function testExport()
    {
        $r = new Registry;
        
        $r->foo = 12345;
        $r->offsetSet('bar', 'Amsterdam');
        $r['baz'] = new stdClass;
        $r->register('qux', function () { return 'QUUX'; });

        $export = $r->export();

        $this->assertTrue(array_key_exists('foo', $export));
        $this->assertEquals(12345, $export['foo']);

        $this->assertTrue(array_key_exists('bar', $export));
        $this->assertEquals('Amsterdam', $export['bar']);

        $this->assertTrue(array_key_exists('baz', $export));
        $this->assertEquals('stdClass', get_class($export['baz']));

        $this->assertFalse(array_key_exists('qux', $export));
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
        $this->assertFalse(isSet($r->bar));
    }

    public function testBadIndexAccess()
    {
        $r = new Registry;

        $this->assertNull($r->nothing_here);
        $this->assertEquals(12345, $r->offsetGet('nothing_here', 12345));
    }

    public function testFactory()
    {
        $r = new Registry;
        $this->assertFalse(isSet($r['zip']));

        $r->zip = function($r) {
            return new \ZipArchive;
        };

        $r->register('registryWithZip', function ($r) {
                $f = new Registry;
                $f->zipArchive = $r->zip;
                return $f;
            });

        $this->assertTrue(isSet($r->zip));
        $this->assertTrue(is_callable($r->zip));

        $this->assertTrue($r->registered('registryWithZip'));
        $this->assertTrue(isSet($r->registryWithZip));
        $this->assertTrue(isSet($r->registryWithZip->zipArchive));
        $this->assertEquals($r->zip, $r->registryWithZip->zipArchive);
        $r->unregister('registryWithZip');

        try {
            $zwa = $r->build('zipWithArchive');
        } catch (\RuntimeException $e) {
            $this->assertEquals("Unregistered factory: zipWithArchive", $e->getMessage());
        }
    }

    public function testFactoryWithSingleton()
    {
        $r = new Registry();

        $r->register('callback', function ($r) {
                return new StdClass;
            });

        $this->assertTrue($r->registered('callback'));
        $this->assertTrue(true, $r->callback);
        $this->assertTrue(true, $r['callback']);
        $this->assertEquals($r->callback, $r['callback']);
        $this->assertFalse($r->callback === $r->build('callback'));
    }
}
