<?php

namespace app\controllers;

class TestController extends \pew\Controller
{
    public function myAction()
    {
        return $this->renderJson('myAction');
    }

    public function templateResponse()
    {
        return [
            'hello' => 'world',
        ];
    }

    public function jsonResponse()
    {
        return $this->renderJson([
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
