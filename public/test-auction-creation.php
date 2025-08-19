<?php
echo "Testing auction creation process...<br><br>";

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
    
    // Check if tables exist
    $tables = ['membre', 'timbre', 'enchere', 'images', 'offre', 'favoris'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "Table '$table' exists with " . $result['count'] . " records<br>";
        } catch (PDOException $e) {
            echo "Table '$table' does not exist or error: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br>";
    
    // Check if there are any members
    try {
        $stmt = $pdo->query("SELECT id_membre, nom_utilisateur FROM membre LIMIT 5");
        $members = $stmt->fetchAll();
        echo "Members found: " . count($members) . "<br>";
        foreach ($members as $member) {
            echo "- ID: " . $member['id_membre'] . ", Username: " . $member['nom_utilisateur'] . "<br>";
        }
    } catch (PDOException $e) {
        echo "Error checking members: " . $e->getMessage() . "<br>";
    }
    
    echo "<br>";
    
    // Check if there are any timbres
    try {
        $stmt = $pdo->query("SELECT id_timbre, nom FROM timbre LIMIT 5");
        $timbres = $stmt->fetchAll();
        echo "Timbres found: " . count($timbres) . "<br>";
        foreach ($timbres as $timbre) {
            echo "- ID: " . $timbre['id_timbre'] . ", Name: " . $timbre['nom'] . "<br>";
        }
    } catch (PDOException $e) {
        echo "Error checking timbres: " . $e->getMessage() . "<br>";
    }
    
    echo "<br>";
    
    // Check if there are any auctions
    try {
        $stmt = $pdo->query("SELECT id_enchere, id_timbre, id_membre, statut FROM enchere LIMIT 5");
        $auctions = $stmt->fetchAll();
        echo "Auctions found: " . count($auctions) . "<br>";
        foreach ($auctions as $auction) {
            echo "- ID: " . $auction['id_enchere'] . ", Timbre ID: " . $auction['id_timbre'] . ", Member ID: " . $auction['id_membre'] . ", Status: " . $auction['statut'] . "<br>";
        }
    } catch (PDOException $e) {
        echo "Error checking auctions: " . $e->getMessage() . "<br>";
    }
    
    echo "<br>";
    
    // Test creating a simple timbre
    echo "Testing timbre creation...<br>";
    try {
        $stmt = $pdo->prepare("INSERT INTO timbre (nom, pays_origine, `condition`) VALUES (?, ?, ?)");
        $result = $stmt->execute(['Test Timbre', 'Canada', 'Bonne']);
        if ($result) {
            $timbreId = $pdo->lastInsertId();
            echo "Test timbre created successfully with ID: $timbreId<br>";
            
            // Clean up
            $stmt = $pdo->prepare("DELETE FROM timbre WHERE id_timbre = ?");
            $stmt->execute([$timbreId]);
            echo "Test timbre cleaned up<br>";
        } else {
            echo "Failed to create test timbre<br>";
        }
    } catch (PDOException $e) {
        echo "Error creating test timbre: " . $e->getMessage() . "<br>";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

echo "<br>Test completed!";
?>
