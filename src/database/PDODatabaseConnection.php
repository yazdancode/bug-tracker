<?php
namespace Yshabanei\BugTracker\database;

use PDO;
use PDOException;
use Yshabanei\BugTracker\contracts\DatabaseConnetionInterface;
use Yshabanei\BugTracker\exceptions\ConfigNotValidException;
use Yshabanei\BugTracker\exceptions\DatabaseConnectionException;

class PDODatabaseConnection implements DatabaseConnetionInterface
{
    protected $connection;
    protected $config;

    const REQUIRED_CONFIG_KEYS = [
        'driver',
        'host',
        'database',
        'db_user',
        'db_password'
    ];

    public function __construct(array $config)
    {
        if(!$this->isConfigValid($config)){
            throw new ConfigNotValidException();
        }
        $this->config = $config;
    }

    /**
     * @throws DatabaseConnectionException
     */
    public function connect(): PDODatabaseConnection
    {
        if (!($this->connection instanceof PDO)) {
            [$dsn, $username, $password] = $this->generateDsn($this->config);

            try {
                $this->connection = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                throw new DatabaseConnectionException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $this;
    }


    private function generateDsn(array $config): array
    {
        $charset = $config['charset'] ?? 'utf8mb4';
        $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['database']};charset=$charset";
        return [$dsn, $config['db_user'], $config['db_password']];
    }

    public function getConnection()
    {
        return $this->connection;
    }

    private function isConfigValid($config)
    {
//        ['driver', 'host', 'db_user']
        $matches = array_intersect(self::REQUIRED_CONFIG_KEYS, array_keys($config));
        return count($matches) === count(self::REQUIRED_CONFIG_KEYS);
    }
}
