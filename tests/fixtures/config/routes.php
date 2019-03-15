<?php

use pew\router\Route;

return [
    '/callback' => function () {
        return 'callback response';
    },

    Route::from("/admin/index")->to("admin@index")->namespace("admin"),
    Route::from("/name/index")->to("admin\\admin@index"),

    Route::from('/middleware')
        ->to('TestController@useMiddleware')
        ->before([\fixtures\services\MiddlewareTest::class])
        ->after([\fixtures\services\MiddlewareTest::class]),

    '/test/{action}' => 'TestController',
];
