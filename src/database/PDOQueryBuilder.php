<?php

namespace Yshabanei\BugTracker\database;

use Exception;
use Yshabanei\BugTracker\contracts\DatabaseConnetionInterface;
use PDO;

class PDOQueryBuilder
{
    protected string $table;
    protected PDO $connection;
    protected array $conditions = [];

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
        $placeholders = array_fill(0, count($data), '?');
        $fields = implode(',', array_map(fn($col) => "`$col`", array_keys($data)));
        $placeholdersString = implode(',', $placeholders);

        $sql = "INSERT INTO `$this->table` ($fields) VALUES ($placeholdersString)";
        $query = $this->connection->prepare($sql);
        $query->execute(array_values($data));

        return (int) $this->connection->lastInsertId();
    }

    public function where(string $column, $value, string $operator = '='): self
    {
        $this->conditions[] = ["`$column`", $operator, $value];
        return $this;
    }

    public function update(array $data): int
    {
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "`$column` = ?";
        }
        $setString = implode(',', $set);

        $params = array_values($data);

        $whereParts = [];
        foreach ($this->conditions as [$column, $operator, $value]) {
            $whereParts[] = "$column $operator ?";
            $params[] = $value;
        }
        $whereString = $whereParts ? ' WHERE ' . implode(' AND ', $whereParts) : '';

        $sql = "UPDATE `$this->table` SET $setString$whereString";
        $query = $this->connection->prepare($sql);
        $query->execute($params);

        $this->resetConditions();
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

        $whereParts = [];
        $params = [];
        foreach ($this->conditions as [$column, $operator, $value]) {
            $whereParts[] = "$column $operator ?";
            $params[] = $value;
        }

        $whereString = ' WHERE ' . implode(' AND ', $whereParts);
        $sql = "DELETE FROM `$this->table`$whereString";
        $query = $this->connection->prepare($sql);
        $query->execute($params);

        $this->resetConditions();
        return $query->rowCount();
    }

    public function get(array $columns = ['*']): array
    {
        $columnsString = implode(',', array_map(function ($col) {
            return $col === '*' ? $col : "`$col`";
        }, $columns));

        $params = [];
        $whereParts = [];
        foreach ($this->conditions as [$column, $operator, $value]) {
            $whereParts[] = "$column $operator ?";
            $params[] = $value;
        }

        $whereString = $whereParts ? ' WHERE ' . implode(' AND ', $whereParts) : '';

        $sql = "SELECT $columnsString FROM `$this->table`$whereString";
        $query = $this->connection->prepare($sql);
        $query->execute($params);

        $result = $query->fetchAll(PDO::FETCH_OBJ);
        $this->resetConditions();
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
            $row = array_merge(array_flip($desiredOrder), $row);
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
        foreach ($query->fetchAll(PDO::FETCH_COLUMN) as $table) {
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

    protected function resetConditions(): void
    {
        $this->conditions = [];
    }
}
