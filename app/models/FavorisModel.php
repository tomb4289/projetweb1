<?php
namespace App\Models;

use PDO;

class FavorisModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function addFavorite($userId, $auctionId)
    {
        $sql = "INSERT INTO favoris (id_membre, id_enchere, date_ajout) 
                VALUES (:user_id, :auction_id, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':auction_id' => $auctionId
        ]);
    }

    public function removeFavorite($userId, $auctionId)
    {
        $sql = "DELETE FROM favoris WHERE id_membre = :user_id AND id_enchere = :auction_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':auction_id' => $auctionId
        ]);
    }

    public function isFavorite($userId, $auctionId)
    {
        $sql = "SELECT COUNT(*) as count FROM favoris 
                WHERE id_membre = :user_id AND id_enchere = :auction_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':auction_id' => $auctionId
        ]);
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    public function getUserFavorites($userId)
    {
        $sql = "SELECT f.*, e.*, t.*, m.nom_utilisateur as vendeur_nom,
                       COALESCE((SELECT MAX(montant) FROM offre o WHERE o.id_enchere = e.id_enchere), 0) as prix_actuel,
                       (SELECT COUNT(*) FROM offre o WHERE o.id_enchere = e.id_enchere) as nombre_offres,
                       (SELECT chemin FROM images i WHERE i.id_timbre = t.id_timbre ORDER BY i.est_principale DESC, i.id_image ASC LIMIT 1) as image_principale
                FROM favoris f
                JOIN enchere e ON f.id_enchere = e.id_enchere
                JOIN timbre t ON e.id_timbre = t.id_timbre
                JOIN membre m ON e.id_membre = m.id_membre
                WHERE f.id_membre = :user_id
                ORDER BY f.date_ajout DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getFavoriteCount($auctionId)
    {
        $sql = "SELECT COUNT(*) as count FROM favoris WHERE id_enchere = :auction_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':auction_id' => $auctionId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
}
