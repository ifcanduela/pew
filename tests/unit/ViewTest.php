<?php

use pew\View;

function rn($text) {
    return str_replace("\r", "", $text);
}

class ViewTest extends PHPUnit\Framework\TestCase
{
    public function testVoidConstructor()
    {
        $v = new View();

        $this->assertEquals(getcwd(), $v->folder());
    }

    public function testBasics()
    {
        $v = new View(__DIR__ . '/../fixtures/views');

        $this->assertEmpty($v->title());

        $this->assertTrue($v->exists('view1'));
        $this->assertFalse($v->exists('nope'));

        $result = $v->render('view1', ['parameter' => 'PARAMETER', 'property' => 'PROPERTY']);
$this->assertEquals("PARAMETER", $v->get("parameter"));

        $this->assertEquals(rn('<div>PARAMETER</div>
<div>PROPERTY</div>
<div>PROPERTY</div>
'), rn($result));

        $v->layout('layout');
        $v->title('test');
        $result = $v->render('view1', ['parameter' => 'PARAMETER', 'property' => 'PROPERTY']);

        $this->assertEquals(rn('<title>test</title>
<div>PARAMETER</div>
<div>PROPERTY</div>
<div>PROPERTY</div>
'), rn($result));
    }

    public function testRenderWithoutTemplateName()
    {
        $v = new View(__DIR__ . '/../fixtures/views');

        $this->assertEmpty($v->title());

        $this->assertTrue($v->exists('view1'));
        $this->assertFalse($v->exists('nope'));

        $v->template('view1');

        $result = $v->render(['parameter' => 'PARAMETER', 'property' => 'PROPERTY']);

        $this->assertEquals(rn('<div>PARAMETER</div>
<div>PROPERTY</div>
<div>PROPERTY</div>
'), rn($result));

        $v->layout('layout');
        $v->title('test');
        $result = $v->render('view1', ['parameter' => 'PARAMETER', 'property' => 'PROPERTY']);

        $this->assertEquals(rn('<title>test</title>
<div>PARAMETER</div>
<div>PROPERTY</div>
<div>PROPERTY</div>
'), rn($result));
    }

    public function testRenderWithoutLayout()
    {
        $v = new View(__DIR__ . '/../fixtures/views');

        $v->layout('layout');
        $v->layout(false);
        $result = $v->render('view1', ['parameter' => 'PARAMETER', 'property' => 'PROPERTY']);

        $this->assertEquals(rn('<div>PARAMETER</div>
<div>PROPERTY</div>
<div>PROPERTY</div>
'), rn($result));
    }

    public function testRenderExceptions()
    {
        $v = new View(__DIR__ . "/../fixtures/views");

        try {
            $v->render(null, [])->toString();
        } catch (\RuntimeException $e) {
            $this->assertEquals($e->getMessage(), "No template specified");
        }

        $v->layout("missing");

        try {
            $v->render("missing", ["parameter" => 1, "property" => 2])->toString();
        } catch (\RuntimeException $e) {
            $this->assertEquals($e->getMessage(), "Template `missing` not found");
        }

        $v->template("view1");

        try {
            $v->render(null, ["parameter" => 1, "property" => 2])->toString();
        } catch (\RuntimeException $e) {
            $this->assertEquals($e->getMessage(), "Layout `missing` not found");
        }

        try {
            $v->render("throws")->toString();
        } catch (\Exception $e) {
            $this->assertEquals("thrown", $e->getMessage());
            $this->assertEquals("", ob_get_contents());
        }
    }

    public function testRenderPartial()
    {
        $v = new View(__DIR__ . "/../fixtures/views");

        try {
            $v->insert("missing");
        } catch (\RuntimeException $e) {
            $this->assertEquals($e->getMessage(), "Partial template `missing` not found");
        }

        $html = $v->insert("partial", ["value" => 1]);
        $this->assertEquals("1", $html);
    }

    public function testEscape()
    {
        $v = new View();

        $this->assertNotEquals(
            "Hello, my name is <script>Pew</script>",
            $v->escape("Hello, my name is <script>Pew</script>")
        );

        $this->assertEquals(
            "Hello, my name is &lt;script&gt;Pew&lt;/script&gt;",
            $v->escape("Hello, my name is <script>Pew</script>")
        );
    }

    public function testFluentInterface()
    {
        $v = new View(__DIR__ . "/../fixtures/views");

        $result = $v
            ->set('parameter', 'PARAMETER')
            ->set('property', 'PROPERTY')
            ->title("test title")
            ->template("view1")
            ->layout("layout")
            ->render();

        $this->assertEquals(rn('<title>test title</title>
<div>PARAMETER</div>
<div>PROPERTY</div>
<div>PROPERTY</div>
'), rn((string) $result));
    }

    public function testDataPropertyHandling()
    {
        $v = new View();
        $v->set("alpha", "ALPHA");

        $this->assertTrue($v->has("alpha"));
        $this->assertEquals($v->get("alpha"), "ALPHA");

        $this->assertFalse($v->has("beta"));
    }

    public function testTemplateExists()
    {
        $v = new View(__DIR__ . "/../fixtures/views");

        $this->assertTrue($v->exists("view1"));
        $this->assertTrue($v->exists("view1"));

        $v = new View(__DIR__ . "/../fixtures/views");

        try {
            $v->exists();
        } catch (\Exception $e) {
            $this->assertEquals($e->getMessage(), "No template specified");
        }

        $v->template("not");
        $this->assertFalse($v->exists());
    }

    public function testBlocks()
    {
        $v = new View();

        // test a non-existing block
        $this->assertFalse($v->hasBlock("alpha"));
        $this->assertEquals($v->block("alpha"), "");

        // test a basic block
        $v->beginBlock("alpha");
        echo "ALPHA";
        $v->endBlock();

        $this->assertTrue($v->hasBlock("alpha"));
        $this->assertFalse($v->hasBlock("beta"));
        $this->assertEquals($v->block("alpha"), "ALPHA");
        $this->assertEquals($v->block("alpha"), "ALPHA");

        // test appending content to a block
        $v->beginBlock("alpha");
        echo "BETA";
        $v->endBlock();

        $this->assertEquals($v->block("alpha"), "ALPHABETA");

        // test replacing the content of a block
        $v->beginBlock("alpha", true);
        echo "GAMMA";
        $v->endBlock();

        $this->assertEquals($v->block("alpha"), "GAMMA");
    }

    public function testFilenameGettersAndSetters()
    {
        $v = new View(__DIR__ . "/../fixtures/views");

        $v->template("view1");
        $this->assertTrue($v->exists());
        $this->assertEquals($v->extension(), ".php");

        $v->extension("tpl");
        $this->assertFalse($v->exists());
        $this->assertEquals($v->template(), "view1");
        $this->assertEquals($v->extension(), ".tpl");

        $v->template("view2");
        $this->assertTrue($v->exists());
        $this->assertEquals($v->template(), "view2");
        $this->assertEquals($v->extension(), ".tpl");

        $this->assertEquals($v->layout(), "");
        $v->layout("layout");
        $this->assertEquals($v->layout(), "layout");
    }

    public function testGetDataAndSetData()
    {
        $v = new View();

        $data = ["a" => "alpha", "b" => "beta"];

        $v->setData($data);

        $this->assertEquals($data, $v->getData());
    }
}
