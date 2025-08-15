<?php

use Yshabanei\BugTracker\database\PDODatabaseConnection;
use Yshabanei\BugTracker\database\PDOQueryBuilder;
use Yshabanei\BugTracker\exceptions\ConfigFileNotFoundException;
use Yshabanei\BugTracker\exceptions\ConfigNotValidException;
use Yshabanei\BugTracker\exceptions\DatabaseConnectionException;
use Yshabanei\BugTracker\Helpers\Config;

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $config = Config::get('database', 'pdo_testing');
} catch (ConfigFileNotFoundException $e) {

}

try {
    $pdoConnection = (new PDODatabaseConnection($config));
} catch (ConfigNotValidException $e) {

}

try {
    $queryBuilder = new PDOQueryBuilder($pdoConnection->connect());
} catch (DatabaseConnectionException $e) {

}

$queryBuilder->truncateAllTable();