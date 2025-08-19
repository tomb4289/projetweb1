<?php
echo "Testing timbre creation directly...<br><br>";

try {
    $config = require_once __DIR__ . '/../config.php';
    $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
    $pdo = new PDO($dsn, $config['database']['user'], $config['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connected successfully<br>";
    
    // Test data that matches what the controller sends
    $testData = [
        'nom' => 'Test Timbre Debug',
        'date_creation' => '2020',
        'couleurs' => 'Rouge, Bleu',
        'pays_origine' => 'Canada',
        'condition' => 'Bonne',
        'tirage' => '1000',
        'dimensions' => '25x30mm',
        'certifie' => 0
    ];
    
    echo "Test data: <pre>" . print_r($testData, true) . "</pre><br>";
    
    // Test the exact SQL query from TimbreModel
    $sql = "INSERT INTO timbre (nom, date_creation, couleurs, pays_origine, `condition`, tirage, dimensions, certifie)
            VALUES (:nom, :date_creation, :couleurs, :pays_origine, :condition, :tirage, :dimensions, :certifie)";
    
    echo "SQL Query: <pre>$sql</pre><br>";
    
    $stmt = $pdo->prepare($sql);
    echo "Statement prepared successfully<br>";
    
    $result = $stmt->execute($testData);
    echo "Execute result: " . ($result ? 'SUCCESS' : 'FAILED') . "<br>";
    
    if ($result) {
        $timbreId = $pdo->lastInsertId();
        echo "Timbre created with ID: $timbreId<br>";
        
        // Clean up
        $stmt = $pdo->prepare("DELETE FROM timbre WHERE id_timbre = ?");
        $stmt->execute([$timbreId]);
        echo "Test timbre cleaned up<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Error code: " . $e->getCode() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre><br>";
}

echo "<br>Test completed!";
?>
