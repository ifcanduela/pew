<?php

use pew\router\RouteBuilder as R;

R::from("/callback")->to(function () {
    return "callback response";
});

R::from("/admin/index")->to("admin@index")->namespace("admin");
R::from("/name/index")->to("admin\\admin@index");

R::from("/middleware")
    ->to("TestController@useMiddleware")
    ->before([\fixtures\services\MiddlewareTest::class])
    ->after([\fixtures\services\MiddlewareTest::class]);

R::get("/test/{action}")->to("TestController");
