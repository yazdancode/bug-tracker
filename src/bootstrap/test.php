<?php

use Yshabanei\BugTracker\database\PDODatabaseConnection;
use Yshabanei\BugTracker\database\PDOQueryBuilder;
use Yshabanei\BugTracker\Helpers\Config;

require_once __DIR__ . '/../../vendor/autoload.php';

$config = Config::get('database', 'pdo_testing');

$pdoConnection = (new PDODatabaseConnection($config));

$queryBuilder = new PDOQueryBuilder($pdoConnection->connect());

$queryBuilder->truncateAllTable();
