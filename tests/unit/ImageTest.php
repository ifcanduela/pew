<?php

use pew\lib\Image;

const FIXTURES_DIR = __DIR__ . "/../fixtures";
const TEST_JPG = FIXTURES_DIR . DIRECTORY_SEPARATOR . "test.jpg";

class ImageTest extends PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $img = imagecreatetruecolor(400, 300);
        imagejpeg($img, TEST_JPG);
    }

    public function tearDown()
    {
        unlink(TEST_JPG);
    }

    public function testLoadImage()
    {
        $i = new Image(TEST_JPG);

        $this->assertInstanceOf(Image::class, $i);
        $this->assertEquals(400, $i->width());
        $this->assertEquals(300, $i->height());
        $this->assertEquals(400 / 300, $i->ratio());
    }

    public function testSaveImage()
    {
        $i = new Image();
        $i->load(TEST_JPG);

        $i->save(__DIR__ . "/../fixtures/saved.jpg");
        $i->save(__DIR__ . "/../fixtures/", IMAGETYPE_PNG);
        $i->save(__DIR__ . "/../fixtures/saved.gif");

        $this->assertTrue(file_exists(__DIR__ . "/../fixtures/saved.jpg"));
        
        $l = new Image(__DIR__ . "/../fixtures/saved.jpg");
        $this->assertEquals(400, $l->width());

        $l = new Image(__DIR__ . "/../fixtures/test.png");
        $this->assertEquals(400, $l->width());

        $l = new Image(__DIR__ . "/../fixtures/saved.gif");
        $this->assertEquals(400, $l->width());

        unlink(__DIR__ . "/../fixtures/saved.jpg");
        unlink(__DIR__ . "/../fixtures/test.png");
        unlink(__DIR__ . "/../fixtures/saved.gif");
    }

    public function testLoadImageResource()
    {
        $r = imagecreatefromjpeg(TEST_JPG);
        
        $i = new Image();
        $i->image($r);
        
        $this->assertEquals(400, $i->width());
        $this->assertEquals(300, $i->height());
        
        $res = $i->image();
        $this->assertTrue(is_resource($res));
    }

    public function testResize()
    {
        $i = new Image(TEST_JPG);
        $i->resize(200, null);
        $i->save(FIXTURES_DIR . "/resized_w.jpg");
        $result = imagecreatefromjpeg(FIXTURES_DIR . "/resized_w.jpg");

        $this->assertEquals(150, imagesy($result));

        $i = new Image(TEST_JPG);
        $i->resize(null, 600);
        $i->save(FIXTURES_DIR . "/resized_h.jpg");
        $result = imagecreatefromjpeg(FIXTURES_DIR . "/resized_h.jpg");

        $this->assertEquals(800, imagesx($result));

        unlink(FIXTURES_DIR . "/resized_w.jpg");
        unlink(FIXTURES_DIR . "/resized_h.jpg");
    }

    public function testCrop()
    {
        $i = new Image(TEST_JPG);

        $i->crop(100, 100, Image::ANCHOR_BOTTOM_RIGHT);
        $i->save(FIXTURES_DIR . "/cropped_1.jpg");
        $result = imagecreatefromjpeg(FIXTURES_DIR . "/cropped_1.jpg");

        $this->assertEquals(100, imagesx($result));
        $this->assertEquals(100, imagesy($result));

        unlink(FIXTURES_DIR . "/cropped_1.jpg");
    }
}
