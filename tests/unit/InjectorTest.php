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

class Type4 implements Type {
    public $type;

    public function __construct(Type $type)
    {
        $this->type = $type;
    }
}

} // namespace types

namespace {

use pew\di\Injector;

class InjectorTest extends PHPUnit\Framework\TestCase
{
    public function testCallAnonymousFunction(): void
    {
        $t1 = new \types\Type1();
        $t2 = new \types\Type2();

        $injector = new Injector([
            \types\Type1::class => $t1,
            \types\Type::class => $t2,
            'type1' => false,
        ]);

        $callback = function (\types\Type $type2, $type1 = true) {
            return [$type2, $type1];
        };

        $injections = $injector->callFunction($callback);

        $this->assertEquals(\pew\di\Injector::class, get_class($injector));
        $this->assertEquals($injections, [$t2, false]);
    }

    public function testCallStdFunction(): void
    {
        $values = [
            'str' => "baz bar foo",
            'start' => 8,
            'length' => 3
        ];

        // the parameter names changed in PHP 8
        if (substr(PHP_VERSION, 0, 1) === "8") {
            $values = [
                'string' => "baz bar foo",
                'offset' => 8,
                'length' => 3,
            ];
        }

        $injector = new Injector($values);

        $foo = $injector->callFunction('substr');

        $this->assertEquals("foo", $foo);
    }

    public function testCreateInstance(): void
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

    public function testCreateInstanceWithoutConstructor(): void
    {
        $injector = new Injector([]);

        $type1 = $injector->createInstance(\types\Type1::class);

        $this->assertEquals(\types\Type1::class, get_class($type1));
    }

    public function testCallMethodOnObject(): void
    {
        $t2 = new \types\Type2;

        $injector = new Injector([
            \types\Type::class => $t2,
        ]);

        $t3 = new \types\Type3(new \types\Type1);
        $t = $injector->callMethod($t3, 'method');
        $this->assertEquals($t2, $t);
    }

    public function testCallMethodWithoutObject($value=''): void
    {
        $this->expectException(\TypeError::class);

        $t2 = new \types\Type2;

        $injector = new Injector([
            \types\Type::class => $t2,
        ]);

        $invalid = [];
        $t = $injector->callMethod($invalid, 'method');
    }

    public function testAddContainers(): void
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

    public function testUseDefaultParamValue(): void
    {
        $injector = new Injector([]);

        $callback = function (int $two = 2) {
            return $two;
        };

        $injections = $injector->callFunction($callback);

        $this->assertEquals($injections, 2);
    }

    public function testGetInjections(): void
    {
        $callback = function () {};
        $arrays = [1, 2, 3, 4];
        $injector = new Injector([
            'callback' => $callback,
            'array' => $arrays,
        ]);

        $functionReflector = new ReflectionFunction(function ($callback, $array) {});
        $injections = $injector->getInjections($functionReflector);

        $this->assertEquals([$callback, $arrays], $injections);
    }

    public function testGetInjectionMissing(): void
    {
        $this->expectException(\pew\di\KeyNotFoundException::class);

        $callback = function () {};

        $injector = new Injector([
            'callback' => $callback,
        ]);

        $functionReflector = new ReflectionFunction('array_map');
        $injections = $injector->getInjections($functionReflector);
    }

    public function testCallGenericFunction(): void
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

    public function testCallGenericMethod(): void
    {
        $injector = new Injector([]);

        $type1 = $injector->createInstance(\types\Type1::class);
        $this->assertInstanceOf(\types\Type1::class, $type1);

        $injector->appendContainer([
            \types\Type1::class => $type1,
            \types\Type::class => $type1,
        ]);

        $type3 = $injector->createInstance(\types\Type3::class);
        $result = $injector->call([$type3, "method"]);
        $this->assertEquals($type1, $result);
    }

    public function testAutoResolve(): void
    {
        $injector = new Injector();
        $type1 = $injector->autoResolve(\types\Type1::class);
        $this->assertInstanceOf(\types\Type1::class, $type1);

        $type3 = $injector->autoResolve(\types\Type3::class);
        $this->assertInstanceOf(\types\Type3::class, $type3);
        $this->assertInstanceOf(\types\Type1::class, $type3->type1);

        $this->expectException(\pew\di\KeyNotFoundException::class);
        $this->expectExceptionMessage("Could not find a definition for `\$type (types\Type)`");
        $injector->autoResolve(\types\Type4::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Cannot auto-resolve `types\TypeNothing`: class not found");
        $injector->autoResolve(\types\TypeNothing::class);
    }
} // class InjectorTest

} // namespace \
