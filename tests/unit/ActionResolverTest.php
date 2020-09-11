<?php

use ifcanduela\router\Route;
use pew\request\ActionResolver;

class ActionResolverTest extends PHPUnit\Framework\TestCase
{
    public function testResolveController()
    {
        $r = new Route();
        $r->to("test@index");

        $resolver = new ActionResolver($r);

        $controllerClass = $resolver->getController("\\app\\controllers\\");
        $this->assertEquals("\\app\\controllers\\TestController", $controllerClass);
    }

    public function testResolveNamespacedController()
    {
        $r = new Route();
        $r->to("admin@index");
        $r->namespace("admin");

        $app = new ActionResolver($r);

        $controllerClass = $app->getController("app\\controllers");
        $this->assertEquals("\\app\\controllers\\admin\\AdminController", $controllerClass);

        $r = new Route();
        $r->to("admin\\admin@index");

        $app = new ActionResolver($r);

        $controllerClass = $app->getController("app\\controllers");
        $this->assertEquals("\\app\\controllers\\admin\\AdminController", $controllerClass);
    }


    public function testMissingController()
    {
        $r = new Route();
        $r->to("noController@index");

        $app = new ActionResolver($r);

        try {
            $app->getController("app\\controllers");
        } catch (\Exception $e) {
            $this->assertEquals("No controller found for handler `noController@index`", $e->getMessage());
        }
    }
}
