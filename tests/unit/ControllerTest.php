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

        $this->assertInstanceOf(\pew\response\JsonResponse::class, $response);
        $this->assertStringContainsString('"myAction"', (string) $response);
    }

    public function testControllerRedirect()
    {
        $request = $this->getMockBuilder('\pew\request\Request')
                    ->disableOriginalConstructor()
                    ->getMock();

        $c = new TestController($request, new View());

        $response = $c->redirect("/user/profile");

        $this->assertInstanceOf(\pew\response\RedirectResponse::class, $response);
        $responseBody = (string) $response;
        $this->assertStringContainsString("HTTP", $responseBody);
        $this->assertStringContainsString("302", $responseBody);
    }

    public function testControllerRender()
    {
        $request = $this->getMockBuilder('\pew\request\Request')
                    ->disableOriginalConstructor()
                    ->getMock();

        $view = new View(__DIR__ . "/../fixtures/views");
        $c = new TestController($request, $view);

        $response = $c->render("partial", ["value" => 1]);

        $this->assertInstanceOf(\pew\response\Response::class, $response);

        $responseBody = (string) $response;
        $this->assertStringContainsString("200 OK", $responseBody);
        $this->assertStringContainsString("Cache-Control", $responseBody);
        $this->assertStringEndsWith("1", $responseBody);
    }

    public function testMiddlewareRedirect()
    {
        $m = new TestMiddleware();
        $response = $m->redirect("/accounts/edit/1");
        $this->assertInstanceOf(\pew\response\RedirectResponse::class, $response);

        $symfonyResponse = $response->getSymfonyResponse();
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $symfonyResponse);
        $this->assertEquals("/accounts/edit/1", $symfonyResponse->getTargetUrl());
    }
}
