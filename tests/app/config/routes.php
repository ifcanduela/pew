<?php

use pew\router\Route;

return [
    '/callback' => function () {
        return 'callback response';
    },

    Route::from('/middleware')
        ->to('TestController@use_middleware')
        ->before([\app\services\MiddlewareTest::class])
        ->after([\app\services\MiddlewareTest::class]),

    '/test/{action}' => 'TestController',
];
