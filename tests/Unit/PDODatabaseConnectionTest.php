<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yshabanei\BugTracker\contracts\DatabaseConnetionInterface;
use Yshabanei\BugTracker\database\PDODatabaseConnection;
use Yshabanei\BugTracker\Helpers\Config;

class PDODatabaseConnectionTest extends TestCase
{
    public function testPDODatabaseConnectionImplementDatabaseConnectionInterface()
    {
        $config = $this->getConfig();
        $pdoConnection = new PDODatabaseConnection($config);
        $this->assertInstanceOf(DatabaseConnetionInterface::class, $pdoConnection);
    }

    private function getConfig()
    {
        return Config::get('database', 'pdo_testing');
    }

}
