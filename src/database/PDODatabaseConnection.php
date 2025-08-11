<?php
namespace Yshabanei\BugTracker\database;

use PDO;
use PDOException;
use Yshabanei\BugTracker\contracts\DatabaseConnetionInterface;

class PDODatabaseConnection implements DatabaseConnetionInterface
{
    protected $connection;
    protected $config;

    public function __construct(array $config)
    {
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
}
