<?php

namespace Tests\Functional;

use GuzzleHttp\Exception\GuzzleException;
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
    private ?HttpClient $httpClient = null; // nullable

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

    public function testItCanCreateDataWithAPI()
    {
        $data = [
            'name' => 'API',
            'user' => 'Ahmad',
            'email' => 'api@gmail.com',
            'link' => 'api.com'
        ];

        // ارسال داده‌ها به صورت JSON
        try {
            $response = $this->httpClient->post('http://localhost/bug-tracker/index.php', [
                'json' => $data,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);
        } catch (GuzzleException) {

        }

        $responseBody = (string) $response->getBody();
        $responseData = json_decode($responseBody, true);

        $this->assertIsArray($responseData, "Response is not a valid array");
        $this->assertArrayHasKey('id', $responseData, "Response does not contain 'id'");
        $this->assertEquals('API', $responseData['name']);
        $this->assertEquals('Ahmad', $responseData['user']);
        $this->assertEquals('api@gmail.com', $responseData['email']);
        $this->assertEquals('api.com', $responseData['link']);

        // بررسی دیتابیس
        $bug = $this->queryBuilder
            ->table('bugs')
            ->where('name', 'API')
            ->where('user', 'Ahmad')
            ->first();
        $this->assertNotNull($bug);
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        // حذف مقداردهی null، یا اگر خواستی باقی بماند مشکلی نیست چون nullable است
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
