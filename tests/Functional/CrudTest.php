<?php

namespace Tests\Functional;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Exception\GuzzleException;
use Yshabanei\BugTracker\database\PDODatabaseConnection;
use Yshabanei\BugTracker\database\PDOQueryBuilder;
use Yshabanei\BugTracker\Helpers\Config;
use Yshabanei\BugTracker\Helpers\HttpClient;
use Yshabanei\BugTracker\exceptions\ConfigFileNotFoundException;
use Yshabanei\BugTracker\exceptions\ConfigNotValidException;
use Yshabanei\BugTracker\exceptions\DatabaseConnectionException;

class CrudTest extends TestCase
{
    private PDOQueryBuilder $queryBuilder;
    private HttpClient $httpClient;
    private string $baseUrl = 'http://localhost/bug-tracker/index.php';
    private ?int $createdId = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeDatabaseConnection();
        $this->httpClient = new HttpClient();
        $this->cleanupTestData();
        $this->createTestRecord(); // رکورد تستی قبل از هر تست بسازید
    }

    private function createTestRecord(): void
    {
        $data = [
            'name' => 'API',
            'user' => 'Ahmad',
            'email' => 'api@gmail.com',
            'link' => 'api.com'
        ];

        $response = $this->httpClient->post($this->baseUrl, [
            'json' => $data,
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $responseData = json_decode((string)$response->getBody(), true);
        $this->createdId = $responseData['id'] ?? null;
        $this->assertNotNull($this->createdId, "Test record should be created and return an ID");
    }

    public function testItCanUpdateDataWithApi(): void
    {
        $this->assertNotNull($this->createdId, "No record created for update test");

        $updateData = [
            'json' => [
                'id' => $this->createdId,
                'name' => 'API for Update'
            ]
        ];

        try {
            $response = $this->httpClient->put($this->baseUrl, $updateData);
            $this->assertEquals(200, $response->getStatusCode());

            $responseData = json_decode((string)$response->getBody(), true);
            $this->assertEquals('API for Update', $responseData['name']);

            $bug = $this->queryBuilder->table('bugs')->find($this->createdId);
            $this->assertNotNull($bug);
            $this->assertEquals('API for Update', $bug->name);

        } catch (GuzzleException $e) {
            $this->fail("Update request failed: " . $e->getMessage());
        }
    }

    public function testItCanFetchDataWithApi(): void
    {
        $this->assertNotNull($this->createdId, "No record created for fetch test");

        try {
            $response = $this->httpClient->get($this->baseUrl, [
                'query' => ['id' => $this->createdId]
            ]);

            $this->assertEquals(200, $response->getStatusCode());

            $responseData = json_decode((string)$response->getBody(), true);
            $this->assertEquals($this->createdId, $responseData['id']);
            $this->assertEquals('API', $responseData['name']);

        } catch (GuzzleException $e) {
            $this->fail("Fetch request failed: " . $e->getMessage());
        }
    }

    public function testItCanDeleteWithApi(): void
    {
        $this->assertNotNull($this->createdId, "No record created for delete test");

        try {
            $response = $this->httpClient->delete($this->baseUrl, [
                'json' => ['id' => $this->createdId]
            ]);

            $this->assertEquals(200, $response->getStatusCode());

            $responseData = json_decode((string)$response->getBody(), true);
            $this->assertArrayHasKey('message', $responseData);
            $this->assertStringContainsString('deleted', strtolower($responseData['message']));

            $bug = $this->queryBuilder->table('bugs')->find($this->createdId);
            $this->assertNull($bug, "Record should be deleted from the database");

        } catch (GuzzleException $e) {
            $this->fail("Delete request failed: " . $e->getMessage());
        }
    }

    private function cleanupTestData(): void
    {
        try {
            $this->queryBuilder->table('bugs')->where('name', 'API')->delete();
            $this->queryBuilder->table('bugs')->where('name', 'API for Update')->delete();
        } catch (\Exception) {}
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
}
