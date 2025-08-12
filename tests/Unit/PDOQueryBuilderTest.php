<?php

namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
use Yshabanei\BugTracker\database\PDODatabaseConnection;
use Yshabanei\BugTracker\database\PDOQueryBuilder;
use Yshabanei\BugTracker\Helpers\Config;

class PDOQueryBuilderTest extends TestCase
{
    public function testItCanCreateData()
    {
        $pdoConnection =new PDODatabaseConnection($this->getConfig());
        $queryBuilder = new PDOQueryBuilder($pdoConnection->connect());

        $data = [
            'name'=>'First Bug Report',
            'link'=>'http://link.com',
            'user'=>'Mehrdad Sami',
            'email'=>'yshabanei@gmail.com'
        ];

        $result =$queryBuilder->table('bugs')->create($data);
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    private function getConfig()
    {
        return Config::get('database', 'pdo_testing');
    }
}
