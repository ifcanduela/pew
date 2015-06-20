<?php

use \pew\libs\ArgumentResolver;

class TestBase {
    public $value;
    public $data;
    public function __construct($value) { $this->value = $value; }
}

class Test1 {
    public function __construct($value)
    {
        parent::__construct($value);
    }
}

class Test2 {
    public function __construct($value = 2)
    {
        parent::__construct($value);
    }
}

class Test3 {
    public function __construct($value = null)
    {
        parent::__construct($value);
    }
}

class Test4 {
    public function __construct($value = 4, $data)
    {
        parent::__construct($value);
    }
}

class Test5 {
    public function __construct($value, $data)
    {
        parent::__construct($value);
    }
}

class ArgumentResolverTest  extends PHPUnit_Framework_TestCase
{
    public function testResolveConstructorWithAllArguments()
    {
        $ar = new ArgumentResolver;

        $ar->append_list([
            'value' => 1234
        ]);

        $className = 'Test1';
        $reflectionClass = new \ReflectionClass($className);

        $args = $ar->resolve_constructor($reflectionClass);
        $this->assertEquals([1234], $args);
    }

    public function testResolveConstructorWithDefaultValueArguments()
    {
        $ar = new ArgumentResolver;

        $ar->append_list([
        ]);

        $className = 'Test2';
        $reflectionClass = new \ReflectionClass($className);

        $args = $ar->resolve_constructor($reflectionClass);
        $this->assertEquals([2], $args);
    }

    public function testResolveConstructorWithOptionalArguments()
    {
        $ar = new ArgumentResolver;

        $ar->append_list([
        ]);

        $className = 'Test3';
        $reflectionClass = new \ReflectionClass($className);

        $args = $ar->resolve_constructor($reflectionClass);
        $this->assertEquals([null], $args);
    }

    public function testResolveConstructorWithFirstOptionalArgument()
    {
        $ar = new ArgumentResolver;

        $ar->append_list([
            'data' => true
        ]);

        $className = 'Test4';
        $reflectionClass = new \ReflectionClass($className);

        $args = $ar->resolve_constructor($reflectionClass);
        $this->assertEquals([4, true], $args);
    }

    public function testResolveArgumentsWithPriority()
    {
        $ar = new ArgumentResolver;

        $ar->append_list([
            'data' => true,
            'value' => 5
        ]);

        $ar->prepend_list([
            'data' => false
        ]);

        $ar->append_list([
            'value' => -5,
            'data' => null,
        ]);

        $className = 'Test5';
        $reflectionClass = new \ReflectionClass($className);

        $args = $ar->resolve_constructor($reflectionClass);
        $this->assertEquals([5, false], $args);
    }
}
