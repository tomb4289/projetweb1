<?php
namespace App\models;

use PDO;

class OffreModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($data)
    {
        $sql = "INSERT INTO offre (id_enchere, id_membre, montant, date_offre)
                VALUES (:id_enchere, :id_membre, :montant, :date_offre)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }

    public function getOffresByAuction($auctionId)
    {
        $sql = "SELECT o.*, m.nom_utilisateur
                FROM offre o
                JOIN membre m ON o.id_membre = m.id_membre
                WHERE o.id_enchere = :auction_id
                ORDER BY o.montant DESC, o.date_offre ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':auction_id' => $auctionId]);
        return $stmt->fetchAll();
    }

    public function getHighestBid($auctionId)
    {
        $sql = "SELECT o.*, m.nom_utilisateur
                FROM offre o
                JOIN membre m ON o.id_membre = m.id_membre
                WHERE o.id_enchere = :auction_id
                ORDER BY o.montant DESC
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':auction_id' => $auctionId]);
        return $stmt->fetch();
    }

    public function getBidsByUser($userId)
    {
        $sql = "SELECT o.*, e.*, t.nom as timbre_nom
                FROM offre o
                JOIN enchere e ON o.id_enchere = e.id_enchere
                JOIN timbre t ON e.id_timbre = t.id_timbre
                WHERE o.id_membre = :user_id
                ORDER BY o.date_offre DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function validateBid($auctionId, $amount)
    {
        $highestBid = $this->getHighestBid($auctionId);
        if ($highestBid && $amount <= $highestBid['montant']) {
            return false;
        }
        return true;
    }

    public function getBidCount($auctionId)
    {
        $sql = "SELECT COUNT(*) as count FROM offre WHERE id_enchere = :auction_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':auction_id' => $auctionId]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getBidHistoryByMember(int $memberId): array
    {
        $sql = "SELECT o.*, e.*, t.nom as timbre_nom, t.pays_origine, t.condition,
                       m.nom_utilisateur as vendeur_nom,
                       (SELECT chemin FROM images i WHERE i.id_timbre = t.id_timbre ORDER BY i.est_principale DESC, i.id_image ASC LIMIT 1) as image_principale,
                       CASE 
                           WHEN e.date_fin < NOW() AND o.montant = (SELECT MAX(montant) FROM offre o2 WHERE o2.id_enchere = e.id_enchere) THEN 'Gagnée'
                           WHEN e.date_fin < NOW() THEN 'Perdue'
                           WHEN e.statut = 'Archivée' THEN 'Archivée'
                           ELSE 'En cours'
                       END as statut_offre,
                       CASE 
                           WHEN e.date_fin < NOW() AND o.montant = (SELECT MAX(montant) FROM offre o2 WHERE o2.id_enchere = e.id_enchere) THEN o.montant
                           ELSE NULL
                       END as montant_gagnant,
                       CASE 
                           WHEN e.date_fin < NOW() AND o.montant = (SELECT MAX(montant) FROM offre o2 WHERE o2.id_enchere = e.id_enchere) THEN o.id_membre
                           ELSE NULL
                       END as gagnant_id
                FROM offre o
                JOIN enchere e ON o.id_enchere = e.id_enchere
                JOIN timbre t ON e.id_timbre = t.id_timbre
                JOIN membre m ON e.id_membre = m.id_membre
                WHERE o.id_membre = :member_id
                ORDER BY o.date_offre DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':member_id' => $memberId]);
        return $stmt->fetchAll();
    }
}
