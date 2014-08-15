<?php

use \pew\libs\Router;

class RouterTest extends PHPUnit_Framework_TestCase
{
    public $config = [
        ['/tests/:action/:param1', '/controller1/:action1/!action/!param1'], // 
        ['/tests/:action/:param1/#param2', '/controller2/action2/!action/!param1/!param2'], // 
        ['/tests/#id', '/controller3/action3/!id'],
        ['/tests', '/controller4/action4/run'],
        ['/tests/*', '/controller5/action5/*1/*2/*'], // should match /tests/foo/bar and /foo/bar/baz
        ['/foo/*', '/controller6/action6/*'], // should match /foo, /foo/bar and /foor/bar/baz
    ];

    public function testAddRoute()
    {
        $r = new Router;
        $uri = '/';

        $r->add(['/basic', '/basic/basic', 'get post']);
        $r->add(['/route', '/route/route', 'get|post']);
        $r->add(['/', '/basic/route']);

        $r->route($uri);
        $this->assertEquals('basic', $r->controller());
        $this->assertEquals('route', $r->action());
    }

    /**
     * @expectedException Exception
     */
    public function testAddBadRoute()
    {
        $r = new Router;
        $r->add(['/']);
    }

    public function testSimpleMatching()
    {
        $r = new Router($this->config);
        $uri = '/tests/param_1/param_2';

        $route = $r->route($uri, 'get');

        $this->assertEquals('controller1', $route->controller());
        $this->assertEquals('action1', $route->action());
        $this->assertEquals('param_1', $route->parameters(0));
    }

    public function testMatchingIntegerSegment()
    {
        $r = new Router($this->config);
        $uri = '/tests/param_1/param_2/3';
        
        $route = $r->route($uri, 'get');

        $this->assertEquals('controller2', $route->controller());
        $this->assertEquals('action2', $route->action());
        $this->assertEquals('param_1', $route->parameters(0));
        $this->assertEquals(['param_1', 'param_2', '3'], $route->parameters());
    }

    public function testWithTwoSegments()
    {
        $r = new Router($this->config);
        $uri = '/tests/123';

        $route = $r->route($uri, 'get');

        $this->assertEquals('controller3', $route->controller());
        $this->assertEquals('action3', $route->action());
        $this->assertEquals(['123'], $route->parameters());
    }

    public function testWithOneSegment()
    {
        $r = new Router($this->config);
        $uri = '/tests';

        $route = $r->route($uri, 'get');

        $this->assertEquals('controller4', $route->controller());
        $this->assertEquals('action4', $route->action());
        $this->assertEquals('run', $route->parameters(0));
    }

    public function testSequentialPlaceholders()
    {
        $r = new Router($this->config);
        $uri = '/tests/p1/p2/p3/p4/p5/p6';

        $route = $r->route($uri, 'get');

        $this->assertEquals('controller5', $route->controller());
        $this->assertEquals('action5', $route->action());
        $this->assertEquals('p1', $route->parameters(0));
        $this->assertEquals('p2', $route->parameters(1));
        $this->assertEquals('p1', $route->parameters(2));
        $this->assertEquals('p2', $route->parameters(3));
        $this->assertEquals('p3', $route->parameters(4));
        $this->assertEquals('p4', $route->parameters(5));
        $this->assertEquals('p5', $route->parameters(6));
        $this->assertEquals('p6', $route->parameters(7));
    }

    public function testPrefixes()
    {
        $r = new Router();

        $r->token_prefix('^');
        $r->sequence_prefix('_');

        $r->add(['/test/:prefix', '/test/!prefix']);
        $r->route('/test/view/12');

        $this->assertEquals('^', $r->token_prefix());
        $this->assertEquals('_', $r->sequence_prefix());
        
        $this->assertEquals('view', $r->action());
        $this->assertEquals('12', $r->parameters(0));
    }

    public function testOptionalSegments()
    {
        $r = new Router($this->config);
        $uri = '/foo';

        $route = $r->route($uri, 'get');

        $this->assertEquals('controller6', $route->controller());
        $this->assertEquals('action6', $route->action());
        $this->assertEquals(null, $route->parameters(0));

        $uri = '/foo/bar';

        $route = $r->route($uri, 'get');

        $this->assertEquals('controller6', $route->controller());
        $this->assertEquals('action6', $route->action());
        $this->assertEquals('bar', $route->parameters(0));
    }

    public function testRouteUri()
    {
        $r = new Router();
        $r->add(['/*', '/c/a']);

        $r->route('/testing/the/uri/method');

        $this->assertEquals('/testing/the/uri/method', $r->uri());
    }

    public function testResponseTypeHtml()
    {
        $r = new Router();
        $r->add(['/:controller/:action', '/!controller/!action']);
        
        $r->route('c/a');
        $this->assertEquals('html', $r->response_type());

        $r->route('c/:a');
        $this->assertEquals('json', $r->response_type());

        $r->route('c/@a');
        $this->assertEquals('xml', $r->response_type());

        $r->route('c/+a');
        $this->assertEquals('', $r->response_type());
    }

    public function testRootPathWithSlash()
    {
        $r = new Router($this->config);
        $r->add(['/', '/pages/index']);
        $uri = '/';

        $route = $r->route($uri, 'get');

        $this->assertEquals('pages', $route->controller());
        $this->assertEquals('index', $route->action());
        $this->assertEquals(null, $route->parameters(0));
    }

    public function testRootPathWithoutSlash()
    {
        $r = new Router($this->config);
        $uri = '';

        $route = $r->route($uri, 'get');

        $this->assertEquals('', $route->controller());
        $this->assertEquals('', $route->action());
        $this->assertEquals(null, $route->parameters(0));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBadDefaultController()
    {
        $r = new Router;
        $r->default_controller(new stdClass);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBadDefaultAction()
    {
        $r = new Router;
        $r->default_action(false);
    }

    public function testDefaultControllerAndAction()
    {
        $r = new Router($this->config);
        $uri = '';

        $r->default_controller('my_default_controller');
        $r->default_action('my_default_action');
        $this->assertEquals('my_default_controller', $r->default_controller());
        $this->assertEquals('my_default_action', $r->default_action());

        $route = $r->route($uri, 'get');

        $this->assertEquals('my_default_controller', $route->controller());
        $this->assertEquals('my_default_action', $route->action());
        $this->assertEquals(null, $route->parameters(0));
    }

    public function testJsonAction()
    {
        $r = new Router($this->config);
        $uri = '/a_controller/:an_action/123';

        $r->default_controller('my_default_controller');
        $r->default_action('my_default_action');

        $route = $r->route($uri, 'get');

        $this->assertEquals('a_controller', $route->controller());
        $this->assertEquals('an_action', $route->action());
        $this->assertEquals('123', $route->parameters(0));
    }

    public function testDefaultRouteWithArgumentsEqualToZero()
    {
        $r = new Router;

        $route = $r->route('/notes/:index/0');

        $this->assertEquals('notes', $route->controller());
        $this->assertEquals('index', $route->action());
        $this->assertEquals(['0'], $route->parameters());
        $this->assertEquals('0', $route->parameters(0));
    }
}
