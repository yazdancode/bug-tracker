<?php

namespace Yshabanei\BugTracker\database;

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
        $fields = implode(',', array_keys($data));
        $placeholdersString = implode(',', $placeholders);

        $sql = "INSERT INTO $this->table ($fields) VALUES ($placeholdersString)";
        $query = $this->connection->prepare($sql);
        $query->execute(array_values($data));

        return (int) $this->connection->lastInsertId();
    }

    public function where(string $column, $value, string $operator = '='): self
    {
        $this->conditions[] = [$column, $operator, $value];
        return $this;
    }

    public function update(array $data): bool
    {
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
        }
        $setString = implode(',', $set);

        $params = array_values($data);
        $whereParts = [];

        foreach ($this->conditions as [$column, $operator, $value]) {
            $whereParts[] = "$column $operator ?";
            $params[] = $value;
        }

        $whereString = $whereParts ? ' WHERE ' . implode(' AND ', $whereParts) : '';

        $sql = "UPDATE $this->table SET $setString$whereString";
        $query = $this->connection->prepare($sql);
        return $query->execute($params);
    }
}
