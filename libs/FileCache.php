<?php

namespace pew\libs;

/**
 * This class can save and load serialized data to and from files.
 *
 * @author Igor F. Canduela <ifcanduela@gmail.com>
 * @package pew/libs
 */
class FileCache
{
    /**
     * @var integer Seconds to cache data
     */
    private $interval = 0;

    /**
     * @var string Location of cache files
     */
    private $folder = '';

    /**
     * @var boolean Use Gzip compression in cache files
     */
    private $gzip = true;

    /**
     * @var boolean Whether Gzip funcionality is available
     */
    private $gzip_enabled = true;

    /**
     * @var string Suffix for Gziped cache files
     */
    private $gzip_suffix = '.gz';

    /**
     * Create a cache manager.
     * 
     * @param integer $seconds Defaults to 12 hours
     */
    public function __construct($seconds = 43200, $folder = 'cache')
    {
        $this->folder = $folder;
        $this->interval($seconds);

        # Create the cache folder if it does not exist
        if (!is_dir($this->folder)) {
            mkdir($this->folder, 0777, true);
        }

        # The gzencode function exists in PHP 5.3, but gzdecode caould not exist
        if (!function_exists('gzdecode')) {
            # If there is not gzdecode function, disable Gzip functionality
            $this->gzip_enabled = false;
            $this->gzip = false;
        }
    }

    /**
     * Enable, disable or check the status of the Gzip functionality.
     * 
     * @param boolean $gzip Either true, false or null
     * @return boolean True if Gzip is available and enabled, false otherwise
     */
    public function gzip($gzip = null)
    {
        if ($this->gzip_enabled && !is_null($gzip)) {
            $this->gzip = $this->gzip_enabled && $gzip;
        }

        return $this->gzip;
    }

    /**
     * Set or get the suffix for Gzipped cache files.
     * 
     * @param string $gzip Either true, false or null
     * @return string The current suffix
     */
    public function gzip_suffix($suffix = null)
    {
        if (!is_null($suffix) && is_string($suffix)) {
            $this->gzip_suffix = $suffix;
        }

        return $this->gzip_suffix;
    }

    /**
     * Configure the default cache interval.
     * 
     * @param integer $seconds Interval duration in seconds
     * @return integer Seconds
     */
    public function interval($seconds = null)
    {
        if (!is_null($seconds)) {
            $this->interval = $seconds;
        }

        return $this->interval;
    }

    /**
     * Build the filename for a cache item.
     * 
     * @param string $key Cache item key
     * @return string Path and filename
     */
    protected function filename($key)
    {
        $filename = $this->folder . DIRECTORY_SEPARATOR . $key;

        # Add an optional suffix for Gzipped cache files
        if ($this->gzip()) {
            $filename .= $this->gzip_suffix();
        }

        return $filename;
    }

    /**
     * Checks if the file is cached.
     * 
     * @param string $key Cache file
     * @param integer $interval Number of seconds
     * @return boolean True is the file is in the cache, false otherwise
     */
    public function cached($key, $interval = null)
    {
        $file = $this->filename($key);

        if (!file_exists($file)) {
            return false;
        }

        if (is_null($interval) || !is_numeric($interval)) {
            $interval = $this->interval();
        }

        $filedate = filemtime($file);

        return (time() - $filedate) < $interval;
    }

    /**
     * Save data to the cache.
     * 
     * @param string $key Cache key to store the data
     * @param mixed $data Data to store
     * @return int|bool Number of bytes written to cache, or false on failure
     */
    public function save($key, $data)
    {
        $file = $this->filename($key);

        $serialized_data = serialize($data);

        if ($this->gzip) {
            $serialized_data = gzencode($serialized_data);
        }

        return file_put_contents($file, $serialized_data);
    }

    /**
     * Load data from a cache file.
     *
     * @param string $key Cache key to read
     * @return mixed The cached data
     * @throws RuntimeException If the cache file does not exit
     */
    public function load($key)
    {
        $file = $this->filename($key);
        
        if (!file_exists($file)) {
            throw new \RuntimeException("Cache file not found: {$file}");
        }

        $serialized_data = file_get_contents($file);

        if ($this->gzip) {
            $serialized_data = gzdecode($serialized_data);
        }

        return unserialize($serialized_data);
    }

    /**
     * Deletes the data associated to a cache key.
     * 
     * @param string $key Cache key to delete
     * @return bool False on failure, true otherwise
     */
    public function delete($key)
    {
        $file = $this->filename($key);
        
        if (!file_exists($file)) {
            return true;
        }

        return unlink($file);
    }

    public function __set($key, $value)
    {
        $this->save($key, $value);
    }

    public function __get($key)
    {
        if ($this->cached($key)) {
            return $this->load($key);
        }

        return null;
    }

    public function __isset($key)
    {
        return $this->cached($key);
    }

    public function __unset($key)
    {
        return $this->delete($key);
    }
}
