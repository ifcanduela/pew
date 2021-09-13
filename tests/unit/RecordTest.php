<?php

use pew\model\Record;

class RecordTest extends \PHPUnit\Framework\TestCase
{
    public function testRecord()
    {
        $r = new Record(["alpha" => "A", "beta" => "B"]);
        $this->assertEquals(["alpha" => "A", "beta" => "B"], $r->all());

        $alpha = $r->get("alpha");
        $this->assertEquals("A", $alpha);

        try {
            $r->get("delta");
        } catch (\Exception $e) {
            $this->assertEquals("Field `delta` not found in record", $e->getMessage());
        }

        $r->merge(["beta" => "BB", "delta" => "DD"]);
        $this->assertEquals(["alpha" => "A", "beta" => "BB"], $r->all());

        try {
            $r->set("delta", "DD");
        } catch (\Exception $e) {
            $this->assertEquals("Field `delta` not found in record", $e->getMessage());
        }
    }

    public function testGetField()
    {
        $r = new Record(["alpha" => "A", "beta" => "B"]);

        $alpha = $r->get("alpha");
        $this->assertEquals("A", $alpha);

        try {
            $r->get("delta");
        } catch (\Exception $e) {
            $this->assertEquals("Field `delta` not found in record", $e->getMessage());
        }
    }

    public function testSetField()
    {
        $r = new Record(["alpha" => "A", "beta" => "B"]);

        try {
            $r->set("delta", "DD");
        } catch (\Exception $e) {
            $this->assertEquals("Field `delta` not found in record", $e->getMessage());
        }

        $r->set("beta", "BB");
        $this->assertEquals("BB", $r->get("beta"));
    }

    public function testHasField()
    {
        $r = new Record(["alpha" => "A", "beta" => "B"]);

        $this->assertTrue($r->has("alpha"));
        $this->assertFalse($r->has("delta"));
    }
}
