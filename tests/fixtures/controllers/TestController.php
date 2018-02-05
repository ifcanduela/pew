<?php

namespace tests\fixtures\controllers;

class TestController extends \pew\Controller
{
    public function my_action()
    {
        return $this->renderJson('myAction');
    }

    public function template_response()
    {
        return [
            'hello' => 'world',
        ];
    }

    public function json_response()
    {
        return $this->renderJson([
            'hello' => 'world',
        ]);
    }

    public function string_response()
    {
        return 'response';
    }

    public function false_response()
    {
        return false;
    }
}
