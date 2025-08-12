<?php
namespace Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;
use Yshabanei\BugTracker\Contracts\DatabaseConnetionInterface;
use Yshabanei\BugTracker\Database\PDODatabaseConnection;
use Yshabanei\BugTracker\Exceptions\ConfigFileNotFoundException;
use Yshabanei\BugTracker\exceptions\ConfigNotValidException;
use Yshabanei\BugTracker\exceptions\DatabaseConnectionException;
use Yshabanei\BugTracker\Helpers\Config;

class PDODatabaseConnectionTest extends TestCase
{
    /**
     * @throws ConfigFileNotFoundException
     */
    public function testPDODatabaseConnectionImplementsDatabaseConnectionInterface()
    {
        $config = $this->getConfig();
        $pdoConnection = new PDODatabaseConnection($config);
        $this->assertInstanceOf(DatabaseConnetionInterface::class, $pdoConnection);
    }

    /**
     * @throws DatabaseConnectionException
     * @throws ConfigFileNotFoundException
     */
    public function testConnectMethodShouldReturnPDODatabaseConnection()
    {
        $config = $this->getConfig();
        $pdoConnection = new PDODatabaseConnection($config);
        $pdoHandler = $pdoConnection->connect(); // اینجا $this برمی‌گرده
        $this->assertInstanceOf(PDODatabaseConnection::class, $pdoHandler);
        return $pdoConnection;
    }

    /**
     * @depends testConnectMethodShouldReturnPDODatabaseConnection
     */
    public function testConnectMethodShouldBeConnectedToDatabase($pdoConnection)
    {
        $this->assertInstanceOf(PDO::class, $pdoConnection->getConnection());
    }

    /**
     * @throws ConfigFileNotFoundException
     */
    public function testItThrowsExceptionIfConfigIsInvalid()
    {
        $this->expectException(DatabaseConnectionException::class);
        $config = $this->getConfig();
        $config['database'] = 'invalid_db_name';
//        unset($config['db_user']);
        $pdoConnection = new PDODatabaseConnection($config);
        $pdoConnection->connect();
    }

    public function testReceivedConfigHavRequiredKey()
    {
        $this->expectException(ConfigNotValidException::class);

        $config = $this->getConfig();
        unset($config['db_user']);

        $pdoConnection = new PDODatabaseConnection($config);
        $pdoConnection->connect();
    }

    /**
     * @throws ConfigFileNotFoundException
     */
    private function getConfig(): array
    {
        return Config::get('database', 'pdo_testing');
    }
}