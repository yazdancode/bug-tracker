<?php

namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
use Yshabanei\BugTracker\database\PDODatabaseConnection;
use Yshabanei\BugTracker\database\PDOQueryBuilder;
use Yshabanei\BugTracker\Helpers\Config;

class PDOQueryBuilderTest extends TestCase
{
    private $queryBuilder;

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
        $result = $this->queryBuilder
            ->table('bugs')
            ->where('user', 'Mehrdad Sami')
            ->where('email', 'Mehrdad11@gmail.com')
            ->update(['email'=>'Mehrdad@gmail.com'
            ]);
        $this->assertEquals(1, $result);

    }

    private function getConfig()
    {
        return Config::get('database', 'pdo_testing');
    }

    private function insertIntoDb()
    {
        $data = [
            'name'=>'First Bug Report',
            'link'=>'http://link.com',
            'user'=>'Mehrdad Sami',
            'email'=>'yshabanei@gmail.com'
        ];
        return $this->queryBuilder->table('bugs')->create($data);
    }
}
