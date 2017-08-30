<?php

namespace app\services;

class MiddlewareTest
{
    public $property;

    public function before()
    {
        $this->property = 'before';
    }

    public function after()
    {
        echo $this->property;
    }
}
