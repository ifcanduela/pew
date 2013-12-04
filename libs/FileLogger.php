<?php

namespace pew\libs;

class FileLoggerInvalidLevelException extends \RuntimeException {}

/**
 * FileLogger class.
 * 
 * Logs messages to a file, including timestamps and levels.
 * 
 * @package pew/libs
 * @author ifcanduela <ifcanduela@gmail.com>
*/
class FileLogger
{
    const DEBUG = 0;
    const INFO = 10;
    const NOTICE = 20;
    const WARNING = 30;
    const ERROR = 40;
    const CRITICAL = 50;
    const ALERT = 60;
    const EMERGENCY = 70;

    protected $levels = [
        0 => 'DEBUG',
        10 => 'INFO',
        20 => 'NOTICE',
        30 => 'WARNING',
        40 => 'ERROR',
        50 => 'CRITICAL',
        60 => 'ALERT',
        70 => 'EMERGENCY',
    ];

    protected $logs_directory;
    protected $log_filename;
    protected $minimum_level;

    public function __construct($logs_directory = 'logs', $minimum_level = self::INFO)
    {
        $this->log_filename = 'log_' . date('Y-m-d') . '.txt';
        $this->logs_directory = $logs_directory;
        $this->minimum_level = $minimum_level;
    }

    /**
     * Set the log file name.
     * 
     * @param string $filename Name of the log file
     * @return FileLogger The logger instance
     */
    public function file($filename)
    {
        $this->log_filename = $filename;

        return $this;
    }

    /**
     * Set the log file location.
     * 
     * @param string $filename Name of the log file
     * @return FileLogger The logger instance
     */
    public function dir($logs_directory)
    {
        $this->logs_directory = $logs_directory;

        return $this;
    }

    /**
     * Get the location of the log file.
     * 
     * @return string
     */
    public function location()
    {
        return rtrim($this->logs_directory, '/') . '/' . ltrim($this->log_filename, '/');
    }

    /**
     * Log a debug-level message.
     *
     * Messages can have placeholders surrounded by {}.
     * 
     * @param string $msg Message to log
     * @param array $context Key/value pairs to replace in the message
     * @return FileLogger The logger instance
     */
    public function debug($msg, array $context = [])
    {
        return $this->log(self::DEBUG, $msg, $context);
    }

    /**
     * Log an info-level message.
     *
     * Messages can have placeholders surrounded by {}.
     * 
     * @param string $msg Message to log
     * @param array $context Key/value pairs to replace in the message
     * @return FileLogger The logger instance
     */
    public function info($msg, array $context = [])
    {
        return $this->log(self::INFO, $msg, $context);
    }

    /**
     * Log a notice-level message.
     *
     * Messages can have placeholders surrounded by {}.
     * 
     * @param string $msg Message to log
     * @param array $context Key/value pairs to replace in the message
     * @return FileLogger The logger instance
     */
    public function notice($msg, array $context = [])
    {
        return $this->log(self::NOTICE, $msg, $context);
    }

    /**
     * Log a warning-level message.
     *
     * Messages can have placeholders surrounded by {}.
     * 
     * @param string $msg Message to log
     * @param array $context Key/value pairs to replace in the message
     * @return FileLogger The logger instance
     */
    public function warning($msg, array $context = [])
    {
        return $this->log(self::WARNING, $msg, $context);
    }

    /**
     * Log an error-level message.
     *
     * Messages can have placeholders surrounded by {}.
     * 
     * @param string $msg Message to log
     * @param array $context Key/value pairs to replace in the message
     * @return FileLogger The logger instance
     */
    public function error($msg, array $context = [])
    {
        return $this->log(self::ERROR, $msg, $context);
    }

    /**
     * Log a critical-level message.
     *
     * Messages can have placeholders surrounded by {}.
     * 
     * @param string $msg Message to log
     * @param array $context Key/value pairs to replace in the message
     * @return FileLogger The logger instance
     */
    public function critical($msg, array $context = [])
    {
        return $this->log(self::CRITICAL, $msg, $context);
    }

    /**
     * Log an alert-level message.
     *
     * Messages can have placeholders surrounded by {}.
     * 
     * @param string $msg Message to log
     * @param array $context Key/value pairs to replace in the message
     * @return FileLogger The logger instance
     */
    public function alert($msg, array $context = [])
    {
        return $this->log(self::ALERT, $msg, $context);
    }

    /**
     * Log an emergency-level message.
     *
     * Messages can have placeholders surrounded by {}.
     * 
     * @param string $msg Message to log
     * @param array $context Key/value pairs to replace in the message
     * @return FileLogger The logger instance
     */
    public function emergency($msg, array $context = [])
    {
        return $this->log(self::EMERGENCY, $msg, $context);
    }

    /**
     * Log a message.
     *
     * Messages can have placeholders surrounded by {}.
     * 
     * @param int $level One of the FileLogger class constants
     * @param string $msg Message to log
     * @param array $context Key/value pairs to replace in the message
     * @return FileLogger The logger instance
     */
    public function log($level, $msg, array $context = [])
    {
        if (!in_array($level, array_keys($this->levels), true)) {
            throw new FileLoggerInvalidLevelException("Logging level {$level} is not supported");
        }

        if ($this->minimum_level <= $level) {
            $message = str_pad($this->levels[$level], 10)
                     . ' | ' 
                     . date('Y/m/d H:i:s')
                     . ' | ' 
                     . $this->interpolate($msg, $context)
                     . PHP_EOL;
                     
            file_put_contents($this->location(), $message, FILE_APPEND);
        }

        return $this;
    }

    /**
     * Replace a set of placeholders in a string with values.
     * 
     * @param $string String with placeholders
     * @param array $context Placeholders in key/value format
     * @return string The string after replacing the placeholders
     */
    protected function interpolate($string, array $context)
    {
        $placeholders = [];

        foreach ($context as $placeholder => $replacement) {
            $placeholders['{' . $placeholder . '}'] = $replacement;
        }

        # replace the placeholders in the message
        return strtr($string, $placeholders);
    }
}
