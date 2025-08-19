<?php
echo "Creating test user...<br>";

// Test config
$config = require_once __DIR__ . '/../config.php';
echo "Config loaded successfully<br>";

// Test database connection
try {
    $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
    $pdo = new PDO($dsn, $config['database']['user'], $config['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "Database connection successful!<br>";
    
    // Check if test user already exists
    $stmt = $pdo->prepare("SELECT * FROM membre WHERE nom_utilisateur = 'testuser'");
    $stmt->execute();
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "Test user already exists:<br>";
        echo "ID: " . $existingUser['id_membre'] . "<br>";
        echo "Username: " . $existingUser['nom_utilisateur'] . "<br>";
        echo "Email: " . $existingUser['courriel'] . "<br>";
        echo "Password hash: " . substr($existingUser['mot_de_passe'], 0, 20) . "...<br>";
    } else {
        // Create test user
        $hashedPassword = password_hash('testpass123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO membre (nom_utilisateur, courriel, mot_de_passe) VALUES (?, ?, ?)");
        $result = $stmt->execute(['testuser', 'test@example.com', $hashedPassword]);
        
        if ($result) {
            $userId = $pdo->lastInsertId();
            echo "Test user created successfully!<br>";
            echo "ID: $userId<br>";
            echo "Username: testuser<br>";
            echo "Password: testpass123<br>";
            echo "Email: test@example.com<br>";
        } else {
            echo "Failed to create test user<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

echo "<br>Test completed!";
?>
