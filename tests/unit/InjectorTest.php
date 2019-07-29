<?php

namespace types {

interface Type {}
class Type1 implements Type {}
class Type2 implements Type {}
class Type3 implements Type {
    public $type1;

    public function __construct(Type1 $type1)
    {
        $this->type1 = $type1;
    }

    public function method(Type $t)
    {
        return $t;
    }
}

} // namespace types

namespace {

use pew\di\Injector;

class InjectorTest extends PHPUnit\Framework\TestCase
{
    public function testCallAnonymousFunction()
    {
        $t1 = new \types\Type1();
        $t2 = new \types\Type2();

        $injector = new Injector([
            \types\Type::class => $t2,
            \types\Type1::class => $t1,
            'type1' => false,
        ]);

        $callback = function (\types\Type $type2, $type1 = true) {
            return [$type2, $type1];
        };

        $injections = $injector->callFunction($callback);

        $this->assertEquals(\pew\di\Injector::class, get_class($injector));
        $this->assertEquals($injections, [$t2, false]);
    }

    // public function testCallFunctionWithBinding()
    // {
    //     $object = (object) [
    //         "foo" => "bar",
    //     ];
    //     $callback = function () {
    //         return $this->foo;
    //     };

    //     $injector = new Injector();
    //     $result = $injector->callFunction($callback, $object);

    //     $this->assertEquals($result, "bar");
    // }

    public function testCallStdFunction()
    {
        $injector = new Injector([
            'str' => "baz bar foo",
            'start' => 8,
            'length' => 3
        ]);

        $foo = $injector->callFunction('substr');

        $this->assertEquals($foo, "foo");
    }

    public function testCreateInstance()
    {
        $t1 = new \types\Type1();
        $t2 = new \types\Type2();

        $injector = new Injector([
            \types\Type::class => $t2,
            \types\Type1::class => $t1,
            'type1' => false,
        ]);

        $type3 = $injector->createInstance(\types\Type3::class);

        $this->assertEquals($type3->type1, $t1);
    }

    public function testCreateInstanceWithoutConstructor()
    {
        $injector = new Injector([]);

        $type1 = $injector->createInstance(\types\Type1::class);

        $this->assertEquals(\types\Type1::class, get_class($type1));
    }

    public function testCallMethodOnObject()
    {
        $t2 = new \types\Type2;

        $injector = new Injector([
            \types\Type::class => $t2,
        ]);

        $t3 = new \types\Type3(new \types\Type1);
        $t = $injector->callMethod($t3, 'method');
        $this->assertEquals($t2, $t);
    }

    public function testCallMethodWithoutObject($value='')
    {
        $this->expectException(\InvalidArgumentException::class);

        $t2 = new \types\Type2;

        $injector = new Injector([
            \types\Type::class => $t2,
        ]);

        $invalid = [];
        $t = $injector->callMethod($invalid, 'method');
    }

    public function testAddContainers()
    {
        $t1 = new \types\Type1();
        $t2 = new \types\Type2();

        $injector = new Injector();
        $injector->prependContainer([
            \types\Type::class => $t2,
            \types\Type1::class => $t1,
            'type1' => false,
        ]);

        $injector->appendContainer([
            \types\Type::class => null,
            \types\Type1::class => null,
            'type1' => null,
        ]);

        $type3 = $injector->createInstance(\Types\Type3::class);

        $this->assertEquals($type3->type1, $t1);
    }

    public function testUseDefaultParamValue()
    {
        $injector = new Injector([]);

        $callback = function (int $two = 2) {
            return $two;
        };

        $injections = $injector->callFunction($callback);

        $this->assertEquals($injections, 2);
    }

    public function testGetInjections()
    {
        $callback = function () {};
        $arrays = [1, 2, 3, 4];

        $injector = new Injector([
            'callback' => $callback,
            'arrays' => $arrays,
        ]);

        $functionReflector = new ReflectionFunction('array_map');
        $injections = $injector->getInjections($functionReflector);

        $this->assertEquals($injections, [$callback, $arrays]);
    }

    public function testGetInjectionMissing()
    {
        $this->expectException(\pew\di\KeyNotFoundException::class);

        $callback = function () {};

        $injector = new Injector([
            'callback' => $callback,
        ]);

        $functionReflector = new ReflectionFunction('array_map');
        $injections = $injector->getInjections($functionReflector);
    }

    public function testCallGenericFunction()
    {
        $injector = new Injector([
            "alpha" => "ALPHA",
            "beta" => "BETA",
        ]);

        $result = $injector->call(function ($alpha, $beta) {
            return "{$alpha} {$beta}";
        });

        $this->assertEquals("ALPHA BETA", $result);
    }

    public function testCallGenericMethod()
    {
        $injector = new Injector([]);

        $type1 = $injector->createInstance(\types\Type1::class);
        $this->assertInstanceOf(\types\Type1::class, $type1);

        $injector->appendContainer([
            \types\Type1::class => $type1,
            \types\Type::class => $type1,
        ]);

        $result = $injector->call([\types\Type3::class, "method"]);
        $this->assertEquals($type1, $result);
    }

    public function testAutoResolve()
    {
        $injector = new Injector();
        $type1 = $injector->autoResolve(\types\Type1::class);
        $this->assertInstanceOf(\types\Type1::class, $type1);

        $type3 = $injector->autoResolve(\types\Type3::class);
        $this->assertInstanceOf(\types\Type3::class, $type3);
        $this->assertInstanceOf(\types\Type1::class, $type3->type1);
    }
} // class InjectorTest

} // namespace /
