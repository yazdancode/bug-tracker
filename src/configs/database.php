<?php

return [
    'pdo' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'database' => 'bug_tracker',
        'db_user' => 'root',
        'db_password' => '123456'
    ],
    'pdo_testing' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'bug_tracker_testing',
        'db_user' => 'root',
        'db_password' => '',
        'charset' => 'utf8mb4'
    ],
];
