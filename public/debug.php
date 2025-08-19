<?php
echo "Debug file is working!<br>";

// Test autoloader
require_once __DIR__ . '/../vendor/autoload.php';
echo "Autoloader loaded successfully<br>";

// Test config
$config = require_once __DIR__ . '/../config.php';
echo "Config loaded successfully<br>";

// Test Route class
use App\Routes\Route;
echo "Route class imported successfully<br>";

// Test HomeController
use App\Controllers\HomeController;
echo "HomeController imported successfully<br>";

// Test models
use App\Models\AuctionModel;
echo "AuctionModel imported successfully<br>";

echo "<br>All tests passed!";
?>
