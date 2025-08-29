<?php
namespace App\controllers;

use PDO;
use Twig\Environment;
use App\models\MembreModel;
use App\models\AuctionModel;
use App\models\OffreModel;
use App\models\FavorisModel;

class ProfileController extends BaseController
{
    private MembreModel $membreModel;
    private AuctionModel $auctionModel;
    private OffreModel $offreModel;
    private FavorisModel $favorisModel;

    public function __construct(PDO $pdo, Environment $twig, array $config)
    {
        parent::__construct($pdo, $twig, $config);
        $this->membreModel = new MembreModel($pdo);
        $this->auctionModel = new AuctionModel($pdo);
        $this->offreModel = new OffreModel($pdo);
        $this->favorisModel = new FavorisModel($pdo);
    }

    public function showProfile()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /projetweb2/public/login');
            return;
        }

        try {
            $user = $this->membreModel->findById($_SESSION['user_id']);
            $publishedAuctions = $this->auctionModel->getAuctionsByMember($_SESSION['user_id']);
            $bidHistory = $this->offreModel->getBidHistoryByMember($_SESSION['user_id']);
            $favorites = $this->favorisModel->getFavoritesByMember($_SESSION['user_id']);
            
            $totalBids = count($bidHistory);
            $totalPublished = count($publishedAuctions);
            $totalFavorites = count($favorites);
            
            $totalSpent = 0;
            foreach ($bidHistory as $bid) {
                if ($bid['statut'] === 'Terminée' && isset($bid['gagnant_id']) && $bid['gagnant_id'] == $_SESSION['user_id']) {
                    $totalSpent += $bid['montant'];
                }
            }

            echo $this->twig->render('profile/index.twig', [
                'user' => $user,
                'publishedAuctions' => $publishedAuctions,
                'bidHistory' => $bidHistory,
                'favorites' => $favorites,
                'stats' => [
                    'totalBids' => $totalBids,
                    'totalPublished' => $totalPublished,
                    'totalFavorites' => $totalFavorites,
                    'totalSpent' => number_format($totalSpent, 2)
                ],
                'session' => $_SESSION ?? []
            ]);
        } catch (\Exception $e) {
            error_log("Error in ProfileController::showProfile: " . $e->getMessage());
            http_response_code(500);
            echo $this->twig->render('errors/500.twig', [
                'error' => 'Erreur lors du chargement du profil',
                'session' => $_SESSION ?? []
            ]);
        }
    }

    public function getPersonalInfo()
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        try {
            $user = $this->membreModel->findById($_SESSION['user_id']);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'Utilisateur non trouvé']);
                return;
            }

            unset($user['mot_de_passe']);
            
            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function updateProfile()
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        $data = $_POST;
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Données manquantes']);
            return;
        }

        try {
            $updateData = [];
            
            if (isset($data['nom_utilisateur'])) {
                $updateData['nom_utilisateur'] = trim($data['nom_utilisateur']);
            }
            
            if (isset($data['courriel'])) {
                $updateData['courriel'] = trim($data['courriel']);
            }

            if (empty($updateData)) {
                http_response_code(400);
                echo json_encode(['error' => 'Aucune donnée à mettre à jour']);
                return;
            }

            $success = $this->membreModel->updateProfile($_SESSION['user_id'], $updateData);
            
            if ($success) {
                if (isset($updateData['nom_utilisateur'])) {
                    $_SESSION['username'] = $updateData['nom_utilisateur'];
                    $_SESSION['user_name'] = $updateData['nom_utilisateur'];
                }
                if (isset($updateData['courriel'])) {
                    $_SESSION['user_email'] = $updateData['courriel'];
                }

                $updatedUser = $this->membreModel->findById($_SESSION['user_id']);
                unset($updatedUser['mot_de_passe']);
                
                echo json_encode([
                    'success' => true,
                    'user' => $updatedUser,
                    'message' => 'Profil mis à jour avec succès'
                ]);
            } else {
                throw new \Exception('Erreur lors de la mise à jour du profil');
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getOfferHistory()
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        try {
            $bidHistory = $this->offreModel->getBidHistoryByMember($_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'bidHistory' => $bidHistory
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getPublishedAuctions()
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        try {
            $publishedAuctions = $this->auctionModel->getAuctionsByMember($_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'publishedAuctions' => $publishedAuctions
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
