<?php
namespace App\models;

use PDO;
use PDOException;

class CommentModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getCommentsByAuction(int $auctionId): array
    {
        try {
            $sql = "SELECT c.id_commentaire as id, c.id_enchere, c.id_membre, c.contenu, c.note, c.date_creation, c.approuve, m.nom_utilisateur
                    FROM commentaires c
                    JOIN membre m ON c.id_membre = m.id_membre
                    WHERE c.id_enchere = :auction_id AND c.approuve = 1
                    ORDER BY c.date_creation DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':auction_id' => $auctionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in CommentModel::getCommentsByAuction: " . $e->getMessage());
            return [];
        }
    }

    public function createComment(array $data): ?int
    {
        try {
            $sql = "INSERT INTO commentaires (id_enchere, id_membre, contenu, note) 
                    VALUES (:id_enchere, :id_membre, :contenu, :note)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':id_enchere' => $data['id_enchere'],
                ':id_membre' => $data['id_membre'],
                ':contenu' => $data['contenu'],
                ':note' => $data['note'] ?? null
            ]);
            
            if ($result) {
                return $this->pdo->lastInsertId();
            }
            return null;
        } catch (PDOException $e) {
            error_log("Error in CommentModel::createComment: " . $e->getMessage());
            return null;
        }
    }

    public function updateComment(int $commentId, int $userId, array $data): bool
    {
        try {
            $sql = "UPDATE commentaires 
                    SET contenu = :contenu, note = :note, date_modification = CURRENT_TIMESTAMP
                    WHERE id_commentaire = :id_commentaire AND id_membre = :id_membre";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id_commentaire' => $commentId,
                ':id_membre' => $userId,
                ':contenu' => $data['contenu'],
                ':note' => $data['note'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error in CommentModel::updateComment: " . $e->getMessage());
            return false;
        }
    }

    public function deleteComment(int $commentId, int $userId): bool
    {
        try {
            $sql = "DELETE FROM commentaires 
                    WHERE id_commentaire = :id_commentaire AND id_membre = :id_membre";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id_commentaire' => $commentId,
                ':id_membre' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Error in CommentModel::deleteComment: " . $e->getMessage());
            return false;
        }
    }

    public function getCommentById(int $commentId): ?array
    {
        try {
            $sql = "SELECT c.id_commentaire as id, c.id_enchere, c.id_membre, c.contenu, c.note, c.date_creation, c.approuve, m.nom_utilisateur 
                    FROM commentaires c
                    JOIN membre m ON c.id_membre = m.id_membre
                    WHERE c.id_commentaire = :id_commentaire";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_commentaire' => $commentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result;
            }
            
            $sql = "SELECT id_commentaire as id, id_enchere, id_membre, contenu, note, date_creation, approuve
                    FROM commentaires 
                    WHERE id_commentaire = :id_commentaire";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_commentaire' => $commentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $result['nom_utilisateur'] = 'Utilisateur';
                return $result;
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Error in CommentModel::getCommentById: " . $e->getMessage());
            
            try {
                $sql = "SELECT id_commentaire as id, id_enchere, id_membre, contenu, note, date_creation, approuve
                        FROM commentaires 
                        WHERE id_commentaire = :id_commentaire";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':id_commentaire' => $commentId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $result['nom_utilisateur'] = 'Utilisateur';
                    return $result;
                }
            } catch (PDOException $e2) {
                error_log("Fallback query also failed: " . $e2->getMessage());
            }
            
            return null;
        }
    }

    public function getAverageRating(int $auctionId): float
    {
        try {
            $sql = "SELECT AVG(note) as moyenne
                    FROM commentaires 
                    WHERE id_enchere = :auction_id AND note IS NOT NULL AND approuve = 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':auction_id' => $auctionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['moyenne'] ? round($result['moyenne'], 1) : 0.0;
        } catch (PDOException $e) {
            error_log("Error in CommentModel::getAverageRating: " . $e->getMessage());
            return 0.0;
        }
    }

    public function getCommentCount(int $auctionId): int
    {
        try {
            $sql = "SELECT COUNT(*) as total
                    FROM commentaires 
                    WHERE id_enchere = :auction_id AND approuve = 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':auction_id' => $auctionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Error in CommentModel::getCommentCount: " . $e->getMessage());
            return 0;
        }
    }
}
