<?php

declare(strict_types=1);

use pew\View;

function rn($text)
{
    return str_replace("\r", "", $text);
}

class ViewTest extends PHPUnit\Framework\TestCase
{
    public function testVoidConstructor(): void
    {
        $v = new View();

        $this->assertEquals(getcwd(), $v->folder());
    }

    public function testBasics(): void
    {
        $v = new View(__DIR__ . "/../fixtures/views");

        $this->assertEmpty($v->title());

        $this->assertTrue($v->exists("view1"));
        $this->assertFalse($v->exists("nope"));

        $result = $v->render("view1", ["parameter" => "PARAMETER", "property" => "PROPERTY"]);
        $this->assertNull($v->get("parameter"));

        $this->assertEquals(rn("<div>PARAMETER</div>
<div></div>
<div>PROPERTY</div>
"), rn($result));

        $v->layout("layout");
        $v->title("test");
        $result = $v->render("view1", ["parameter" => "PARAMETER", "property" => "PROPERTY"]);

        $this->assertEquals(rn("<title>test</title>
<div>PARAMETER</div>
<div></div>
<div>PROPERTY</div>
"), rn($result));
    }

    public function testRenderWithoutTemplateName(): void
    {
        $v = new View(__DIR__ . "/../fixtures/views");

        $this->assertEmpty($v->title());

        $this->assertTrue($v->exists("view1"));
        $this->assertFalse($v->exists("nope"));

        $v->template("view1");

        $result = $v->render(["parameter" => "PARAMETER", "property" => "PROPERTY"]);

        $this->assertEquals(rn("<div>PARAMETER</div>
<div></div>
<div>PROPERTY</div>
"), rn($result));

        $v->layout("layout");
        $v->title("test");
        $result = $v->render("view1", ["parameter" => "PARAMETER", "property" => "PROPERTY"]);

        $this->assertEquals(rn("<title>test</title>
<div>PARAMETER</div>
<div></div>
<div>PROPERTY</div>
"), rn($result));
    }

    public function testRenderWithoutLayout(): void
    {
        $v = new View(__DIR__ . "/../fixtures/views");

        $v->layout("layout");
        $v->layout(false);
        $result = $v->render("view1", ["parameter" => "PARAMETER", "property" => "PROPERTY"]);

        $this->assertEquals(rn("<div>PARAMETER</div>
<div></div>
<div>PROPERTY</div>
"), rn($result));

        $v = new View(__DIR__ . "/../fixtures/views");

        $result = $v->noLayout()->render("view1", ["parameter" => "PARAMETER", "property" => "PROPERTY"]);

        $this->assertEquals(rn("<div>PARAMETER</div>
<div></div>
<div>PROPERTY</div>
"), rn($result));
    }

    public function testRenderExceptions(): void
    {
        $v = new View(__DIR__ . "/../fixtures/views");

        try {
            $v->render(null, [])->toString();
        } catch (\RuntimeException $e) {
            $this->assertEquals("No template specified", $e->getMessage());
        }

        $v->layout("missing");

        try {
            $v->render("missing", ["parameter" => 1, "property" => 2])->toString();
        } catch (\RuntimeException $e) {
            $this->assertEquals("Template `missing` not found", $e->getMessage());
        }

        $v->template("view1");

        try {
            $v->render(null, ["parameter" => 1, "property" => 2])->toString();
        } catch (\RuntimeException $e) {
            $this->assertEquals("Layout `missing` not found", $e->getMessage());
        }

        try {
            $v->render("throws")->toString();
        } catch (\Exception $e) {
            $this->assertEquals("thrown", $e->getMessage());
            $this->assertEquals("", ob_get_contents());
        }
    }

    public function testRenderPartial(): void
    {
        $v = new View(__DIR__ . "/../fixtures/views");

        try {
            $v->insert("missing");
        } catch (\RuntimeException $e) {
            $this->assertEquals("Partial template `missing` not found", $e->getMessage());
        }

        $html = $v->insert("partial", ["value" => 1]);
        $this->assertEquals("1", $html);
    }

    public function testEscape(): void
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

    public function testFluentInterface(): void
    {
        $v = new View(__DIR__ . "/../fixtures/views");

        $result = $v
            ->set("parameter", "PARAMETER")
            ->set("property", "PROPERTY")
            ->title("test title")
            ->template("view1")
            ->layout("layout")
            ->render();

        $this->assertEquals(rn("<title>test title</title>
<div>PARAMETER</div>
<div>PROPERTY</div>
<div>PROPERTY</div>
"), rn((string) $result));
    }

    public function testDataPropertyHandling(): void
    {
        $v = new View();
        $v->set("alpha", "ALPHA");

        $this->assertTrue($v->has("alpha"));
        $this->assertEquals($v->get("alpha"), "ALPHA");

        $this->assertFalse($v->has("beta"));
    }

    public function testTemplateExists(): void
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

    public function testBlocks(): void
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
        $this->assertEquals("ALPHA", $v->block("alpha"));

        // test appending content to a block
        $v->beginBlock("alpha");
        echo "BETA";
        $v->endBlock();

        $this->assertEquals("ALPHABETA", $v->block("alpha"));

        // test replacing the content of a block
        $v->beginBlock("alpha", true);
        echo "GAMMA";
        $v->endBlock();

        $this->assertEquals("GAMMA", $v->block("alpha"));
    }

    public function testFilenameGettersAndSetters(): void
    {
        $v = new View(__DIR__ . "/../fixtures/views");

        $v->template("view1");
        $this->assertTrue($v->exists());
        $this->assertEquals(".php", $v->extension());

        $v->extension("tpl");
        $this->assertFalse($v->exists());
        $this->assertEquals("view1", $v->template());
        $this->assertEquals(".tpl", $v->extension());

        $v->template("view2");
        $this->assertTrue($v->exists());
        $this->assertEquals("view2", $v->template());
        $this->assertEquals(".tpl", $v->extension());

        $this->assertEquals("", $v->layout());
        $v->layout("layout");
        $this->assertEquals("layout", $v->layout());
    }

    public function testGetDataAndSetData(): void
    {
        $v = new View();

        $data = ["a" => "alpha", "b" => "beta"];

        $v->setData($data);

        $this->assertEquals($data, $v->getData());
    }
}
