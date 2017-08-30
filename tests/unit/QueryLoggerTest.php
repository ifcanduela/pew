<?php

use ifcanduela\db\Database;

class QueryLoggerTest extends PHPUnit\Framework\TestCase
{
    public function testLoggerLogs()
    {
        $db = Database::fromArray([
            'engine' => 'sqlite',
            'file' => ':memory:',
        ]);

        $db->run('SELECT 1');

        $logger = new \Monolog\Logger('SQL Logger');
        $logfile = __DIR__ . '/app.log';
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($logfile, \Monolog\Logger::INFO));

        $db->setLogger($logger);
        $db->run('SELECT 2');

        $this->assertFileExists(__DIR__ . '/app.log');
    }
}
