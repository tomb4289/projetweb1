<?php
namespace App\models;

use PDO;
use PDOException;

class MembreModel
{
    private $pdo;
    protected string $table = 'membre';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUsernameOrEmail(string $identifier)
    {
        try {
            error_log("MembreModel::findByUsernameOrEmail called for identifier: $identifier");
            
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE nom_utilisateur = :identifier OR courriel = :identifier LIMIT 1");
            $stmt->execute([':identifier' => $identifier]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Database query result: " . print_r($result, true));
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error in MembreModel::findByUsernameOrEmail: " . $e->getMessage());
            return false;
        }
    }

    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id_membre = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error in MembreModel::findById: " . $e->getMessage());
            return null;
        }
    }

    public function checkUser(string $username, string $password)
    {
        error_log("MembreModel::checkUser called for username: $username");
        
        $user = $this->findByUsernameOrEmail($username);

        if ($user) {
            error_log("User found in database: " . print_r($user, true));
            
            if (password_verify($password, $user['mot_de_passe'])) {
                error_log("Password verification successful");
                
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id_membre'];
                $_SESSION['username'] = $user['nom_utilisateur'];
                $_SESSION['user_email'] = $user['courriel'];
                $_SESSION['user_name'] = $user['nom_utilisateur'];
                $_SESSION['user_role'] = $user['role'] ?? 'utilisateur';
                $_SESSION['fingerPrint'] = md5(($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                $_SESSION['authenticated'] = true;
                $_SESSION['login_time'] = time();
                
                error_log("Session data set: " . print_r($_SESSION, true));
                return $user;
            } else {
                error_log("Password verification failed");
            }
        } else {
            error_log("User not found in database");
        }
        
        return false;
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function create(array $data): ?int
    {
        try {
            error_log("MembreModel::create called with data: " . print_r($data, true));
            
            $sql = "INSERT INTO {$this->table} (nom_utilisateur, courriel, mot_de_passe) VALUES (:nom_utilisateur, :courriel, :mot_de_passe)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                'nom_utilisateur' => $data['nom_utilisateur'],
                'courriel' => $data['courriel'],
                'mot_de_passe' => $data['mot_de_passe']
            ]);
            
            if ($result) {
                $userId = $this->pdo->lastInsertId();
                error_log("User created successfully with ID: $userId");
                return $userId;
            } else {
                error_log("Failed to create user - execute returned false");
                return null;
            }
            
        } catch (PDOException $e) {
            error_log("Error in MembreModel::create: " . $e->getMessage());
            throw $e;
        }
    }

    public function unique(string $column, string $value): bool
    {
        try {
            error_log("Checking uniqueness for $column: $value");
            
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$column} = ?");
            $stmt->execute([$value]);
            $count = $stmt->fetchColumn();
            
            $isUnique = $count == 0;
            error_log("Uniqueness check for $column: $value - Result: " . ($isUnique ? 'unique' : 'duplicate'));
            
            return $isUnique;
            
        } catch (PDOException $e) {
            error_log("Error in MembreModel::unique: " . $e->getMessage());
            return false;
        }
    }

    public function updateProfile(int $userId, array $data): bool
    {
        try {
            $fields = [];
            $params = [':id' => $userId];
            
            foreach ($data as $field => $value) {
                if (in_array($field, ['nom_utilisateur', 'courriel'])) {
                    $fields[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id_membre = :id";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in MembreModel::updateProfile: " . $e->getMessage());
            return false;
        }
    }
}
