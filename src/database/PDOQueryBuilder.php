<?php

namespace Yshabanei\BugTracker\database;

use Yshabanei\BugTracker\contracts\DatabaseConnetionInterface;

class PDOQueryBuilder
{
    protected $table;
    protected $connection;

    public function __construct(DatabaseConnetionInterface $connection)
    {
        $this->connection = $connection->getConnection();

    }
    public function table($table): PDOQueryBuilder
    {
        $this->table = $table;
        return $this;
    }

    public function create($data)
    {
        $placeholder = [];
        foreach ($data as $column=>$value){
            $placeholder[] = '?';
        }
        $fields = implode(',',array_keys($data));
        $placeholder = implode(',', $placeholder);
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ($placeholder)";
        $query = $this->connection->prepare($sql);
        $query->execute(array_values($data));
        return (int)$this->connection->lastInsertId();
    }

}
