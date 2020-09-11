<?php

/** @var Router $router */

use ifcanduela\router\Router;

$router->from("/callback")->to(function () {
    return "callback response";
});

$router->from("/admin/index")->to("admin@index")->namespace("admin");
$router->from("/name/index")->to("admin\\admin@index");

$router->from("/middleware")
    ->to("TestController@useMiddleware")
    ->before(\fixtures\services\MiddlewareTest::class)
    ->after(\fixtures\services\MiddlewareTest::class);

$router->get("/test/{action}")->to("TestController");
