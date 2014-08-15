<?php

use pew\libs\Validator;

class TestValidator extends PHPUnit_Framework_TestCase
{
    public function testCustomValidationMessage()
    {
        $rules = [
            'name' => [
                'max_length' => 3,
            ],
        ];

        $what = [
            'name' => 'Abcdefghijk',
        ];

        $v = new Validator($rules);

        $result = $v->validate($what);
        $errors = $v->errors();

        $this->assertFalse($result);
        $this->assertEquals('Value of name (Abcdefghijk) does not match max_length (3)', $errors[0][0]);
    }

    public $rules = [
        'name' => [
            'min_length' => 6,
            'max_length' => 15,
        ],
        'password' => [
            'min_length' => 6,
        ],
        'password_confirm' => [
            'compare' => 'password',
        ],
        'email' => [
            'email',
        ],
        'active' => [
            'boolean',
        ],
        'role' => [
            'values' => [0, 1, 2, 3],
        ],
        'postal_code' => [
            'regex' => '/\d{5} ?\w{2}/',
        ],
        'date' => [
            'regex' => '/^(\d{2}|\d{4})-\d{1,2}-\d{1,2}$/',
        ],
        'height' => [
            'number',
        ],
        'something' => [
            'type' => 'int',
        ],
        'a_null_field' => [
            'not_null',
        ]
    ];

    public function testValidItem()
    {
        $item = [
            'name' => 'Igordo',
            'active' => 1,
            'role' => 3,
            'email' => 'ifcanduela@aalpha.beta',
            'password' => 'password',
            'password_confirm' => 'password',
            'postal_code' => '48902AB',
            'date' => '1020-12-5',
            'height' => '1.45',
            'something' => 42,
            'a_null_field' => 0,
        ];

        $validator = new \pew\libs\Validator($this->rules);
        $result = $validator->validate($item);

        $this->assertEquals(0, count($validator->errors()));
    }

    public function testInvalidItem()
    {
        $item = [
            'name' => 'Igordo_12345678901234567890',
            'active' => 'yes',
            'role' => 'admin',
            'email' => 'ifc.anduel.b',
            'password' => 'pass',
            'password_confirm' => 'password',
            'postal_code' => '402_B',
            'date' => '100-12-5',
            'height' => '5\'7"',
            'something' => 3.5,
            'a_null_field' => null,
        ];

        $validator = new \pew\libs\Validator($this->rules);
        $result = $validator->validate($item);

        $this->assertTrue(0 < count($validator->errors()));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid Validator: invalid
     * @return [type] [description]
     */
    public function testInvalidValidator()
    {
        $item = [
            'name' => 'some name'
        ];

        $validator = new \pew\libs\Validator([
            'name' => [
                'invalid'
            ]
        ]);

        $result = $validator->validate($item);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Validation of type none is not available
     * @return [type] [description]
     */
    public function testInvalidTypeValidator()
    {
        $item = [
            'name' => 'some name'
        ];

        $validator = new \pew\libs\Validator([
            'name' => [
                'type' => 'none'
            ]
        ]);

        $result = $validator->validate($item);
    }
}
