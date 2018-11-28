<?php

namespace pew\lib;

/**
 * A class to facilitate the creation of thumbnails.
 */
class Image
{
    const ANCHOR_BOTTOM = "bottom";
    const ANCHOR_BOTTOM_LEFT = "bottom left";
    const ANCHOR_BOTTOM_RIGHT = "bottom right";
    const ANCHOR_CENTER = "center";
    const ANCHOR_LEFT = "left";
    const ANCHOR_RIGHT = "right";
    const ANCHOR_TOP = "top";
    const ANCHOR_TOP_LEFT = "top left";
    const ANCHOR_TOP_RIGHT = "top right";

    /** @var string Original file name */
    protected $sourceFileName;

    /** @var string Destination file name */
    protected $filename;

    /** @var resource A GD resource containing the image data */
    protected $resource;

    /**
     * @var int Image type
     * @see http://www.php.net/manual/en/function.image-type-to-mime-type.php
     */
    protected $imageType;

    /** @var string Image file MIME type */
    protected $mimeType;

    /** @var int Output quality */
    protected $quality;

    /** @var int */
    protected $width;

    /** @var int */
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
            imagedestroy($this->resource);
        }
    }

    /**
     * Load an image file.
     *
     * Only JPEG, PNG and GIF images are supported.
     *
     * @param string $filename Location of the image file
     * @return self
     * @throws \Exception
     */
    public function load(string $filename)
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
     * @return self
     * @throws \Exception
     */
    public function upload(array $file)
    {
        if (!file_exists($file["tmp_name"]) || !is_uploaded_file($file["tmp_name"])) {
            $filename = $file["filename"];
            $tmpname = $file["tmp_name"];
            throw new \Exception("Uploaded file {$filename} not found [temp={$tmpname}]");
        }

        $this->sourceFileName = $file["tmp_name"];
        $this->filename = $file["name"];

        $this->init();
        $this->loadFile($file["tmp_name"], $this->imageType);

        return $this;
    }

    /**
     * Create an image from a file.
     *
     * @param string $filename The image file name
     * @param int $imageType One of the IMAGETYPE_* constants
     * @return int Return value from the imageCreateFrom* function
     * @throws \Exception
     */
    protected function loadFile(string $filename, $imageType)
    {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $this->resource = @imagecreatefromjpeg($filename);
                break;

            case IMAGETYPE_PNG:
                $this->resource = @imagecreatefrompng($filename);
                break;

            case IMAGETYPE_GIF:
                $this->resource = @imagecreatefromgif($filename);
                break;

            default:
                throw new \Exception("The image format of file {$filename} is not supported");
        }

        if (!$this->resource) {
            $error = error_get_last();
            $message = $error["message"];
            throw new \Exception("The file {$filename} is not a valid image resouce. {$message}");
        }
    }

    /**
     * Get the image resource.
     *
     * @param resource $resource
     * @return resource|self The image data or the Image object
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
     * @return self
     */
    protected function init()
    {
        $fileinfo = getimagesize($this->sourceFileName);
        list($this->width, $this->height, $this->imageType) = $fileinfo;
        $this->mimeType = $fileinfo["mime"];

        return $this;
    }

    /**
     * Reload the original image.
     *
     * This method undoes resizing and cropping.
     *
     * @return self
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
     * @param string $destination Destination folder or filename
     * @param int $imageType One of the IMAGETYPE_* constants
     * @param int $quality Output quality (0 - 100)
     * @return bool Result of the image* functions
     * @throws \Exception
     */
    public function save($destination, $imageType = null, $quality = null)
    {
        $this->checkResource();

        if (!$destination) {
            $destination = getcwd();
        }

        if (is_null($imageType)) {
            $imageType = $this->imageType;
        }

        if (is_null($quality)) {
            $quality = $this->quality;
        }

        if (strpos(basename($destination), ".") === false) {
            $destination .= DIRECTORY_SEPARATOR . basename($this->filename);
        }

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagejpeg($this->resource, $destination, $quality);

            case IMAGETYPE_PNG:
                return imagepng($this->resource, $destination, $quality * 9 / 100);

            case IMAGETYPE_GIF:
                return imagegif($this->resource, $destination);

            default:
                $filename = $this->sourceFileName;
                throw new \Exception("The image format of file {$filename} ({$imageType}) is not supported");
        }
    }

    /**
     * Set or get the file name.
     *
     * @param string $filename New file name
     * @return string|self The image object, or the file name
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
     * @param int $imageType One of the IMAGETYPE_* constants
     * @return string The extension corresponding to the image
     * @throws \Exception
     */
    public function extension($dot = true, $imageType = null)
    {
        if (is_null($imageType)) {
            if (!$this->imageType) {
                throw new \Exception("Cannot find extension");
            }

            $imageType = $this->imageType;
        }

        $extension = image_type_to_extension($imageType, $dot);

        return str_replace(["jpeg", "tiff"], ["jpg", "tif"], $extension);
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

        return imagesx($this->resource);
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

        return imagesy($this->resource);
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
     * @return int|self The image object, or the current quality setting
     */
    public function quality($quality = null)
    {
        if (!is_null($quality)) {
            $quality = max(0, min(100, (int) $quality));

            $this->quality = $quality;

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
     * @return self
     * @throws \Exception
     */
    public function resize($w, $h)
    {
        $this->checkResource();

        if (!is_numeric($w) && !is_numeric($h)) {
            throw new \BadMethodCallException("Image::resize() requires its first or second argument to be integers");
        }

        $newWidth = (int) $w;
        $newHeight = (int) $h;

        if (!$newWidth) {
            $newWidth = $newHeight * $this->width / $this->height;
        } elseif (!$newHeight) {
            $newHeight = $newWidth * $this->height / $this->width;
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled(
            $resized,
            $this->resource,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $this->width,
            $this->height
        );

        imagedestroy($this->resource);
        $this->resource = $resized;
        $this->width = imagesx($resized);
        $this->height = imagesy($resized);

        return $this;
    }

    /**
     * Resize an image to a set width, keeping the aspect ratio.
     *
     * @param int $width
     * @return self
     * @internal param int $w The target width
     */
    public function resizeWidth(int $width)
    {
        return $this->resize($width, null);
    }

    /**
     * Resize an image to a set height, keeping the aspect ratio.
     *
     * @param int $height
     * @return self
     * @internal param int|null $h The target height
     */
    public function resizeHeight(int $height)
    {
        return $this->resize(null, $height);
    }

    /**
     * Crop an image.
     *
     * @param int $w Cropped width
     * @param int $h Cropped height
     * @param string $anchor One of the ANCHOR constants of the class.
     * @return self
     * @throws \Exception
     */
    public function crop(int $w, int $h, $anchor = self::ANCHOR_CENTER)
    {
        $this->checkResource();

        $validAnchors = [
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

        if (!in_array($anchor, $validAnchors)) {
            throw new \BadMethodCallException("Invalid anchor point for Image::crop()");
        }

        $oldWidth = $this->width();
        $oldHeight = $this->height();

        $xOffset = ($oldWidth - $w) / 2;
        $yOffset = ($oldHeight - $h) / 2;

        if (in_array($anchor, [self::ANCHOR_TOP_LEFT, self::ANCHOR_TOP, self::ANCHOR_TOP_RIGHT])) {
            $yOffset = 0;
        }

        if (in_array($anchor, [self::ANCHOR_BOTTOM_LEFT, self::ANCHOR_BOTTOM, self::ANCHOR_BOTTOM_RIGHT])) {
            $yOffset = $oldHeight - $h;
        }

        if (in_array($anchor, [self::ANCHOR_TOP_LEFT, self::ANCHOR_LEFT, self::ANCHOR_BOTTOM_LEFT])) {
            $xOffset = 0;
        }

        if (in_array($anchor, [self::ANCHOR_TOP_RIGHT, self::ANCHOR_RIGHT, self::ANCHOR_BOTTOM_RIGHT])) {
            $xOffset = $oldWidth - $w;
        }

        $cropped = imagecreatetruecolor($w, $h);
        imagecopyresampled($cropped, $this->resource, 0, 0, $xOffset, $yOffset, $w, $h, $w, $h);

        imagedestroy($this->resource);
        $this->resource = $cropped;
        $this->width = $w;
        $this->height = $h;

        return $this;
    }

    /**
     * Resize and crop an image to create a thumbnail.
     *
     * @param int $width
     * @param int $height
     * @param string $anchor Anchor location for the resizing
     * @return self
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
     * @return self
     */
    public function fit(int $width, int $height)
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
     * @param int $imageType One of the IMAGETYPE_* constants
     * @param int $quality Quality of the PNG or JPEG output, from 0 to 100
     * @throws \Exception
     */
    public function serve($imageType = IMAGETYPE_JPEG, $quality = 75)
    {
        $this->checkResource();

        ob_start();

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                imagejpeg($this->resource, null, $quality);
                break;

            case IMAGETYPE_PNG:
                imagepng($this->resource, null, $quality * 9 / 100);
                break;

            case IMAGETYPE_GIF:
                imagegif($this->resource);
                break;

            default:
                throw new \Exception("The image type supplied to Image::serve() is not supported");
        }

        $stream = ob_get_clean();
        header("Content-type:" . image_type_to_mime_type($imageType));
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
            $rgb = imagecolorat($this->resource, $x, $y);
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
        if (!$this->resource) {
            throw new \RuntimeException("No image loaded");
        }
    }
}
