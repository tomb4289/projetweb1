<?php
session_start();
echo "Session Debug Information:<br><br>";

echo "Session ID: " . session_id() . "<br>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre><br>";

if (isset($_SESSION['user_id'])) {
    echo "User is logged in with ID: " . $_SESSION['user_id'] . "<br>";
    echo "Username: " . ($_SESSION['username'] ?? 'Not set') . "<br>";
} else {
    echo "User is NOT logged in<br>";
}

echo "<br>POST data: <pre>" . print_r($_POST, true) . "</pre><br>";
echo "FILES data: <pre>" . print_r($_FILES, true) . "</pre><br>";

try {
    $config = require_once __DIR__ . '/../config.php';
    $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
    $pdo = new PDO($dsn, $config['database']['user'], $config['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM membre WHERE id_membre = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        echo "Database user lookup: <pre>" . print_r($user, true) . "</pre><br>";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}
?>
