<?php
namespace App\models;

use PDO;

class TimbreModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($data)
    {
        $sql = "INSERT INTO timbre (nom, date_creation, couleurs, pays_origine, `condition`, tirage, dimensions, certifie)
                VALUES (:nom, :date_creation, :couleurs, :pays_origine, :condition, :tirage, :dimensions, :certifie)";
        
        $stmt = $this->pdo->prepare($sql);
        
        $params = [
            ':nom' => $data['nom'],
            ':date_creation' => $data['date_creation'],
            ':couleurs' => $data['couleurs'],
            ':pays_origine' => $data['pays_origine'],
            ':condition' => $data['condition'],
            ':tirage' => $data['tirage'],
            ':dimensions' => $data['dimensions'],
            ':certifie' => $data['certifie']
        ];
        
        $stmt->execute($params);
        return $this->pdo->lastInsertId();
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM timbre WHERE id_timbre = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function update($id, $data)
    {
        $sql = "UPDATE timbre SET 
                nom = :nom, 
                date_creation = :date_creation, 
                couleurs = :couleurs, 
                pays_origine = :pays_origine, 
                `condition` = :condition, 
                tirage = :tirage, 
                dimensions = :dimensions, 
                certifie = :certifie
                WHERE id_timbre = :id";
        
        $stmt = $this->pdo->prepare($sql);
        
        $params = [
            ':nom' => $data['nom'],
            ':date_creation' => $data['date_creation'],
            ':couleurs' => $data['couleurs'],
            ':pays_origine' => $data['pays_origine'],
            ':condition' => $data['condition'],
            ':tirage' => $data['tirage'],
            ':dimensions' => $data['dimensions'],
            ':certifie' => $data['certifie'],
            ':id' => $id
        ];
        
        return $stmt->execute($params);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM timbre WHERE id_timbre = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function saveImage($timbreId, $imagePath, $isMain = false)
    {
        $sql = "INSERT INTO images (id_timbre, chemin, est_principale) VALUES (:id_timbre, :chemin, :est_principale)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id_timbre' => $timbreId,
            ':chemin' => $imagePath,
            ':est_principale' => $isMain ? 1 : 0
        ]);
    }

    public function getImages($timbreId)
    {
        $sql = "SELECT * FROM images WHERE id_timbre = :id_timbre ORDER BY est_principale DESC, id_image ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_timbre' => $timbreId]);
        return $stmt->fetchAll();
    }

    public function getMainImage($timbreId)
    {
        $sql = "SELECT chemin FROM images WHERE id_timbre = :id_timbre AND est_principale = 1 LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_timbre' => $timbreId]);
        $result = $stmt->fetch();
        return $result ? $result['chemin'] : null;
    }

    public function deleteImage($imageId)
    {
        $sql = "SELECT chemin, est_principale FROM images WHERE id_image = :id_image";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_image' => $imageId]);
        $image = $stmt->fetch();
        
        if (!$image) {
            return false;
        }
        
        $filePath = ROOT_PATH . $image['chemin'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $sql = "DELETE FROM images WHERE id_image = :id_image";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([':id_image' => $imageId]);
        
        if ($result && $image['est_principale']) {
            $this->setNewMainImage($imageId);
        }
        
        return $result;
    }

    public function setMainImage(int $imageId): bool
    {
        $sql = "SELECT id_timbre FROM images WHERE id_image = :id_image";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_image' => $imageId]);
        $image = $stmt->fetch();

        if (!$image) {
            return false;
        }

        $timbreId = (int)$image['id_timbre'];

        try {
            $this->pdo->beginTransaction();

            $sqlUnset = "UPDATE images SET est_principale = 0 WHERE id_timbre = :id_timbre";
            $stmtUnset = $this->pdo->prepare($sqlUnset);
            $stmtUnset->execute([':id_timbre' => $timbreId]);

            $sqlSet = "UPDATE images SET est_principale = 1 WHERE id_image = :id_image";
            $stmtSet = $this->pdo->prepare($sqlSet);
            $stmtSet->execute([':id_image' => $imageId]);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    private function setNewMainImage($excludedImageId)
    {
        $stmtFind = $this->pdo->prepare('SELECT id_timbre FROM images WHERE id_image = :excluded_id');
        $stmtFind->execute([':excluded_id' => $excludedImageId]);
        $row = $stmtFind->fetch();
        if (!$row) {
            return;
        }
        $timbreId = (int)$row['id_timbre'];

        $sql = "UPDATE images SET est_principale = 1 WHERE id_image = (
                    SELECT id_image FROM (
                        SELECT id_image FROM images 
                        WHERE id_timbre = :id_timbre AND id_image != :excluded_id 
                        ORDER BY id_image ASC 
                        LIMIT 1
                    ) AS subquery
                )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':excluded_id' => $excludedImageId, ':id_timbre' => $timbreId]);
    }

    public function getAllCountries()
    {
        $sql = "SELECT DISTINCT pays_origine FROM timbre WHERE pays_origine IS NOT NULL ORDER BY pays_origine";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAllConditions()
    {
        return ['Parfaite', 'Excellente', 'Bonne', 'Moyenne', 'Endommagé'];
    }

    public function getAllYears()
    {
        $sql = "SELECT DISTINCT YEAR(date_creation) as annee FROM timbre WHERE date_creation IS NOT NULL ORDER BY annee DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
