<?php

use \pew\libs\Env;
use \pew\libs\Router;
use \pew\libs\Request;

class RequestTest extends PHPUnit_Framework_TestCase
{
    public $_GET = [
        'order' => 'id DESC',
        'limit' => '10',
    ];

    public $_POST = [
        'id' => '22',
        'name' => 'Project #1',
        'date' => '2011-09-30',
    ];

    public $_COOKIE = [
        'PHP_SESSID' => 'abcdefg0123456789',
        'login' => '1',
    ];

    public $_FILES = [
        'file1' => [
            'filename' => 'file1.png',
            'tmpname' => 'c09pn1290un123',
        ],
        'file2' => [
            'filename' => 'file2.jpg',
            'tmpname' => '123124das213',
        ],
    ];

    public $routes = [
        ['/profile', '/users/@view'],
        ['/login', '/users/login'],
        ['/project/#id', '/projects/:details/!id'],
        ['/', '/projects/index'],
    ];

    public function testConstruct()
    {
        $env = new Env;
        $request = new Request(new Router, $env);

        $this->assertEquals($env->local, $request->is_localhost());
        $this->assertEquals($env->headers, $request->headers());
        $this->assertEquals($env->scheme, $request->scheme());
        $this->assertEquals($env->host, $request->hostname());
        $this->assertEquals($env->port, $request->port());
        $this->assertEquals($env->path, $request->path());
        $this->assertEquals($env->script, $request->script_name());
    }

    public function testGetAccess()
    {
        $env = new Env;
        $env->method = 'GET';
        $env->get = $this->_GET;
        $request = new Request(new Router, $env);
        
        $this->assertTrue($request->is_get());
        $this->assertEquals(Env::GET, $request->method());
        $this->assertEquals($this->_GET, $request->get());
        $this->assertEquals($this->_GET['order'], $request->get('order'));
        $this->assertEquals($this->_GET['limit'], $request->get('limit'));
        $this->assertNull($request->get('group'));
        $this->assertEquals('products', $request->get('group', 'products'));
    }

    public function testPostAccess()
    {
        $env = new Env;
        $env->method = 'POST';
        $env->post = $this->_POST;
        $request = new Request(new Router, $env);

        $this->assertTrue($request->is_post());
        $this->assertEquals(Env::POST, $request->method());
        $this->assertEquals($this->_POST, $request->post());
        $this->assertEquals($this->_POST['id'], $request->post('id'));
        $this->assertEquals($this->_POST['name'], $request->post('name'));
        $this->assertNull($request->post('username'));
        $this->assertEquals('admin', $request->post('username', 'admin'));
    }

    public function testInputAccess()
    {
        $env = $this->getMock('\pew\libs\Env', array('data'));
        $env->method = 'PUT';
        $env->input = $this->_POST;
        $request = new Request(new Router, $env);

        $this->assertEquals('PUT', $request->method());
        $this->assertEquals($this->_POST, $request->input());
        $this->assertEquals($this->_POST['id'], $request->input('id'));
        $this->assertEquals($this->_POST['name'], $request->input('name'));
        $this->assertNull($request->input('username'));
        $this->assertEquals('admin', $request->input('username', 'admin'));
    }

    public function testCookieAccess()
    {
        $env = new Env;
        $env->cookie = $this->_COOKIE;
        $request = new Request(new Router, $env);


        $this->assertEquals($this->_COOKIE, $request->cookie());
        $this->assertEquals($this->_COOKIE['PHP_SESSID'], $request->cookie('PHP_SESSID'));
        $this->assertEquals($this->_COOKIE['login'], $request->cookie('login'));
        $this->assertNull($request->cookie('username'));
        $this->assertEquals('admin', $request->cookie('username', 'admin'));
    }

    public function testFilesAccess()
    {
        $env = new Env;
        $env->files = $this->_FILES;
        $request = new Request(new Router, $env);

        $this->assertEquals($this->_FILES, $request->files());
        $this->assertEquals($this->_FILES['file1'], $request->files('file1'));
        $this->assertEquals($this->_FILES['file2'], $request->files('file2'));
        $this->assertNull($request->files('username'));
        $this->assertEquals([], $request->files('file3', []));
    }

    public function testUndefinedAccess()
    {
        $env = new Env;
        $request = new Request(new Router, $env);

        $this->assertNull($request->data('PUT', 'field'));
        $this->assertTrue($request->data('DELETE', 'field', true));
    }

    public function testRouteController()
    {
        $router = new Router($this->routes);
        
        $request = new Request($router->route('/project/22'), new Env);
        $this->assertEquals('projects', $request->controller());
        
        $request = new Request($router->route('/login'), new Env);
        $this->assertEquals('users', $request->controller());
        
        $request = new Request($router->route('/'), new Env);
        $this->assertEquals('projects', $request->controller());
    }
    
    public function testRouteAction()
    {
        $router = new Router($this->routes);
        
        $request = new Request($router->route('/project/22'), new Env);
        $this->assertEquals('details', $request->action());
        
        $request = new Request($router->route('/login'), new Env);
        $this->assertEquals('login', $request->action());
        
        $request = new Request($router->route('/'), new Env);
        $this->assertEquals('index', $request->action());
    }

    public function testRouteArguments()
    {
        $router = new Router($this->routes);
        $request = new Request($router->route('/project/22'), new Env);

        $this->assertEquals(['22'], $request->args());
        $this->assertEquals('22', $request->arg(0));
        $this->assertNull($request->arg(1));
        $this->assertNull($request->arg(2));
    }
    
    public function testRouteUriAndDestination()
    {
        $router = new Router($this->routes);
        
        $request = new Request($router->route('/project/22'), new Env);
        $this->assertEquals('/project/22', $request->uri());
        $this->assertEquals('/projects/details/22', $request->destination());
        
        $request = new Request($router->route('/login'), new Env);
        $this->assertEquals('/login', $request->uri());
        $this->assertEquals('/users/login', $request->destination());
        
        $request = new Request($router->route('/'), new Env);
        $this->assertEquals('/', $request->uri());
        $this->assertEquals('/projects/index', $request->destination());
    }
    
    public function testRouteResponseType()
    {
        $router = new Router($this->routes);
        
        $request = new Request($router->route('/project/22'), new Env);
        $this->assertEquals(Router::JSON, $request->response_type());
        $this->assertTrue($request->is_json());
        $this->assertFalse($request->is_html());
        $this->assertFalse($request->is_xml());
        
        $request = new Request($router->route('/login'), new Env);
        $this->assertEquals(Router::HTML, $request->response_type());
        $this->assertTrue($request->is_html());
        $this->assertFalse($request->is_json());
        $this->assertFalse($request->is_xml());

        $request = new Request($router->route('/profile'), new Env);
        $this->assertEquals(Router::XML, $request->response_type());
        $this->assertTrue($request->is_xml());
        $this->assertFalse($request->is_json());
        $this->assertFalse($request->is_html());
    }
}
