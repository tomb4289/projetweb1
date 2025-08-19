<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "You need to be logged in to create an auction.<br>";
    echo "<a href='/projetweb2/public/login'>Login here</a><br>";
    exit;
}

echo "User ID: " . $_SESSION['user_id'] . "<br><br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "Form submitted!<br>";
    echo "POST data: <pre>" . print_r($_POST, true) . "</pre><br>";
    
    // Test creating a simple auction
    try {
        $config = require_once __DIR__ . '/../config.php';
        $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
        $pdo = new PDO($dsn, $config['database']['user'], $config['database']['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create timbre
        $stmt = $pdo->prepare("INSERT INTO timbre (nom, pays_origine, `condition`) VALUES (?, ?, ?)");
        $result = $stmt->execute([$_POST['nom'], $_POST['pays_origine'], $_POST['condition']]);
        
        if ($result) {
            $timbreId = $pdo->lastInsertId();
            echo "Timbre created with ID: $timbreId<br>";
            
            // Create auction
            $stmt = $pdo->prepare("INSERT INTO enchere (id_timbre, id_membre, date_debut, date_fin, prix_plancher, statut) VALUES (?, ?, NOW(), ?, ?, 'Active')");
            $result = $stmt->execute([$timbreId, $_SESSION['user_id'], $_POST['date_fin'], $_POST['prix_plancher']]);
            
            if ($result) {
                $auctionId = $pdo->lastInsertId();
                echo "Auction created with ID: $auctionId<br>";
                echo "Success! <a href='/projetweb2/public/auctions'>View all auctions</a><br>";
            } else {
                echo "Failed to create auction<br>";
            }
        } else {
            echo "Failed to create timbre<br>";
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
} else {
    ?>
    <form method="POST">
        <h2>Test Auction Creation</h2>
        
        <div>
            <label>Nom du Timbre:</label>
            <input type="text" name="nom" value="Test Timbre" required>
        </div>
        
        <div>
            <label>Pays d'Origine:</label>
            <select name="pays_origine" required>
                <option value="Canada">Canada</option>
                <option value="France">France</option>
            </select>
        </div>
        
        <div>
            <label>Condition:</label>
            <select name="condition">
                <option value="Bonne">Bonne</option>
                <option value="Excellente">Excellente</option>
            </select>
        </div>
        
        <div>
            <label>Prix de Départ:</label>
            <input type="number" name="prix_plancher" value="10" step="0.01" required>
        </div>
        
        <div>
            <label>Date de Fin:</label>
            <input type="datetime-local" name="date_fin" required>
        </div>
        
        <button type="submit">Create Test Auction</button>
    </form>
    
    <script>
        // Set default date (tomorrow)
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.querySelector('input[name="date_fin"]').value = tomorrow.toISOString().slice(0, 16);
    </script>
    <?php
}
?>
