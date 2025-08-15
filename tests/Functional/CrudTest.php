<?php

namespace Tests\Functional;

use PHPUnit\Framework\TestCase;
use Yshabanei\BugTracker\database\PDODatabaseConnection;
use Yshabanei\BugTracker\database\PDOQueryBuilder;
use Yshabanei\BugTracker\exceptions\ConfigFileNotFoundException;
use Yshabanei\BugTracker\exceptions\ConfigNotValidException;
use Yshabanei\BugTracker\exceptions\DatabaseConnectionException;
use Yshabanei\BugTracker\Helpers\Config;
use Yshabanei\BugTracker\Helpers\HttpClient;

class CrudTest extends TestCase
{
    private PDOQueryBuilder $queryBuilder;
    private HttpClient $httpClient;

    /**
     * Set up database connection and HTTP client before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $pdoConnection = new PDODatabaseConnection($this->getConfig());
            $this->queryBuilder = new PDOQueryBuilder($pdoConnection->connect());
        } catch (ConfigNotValidException | DatabaseConnectionException $e) {
            $this->fail("Setup failed: " . $e->getMessage());
        }

        $this->httpClient = new HttpClient();
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        $this->httpClient = null;
        parent::tearDown();
    }

    /**
     * Load database configuration for testing.
     *
     * @return array
     */
    private function getConfig(): array
    {
        try {
            return Config::get('database', 'pdo_testing');
        } catch (ConfigFileNotFoundException $e) {
            $this->fail("Config file not found: " . $e->getMessage());
        }
    }
}
