<?php 

use \pew\libs\Image;

class ImageTest extends PHPUnit_Framework_TestCase
{
    public function testFakeImage()
    {
        try {
            $img = new Image('fake.jpg');
        } catch(\Exception $e) {
            $this->assertEquals('pew\libs\ImageNotFoundException', get_class($e));
        }
    }

    public function testLoadJpegImage()
    {
        $img = new Image;
        
        $img->load(__DIR__ . '/../assets/test.jpg');

        $this->assertEquals(__DIR__ . '/../assets/test.jpg', $img->filename());
        $this->assertEquals('image/jpeg', $img->mime_type());
        
        unset($img);
    }

    public function testLoadPngImage()
    {
        $img = new Image;
        
        $img->load(__DIR__ . '/../assets/test.png');

        $this->assertEquals(__DIR__ . '/../assets/test.png', $img->filename());
        $this->assertEquals('image/png', $img->mime_type());
        
        unset($img);
    }

    public function testLoadGifImage()
    {
        $img = new Image;
        
        $img->load(__DIR__ . '/../assets/test.gif');

        $this->assertEquals(__DIR__ . '/../assets/test.gif', $img->filename());
        $this->assertEquals('image/gif', $img->mime_type());
        
        unset($img);
    }

    /**
     * @expectedException pew\libs\ImageNotSupportedException
     */
    public function testLoadInvalidFileType()
    {
        $img = new Image;
        
        $img->load(__DIR__ . '/../assets/test.bmp');
        
        unset($img);
    }

    /**
     * @expectedException \pew\libs\ImageNotFoundException
     * @expectedExceptionMessage Uploaded file upload.jpg not found [temp=none.png]
     */
    public function testLoadFromArrayException()
    {
        $info = [
            'tmp_name' => 'none.png',
            'filename' => 'upload.jpg',
        ];

        $img = new Image($info);
    }

    public function testGetImage()
    {
        $img = new Image(__DIR__ . '/../assets/test.jpg');
        $r = $img->image();
        $this->assertTrue(is_resource($r));
    }

    /**
     * @expectedException pew\libs\ImageNotLoadedException
     * @expectedExceptionMessage Image not loaded
     */
    public function testGetImageBeforeLoading()
    {
        $img = new Image;
        $img->image();
    }

    /**
     * @expectedException pew\libs\ImageNotLoadedException
     * @expectedExceptionMessage Image not loaded
     */
    public function testFilenameBeforeLoading()
    {
        $img = new Image;
        $img->filename();
    }
    /**
     * @expectedException pew\libs\ImageNotLoadedException
     * @expectedExceptionMessage Image not loaded
     */
    public function testMimeTypeBeforeLoading()
    {
        $img = new Image;
        $img->mime_type();
    }

    /**
     * @expectedException pew\libs\ImageNotLoadedException
     * @expectedExceptionMessage Image not loaded
     */
    public function testReloadImageBeforeLoading()
    {
        $img = new Image;
        $img->reload();
    }

    /**
     * @expectedException pew\libs\ImageNotLoadedException
     * @expectedExceptionMessage Image not loaded
     */
    public function testRatioBeforeLoading()
    {
        $img = new Image;
        $img->ratio();
    }

    /**
     * @expectedException pew\libs\ImageNotLoadedException
     * @expectedExceptionMessage Image not loaded
     */
    public function testSaveImageBeforeLoading()
    {
        $img = new Image;
        $img->save('dummy');
    }

    /**
     * @expectedException \pew\libs\ImageNotLoadedException
     * @expectedExceptionMessage Image not loaded
     */
    public function testWidthBeforeLoadingImage()
    {
        $img = new Image;
        $img->width();
    }

    /**
     * @expectedException \pew\libs\ImageNotLoadedException
     * @expectedExceptionMessage Image not loaded
     */
    public function testHeightBeforeLoadingImage()
    {
        $img = new Image;
        $img->height();
    }

    /**
     * @expectedException \pew\libs\ImageNotLoadedException
     * @expectedExceptionMessage Image not loaded
     */
    public function testResizeBeforeLoadingImage()
    {
        $img = new Image;
        $img->resize(100, 100);
    }

    /**
     * @expectedException \pew\libs\ImageNotLoadedException
     * @expectedExceptionMessage Image not loaded
     */
    public function testCropBeforeLoadingImage()
    {
        $img = new Image;
        $img->crop(100, 100);
    }

    /**
     * @expectedException \pew\libs\ImageNotLoadedException
     * @expectedExceptionMessage Image not loaded
     */
    public function testBoxBeforeLoadingImage()
    {
        $img = new Image;
        $img->box(100, 100);
    }

    /**
     * @expectedException \pew\libs\ImageNotLoadedException
     * @expectedExceptionMessage Image not loaded
     */
    public function testColorAtBeforeLoadingImage()
    {
        $img = new Image;
        $img->color_at(50, 50);
    }

    /**
     * @expectedException \pew\libs\ImageNotLoadedException
     * @expectedExceptionMessage Image not loaded
     */
    public function testServeBeforeLoadingImage()
    {
        $img = new Image;
        $img->serve();
    }

    public function testImageSizes()
    {
        $img = new Image;

        $img->load(__DIR__ . '/../assets/test.jpg');

        $this->assertEquals(1024, $img->width());
        $this->assertEquals(768, $img->height());
        $this->assertEquals(1024 / 768, $img->ratio());
    }

    public function testResize()
    {
        $img = new Image;
        
        $img->load(__DIR__ . '/../assets/test.jpg');

        $img->resize(400, 300);
        $this->assertEquals(400, $img->width());
        $this->assertEquals(300, $img->height());

        $img->reload();

        $img->resize(192, null);
        $this->assertEquals(192, $img->width());
        $this->assertEquals(144, $img->height());

        $img->reload();

        $img->resize(null, 240);
        $this->assertEquals(320, $img->width());
        $this->assertEquals(240, $img->height());
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testBadCropAnchor()
    {
        $img = new Image(__DIR__ . '/../assets/test.jpg');

        $img->crop(100, 100, 'northeast');
    }

    public function testCrop()
    {
        $img = new Image(__DIR__ . '/../assets/test.jpg');

        $img->crop(400, 300, Image::ANCHOR_CENTER);
        $this->assertEquals(400, $img->width());
        $this->assertEquals(300, $img->height());

        $img->reload();
        $img->crop(100, 200, Image::ANCHOR_TOP_LEFT);
        $this->assertEquals(100, $img->width());
        $this->assertEquals(200, $img->height());

        $img->reload();
        $img->crop(100, 200, Image::ANCHOR_BOTTOM_RIGHT);
        $this->assertEquals(100, $img->width());
        $this->assertEquals(200, $img->height());
    }

    public function testBox()
    {
        $img = new Image(__DIR__ . '/../assets/test.jpg');

        $img->box(120, 400);
        $this->assertEquals(120, $img->width());
        
        $img->reload()->box(800, 200);
        $this->assertEquals(800, $img->width());
    }
    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Image::resize() requires its first or second argument to be integers
     */
    public function testBadResize()
    {
        $img = new Image;
        
        $img->load(__DIR__ . '/../assets/test.jpg');

        $img->resize(null, 'not a number');
    }

    public function testThumbnails()
    {
        $img = new Image;
        
        $img->load(__DIR__ . '/../assets/test.jpg');
        
        $img->filename('kate_thumb.jpg');
        $img->resize(120, 75);
        $this->assertTrue($img->save(__DIR__ . '/../assets'));
        $this->assertTrue(file_exists(__DIR__ . '/../assets/kate_thumb.jpg'));
        
        $thumb = new Image(__DIR__ . '/../assets/kate_thumb.jpg');

        $this->assertEquals(120, $thumb->width());
        $this->assertEquals(75, $thumb->height());

        unlink(__DIR__ . '/../assets/kate_thumb.jpg');
    }

    public function testServeAsJpeg()
    {
        $this->markTestSkipped();
    }

    public function testServeAsPng()
    {
        $this->markTestSkipped();
    }

    public function testServeAsGif()
    {
        $this->markTestSkipped();
    }

    public function testColorAt()
    {
        $img = new Image(__DIR__ . '/../assets/test.jpg');

        $color = $img->color_at(1, 1);
        $this->assertEquals([245, 237, 226], $color);
    }

    /**
     * @expectedException ErrorException
     * @expectedExceptionMessage imagecolorat(): 10000,2000000 is out of bounds
     */
    public function testColorAtOutOfBoundsError()
    {
        $img = new Image(__DIR__ . '/../assets/test.jpg');
        
        $color = $img->color_at(10000, 2000000);
    }

    public function testSingleUpload()
    {
        if (isSet($_FILES['single_file'])) {
            $upl = new Image($_FILES['single_file']);
            $img->upload($_FILES['single_file']);

            $this->assertEquals($upl->filename(), $img->filename());
            $this->assertEquals($_FILES['single_file']['name'], $img->filename());
            $this->assertEquals($_FILES['single_file']['name'], $upl->filename());
        } else {
            $this->markTestSkipped('No image available for upload');
        }
    }

    public function testSaveWithoutDestination()
    {
        $img = new Image(__DIR__ . '/../assets/test.jpg');
        
        $this->assertTrue($img->save(null));

        unlink(getcwd() . '/' . basename($img->filename()));
    }

    public function testSaveAsGif()
    {
        $img = new Image(__DIR__ . '/../assets/test.jpg');

        $this->assertTrue($img->save(null, IMAGETYPE_GIF));

        unlink(getcwd() . '/' . basename($img->filename()));
    }

    public function testSaveAsPng()
    {
        $img = new Image(__DIR__ . '/../assets/test.jpg');

        $this->assertTrue($img->save(null, IMAGETYPE_PNG));

        unlink(getcwd() . '/' . basename($img->filename()));
    }

    /**
     * @expectedException pew\libs\ImageNotSupportedException
     */
    public function testSaveUnknownFormatError()
    {
        $img = new Image(__DIR__ . '/../assets/test.jpg');

        $img->save(null, IMAGETYPE_SWF);
    }

    public function testExtension()
    {
        $img = new Image;

        $this->assertEquals('jpg', $img->extension(false, IMAGETYPE_JPEG));
        $this->assertEquals('png', $img->extension(false, IMAGETYPE_PNG));
        $this->assertEquals('.gif', $img->extension(true, IMAGETYPE_GIF));

        $jpg = new Image(__DIR__ . '/../assets/test.jpg');
        $png = new Image(__DIR__ . '/../assets/test.png');
        $gif = new Image(__DIR__ . '/../assets/test.gif');

        $this->assertEquals('.jpg', $jpg->extension(true));
        $this->assertEquals('.png', $png->extension(true));
        $this->assertEquals('gif', $gif->extension(false));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testExtensionCannotBeFound()
    {
        $img = new Image;

        $img->extension();
    }

    public function testQuality()
    {
        $img = new Image(__DIR__ . '/../assets/test.jpg');

        $this->assertEquals(75, $img->quality());
        $img->quality(25);
        $this->assertEquals(25, $img->quality());
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testQualityOutOfRange()
    {
        $img = new Image(__DIR__ . '/../assets/test.jpg');

        $img->quality(101);
    }
}
