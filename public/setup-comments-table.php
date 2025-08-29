<?php
session_start();

require_once __DIR__ . '/../app/config/database.php';

echo "<h1>Comment System Setup</h1>";

try {
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'commentaires'");
    $commentTableExists = $stmt->rowCount() > 0;
    
    echo "<h2>Database Check:</h2>";
    echo "<p><strong>Commentaires table exists:</strong> " . ($commentTableExists ? "✅ Yes" : "❌ No") . "</p>";
    
    if (!$commentTableExists) {
        echo "<h3>Creating commentaires table...</h3>";

        $sql = "CREATE TABLE IF NOT EXISTS `commentaires` (
            `id_commentaire` int NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the comment',
            `id_enchere` int NOT NULL COMMENT 'Foreign key to Enchere table (archived auctions only)',
            `id_membre` int NOT NULL COMMENT 'Foreign key to Membre table (the member who wrote the comment)',
            `contenu` text NOT NULL COMMENT 'Content of the comment',
            `note` tinyint(1) DEFAULT NULL COMMENT 'Rating from 1 to 5 (optional)',
            `date_creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Date and time the comment was created',
            `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date and time the comment was last modified',
            `approuve` tinyint(1) DEFAULT '1' COMMENT 'Whether the comment is approved (moderation)',
            PRIMARY KEY (`id_commentaire`),
            KEY `fk_commentaire_enchere` (`id_enchere`),
            KEY `fk_commentaire_membre` (`id_membre`),
            KEY `idx_date_creation` (`date_creation`),
            KEY `idx_approuve` (`approuve`),
            CONSTRAINT `fk_commentaire_enchere` FOREIGN KEY (`id_enchere`) REFERENCES `enchere` (`id_enchere`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk_commentaire_membre` FOREIGN KEY (`id_membre`) REFERENCES `membre` (`id_membre`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `chk_note` CHECK (`note` >= 1 AND `note` <= 5)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table to store comments and ratings for archived auctions.'";
        
        $pdo->exec($sql);
        echo "<p>✅ <strong>Commentaires table created successfully!</strong></p>";

        $stmt = $pdo->query("SHOW TABLES LIKE 'commentaires'");
        $tableCreated = $stmt->rowCount() > 0;
        
        if ($tableCreated) {
            echo "<p>✅ <strong>Table verification successful!</strong></p>";

            $stmt = $pdo->query("DESCRIBE commentaires");
            $columns = $stmt->fetchAll();
            
            echo "<h3>New commentaires table structure:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "<td>{$column['Extra']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>❌ <strong>Error: Table was not created!</strong></p>";
        }
    } else {
        echo "<p>✅ <strong>Commentaires table already exists!</strong></p>";

        $stmt = $pdo->query("DESCRIBE commentaires");
        $columns = $stmt->fetchAll();
        
        echo "<h3>Existing commentaires table structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    $stmt = $pdo->query("SELECT DISTINCT statut FROM enchere");
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Current auction statuses in database:</h3>";
    echo "<ul>";
    foreach ($statuses as $status) {
        echo "<li><strong>$status</strong></li>";
    }
    echo "</ul>";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM enchere WHERE statut = 'Terminée'");
    $terminatedCount = $stmt->fetch()['count'];
    
    echo "<p><strong>Terminated auctions:</strong> $terminatedCount</p>";
    
    if ($terminatedCount > 0) {
        
        $stmt = $pdo->query("SELECT id_enchere, id_timbre, date_fin, prix_plancher FROM enchere WHERE statut = 'Terminée' LIMIT 1");
        $auction = $stmt->fetch();
        
        echo "<h3>Sample terminated auction:</h3>";
        echo "<p><strong>ID:</strong> {$auction['id_enchere']}</p>";
        echo "<p><strong>Timbre ID:</strong> {$auction['id_timbre']}</p>";
        echo "<p><strong>End date:</strong> {$auction['date_fin']}</p>";
        echo "<p><strong>Starting price:</strong> {$auction['prix_plancher']}</p>";
    }

    echo "<h2>Session Check:</h2>";
    if (isset($_SESSION['user_id'])) {
        echo "<p><strong>User ID:</strong> {$_SESSION['user_id']}</p>";
        echo "<p><strong>Username:</strong> {$_SESSION['username']}</p>";
        echo "<p><strong>Authenticated:</strong> " . ($_SESSION['authenticated'] ? "Yes" : "No") . "</p>";
    } else {
        echo "<p>❌ No user logged in</p>";
        echo "<p><a href='/projetweb2/public/login'>Login here</a></p>";
    }
    
    echo "<hr>";
    echo "<h2>Next Steps:</h2>";
    echo "<p>1. ✅ <strong>Commentaires table is now set up</strong></p>";
    echo "<p>2. 🔄 <strong>Test the commenting system</strong> on an archived auction</p>";
    echo "<p>3. 📝 <strong>Try adding a comment</strong> to see if it works</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
