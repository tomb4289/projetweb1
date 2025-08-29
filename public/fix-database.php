<?php
session_start();

require_once __DIR__ . '/../app/config/database.php';

echo "<h1>Database Fix Script</h1>";

try {
    
    echo "<h2>1. Setting up commentaires table...</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'commentaires'");
    $commentTableExists = $stmt->rowCount() > 0;
    
    if (!$commentTableExists) {
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
    } else {
        echo "<p>✅ <strong>Commentaires table already exists!</strong></p>";
    }

    echo "<h2>2. Updating auction statuses...</h2>";

    $stmt = $pdo->query("SELECT DISTINCT statut FROM enchere");
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Current auction statuses:</strong> " . implode(', ', $statuses) . "</p>";
    
    // Mise à jour des enchères terminées vers le statut 'Terminée'
    $sql = "UPDATE enchere SET statut = 'Terminée' WHERE date_fin < NOW() AND statut = 'Active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $updatedCount = $stmt->rowCount();
    
    echo "<p>✅ <strong>Updated $updatedCount auctions to 'Terminée' status</strong></p>";

    $stmt = $pdo->query("SELECT DISTINCT statut FROM enchere");
    $finalStatuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Final auction statuses:</strong> " . implode(', ', $finalStatuses) . "</p>";

    echo "<h2>3. Checking terminated auctions...</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM enchere WHERE statut = 'Terminée'");
    $terminatedCount = $stmt->fetch()['count'];
    
    echo "<p><strong>Terminated auctions:</strong> $terminatedCount</p>";
    
    if ($terminatedCount > 0) {
        
        $stmt = $pdo->query("SELECT id_enchere, id_timbre, date_fin, prix_plancher FROM enchere WHERE statut = 'Terminée' LIMIT 3");
        $auctions = $stmt->fetchAll();
        
        echo "<h3>Sample terminated auctions:</h3>";
        foreach ($auctions as $auction) {
            echo "<p><strong>ID:</strong> {$auction['id_enchere']}, <strong>Timbre ID:</strong> {$auction['id_timbre']}, <strong>End date:</strong> {$auction['date_fin']}</p>";
        }
    }

    echo "<h2>4. Testing commentaires table...</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'commentaires'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        $stmt = $pdo->query("DESCRIBE commentaires");
        $columns = $stmt->fetchAll();
        
        echo "<p>✅ <strong>Commentaires table structure:</strong></p>";
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

    echo "<h2>5. Session check...</h2>";
    if (isset($_SESSION['user_id'])) {
        echo "<p>✅ <strong>User logged in:</strong> {$_SESSION['username']} (ID: {$_SESSION['user_id']})</p>";
    } else {
        echo "<p>❌ <strong>No user logged in</strong></p>";
        echo "<p><a href='/projetweb2/public/login'>Login here</a></p>";
    }
    
    echo "<hr>";
    echo "<h2>🎉 Database Fix Complete!</h2>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<p>1. ✅ Commentaires table is set up</p>";
    echo "<p>2. ✅ Auction statuses are updated</p>";
    echo "<p>3. 🔄 <strong>Test the commenting system:</strong></p>";
    echo "<p>   - Go to <a href='/projetweb2/public/auctions/archives'>Archives page</a></p>";
    echo "<p>   - Click 'Commentaires' on a terminated auction</p>";
    echo "<p>   - Try adding a comment</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
