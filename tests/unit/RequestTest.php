<?php

use \pew\libs\Env;
use \pew\router\Route;
use \pew\request\Request;

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
        $request = new Request($env, new Route('/', 'controller/action'));

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
        $request = new Request($env, new Route('/', 'controller/action'));
        
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
        $request = new Request($env, new Route('/', 'controller/action'));

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
        $request = new Request($env, new Route('/', 'controller/action'));

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
        $request = new Request($env, new Route('/', 'controller/action'));


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
        $request = new Request($env, new Route('/', 'controller/action'));

        $this->assertEquals($this->_FILES, $request->files());
        $this->assertEquals($this->_FILES['file1'], $request->files('file1'));
        $this->assertEquals($this->_FILES['file2'], $request->files('file2'));
        $this->assertNull($request->files('username'));
        $this->assertEquals([], $request->files('file3', []));
    }

    public function testUndefinedAccess()
    {
        $env = new Env;
        $request = new Request($env, new Route('/', 'controller/action'));

        $this->assertNull($request->data('PUT', 'field'));
        $this->assertTrue($request->data('DELETE', 'field', true));
    }

    public function testRouteController()
    {
        $request = new Request(new Env, new Route('project/22', 'projects/details'));
        $this->assertEquals('projects', $request->controller());
        
        $request = new Request(new Env, new Route('login', 'users/login'));
        $this->assertEquals('users', $request->controller());
        
        $request = new Request(new Env, new Route('/', 'projects/index'));
        $this->assertEquals('projects', $request->controller());
    }
    
    public function testRouteAction()
    {
        $request = new Request(new Env, new Route('/project/22', 'project/details'));
        $this->assertEquals('details', $request->action());
        
        $request = new Request(new Env, new Route('login', 'users/login'));
        $this->assertEquals('login', $request->action());
        
        $request = new Request(new Env, new Route('/', 'projects/index'));
        $this->assertEquals('index', $request->action());
    }

    public function testRouteArguments()
    {
        $route = new Route('/project/{id}', 'projects/details');
        $route->match('project/22');

        $request = new Request(new Env, $route);

        $this->assertEquals(['id' => '22'], $request->args());
        $this->assertEquals('22', $request->arg('id'));
        $this->assertNull($request->arg(1));
        $this->assertNull($request->arg(2));
    }
    
    public function testRouteUriAndDestination()
    {
        $_SERVER['PATH_INFO'] = '/project/22';
        $route1 = new Route('project/22', 'projects/details');
        $route1->match('project/22');

        $request = new Request(new Env, $route1);
        $this->assertEquals('/project/22', $request->uri());
        $this->assertEquals('projects/details', $request->destination());
        
        $_SERVER['PATH_INFO'] = '/login';
        $route2 = new Route('login', 'users/login');
        $route2->match('login');

        $request = new Request(new Env, $route2);
        $this->assertEquals('/login', $request->uri());
        $this->assertEquals('users/login', $request->destination());
        
        $_SERVER['PATH_INFO'] = '/';
        $route3 = new Route('/', 'projects/index');
        $route3->match('/');

        $request = new Request(new Env, $route3);
        $this->assertEquals('/', $request->uri());
        $this->assertEquals('projects/index', $request->destination());
    }
    
    public function testRouteResponseType()
    {
        $_SERVER['PATH_INFO'] = '/project/22';
        $request = new Request(new Env, new Route('/login', 'controller/action'));
        $this->assertEquals(Request::HTML, $request->response_type());
        $this->assertEquals('/project/22', $request->path());
        $this->assertTrue($request->is_html());
        $this->assertFalse($request->is_json());
        $this->assertFalse($request->is_xml());

        $_SERVER['PATH_INFO'] = '/project/23.json';
        $request = new Request(new Env, new Route('/project/23', 'controller/action'));
        $this->assertEquals(Request::JSON, $request->response_type());
        $this->assertEquals('/project/23', $request->path());
        $this->assertFalse($request->is_html());
        $this->assertTrue($request->is_json());
        $this->assertFalse($request->is_xml());
        
        $_SERVER['PATH_INFO'] = '/project/24.html';
        $request = new Request(new Env, new Route('/login', 'controller/action'));
        $this->assertEquals(Request::HTML, $request->response_type());
        $this->assertEquals('/project/24', $request->path());
        $this->assertTrue($request->is_html());
        $this->assertFalse($request->is_json());
        $this->assertFalse($request->is_xml());

        $_SERVER['PATH_INFO'] = '/project/25.xml';
        $request = new Request(new Env, new Route('/profile', 'controller/action'));
        $this->assertEquals(Request::XML, $request->response_type());
        $this->assertEquals('/project/25', $request->path());
        $this->assertFalse($request->is_html());
        $this->assertFalse($request->is_json());
        $this->assertTrue($request->is_xml());

        $_SERVER['PATH_INFO'] = '/project/25.xml';
        $_POST['_format'] = 'json';
        $request = new Request(new Env, new Route('/profile', 'controller/action'));
        $this->assertEquals(Request::JSON, $request->response_type());
        $this->assertEquals('/project/25', $request->path());
        $this->assertFalse($request->is_html());
        $this->assertTrue($request->is_json());
        $this->assertFalse($request->is_xml());
    }
}
