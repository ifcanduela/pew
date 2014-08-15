<?php

use \pew\libs\FileLogger;

class FileLoggerTest extends PHPUnit_Framework_TestCase
{
    public $l;
    
    public function tearDown()
    {
        if (file_exists(__DIR__ . '/log_' . date('Y-m-d') . '.txt')) {
            unlink(__DIR__ . '/log_' . date('Y-m-d') . '.txt');
        }
    }

    public function add_entries()
    {
        $this->logger->debug('This is a {level} message', ['level' => 'debug']);
        $this->logger->info('This is an {level} message', ['level' => 'info']);
        $this->logger->notice('This is a {level}', ['level' => 'notice']);
        $this->logger->warning('This is a {level}', ['level' => 'warning']);
        $this->logger->error('This is an {level}', ['level' => 'error']);
        $this->logger->critical('This is a {level} error', ['level' => 'critical']);
        $this->logger->alert('This is an {level}-level error', ['level' => 'alert']);
        $this->logger->emergency('This is an {level}', ['level' => 'emergency']);
    }

    public function get_logged_lines()
    {
        $txt = trim(file_get_contents(__DIR__ . '/log_' . date('Y-m-d') . '.txt'));
        return explode(PHP_EOL, $txt);
    }

    public function testLogDebugAndAbove()
    {
        $this->logger = new FileLogger(__DIR__, FileLogger::DEBUG);

        $this->add_entries();
        $f = $this->get_logged_lines();

        $this->assertEquals(8, count($f));
        $this->assertEquals('This is a debug message', substr($f[0], 35));
    }

    public function testLogInfoAndAbove()
    {
        $this->logger = new FileLogger(__DIR__, FileLogger::INFO);

        $this->add_entries();
        $f = $this->get_logged_lines();

        $this->assertEquals(7, count($f));
        $this->assertEquals('This is an info message', substr($f[0], 35));
    }

    public function testLogNoticeAndAbove()
    {
        $this->logger = new FileLogger(__DIR__, FileLogger::NOTICE);

        $this->add_entries();
        $f = $this->get_logged_lines();

        $this->assertEquals(6, count($f));
        $this->assertEquals('This is a notice', substr($f[0], 35));
    }

    public function testLogWarningAndAbove()
    {
        $this->logger = new FileLogger(__DIR__, FileLogger::WARNING);

        $this->add_entries();
        $f = $this->get_logged_lines();

        $this->assertEquals(5, count($f));
        $this->assertEquals('This is a warning', substr($f[0], 35));
    }

    public function testLogErrorAndAbove()
    {
        $this->logger = new FileLogger(__DIR__, FileLogger::ERROR);

        $this->add_entries();
        $f = $this->get_logged_lines();

        $this->assertEquals(4, count($f));
        $this->assertEquals('This is an error', substr($f[0], 35));
    }

    public function testLogCriticalAndAbove()
    {
        $this->logger = new FileLogger(__DIR__, FileLogger::CRITICAL);

        $this->add_entries();
        $f = $this->get_logged_lines();

        $this->assertEquals(3, count($f));
        $this->assertEquals('This is a critical error', substr($f[0], 35));
    }

    public function testLogAlertAndAbove()
    {
        $this->logger = new FileLogger(__DIR__, FileLogger::ALERT);

        $this->add_entries();
        $f = $this->get_logged_lines();
        
        $this->assertEquals(2, count($f));
        $this->assertEquals('This is an alert-level error', substr($f[0], 35));
    }

    public function testLogEmergencyAndAbove()
    {
        $this->logger = new FileLogger(__DIR__, FileLogger::EMERGENCY);

        $this->add_entries();
        $f = $this->get_logged_lines();

        $this->assertEquals(1, count($f));
        $this->assertEquals('This is an emergency', substr($f[0], 35));
    }

    /**
     * @expectedException pew\libs\FileLoggerInvalidLevelException
     */
    public function testLogWithInvalidLevel()
    {
        $logger = new FileLogger(__DIR__);

        $logger->log(12, 'This should not be logged');
    }

    public function testFilenameAndDir()
    {
        $this->logger = new FileLogger(__DIR__, FileLogger::DEBUG);
        $this->logger->dir(__DIR__)->file('log_test.txt');

        $this->add_entries();

        $this->assertFileExists(__DIR__ . '/log_test.txt');
    }
}
