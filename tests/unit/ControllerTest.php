<?php

namespace app\controllers {
    use pew\Controller;

    class HasAction extends Controller
    {
        public function test_action($id)
        {
            return [$id, 2, 3];
        }
    }

    class OverridesInvoke extends Controller
    {
        public function __invoke(\pew\libs\Request $request)
        {
            return $request->args();
        }
    }
}

namespace {
    class ControllerTest extends PHPUnit_Framework_TestCase
    {
        public function getRequestMock($action, $args)
        {
            $request = $this->getMockBuilder('\pew\libs\Request')
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

        public function testDefaultController()
        {
            $request = $this->getRequestMock('test_action', [4, 2, 3]);
            $test1 = new \app\controllers\HasAction();

            $result = $test1($request);

            $this->assertEquals(4, $result[0]);
            $this->assertEquals(2, $result[1]);
            $this->assertEquals(3, $result[2]);
            $this->assertFalse(array_key_exists(3, $result));
        }

        public function testOverridenController()
        {
            $request = $this->getRequestMock('does_not_matter', ['Jack', 'Kate', 'Hugo', 'Sawyer']);
            $test2 = new \app\controllers\OverridesInvoke();

            $result = $test2($request);

            $this->assertEquals('Jack', $result[0]);
            $this->assertEquals('Kate', $result[1]);
            $this->assertEquals('Hugo', $result[2]);
            $this->assertEquals('Sawyer', $result[3]);
        }

        /**
         * @expectedException \pew\ControllerActionMissingException
         */
        public function testControllerActionDoesNotExist()
        {
            $request = $this->getRequestMock('does_not_exist', []);
            $test1 = new \app\controllers\HasAction();

            $test1($request);
        }
        
        public function testSlugsAreCorrect()
        {
            $has_action = new \app\controllers\HasAction();
            $overrides_invoke = new \app\controllers\OverridesInvoke();
            
            $this->assertEquals('has_action', $has_action->slug());
            $this->assertEquals('overrides_invoke', $overrides_invoke->slug());
        }
    }
}
