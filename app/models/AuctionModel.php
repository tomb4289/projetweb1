<?php
namespace App\Models;

use PDO;

class AuctionModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllActiveAuctions()
    {
        $sql = "SELECT e.*, t.*, m.nom_utilisateur as vendeur_nom,
                       COALESCE((SELECT MAX(montant) FROM offre o WHERE o.id_enchere = e.id_enchere), 0) as prix_actuel,
                       (SELECT COUNT(*) FROM offre o WHERE o.id_enchere = e.id_enchere) as nombre_offres,
                       (SELECT chemin FROM images i WHERE i.id_timbre = t.id_timbre ORDER BY i.est_principale DESC, i.id_image ASC LIMIT 1) as image_principale
                FROM enchere e
                JOIN timbre t ON e.id_timbre = t.id_timbre
                JOIN membre m ON e.id_membre = m.id_membre
                WHERE e.statut = 'Active' AND e.date_fin > NOW()
                ORDER BY e.date_fin ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getFeaturedAuctions()
    {
        $sql = "SELECT e.*, t.*, m.nom_utilisateur as vendeur_nom,
                       COALESCE((SELECT MAX(montant) FROM offre o WHERE o.id_enchere = e.id_enchere), 0) as prix_actuel,
                       (SELECT COUNT(*) FROM offre o WHERE o.id_enchere = e.id_enchere) as nombre_offres,
                       (SELECT chemin FROM images i WHERE i.id_timbre = t.id_timbre ORDER BY i.est_principale DESC, i.id_image ASC LIMIT 1) as image_principale
                FROM enchere e
                JOIN timbre t ON e.id_timbre = t.id_timbre
                JOIN membre m ON e.id_membre = m.id_membre
                WHERE e.coup_de_coeur_lord = 1 AND e.statut = 'Active' AND e.date_fin > NOW()
                ORDER BY e.date_fin ASC
                LIMIT 6";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function searchAuctions($filters)
    {
        $sql = "SELECT e.*, t.*, m.nom_utilisateur as vendeur_nom,
                       COALESCE((SELECT MAX(montant) FROM offre o WHERE o.id_enchere = e.id_enchere), 0) as prix_actuel,
                       (SELECT COUNT(*) FROM offre o WHERE o.id_enchere = e.id_enchere) as nombre_offres,
                       (SELECT chemin FROM images i WHERE i.id_timbre = t.id_timbre ORDER BY i.est_principale DESC, i.id_image ASC LIMIT 1) as image_principale
                FROM enchere e
                JOIN timbre t ON e.id_timbre = t.id_timbre
                JOIN membre m ON e.id_membre = m.id_membre
                WHERE e.statut = 'Active' AND e.date_fin > NOW()";
        
        $params = [];
        $conditions = [];

        if (!empty($filters['recherche'])) {
            $conditions[] = "(t.nom LIKE :recherche OR t.pays_origine LIKE :recherche)";
            $params[':recherche'] = '%' . $filters['recherche'] . '%';
        }

        if (!empty($filters['pays'])) {
            $conditions[] = "t.pays_origine = :pays";
            $params[':pays'] = $filters['pays'];
        }

        if (!empty($filters['annee'])) {
            $conditions[] = "YEAR(t.date_creation) = :annee";
            $params[':annee'] = $filters['annee'];
        }

        if (!empty($filters['condition'])) {
            $conditions[] = "t.condition = :condition";
            $params[':condition'] = $filters['condition'];
        }

        if (!empty($filters['prix_min'])) {
            $conditions[] = "e.prix_plancher >= :prix_min";
            $params[':prix_min'] = $filters['prix_min'];
        }

        if (!empty($filters['prix_max'])) {
            $conditions[] = "e.prix_plancher <= :prix_max";
            $params[':prix_max'] = $filters['prix_max'];
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY e.date_fin ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAuctionById($id)
    {
        $sql = "SELECT e.*, t.*, m.nom_utilisateur as vendeur_nom,
                       COALESCE((SELECT MAX(montant) FROM offre o WHERE o.id_enchere = e.id_enchere), 0) as prix_actuel,
                       (SELECT COUNT(*) FROM offre o WHERE o.id_enchere = e.id_enchere) as nombre_offres,
                       (SELECT chemin FROM images i WHERE i.id_timbre = t.id_timbre ORDER BY i.est_principale DESC, i.id_image ASC LIMIT 1) as image_principale
                FROM enchere e
                JOIN timbre t ON e.id_timbre = t.id_timbre
                JOIN membre m ON e.id_membre = m.id_membre
                WHERE e.id_enchere = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create($data)
    {
        $sql = "INSERT INTO enchere (id_timbre, id_membre, date_debut, date_fin, prix_plancher, coup_de_coeur_lord, statut)
                VALUES (:id_timbre, :id_membre, :date_debut, :date_fin, :prix_plancher, :coup_de_coeur_lord, :statut)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }

    public function updateStatus($id, $status)
    {
        $sql = "UPDATE enchere SET statut = :statut WHERE id_enchere = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':statut' => $status, ':id' => $id]);
    }

    public function update($id, $data)
    {
        $sql = "UPDATE enchere SET ";
        $updates = [];
        $params = [':id' => $id];

        foreach ($data as $field => $value) {
            $updates[] = "$field = :$field";
            $params[":$field"] = $value;
        }

        $sql .= implode(', ', $updates);
        $sql .= " WHERE id_enchere = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id)
    {
        $auction = $this->getAuctionById($id);
        if (!$auction) {
            return false;
        }

        $sql = "DELETE FROM offre WHERE id_enchere = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $sql = "DELETE FROM favoris WHERE id_enchere = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $sql = "DELETE FROM enchere WHERE id_enchere = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $sql = "DELETE FROM timbre WHERE id_timbre = :id_timbre";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id_timbre' => $auction['id_timbre']]);
    }

    public function getAuctionsByUser($userId)
    {
        $sql = "SELECT e.*, t.*, 
                       COALESCE((SELECT MAX(montant) FROM offre o WHERE o.id_enchere = e.id_enchere), 0) as prix_actuel,
                       (SELECT COUNT(*) FROM offre o WHERE o.id_enchere = e.id_enchere) as nombre_offres,
                       (SELECT chemin FROM images i WHERE i.id_timbre = t.id_timbre ORDER BY i.est_principale DESC, i.id_image ASC LIMIT 1) as image_principale
                FROM enchere e
                JOIN timbre t ON e.id_timbre = t.id_timbre
                WHERE e.id_membre = :user_id
                ORDER BY e.date_debut DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getEndingSoonAuctions($limit = 5)
    {
        $sql = "SELECT e.*, t.*, m.nom_utilisateur as vendeur_nom,
                       COALESCE((SELECT MAX(montant) FROM offre o WHERE o.id_enchere = e.id_enchere), 0) as prix_actuel,
                       (SELECT COUNT(*) FROM offre o WHERE o.id_enchere = e.id_enchere) as nombre_offres,
                       (SELECT chemin FROM images i WHERE i.id_timbre = t.id_timbre AND i.est_principale = 1 LIMIT 1) as image_principale
                FROM enchere e
                JOIN timbre t ON e.id_timbre = t.id_timbre
                JOIN membre m ON e.id_membre = m.id_membre
                WHERE e.statut = 'Active' AND e.date_fin > NOW() AND e.date_fin <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
                ORDER BY e.date_fin ASC
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function toggleLordFavorite($auctionId)
    {
        $sql = "UPDATE enchere SET coup_de_coeur_lord = NOT coup_de_coeur_lord WHERE id_enchere = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $auctionId]);
    }
}
