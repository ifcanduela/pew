<?php

namespace pew\libs;

/**
 * A class to facilitate the creation of thumbnails.
 *
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Image
{
    const ANCHOR_BOTTOM = 'bottom';
    const ANCHOR_BOTTOM_LEFT = 'bottom left';
    const ANCHOR_BOTTOM_RIGHT = 'bottom right';
    const ANCHOR_CENTER = 'center';
    const ANCHOR_LEFT = 'left';
    const ANCHOR_RIGHT = 'right';
    const ANCHOR_TOP = 'top';
    const ANCHOR_TOP_LEFT = 'top left';
    const ANCHOR_TOP_RIGHT = 'top right';

    /**
     * @var string Original file name.
     */
    protected $sourceFileName;

    /**
     * @var string Destination file name.
     */
    protected $filename;

    /**
     * @var resource A GD resource containing the image data
     */
    protected $resource;

    /**
     * @var int Image type
     * @see http://www.php.net/manual/en/function.image-type-to-mime-type.php
     */
    protected $imageType;

    /**
     * @var string Image file MIME type.
     */
    protected $mimeType;

    /**
     * @var int Output quality.
     */
    protected $quality;

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * Build a new image object.
     *
     * @param string|array $file Image file to load
     */
    public function __construct($file = null)
    {
        $this->quality = 75;

        if (is_array($file)) {
            $this->upload($file);
        } elseif (is_string($file)) {
            $this->load($file);
        }
    }

    /**
     * Remove the resource data.
     */
    public function __destruct()
    {
        if ($this->resource) {
            imageDestroy($this->resource);
        }
    }

    /**
     * Load an image file.
     *
     * Only JPEG, PNG and GIF images are supported.
     *
     * @param string $filename Location of the image file
     * @return Image The image object
     * @throws \Exception
     */
    public function load(string $filename): self
    {
        if (!file_exists($filename)) {
            throw new \Exception("File {$filename} not found");
        }

        $this->sourceFileName = $this->filename = $filename;

        $this->init();
        $this->loadFile($filename, $this->imageType);

        return $this;
    }

    /**
     * Load an image uploaded from the $_FILES array.
     *
     * @param array $file An uploaded file
     * @return Image The image resource
     * @throws \Exception
     */
    public function upload(array $file): self
    {
        if (!file_exists($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \Exception("Uploaded file {$file['filename']} not found [temp={$file['tmp_name']}]");
        }

        $this->sourceFileName = $file['tmp_name'];
        $this->filename = $file['name'];

        $this->init();
        $this->loadFile($file['tmp_name'], $this->imageType);

        return $this;
    }

    /**
     * Create an image from a file.
     *
     * @param string $filename The image file name
     * @param int $image_type One of the IMAGETYPE_* constants
     * @return int Return value from the imageCreateFrom* function
     * @throws \Exception
     */
    protected function loadFile(string $filename, $image_type)
    {
        switch ($image_type) {
            case IMAGETYPE_JPEG:
                $this->resource = @imageCreateFromJPEG($filename);
                break;

            case IMAGETYPE_PNG:
                $this->resource = @imageCreateFromPNG($filename);
                break;

            case IMAGETYPE_GIF:
                $this->resource = @imageCreateFromGIF($filename);
                break;

            default:
                throw new \Exception("The image format of file {$filename} is not supported");
        }

        if (!$this->resource) {
            $error = error_get_last();
            throw new \Exception("The file {$filename} is not a valid image resouce. " . $error['message']);
        }

        return false;
    }

    /**
     * Get the image resource.
     *
     * @param resource $resource
     * @return resource|Image The image data
     * @throws \Exception
     */
    public function image($resource = null)
    {
        if (!is_null($resource)) {
            $this->resource = $resource;
            return $this;
        }

        $this->checkResource();

        return $this->resource;
    }

    /**
     * Initialize some image properties.
     *
     * @return Image Then image object
     */
    protected function init(): self
    {
        $fileinfo = getImageSize($this->sourceFileName);
        list($this->width, $this->height, $this->imageType) = $fileinfo;
        $this->mimeType = $fileinfo['mime'];

        return $this;
    }

    /**
     * Reload the original image.
     *
     * This method undoes resizing and cropping.
     *
     * @return Image The image object
     * @throws \Exception
     */
    public function reload()
    {
        if (!$this->sourceFileName) {
            throw new \Exception("Image not loaded");
        }

        $this->init();
        $this->loadFile($this->sourceFileName, $this->imageType);

        return $this;
    }

    /**
     * Save the loaded image to a file.
     *
     * The image type defaults to the original image type, and the quality defaults to 75.
     *
     * @see http://www.php.net/manual/en/function.image-type-to-mime-type.php
     *
     * @param string $destination Destiantion folder or filename
     * @param int $image_type One of the IMAGETYPE_* constants
     * @param int $quality Output quality (0 - 100)
     * @return int Result of the image* functions
     * @throws \Exception
     */
    public function save($destination, $image_type = null, $quality = null)
    {
        $this->checkResource();

        if (!$destination) {
            $destination = getcwd();
        }

        if (is_null($image_type)) {
            $image_type = $this->imageType;
        }

        if (is_null($quality)) {
            $quality = $this->quality;
        }

        if (strpos(basename($destination), '.') === false) {
            $destination .= DIRECTORY_SEPARATOR . basename($this->filename);
        }

        switch ($image_type) {
            case IMAGETYPE_JPEG:
                return imageJPEG($this->resource, $destination, $quality);

            case IMAGETYPE_PNG:
                return imagePNG($this->resource, $destination, $quality * 9 / 100);

            case IMAGETYPE_GIF:
                return imageGIF($this->resource, $destination);

            default:
                throw new \Exception("The image format of file {$this->sourceFileName} ({$image_type}) is not supported");
        }
    }

    /**
     * Set or get the file name.
     *
     * @param string $filename New file name
     * @return Image|string The image object, or the file name
     * @throws \Exception
     */
    public function filename($filename = null)
    {
        $this->checkResource();

        if (!is_null($filename)) {
            $this->filename = $filename;
            return $this;
        }

        return $this->filename;
    }

    /**
     * Get the extension for the image.
     *
     * @param boolean $dot Whether to include a dot before the extension or not
     * @param int $image_type One of the IMAGETYPE_* constants
     * @return string The extension corresponding to the image
     * @throws \Exception
     */
    public function extension($dot = true, $image_type = null)
    {
        if (is_null($image_type)) {
            if (!$this->imageType) {
                throw new \Exception("Cannot find extension");
            }

            $image_type = $this->imageType;
        }

        $extension = image_type_to_extension($image_type, $dot);

        return str_replace(['jpeg', 'tiff'], ['jpg', 'tif'], $extension);
    }

    /**
     * Get the aspect ratio of the loaded image.
     *
     * @return float Aspect ratio of the loaded image
     * @throws \Exception
     */
    public function ratio()
    {
        $this->checkResource();

        return $this->width / $this->height;
    }


    /**
     * Get the width nof the loaded image.
     *
     * @return int Width in pixels
     * @throws \Exception
     */
    public function width()
    {
        $this->checkResource();

        return imageSX($this->resource);
    }

    /**
     * Get the height nof the loaded image.
     *
     * @return int Height in pixels
     * @throws \Exception
     */
    public function height()
    {
        $this->checkResource();

        return imageSY($this->resource);
    }

    /**
     * Get the MIME type of the loaded image.
     *
     * @return string The MIME type
     * @throws \Exception
     */
    public function mimeType()
    {
        $this->checkResource();

        return $this->mimeType;
    }

    /**
     * Set or get the output quality.
     *
     * The value must be a percentage, even for PNG images.
     *
     * @param int $quality Output quality (0 - 100)
     * @return Image|int The image object, or the current quality setting
     */
    public function quality($quality = null)
    {
        if (!is_null($quality)) {
            $quality = (int) $quality;

            if ($quality >= 0 && $quality <= 100) {
                $this->quality = $quality;
            } else {
                throw new \BadMethodCallException("The quality for Image::quality must be an integer between 0 and 100");
            }

            return $this;
        }

        return $this->quality;
    }

    /**
     * Resize an image.
     *
     * If one of the dimensions is null the resize operation will
     * maintain the aspect ratio of the source image.
     *
     * @param int|null $w The target width
     * @param int|null $h The target height
     * @return Image The image object
     * @throws \Exception
     */
    public function resize($w, $h): self
    {
        $this->checkResource();

        if (!is_numeric($w) && !is_numeric($h)) {
            throw new \BadMethodCallException("Image::resize() requires its first or second argument to be integers");
        }

        $new_w = (int) $w;
        $new_h = (int) $h;

        if (!$new_w) {
            $new_w = $new_h * $this->width / $this->height;
        } elseif (!$new_h) {
            $new_h = $new_w * $this->height / $this->width;
        }

        $resized = imageCreateTrueColor($new_w, $new_h);
        imageCopyResampled($resized, $this->resource, 0, 0, 0, 0, $new_w, $new_h, $this->width, $this->height);

        imageDestroy($this->resource);
        $this->resource = $resized;
        $this->width = imageSX($resized);
        $this->height = imageSY($resized);

        return $this;
    }

    /**
     * Resize an image to a set width, keeping the aspect ratio.
     *
     * @param int $width
     * @return Image The image object
     * @internal param int $w The target width
     */
    public function resizeWidth(int $width): self
    {
        return $this->resize($width, null);
    }

    /**
     * Resize an image to a set height, keeping the aspect ratio.
     *
     * @param int $height
     * @return Image The image object
     * @internal param int|null $h The target height
     */
    public function resizeHeight(int $height): self
    {
        return $this->resize(null, $height);
    }

    /**
     * Crop an image.
     *
     * @param int $w Cropped width
     * @param int $h Cropped height
     * @param string $anchor One of the ANCHOR constants of the class.
     * @return Image The image object
     * @throws \Exception
     */
    public function crop(int $w, int $h, $anchor = self::ANCHOR_CENTER): self
    {
        $this->checkResource();

        $valid_anchors = [
            self::ANCHOR_BOTTOM,
            self::ANCHOR_BOTTOM_LEFT,
            self::ANCHOR_BOTTOM_RIGHT,
            self::ANCHOR_CENTER,
            self::ANCHOR_LEFT,
            self::ANCHOR_RIGHT,
            self::ANCHOR_TOP,
            self::ANCHOR_TOP_LEFT,
            self::ANCHOR_TOP_RIGHT,
        ];

        if (!in_array($anchor, $valid_anchors)) {
            throw new \BadMethodCallException("Invalid anchor point for Image::crop()");
        }

        $old_w = $this->width();
        $old_h = $this->height();

        $x_offset = ($old_w - $w) / 2;
        $y_offset = ($old_h - $h) / 2;

        if (in_array($anchor, [self::ANCHOR_TOP_LEFT, self::ANCHOR_TOP, self::ANCHOR_TOP_RIGHT])) {
            $y_offset = 0;
        }

        if (in_array($anchor, [self::ANCHOR_BOTTOM_LEFT, self::ANCHOR_BOTTOM, self::ANCHOR_BOTTOM_RIGHT])) {
            $y_offset = $old_h - $h;
        }

        if (in_array($anchor, [self::ANCHOR_TOP_LEFT, self::ANCHOR_LEFT, self::ANCHOR_BOTTOM_LEFT])) {
            $x_offset = 0;
        }

        if (in_array($anchor, [self::ANCHOR_TOP_RIGHT, self::ANCHOR_RIGHT, self::ANCHOR_BOTTOM_RIGHT])) {
            $x_offset = $old_w - $w;
        }

        $cropped = imageCreateTrueColor($w, $h);
        imagecopyresampled($cropped, $this->resource, 0, 0, $x_offset, $y_offset, $w, $h, $w, $h);

        imageDestroy($this->resource);
        $this->resource = $cropped;
        $this->width = $w;
        $this->height = $h;

        return $this;
    }

    /**
     * Resize and crop an image to create a thumbnail.
     *
     * @param $width
     * @param $height
     * @param string $anchor Anchor location for the resizing
     * @return Image The image object
     * @throws \Exception
     */
    public function box($width, $height, $anchor = self::ANCHOR_CENTER)
    {
        $this->checkResource();

        $ratio = $width / $height;

        if ($this->ratio() >= $ratio) {
            $this->resize(null, $height);
        } else {
            $this->resize($width, null);
        }

        $this->crop($width, $height, $anchor);

        return $this;
    }

    /**
     * Resize and crop an image to create a thumbnail.
     *
     * @param int $width Thumbnail width
     * @param int $height Thumbnail height
     * @return Image The image object
     */
    public function fit(int $width, int $height): self
    {
        return $this->box($width, $height);
    }

    /**
     * Send the current image to the browser.
     *
     * The image type defaults to the original image type, and the quality defaults to 75.
     *
     * @see http://www.php.net/manual/en/function.image-type-to-mime-type.php
     *
     * @param int $image_type One of the IMAGETYPE_* constants
     * @param int $quality Quality of the PNG or JPEG output, from 0 to 100
     * @throws \Exception
     */
    public function serve($image_type = IMAGETYPE_JPEG, $quality = 75)
    {
        $this->checkResource();

        ob_start();

        switch ($image_type) {
            case IMAGETYPE_JPEG:
                imageJPEG($this->resource, null, $quality);
                break;

            case IMAGETYPE_PNG:
                imagePNG($this->resource, null, $quality * 9 / 100);
                break;

            case IMAGETYPE_GIF:
                imageGIF($this->resource);
                break;

            default:
                throw new \Exception("The image type supplied to Image::serve() is not supported");
        }

        $stream = ob_get_clean();
        header("Content-type:" . image_type_to_mime_type($image_type));
        die($stream);
    }

    /**
     * Get the RGB values for a pixel in the loaded image.
     *
     * @param int $x Horizontal coordinate
     * @param int $y Vertical coordinate
     * @return array Red, green and blue values, from 0 to 255
     * @throws \ErrorException
     * @throws \Exception
     */
    public function colorAt($x, $y)
    {
        $this->checkResource();

        try {
            $rgb = imageColorAt($this->resource, $x, $y);
        } catch (\Exception $e) {
            throw new \ErrorException($e->getMessage());
        }

        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8)  & 0xFF;
        $b =  $rgb        & 0xFF;

        return [$r, $g, $b];
    }

    /**
     * @throws \Exception
     */
    protected function checkResource()
    {
        $this->checkResource();
    }
}
