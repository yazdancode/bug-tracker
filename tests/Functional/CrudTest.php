<?php

namespace Tests\Functional;
use PHPUnit\Framework\TestCase;
use Yshabanei\BugTracker\database\PDODatabaseConnection;
use Yshabanei\BugTracker\database\PDOQueryBuilder;
use Yshabanei\BugTracker\exceptions\ConfigFileNotFoundException;
use Yshabanei\BugTracker\exceptions\ConfigNotValidException;
use Yshabanei\BugTracker\exceptions\DatabaseConnectionException;
use Yshabanei\BugTracker\Helpers\Config;
use GuzzleHttp\Client;
use Yshabanei\BugTracker\Helpers\HttpClient;

class CrudTest extends TestCase
{
    /**
     * @throws DatabaseConnectionException
     * @throws ConfigNotValidException
     */

    private Client $httpClient;
    private $queryBuilder;
    public function setUp(): void
    {
        parent::setUp();
        try {
            $pdoConnection = new PDODatabaseConnection($this->getConfig());
        } catch (ConfigNotValidException) {

        }
        try {
            $this->queryBuilder = new PDOQueryBuilder($pdoConnection->connect());
        } catch (DatabaseConnectionException) {

        }
        $this->httpClient = new HttpClient();
        parent:: setUp();
    }

    public function tearDown(): void
    {
        $this->httpClient = null;
        parent::tearDown();
    }

    private function getConfig(): array
    {
        try {
            return Config::get('database', 'pdo_testing');
        } catch (ConfigFileNotFoundException $e) {
            $this->fail("Config file not found: " . $e->getMessage());
        }
    }
}
