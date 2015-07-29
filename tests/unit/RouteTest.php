<?php

use \pew\router\Route;

class RouteTest extends PHPUnit_Framework_TestCase
{
    public function testBasicRouteMatch()
    {
        // a route that matches a two-segment URI with the first segment being 'alpha'
        // and the second being 'beta' and points to 'alpha/beta'
        $r = new Route('/alpha/beta', 'alpha/beta');
        $this->assertFalse($r->match('/alpha/delta'));
        $this->assertTrue($r->match('/alpha/beta'));
    }

    public function testPlaceholderRouteMatch()
    {
        // a route that matches a two-segment URI with the first segment being 'alpha'
        // and points to 'alpha/gamma'
        $r = new Route('/alpha/{beta}', 'alpha/gamma');
        $this->assertFalse($r->match('/beta/delta'));
        $this->assertTrue($r->match('/alpha/delta'));
    }

    public function testPlaceholderSubstitution()
    {
        // a route that matches a two-segment URI with the first segment being 'alpha'
        // and the second being used as the action
        $r = new Route('/alpha/{beta}', 'alpha/{beta}');
        $this->assertFalse($r->match('/beta/delta'));
        $this->assertTrue($r->match('/alpha/delta'));
        $this->assertEquals(['beta' => 'delta'], $r->args());
        $this->assertEquals('alpha/delta', $r->to());
    }

    public function testDefaultValues()
    {
        // a route that matches a two-segment or three-segment URI with the first segment 
        // being 'alpha', the second being used as the action and the third being an 
        // 'id' parameter
        $r = Route::create('/alpha/{beta}/{id}', 'alpha/{beta}');

        $this->assertFalse($r->match('/alpha/delta'));

        // make 'id' optional
        $r->with('id', 1234);
        
        $this->assertTrue($r->match('/alpha/delta'));
        $this->assertEquals(['id' => 1234, 'beta' => 'delta'], $r->args());

        $this->assertTrue($r->match('/alpha/gamma/2'));
        $this->assertEquals(['id' => '2', 'beta' => 'gamma'], $r->args());
    }

    public function testWildcard()
    {
        // a route that matches an arbitrary amount of segments
        $r = Route::create('/*', 'alpha/beta');

        $this->assertTrue($r->match('/gamma/delta/epsilon'));
        $this->assertEquals(['gamma', 'delta', 'epsilon'], $r->splat());
    }

    public function testCallback()
    {
        $r = new Route('/alpha', function () { return 1234; });
        
        $this->assertTrue($r->match('/alpha'));
        $to = $r->to();
        $this->assertEquals(1234, $to());
    }

    public function testConstraints()
    {
        // a route that matches a numeric argument only
        $r = new Route('/{gamma}', 'alpha/beta');
        $r->constrain('gamma', '[0-9]+');
        
        $this->assertFalse($r->match('/delta'));

        $this->assertTrue($r->match('/1234'));

        // a route that matches a partial string
        $r = new Route('/{gamma}', 'alpha/beta');
        $r->constrain('gamma', '.+elt.+');
        
        $this->assertFalse($r->match('/dellta'));

        $this->assertTrue($r->match('/delta'));

        // a route with multiple constraints
        $r = new Route('/{gamma}/import/{epsilon}', 'alpha/beta');
        $r
            ->constrain('gamma', '[0-9]+')
            ->constrain('epsilon', 'mu|nu');
        
        $this->assertFalse($r->match('/alpha/import/mu'));
        $this->assertFalse($r->match('/alpha/12/nu'));
        $this->assertFalse($r->match('/12/beta/nu'));

        $this->assertTrue($r->match('/12/import/nu'));
        $this->assertTrue($r->match('/0/import/mu'));
    }
}
