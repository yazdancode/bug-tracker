<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yshabanei\BugTracker\exceptions\ConfigFileNotFoundException;
use Yshabanei\BugTracker\Helpers\Config;

class ConfigTest extends TestCase
{
    /**
     * @throws ConfigFileNotFoundException
     */
    public function testGetFileContentsReturnsString()
    {
        $config = Config::getFileContents('database');
        $this->assertIsString($config);
    }

    public function testItThrowsExceptionIfFileNotFound()
    {
        $this->expectException(ConfigFileNotFoundException::class);
        Config::getFileContents('dummy');
    }

    /**
     * @throws ConfigFileNotFoundException
     */
    public function testGetMethodReturnsValidData()
    {
        $config = Config::get('database', 'pdo');

        $expectedData = [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'database' => 'bug_tracker',
            'db_user' => 'root',
            'db_password' => '123456'
        ];
        $this->assertEquals($expectedData, $config);
    }
}