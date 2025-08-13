<?php

define('ROOT_PATH', __DIR__ . '/');
define('APP_PATH', ROOT_PATH . 'app/');
define('VIEW_PATH', APP_PATH . 'views/');

return [
    'app' => [
        'debug' => true,
    ],
    'database' => [
        'host' => 'localhost',
        'dbname' => 'stampee_db',
        'user' => 'root',
        'password' => 'admin',
        'charset' => 'utf8mb4',
    ],
    'paths' => [
        'root' => ROOT_PATH,
        'templates' => VIEW_PATH,
    ],
];