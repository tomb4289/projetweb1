<?php
echo "Database test file is working!<br>";

$config = require_once __DIR__ . '/../config.php';
echo "Config loaded successfully<br>";

try {
    $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
    $pdo = new PDO($dsn, $config['database']['user'], $config['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "Database connection successful!<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM membre");
    $result = $stmt->fetch();
    echo "Database query successful! Found " . $result['count'] . " members<br>";
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "<br>";
}

echo "<br>Database test completed!";
?>
