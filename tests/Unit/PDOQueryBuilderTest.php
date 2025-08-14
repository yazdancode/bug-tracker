<?php

namespace Tests\Unit;

use Exception;
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
        $this->queryBuilder->beginTransaction();

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
        $result = $this->queryBuilder
            ->table('bugs')
            ->where('user', 'Mehrdad Sami')
            ->where('email', 'yshabanei@gmail.com')
            ->update(['email' => 'Mehrdad@gmail.com']);
        $this->assertEquals(1, $result);
    }

    public function testItCanUpdateMultipleWhere()
    {
        $this->insertIntoDb();
        $this->insertIntoDb(['user'=> 'morteza ahmadi']);
        $result = $this->queryBuilder
            ->table('bugs')
            ->where('user', 'Mehrdad Sami')
            ->where('link', 'https://link.com')
            ->update(['name'=>'After Multiple Where']);
        $this->assertEquals(1,$result);
    }

    /**
     * @throws Exception
     */
    public function testItCanDeleteRecord()
    {
        $this->multipleInsertIntoDB(4);

        $result = $this->queryBuilder
            ->table('bugs')
            ->where('user', 'Mehrdad Sami')
            ->delete();
        $this->assertEquals(4, $result);
    }


    public function testItCanFetchData()
    {
        $this->multipleInsertIntoDB(4);
        $this->multipleInsertIntoDB(10, ['user'=>'Morteza Ahmadi']);

        $result = $this->queryBuilder
            ->table('bugs')
            ->where('user', 'Morteza Ahmadi')
            ->get();
        $this->assertIsArray($result);
        $this->assertCount(10, $result);


    }

    public function testItCanFetchSpecificColumns()
    {
        $this->multipleInsertIntoDB(10);
        $this->multipleInsertIntoDB(10, ['name' => 'New']);
        $result = $this->queryBuilder
            ->table('bugs')
            ->where('name', 'New')
            ->get(['name', 'user']);

        $this->assertIsArray($result);
        $this->assertSame(['name', 'user'], array_keys((array)$result[0]));
//        var_dump(json_decode(json_encode($result[0]), true));
        $result = json_decode(json_encode($result[0]), true);
        $this->assertEquals(['name', 'user'], array_keys($result));

    }
    public function testItCanFirstRow()
    {
        $this->multipleInsertIntoDB(10, ['name' => 'First Row']);

        $result = $this->queryBuilder
            ->table('bugs')
            ->where('name', 'First Row')
            ->first();

        $this->assertIsObject($result);
        $this->assertSame(
            ['id', 'email', 'link', 'name', 'user'],
            array_keys((array) $result)
        );
    }



    /**
     * @throws ConfigFileNotFoundException
     */
    private function getConfig(): ?array
    {
        return Config::get('database', 'pdo_testing');
    }

    private function insertIntoDb($options= []): int
    {
        $data = array_merge([
            'name'  => 'First Bug Report',
            'link'  => 'https://link.com',
            'user'  => 'Mehrdad Sami',
            'email' => 'yshabanei@gmail.com'
        ], $options);

        return $this->queryBuilder->table('bugs')->create($data);
    }

    private function multipleInsertIntoDB($count, $options=[])
    {
        for ($i = 1; $i <=$count; $i++){
            $this->insertIntoDb($options);
        }


    }

    public function tearDown(): void
    {
//        $this->queryBuilder->truncateAllTable();
        $this->queryBuilder->rollback();
        parent::tearDown();
    }
}
