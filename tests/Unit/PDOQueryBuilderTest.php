<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yshabanei\BugTracker\database\PDODatabaseConnection;
use Yshabanei\BugTracker\database\PDOQueryBuilder;
use Yshabanei\BugTracker\exceptions\ConfigFileNotFoundException;
use Yshabanei\BugTracker\exceptions\ConfigNotValidException;
use Yshabanei\BugTracker\exceptions\DatabaseConnectionException;
use Yshabanei\BugTracker\Helpers\Config;

class PDOQueryBuilderTest extends TestCase
{
    private PDOQueryBuilder $queryBuilder;

    /**
     * @throws DatabaseConnectionException
     * @throws ConfigNotValidException
     */
    public function setUp(): void
    {
        $pdoConnection = new PDODatabaseConnection($this->getConfig());
        $this->queryBuilder = new PDOQueryBuilder($pdoConnection->connect());

        parent::setUp();
    }

    public function testItCanCreateData()
    {
        $result = $this->insertIntoDb();
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function testItCanUpdateDate()
    {
        // ابتدا یک رکورد درج می‌کنیم
        $this->insertIntoDb();

        // سپس آپدیت را روی همان رکورد انجام می‌دهیم
        $result = $this->queryBuilder
            ->table('bugs')
            ->where('user', 'Mehrdad Sami')
            ->where('email', 'yshabanei@gmail.com')
            ->update([
                'email' => 'Mehrdad@gmail.com'
            ]);

        // چون فقط یک رکورد باید آپدیت شود، انتظار داریم خروجی 1 باشد
        $this->assertEquals(1, $result);
    }

    /**
     * @throws ConfigFileNotFoundException
     */
    private function getConfig(): ?array
    {
        return Config::get('database', 'pdo_testing');
    }

    private function insertIntoDb(): int
    {
        $data = [
            'name'  => 'First Bug Report',
            'link'  => 'https://link.com',
            'user'  => 'Mehrdad Sami',
            'email' => 'yshabanei@gmail.com'
        ];

        return $this->queryBuilder->table('bugs')->create($data);
    }
}
