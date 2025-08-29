<?php
require_once __DIR__ . '/../app/config/database.php';

try {
    echo "<h1>Complete CREATE TABLE Statements</h1>";

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tables Found: " . count($tables) . "</h2>";
    
    foreach ($tables as $table) {
        echo "<hr>";
        echo "<h3>Table: $table</h3>";

        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $createTable = $stmt->fetch();
        
        echo "<h4>CREATE TABLE Statement:</h4>";
        echo "<pre style='background-color: #f5f5f5; padding: 15px; overflow-x: auto; border: 1px solid #ccc;'>";
        echo htmlspecialchars($createTable['Create Table']);
        echo "</pre>";
        
        echo "<h4>Copy-Paste Ready (no HTML):</h4>";
        echo "<textarea style='width: 100%; height: 120px; font-family: monospace;'>";
        echo $createTable['Create Table'];
        echo ";\n";
        echo "</textarea>";
    }

    echo "<hr>";
    echo "<h2>Complete SQL File Content</h2>";
    echo "<textarea style='width: 100%; height: 400px; font-family: monospace;'>";
    
    echo "-- MySQL Script generated from current Stampee Database\n";
    echo "-- Current Database Structure as of " . date('Y-m-d H:i:s') . "\n\n";
    
    echo "SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;\n";
    echo "SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;\n";
    echo "SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';\n\n";
    
    echo "-- -----------------------------------------------------\n";
    echo "-- Schema stampee_db\n";
    echo "-- -----------------------------------------------------\n";
    echo "CREATE SCHEMA IF NOT EXISTS `stampee_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;\n";
    echo "USE `stampee_db` ;\n\n";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $createTable = $stmt->fetch();
        
        echo "-- -----------------------------------------------------\n";
        echo "-- Table `stampee_db`.`$table`\n";
        echo "-- -----------------------------------------------------\n";
        echo $createTable['Create Table'] . ";\n\n";
    }
    
    echo "SET SQL_MODE=@OLD_SQL_MODE;\n";
    echo "SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;\n";
    echo "SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;\n";
    
    echo "</textarea>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
