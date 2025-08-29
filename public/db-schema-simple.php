<?php

require_once __DIR__ . '/../app/config/database.php';

try {
    echo "<h1>Stampee Database Schema - Diagram Ready</h1>";

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>📊 Database Tables (" . count($tables) . ")</h2>";
    
    foreach ($tables as $table) {
        echo "<div style='border: 2px solid #ddd; margin: 20px 0; padding: 15px; border-radius: 8px;'>";
        echo "<h3 style='color: #2c5aa0;'>📋 Table: $table</h3>";

        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll();
        
        echo "<div style='background-color: #f8f9fa; padding: 10px; border-radius: 5px;'>";
        echo "<h4>Columns:</h4>";
        echo "<table style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background-color: #e9ecef;'>";
        echo "<th style='padding: 8px; border: 1px solid #dee2e6; text-align: left;'>Field</th>";
        echo "<th style='padding: 8px; border: 1px solid #dee2e6; text-align: left;'>Type</th>";
        echo "<th style='padding: 8px; border: 1px solid #dee2e6; text-align: left;'>Key</th>";
        echo "<th style='padding: 8px; border: 1px solid #dee2e6; text-align: left;'>Extra</th>";
        echo "</tr>";
        
        foreach ($columns as $column) {
            $keyClass = '';
            if ($column['Key'] === 'PRI') $keyClass = 'background-color: #d4edda;';
            elseif ($column['Key'] === 'MUL') $keyClass = 'background-color: #fff3cd;';
            elseif ($column['Key'] === 'UNI') $keyClass = 'background-color: #d1ecf1;';
            
            echo "<tr style='$keyClass'>";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6;'><strong>{$column['Field']}</strong></td>";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>{$column['Type']}</td>";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>";
            if ($column['Key'] === 'PRI') echo "🔑 Primary";
            elseif ($column['Key'] === 'MUL') echo "🔗 Foreign";
            elseif ($column['Key'] === 'UNI') echo "✨ Unique";
            else echo "-";
            echo "</td>";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";

        $stmt = $pdo->query("
            SELECT 
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
            echo "<div style='background-color: #e8f4f8; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
            echo "<h4>🔗 Foreign Key Relationships:</h4>";
            echo "<ul>";
            foreach ($foreignKeys as $fk) {
                echo "<li><strong>{$fk['COLUMN_NAME']}</strong> → <strong>{$fk['REFERENCED_TABLE_NAME']}</strong>.{$fk['REFERENCED_COLUMN_NAME']}</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
        echo "</div>";
    }

    echo "<hr>";
    echo "<h2>🎯 ERD Summary for Diagram Tools</h2>";
    echo "<div style='background-color: #d1ecf1; padding: 20px; border-radius: 8px; border-left: 5px solid #17a2b8;'>";
    
    echo "<h3>Tables to Create:</h3>";
    foreach ($tables as $table) {
        echo "<strong>• $table</strong><br>";
    }
    
    echo "<h3>Key Relationships:</h3>";
    $allRelationships = [];
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
        $allRelationships = array_merge($allRelationships, $foreignKeys);
    }
    
    if (!empty($allRelationships)) {
        foreach ($allRelationships as $rel) {
            echo "<strong>• {$rel['TABLE_NAME']}</strong> → <strong>{$rel['REFERENCED_TABLE_NAME']}</strong><br>";
        }
    } else {
        echo "<p>No foreign key relationships found.</p>";
    }
    
    echo "</div>";

    echo "<h2>📋 Copy-Paste for Diagram Tools</h2>";
    echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #dee2e6;'>";
    echo "<h3>Table Names:</h3>";
    echo "<textarea style='width: 100%; height: 60px; font-family: monospace;'>";
    echo implode("\n", $tables);
    echo "</textarea>";
    
    echo "<h3>Relationships:</h3>";
    echo "<textarea style='width: 100%; height: 80px; font-family: monospace;'>";
    foreach ($allRelationships as $rel) {
        echo "{$rel['TABLE_NAME']} → {$rel['REFERENCED_TABLE_NAME']}\n";
    }
    echo "</textarea>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
