<?php

define('ROOT_PATH', __DIR__ . '/');
define('APP_PATH', ROOT_PATH . 'app/');
define('VIEW_PATH', APP_PATH . 'views/');

define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('MAX_UPLOAD_COUNT', 5);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

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