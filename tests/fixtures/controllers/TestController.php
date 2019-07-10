<?php

namespace app\controllers;

class TestController extends \pew\Controller
{
    public function myAction()
    {
        return $this->json('myAction');
    }

    public function templateResponse()
    {
        return [
            'hello' => 'world',
        ];
    }

    public function jsonResponse()
    {
        return $this->json([
            'hello' => 'world',
        ]);
    }

    public function stringResponse()
    {
        return 'response';
    }

    public function falseResponse()
    {
        return false;
    }
}
