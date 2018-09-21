<?php

use pew\router\Route;

return [
    '/callback' => function () {
        return 'callback response';
    },

    Route::from('/middleware')
        ->to('TestController@useMiddleware')
        ->before([\fixtures\services\MiddlewareTest::class])
        ->after([\fixtures\services\MiddlewareTest::class]),

    '/test/{action}' => 'TestController',
];
