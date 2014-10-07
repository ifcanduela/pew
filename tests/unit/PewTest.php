<?php

namespace app\controllers {
    class TestClass
    {
        public $a, $b, $c;

        public function __construct($a, $b, $c)
        {
            $this->a = $a;
            $this->b = $b;
            $this->c = $c;
        }
    }

    class TestController extends \pew\Controller
    {
        public $log;

        public function __construct($pew)
        {
            $this->log = $pew['log'];
        }

        public function get_log()
        {
            return $this->log;
        }
    }
}

namespace {
    use pew\Pew;

    class PewTest extends PHPUnit_Framework_TestCase
    {
        public function testNewPew()
        {
            $a = rand(0, 9);

            $p = new Pew([
                'value' => $a,
            ]);

            $this->assertEquals($a, $p['value']);
            $this->assertEquals(null, $p['no_value']);
        }

        public function testResolveController()
        {
            $a = rand(0, 9);
            $b = rand(10, 19);
            $c = rand(20, 29);

            $p = new Pew([
                'a' => function () use($a) { return $a * 2; },
                'b' => $b,
                'c' => $c,
            ]);

            $testController = $p->controller('test_class');

            $this->assertEquals($a * 2, $testController->a);
            $this->assertEquals($b, $testController->b);
            $this->assertEquals($c, $testController->c);
        }

        public function testResolveModel()
        {
            $p = new Pew();

            $testController = $p->controller('test_controller');

            $this->assertInstanceOf('\\pew\\libs\\FileLogger', $testController->get_log());
        }
    }
}
