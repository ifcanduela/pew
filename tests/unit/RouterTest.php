<?php

use pew\router\Group;
use pew\router\Route;
use pew\router\RouteBuilder;
use pew\router\Router;

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

    public function testRouteName()
    {
        $r = Route::from("/")->to("app@home");

        $r->name("homepage");

        $this->assertEquals("homepage", $r->getName());
    }

    public function testRouteNotFound()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Route not found");

        $routes = [
            ['path' => '/', 'handler' => 'HomeController@index'],
        ];

        $router = new Router($routes);

        $destination = $router->route('/not-found', 'GET');
    }

    public function testMethodNotAllowed()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Method not allowed");

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

    public function testRouteBuilderGroups()
    {
        RouteBuilder::group(function () {
            RouteBuilder::from('[/{action}]');
        })->to('AdminController')->prefix('/admin');

        $routes = RouteBuilder::collect();

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

    public function testNestedRouteGroup()
    {
        $routes = [
            Route::group()->routes([
                Route::group([
                    Route::from("/nested/{action}")
                ]),
                Route::from('[/{action}]'),
            ])->to('AdminController')->prefix('/admin'),
        ];

        $router = new Router($routes);
        $destination = $router->route('/admin/nested/index', 'GET');

        $this->assertInstanceOf(Route::class, $destination);
        $this->assertEquals('AdminController', $destination->getHandler());
    }

    public function testControllerAndActionPlaceholders()
    {
        $routes = [
            Route::from("/{first}/{second}/{id}")
                ->to("{first}@{second}"),
        ];

        $router = new Router($routes);

        $destination = $router->route('/users/profile/1', 'GET');

        $this->assertInstanceOf(Route::class, $destination);
        $this->assertEquals('users@profile', $destination->getHandler());
        $this->assertEquals(1, $destination->getParam("id"));
    }

    public function testRouteDefaultParam()
    {
        $routes = [
            Route::from("/[{action}]")->to("users@{action}")->default("action", "listing"),
        ];

        $router = new Router($routes);

        $route = $router->route("/details", "get");
        $this->assertEquals("users@details", $route->getHandler());

        $route = $router->route("/", "get");
        $this->assertEquals("users@listing", $route->getHandler());
    }

    public function testRouteDefaultParams()
    {
        $routes = [
            Route::from("/[{action}[/{id}]]")->to("users@{action}")->defaults([
                "action" => "listing",
                "id" => 0,
            ]),
        ];

        $router = new Router($routes);

        $route = $router->route("/details/1", "get");
        $this->assertEquals("details", $route["action"]);
        $this->assertEquals(1, $route["id"]);

        $route = $router->route("/details", "get");
        $this->assertEquals("details", $route["action"]);
        $this->assertEquals(0, $route["id"]);

        $route = $router->route("/", "get");
        $this->assertEquals("listing", $route["action"]);
        $this->assertEquals(0, $route["id"]);
    }

    public function testRouteException()
    {
        $route = Route::from("/")->to("users@index");

        try {
            $route["action"] = "view";
        } catch (\Exception $e) {
            $this->assertEquals("Route is read-only: cannot set value `action`", $e->getMessage());
        }

        try {
            unset($route["action"]);
        } catch (\Exception $e) {
            $this->assertEquals("Route is read-only: cannot unset value `action`", $e->getMessage());
        }

        try {
            $route->test();
        } catch (\Exception $e) {
            $this->assertEquals("Method `test` does not exist", $e->getMessage());
        }

        try {
            $route->handler("");
        } catch (\Exception $e) {
            $this->assertEquals("Route handler cannot be empty", $e->getMessage());
        }
    }

    public function testGenerateUrl()
    {
        $routes = [
            Route::from("/admin[/{action}[/{id}]]")->to("admin@index")->name("admin"),
            Route::from("/users/login")->to("users@login")->name("login"),
            Route::from("/")->to("home@index")->name("home"),
        ];

        $router = new Router($routes);

        $this->assertEquals(
            "/admin/my-action/my-id",
            $router->createUrlFromRoute("admin", ["my-action", "my-id"])
        );

        $this->assertEquals(
            "/admin/my-action",
            $router->createUrlFromRoute("admin", ["my-action"])
        );

        $this->assertEquals(
            "/admin",
            $router->createUrlFromRoute("admin")
        );

        $this->assertEquals(
            "/users/login",
            $router->createUrlFromRoute("login")
        );

        $this->assertEquals(
            "/",
            $router->createUrlFromRoute("home")
        );
    }

    public function testIsRoute()
    {
        $routes = [
            Route::from("/admin[/{action}[/{id}]]")->to("admin@index")->name("admin"),
            Route::from("/users/login")->to("users@login")->name("login"),
            Route::from("/")->to("home@index")->name("home"),
        ];

        $router = new Router($routes);

        $this->assertTrue($router->isRoute("admin", "/admin/my-action", "get"));
        $this->assertTrue($router->isRoute("admin", "/admin/my-action", "post"));
        $this->assertTrue($router->isRoute("admin", "/admin/my-action/6", "get"));
        $this->assertTrue($router->isRoute("admin", "/admin/", "get"));

        $this->assertFalse($router->isRoute("login", "/users/login/rediret", "get"));
        $this->assertFalse($router->isRoute("home", "/", "get"));
    }
}
