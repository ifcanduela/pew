<?php

use pew\router\Router;
use pew\router\Route;

class RouterTest extends PHPUnit\Framework\TestCase
{
    public function testRoutesFromArray()
    {
        $routes = [
            ['path' => '/', 'handler' => 'HomeController@index'],
        ];

        $router = new Router($routes);

        $destination = $router->route('/', 'GET');

        $this->assertInstanceOf(Route::class, $destination);
        $this->assertEquals('HomeController@index', $destination->getHandler());
    }

    public function testRoutesFromRoute()
    {
        $routes = [
            Route::from('/')->to('HomeController@index'),
        ];

        $router = new Router($routes);

        $destination = $router->route('/', 'GET');

        $this->assertInstanceOf(Route::class, $destination);
        $this->assertEquals('HomeController@index', $destination->getHandler());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Route not found
     */
    public function testRouteNotFound()
    {
        $routes = [
            ['path' => '/', 'handler' => 'HomeController@index'],
        ];

        $router = new Router($routes);

        $destination = $router->route('/not-found', 'GET');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Method not allowed
     */
    public function testMethodNotAllowed()
    {
        $routes = [
            ['path' => '/', 'handler' => 'HomeController@index', 'methods' => 'POST'],
        ];

        $router = new Router($routes);

        $destination = $router->route('/', 'GET');
    }

    public function testRouteGroup()
    {
        $routes = [
            Route::group()->routes([
                    Route::from('[/{action}]')
                ])->to('AdminController')->prefix('/admin'),
        ];

        $router = new Router($routes);

        $destination = $router->route('/admin/index', 'GET');

        $this->assertInstanceOf(Route::class, $destination);
        $this->assertEquals('AdminController', $destination->getHandler());
    }

    public function testRouteGroupMiddleware()
    {
        $routes = Route::group()->routes([
                    [
                        'path' => '[/{action}]',
                        'before' => ['BeforeMiddleware2'],
                        'after' => ['AfterMiddleware2'],
                    ]
                ])->to('AdminController')
                ->prefix('/admin')
                ->before(['BeforeMiddleware1'])
                ->after(['AfterMiddleware1'])
                ->getRoutes();

        $this->assertEquals(['BeforeMiddleware1', 'BeforeMiddleware2'], $routes[0]->getBefore());
        $this->assertEquals(['AfterMiddleware1', 'AfterMiddleware2'], $routes[0]->getAfter());
    }
}
