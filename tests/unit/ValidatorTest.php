<?php

use pew\model\Validator as v;

class ArrayLike implements \Iterator, \ArrayAccess
{
    public $id = 3;
    public $username = "ifcanduela";
    public $password = "22021981";
    public $height = null;

    public function current() {}
    public function next() {}
    public function key() {}
    public function valid() {}
    public function rewind() {}
    public function offsetExists($offset) { return isset($this->$offset); }
    public function offsetGet($offset) { return $this->$offset; }
    public function offsetSet($offset, $value) { $this->$offset = $value; }
    public function offsetUnset($offset) {}
}

class ValidatorTest extends PHPUnit\Framework\TestCase
{
    public function testBaseValidatorRules()
    {
        $validator = v::string()->minLength(2);

        $this->assertTrue($validator->validate(null));
        $this->assertFalse($validator->validate("1"));
    }

    public function testBooleanValidator()
    {
        $validator = v::boolean();

        $this->assertFalse($validator->validate("true"));
        $this->assertFalse($validator->validate("1"));
        $this->assertFalse($validator->validate(1));
        $this->assertFalse($validator->validate(0));
        $this->assertTrue($validator->validate(true));
        $this->assertTrue($validator->validate(false));
    }

    public function testStringValidator()
    {
        $validator = v::string()->minLength(2)->maxLength(8);

        $this->assertFalse($validator->validate("1"));
        $this->assertFalse($validator->validate("alphanumeric"));
        $this->assertFalse($validator->validate(true));

        $this->assertTrue($validator->validate(33));
        $this->assertTrue($validator->validate("220281"));
        $this->assertTrue($validator->validate("alphabet"));

        $validator = v::string();
        $this->assertTrue($validator->validate(123456));

        $validator = v::string(true);
        $this->assertFalse($validator->validate(123456));
    }

    public function testStringValidatorRegularExpression()
    {
        $validator = v::string()->notNull()->pattern("/\d{3}-\d{8}-\w{2}/");

        $this->assertTrue($validator->validate("111-12345678-abc"));
        $this->assertFalse($validator->validate("11a-12345678-abc"));
        $this->assertFalse($validator->validate(null));
        $this->assertFalse($validator->validate(1111234567000));
    }

    public function testNumericValidator()
    {
        $validator = v::numeric()->positive();

        $this->assertFalse($validator->validate(-4));
        $this->assertFalse($validator->validate(PHP_INT_MIN));
        $this->assertFalse($validator->validate(0));
        $this->assertTrue($validator->validate(1));
        $this->assertTrue($validator->validate(PHP_INT_MAX));

        $validator = v::float()->nonNegative()->between(12, 20);
        $this->assertFalse($validator->validate(-1));
        $this->assertFalse($validator->validate(1));
        $this->assertTrue($validator->validate(12));
        $this->assertTrue($validator->validate(20));
        $this->assertFalse($validator->validate(21));
    }

    public function testIntegerValidator()
    {
        $validator = v::integer()->nonNegative()->between(12, 20);
        $this->assertFalse($validator->validate(-1));
        $this->assertFalse($validator->validate(0.0));
        $this->assertFalse($validator->validate(1.1));
        $this->assertFalse($validator->validate(1));
        $this->assertTrue($validator->validate(12));
        $this->assertTrue($validator->validate(20));
        $this->assertFalse($validator->validate(21));

        $validator = v::integer()->min(-10)->max(0);
        $this->assertTrue($validator->validate(-3));
        $this->assertTrue($validator->validate(0));
        $this->assertTrue($validator->validate(-10));
        $this->assertFalse($validator->validate(3));
    }

    public function testInListRule()
    {
        $validator = v::string()->inList([
            "alpha",
            "beta",
            "gamma",
            1,
            0
        ]);

        $this->assertTrue($validator->validate("alpha"));
        $this->assertTrue($validator->validate("beta"));
        $this->assertTrue($validator->validate("gamma"));
        $this->assertTrue($validator->validate("gamma"));
        $this->assertFalse($validator->validate("Gamma"));
    }

    public function testObjectValidator()
    {
        $validator = v::object([
            "id" => v::integer()->positive()->required(),
            "username" => v::string()->between(3, 20)->required(),
            "password" => v::string()->minLength(8)->required(),
            "height" => v::float(),
        ]);

        $result = $validator->validate((object) [
            "id" => 3,
            "username" => "ifcanduela",
            "password" => "22021981",
            "height" => null,
        ]);

        $this->assertTrue($result);
        $this->assertFalse($validator->validate([1, 2, 3]));

        $result = $validator->validate((object) [
            "id" => 3,
            "username" => "ifcanduela",
            "password" => "",
            "height" => null,
        ]);
        $this->assertFalse($result);
        $this->assertEquals(1, count($validator->getErrors()));

        foreach ($validator->getErrors() as $prop => $rules) {
            foreach ($rules as $rule) {
                $this->assertEquals("password", $prop);
                $this->assertInstanceOf(\pew\model\validator\rule\MinLength::class, $rule);
            }
        }
    }

    public function testArrayValidator()
    {
        $validator = v::array([
            "id" => v::integer()->positive()->required(),
            "username" => v::string()->between(3, 20)->required(),
            "password" => v::string()->minLength(8)->required(),
            "height" => v::float(),
        ]);

        $result = $validator->validate([
            "id" => 3,
            "username" => "ifcanduela",
            "password" => "22021981",
            "height" => null,
        ]);
        $this->assertTrue($result);

        $result = $validator->validate(new ArrayLike);
        $this->assertTrue($result);
    }

    public function testCallbackRule()
    {
        $validator = v::integer()->callback(function ($value) {
            return $value === 3 ? false : null;
        });

        $this->assertTrue($validator->validate(4));
        $this->assertFalse($validator->validate(3));
    }

    public function testCanBeNullRule()
    {
        $validator = v::string()->minLength(4)->canBeNull();

        $this->assertTrue($validator->validate(null));
        $this->assertTrue($validator->validate("12345"));
        $this->assertFalse($validator->validate(""));
    }

    public function testContainsRule()
    {
        $validator = v::array()->contains("alpha");

        $this->assertTrue($validator->validate(["alpha", "beta", "gamma"]));
        $this->assertFalse($validator->validate(["Alpha", 2, 3]));
    }

    public function testCountRule()
    {
        $validator = v::array()->minCount(2)->maxCount(4);

        $this->assertTrue($validator->validate([1, 2]));
        $this->assertFalse($validator->validate([1]));
        $this->assertFalse($validator->validate([1, 2, 3, 4, 6]));

        $validator = v::array()->count(1);

        $this->assertTrue($validator->validate([1]));
        $this->assertFalse($validator->validate([1, 2]));
        $this->assertFalse($validator->validate([]));
    }

    public function testIsEmailRule()
    {
        $validator = v::string()->isEmail();

        $this->assertTrue($validator->validate("ifcanduela@example.com"));
        $this->assertFalse($validator->validate("example.com"));
    }

    public function testNegativeRule()
    {
        $validator = v::integer()->negative();

        $this->assertTrue($validator->validate(-1));
        $this->assertFalse($validator->validate(0));
        $this->assertFalse($validator->validate(2));
    }
}
