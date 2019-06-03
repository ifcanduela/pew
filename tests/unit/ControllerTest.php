<?php

require_once dirname(__DIR__) . '/fixtures/controllers/TestController.php';

use app\controllers\TestController;
use pew\View;
use pew\request\Middleware;

class TestMiddleware extends Middleware {}

class ControllerTest extends PHPUnit\Framework\TestCase
{
    public function getRequestMock($action, $args)
    {
        $request = $this->getMockBuilder('\pew\request\Request')
                    ->disableOriginalConstructor()
                    ->getMock();

        $request->expects($this->any())
                ->method('action')
                ->willReturn($action);

        $request->expects($this->any())
                ->method('args')
                ->willReturn($args);

        return $request;
    }

    public function testController()
    {
        $request = $this->getMockBuilder('\pew\request\Request')
                    ->disableOriginalConstructor()
                    ->getMock();

        $view = new View();

        $controller = new TestController($request, $view);

        $response = $controller->myAction();

        $this->assertInstanceOf(Symfony\Component\HttpFoundation\JsonResponse::class, $response);
        $this->assertEquals('"myAction"', $response->getContent());
    }

    public function testControllerRedirect()
    {
        $request = $this->getMockBuilder('\pew\request\Request')
                    ->disableOriginalConstructor()
                    ->getMock();

        $c = new TestController($request, new View());

        $response = $c->redirect("/user/profile");

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
        $this->assertEquals("/user/profile", $response->getTargetUrl());
    }

    public function testControllerRender()
    {
        $request = $this->getMockBuilder('\pew\request\Request')
                    ->disableOriginalConstructor()
                    ->getMock();

        $view = new View(__DIR__ . "/../fixtures/views");
        $c = new TestController($request, $view);

        $response = $c->render("partial", ["value" => 1]);

        $this->assertInstanceOf(\pew\View::class, $response);
        $this->assertEquals("1", $response->content());
    }

    public function testMiddlewareRedirect()
    {
        $m = new TestMiddleware();
        $response = $m->redirect("/accounts/edit/1");

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
        $this->assertEquals("/accounts/edit/1", $response->getTargetUrl());
    }
}
