<?php

namespace Tests\Functional;

use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
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
    private ?HttpClient $httpClient = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeDatabaseConnection();
        $this->httpClient = new HttpClient();
    }

    public function testItCanCreateDataWithApi(): void
    {
        $testData = $this->getTestBugData();

        try {
            $response = $this->sendCreateRequest($testData);
        } catch (GuzzleException) {

        }
        $this->verifyApiResponse($response, $testData);
        $this->verifyDatabaseRecord($testData);
    }

    protected function tearDown(): void
    {
        $this->httpClient = null;
        parent::tearDown();
    }

    private function initializeDatabaseConnection(): void
    {
        try {
            $pdoConnection = new PDODatabaseConnection($this->getConfig());
            $this->queryBuilder = new PDOQueryBuilder($pdoConnection->connect());
        } catch (ConfigNotValidException | DatabaseConnectionException $e) {
            $this->fail("Setup failed: " . $e->getMessage());
        }
    }

    private function getConfig(): array
    {
        try {
            return Config::get('database', 'pdo_testing');
        } catch (ConfigFileNotFoundException $e) {
            $this->fail("Config file not found: " . $e->getMessage());
        }
    }

    private function getTestBugData(): array
    {
        return [
            'name' => 'API',
            'user' => 'Ahmad',
            'email' => 'api@gmail.com',
            'link' => 'api.com'
        ];
    }

    /**
     * @throws GuzzleException
     */
    private function sendCreateRequest(array $data): ResponseInterface
    {
        return $this->httpClient->post('http://localhost/bug-tracker/index.php', [
            'json' => $data,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    private function verifyApiResponse($response, array $expectedData): void
    {
        $responseBody = (string)$response->getBody();
        $responseData = json_decode($responseBody, true);

        $this->assertIsArray($responseData, "Response should be a valid array");
        $this->assertArrayHasKey('id', $responseData, "Response should contain 'id' field");

        $this->assertEquals($expectedData['name'], $responseData['name']);
        $this->assertEquals($expectedData['user'], $responseData['user']);
        $this->assertEquals($expectedData['email'], $responseData['email']);
        $this->assertEquals($expectedData['link'], $responseData['link']);
    }

    private function verifyDatabaseRecord(array $expectedData): void
    {
        $bug = $this->queryBuilder
            ->table('bugs')
            ->where('name', $expectedData['name'])
            ->where('user', $expectedData['user'])
            ->first();

        $this->assertNotNull($bug, "Record should exist in database");
    }
}