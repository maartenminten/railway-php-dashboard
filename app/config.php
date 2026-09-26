<?php
return [
    'db' => [
        'host' => getenv('MYSQLHOST') ?: '127.0.0.1',
        'port' => (int) (getenv('MYSQLPORT') ?: 3306),
        'database' => getenv('MYSQLDATABASE') ?: 'railway',
        'user' => getenv('MYSQLUSER') ?: 'root',
        'password' => getenv('MYSQLPASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],
];
