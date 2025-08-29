<?php

require_once __DIR__ . '/../app/config/database.php';

try {
    echo "<h1>Stampee Database Schema Extraction</h1>";
    echo "<h2>Current Database: " . $db . "</h2>";

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Tables Found: " . count($tables) . "</h3>";
    
    foreach ($tables as $table) {
        echo "<hr>";
        echo "<h4>Table: <strong>$table</strong></h4>";

        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
        echo "</tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";

        $stmt = $pdo->query("SHOW INDEX FROM `$table`");
        $indexes = $stmt->fetchAll();
        
        if (!empty($indexes)) {
            echo "<h5>Indexes:</h5>";
            echo "<ul>";
            foreach ($indexes as $index) {
                $keyName = $index['Key_name'];
                $column = $index['Column_name'];
                $nonUnique = $index['Non_unique'];
                $type = $nonUnique ? 'INDEX' : 'UNIQUE';
                echo "<li><strong>$keyName</strong> ($type) on <code>$column</code></li>";
            }
            echo "</ul>";
        }

        $stmt = $pdo->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '$db' 
            AND TABLE_NAME = '$table' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $foreignKeys = $stmt->fetchAll();
        
        if (!empty($foreignKeys)) {
            echo "<h5>Foreign Keys:</h5>";
            echo "<ul>";
            foreach ($foreignKeys as $fk) {
                echo "<li><strong>{$fk['CONSTRAINT_NAME']}</strong>: ";
                echo "{$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}</li>";
            }
            echo "</ul>";
        }

        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $createTable = $stmt->fetch();
        echo "<h5>Create Table SQL:</h5>";
        echo "<pre style='background-color: #f5f5f5; padding: 10px; overflow-x: auto;'>";
        echo htmlspecialchars($createTable['Create Table']);
        echo "</pre>";
    }

    echo "<hr>";
    echo "<h3>Summary for Diagram Creation</h3>";
    echo "<div style='background-color: #e8f4f8; padding: 15px; border-radius: 5px;'>";
    echo "<h4>Tables to include in your diagram:</h4>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
    
    echo "<h4>Key Relationships:</h4>";
    $allForeignKeys = [];
    foreach ($tables as $table) {
        $stmt = $pdo->query("
            SELECT 
                TABLE_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '$db' 
            AND TABLE_NAME = '$table' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $foreignKeys = $stmt->fetchAll();
        $allForeignKeys = array_merge($allForeignKeys, $foreignKeys);
    }
    
    if (!empty($allForeignKeys)) {
        echo "<ul>";
        foreach ($allForeignKeys as $fk) {
            echo "<li><strong>{$fk['TABLE_NAME']}</strong>.{$fk['COLUMN_NAME']} → ";
            echo "<strong>{$fk['REFERENCED_TABLE_NAME']}</strong>.{$fk['REFERENCED_COLUMN_NAME']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No foreign key relationships found.</p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
