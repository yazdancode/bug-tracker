<?php

namespace Yshabanei\BugTracker\database;

use Exception;
use PDO;
use Yshabanei\BugTracker\contracts\DatabaseConnetionInterface;

class PDOQueryBuilder
{
    protected string $table;
    protected PDO $connection;
    protected array $conditions = [];
    protected array $bindings = [];

    public function __construct(DatabaseConnetionInterface $connection)
    {
        $this->connection = $connection->getConnection();
    }

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function create(array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $fields);

        $sql = sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $this->table,
            implode(',', array_map(fn($col) => "`$col`", $fields)),
            implode(',', $placeholders)
        );

        $this->execute($sql, $data);

        return (int) $this->connection->lastInsertId();
    }

    public function where(string $column, $value, string $operator = '='): self
    {
        $param = ':where_' . count($this->conditions);
        $this->conditions[] = "`$column` $operator $param";
        $this->bindings[$param] = $value;
        return $this;
    }

    public function update(array $data): int
    {
        $setParts = [];
        foreach ($data as $column => $value) {
            $param = ':set_' . $column;
            $setParts[] = "`$column` = $param";
            $this->bindings[$param] = $value;
        }

        $sql = sprintf(
            "UPDATE `%s` SET %s%s",
            $this->table,
            implode(',', $setParts),
            $this->buildWhereClause()
        );

        $query = $this->connection->prepare($sql);
        $query->execute($this->bindings);

        $this->reset();
        return $query->rowCount();
    }

    /**
     * @throws Exception
     */
    public function delete(): int
    {
        if (empty($this->conditions)) {
            throw new Exception("Cannot delete without conditions.");
        }

        $sql = sprintf(
            "DELETE FROM `%s`%s",
            $this->table,
            $this->buildWhereClause()
        );

        $query = $this->connection->prepare($sql);
        $query->execute($this->bindings);

        $this->reset();
        return $query->rowCount();
    }

    public function get(array $columns = ['*']): array
    {
        $columnsString = implode(',', array_map(
            fn($col) => $col === '*' ? $col : "`$col`",
            $columns
        ));

        $sql = sprintf(
            "SELECT %s FROM `%s`%s",
            $columnsString,
            $this->table,
            $this->buildWhereClause()
        );

        $query = $this->connection->prepare($sql);
        $query->execute($this->bindings);

        $result = $query->fetchAll(PDO::FETCH_OBJ);
        $this->reset();
        return $result;
    }

    public function first(array $columns = ['*']): ?object
    {
        $result = $this->get($columns);

        if (empty($result)) {
            return null;
        }

        $row = (array) $result[0];

        if ($columns === ['*']) {
            $desiredOrder = ['id', 'email', 'link', 'name', 'user'];
            $ordered = [];
            foreach ($desiredOrder as $col) {
                if (array_key_exists($col, $row)) {
                    $ordered[$col] = $row[$col];
                }
            }
            foreach ($row as $key => $value) {
                if (!array_key_exists($key, $ordered)) {
                    $ordered[$key] = $value;
                }
            }
            $row = $ordered;
        }

        return (object) $row;
    }


    public function find(int $id, array $columns = ['*']): ?object
    {
        return $this->table($this->table)
            ->where('id', $id)
            ->first($columns);
    }

    public function findBy(string $column, mixed $value, array $columns = ['*']): ?object
    {
        return $this->table($this->table)
            ->where($column, $value)
            ->first($columns);
    }

    public function truncateAllTable(): void
    {
        $query = $this->connection->prepare("SHOW TABLES");
        $query->execute();
        $tables = $query->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $this->connection->prepare("TRUNCATE TABLE `$table`")->execute();
        }
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }

    // ===================== Private Helpers =====================

    private function execute(string $sql, array $params): void
    {
        $query = $this->connection->prepare($sql);
        $query->execute($params);
    }

    private function buildWhereClause(): string
    {
        return $this->conditions
            ? ' WHERE ' . implode(' AND ', $this->conditions)
            : '';
    }

    private function reset(): void
    {
        $this->conditions = [];
        $this->bindings = [];
    }
}
