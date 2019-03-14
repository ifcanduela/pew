<?php

namespace app\services;

class MiddlewareTest
{
    public $property = "none";

    public function before()
    {
        $this->property = "before";
    }

    public function after()
    {
        echo $this->property;
    }
}
