<?php

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$config = require_once __DIR__ . '/../config.php';

use App\routes\Route;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

if ($config['app']['debug']) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

try {
    $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
    $pdo = new PDO($dsn, $config['database']['user'], $config['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($config['app']['debug']) {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Database connection failed. Please check your configuration.");
    }
}

$loader = new FilesystemLoader($config['paths']['templates']);
$twig = new Environment($loader, [
    'cache' => $config['app']['debug'] ? false : $config['paths']['root'] . 'var/cache/twig',
    'debug' => $config['app']['debug'],
]);

$uri = $_SERVER['REQUEST_URI'];
$basePath = '/projetweb1/public';

if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

$uri = rtrim(strtok($uri, '?'), '/');

if ($uri === '') {
    $uri = '/';
}

if ($config['app']['debug']) {
    echo "<!-- Debug: URI = '$uri', Method = '{$_SERVER['REQUEST_METHOD']}' -->\n";
}

require_once __DIR__ . '/../app/routes/web.php';

Route::dispatch($pdo, $twig, $config, $uri, $_SERVER['REQUEST_METHOD']);
