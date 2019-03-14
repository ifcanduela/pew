<?php

require_once dirname(__DIR__) . '/fixtures/controllers/TestController.php';

use app\controllers\TestController;

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

        $view = new \pew\View();

        $controller = new TestController($request, $view);

        $response = $controller->myAction();

        $this->assertInstanceOf(Symfony\Component\HttpFoundation\JsonResponse::class, $response);
        $this->assertEquals('"myAction"', $response->getContent());
    }
}
