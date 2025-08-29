<?php
namespace App\controllers;

use PDO;
use Twig\Environment;
use App\models\CommentModel;
use App\models\AuctionModel;

class CommentController extends BaseController
{
    private CommentModel $commentModel;
    private AuctionModel $auctionModel;

    public function __construct(PDO $pdo, Environment $twig, array $config)
    {
        parent::__construct($pdo, $twig, $config);
        $this->commentModel = new CommentModel($pdo);
        $this->auctionModel = new AuctionModel($pdo);
    }

    public function createComment()
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['id_enchere']) || !isset($data['contenu'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Données manquantes']);
            return;
        }

        error_log("CommentController::createComment - Received data: " . print_r($data, true));
        error_log("CommentController::createComment - User ID: " . $_SESSION['user_id']);

        $auction = $this->auctionModel->getAuctionById($data['id_enchere']);
        
        error_log("CommentController::createComment - Auction data: " . print_r($auction, true));
        
        if (!$auction) {
            error_log("CommentController::createComment - Auction not found for ID: " . $data['id_enchere']);
            http_response_code(400);
            echo json_encode(['error' => 'Enchère non trouvée']);
            return;
        }
        
        if ($auction['statut'] !== 'Terminée') {
            error_log("CommentController::createComment - Auction status is: " . $auction['statut'] . " (expected: Terminée)");
            http_response_code(400);
            echo json_encode(['error' => 'Commentaires autorisés uniquement sur les enchères terminées. Statut actuel: ' . $auction['statut']]);
            return;
        }

        $commentData = [
            'id_enchere' => (int) $data['id_enchere'],
            'id_membre' => $_SESSION['user_id'],
            'contenu' => trim($data['contenu']),
            'note' => isset($data['note']) ? (int) $data['note'] : null
        ];

        error_log("CommentController::createComment - Comment data: " . print_r($commentData, true));

        if ($commentData['note'] !== null && ($commentData['note'] < 1 || $commentData['note'] > 5)) {
            http_response_code(400);
            echo json_encode(['error' => 'La note doit être entre 1 et 5']);
            return;
        }

        if (strlen($commentData['contenu']) < 10 || strlen($commentData['contenu']) > 1000) {
            http_response_code(400);
            echo json_encode(['error' => 'Le commentaire doit contenir entre 10 et 1000 caractères']);
            return;
        }

        try {
            $commentId = $this->commentModel->createComment($commentData);
            
            if ($commentId) {
                $comment = $this->commentModel->getCommentById($commentId);
                echo json_encode([
                    'success' => true,
                    'comment' => $comment,
                    'message' => 'Commentaire ajouté avec succès'
                ]);
            } else {
                throw new \Exception('Erreur lors de la création du commentaire');
            }
        } catch (\Exception $e) {
            error_log("CommentController::createComment - Exception: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function updateComment($commentId)
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

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['contenu'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Contenu manquant']);
            return;
        }

        $comment = $this->commentModel->getCommentById($commentId);
        if (!$comment || $comment['id_membre'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès non autorisé']);
            return;
        }

        $updateData = [
            'contenu' => trim($data['contenu']),
            'note' => isset($data['note']) ? (int) $data['note'] : null
        ];

        if ($updateData['note'] !== null && ($updateData['note'] < 1 || $updateData['note'] > 5)) {
            http_response_code(400);
            echo json_encode(['error' => 'La note doit être entre 1 et 5']);
            return;
        }

        if (strlen($updateData['contenu']) < 10 || strlen($updateData['contenu']) > 1000) {
            http_response_code(400);
            echo json_encode(['error' => 'Le commentaire doit contenir entre 10 et 1000 caractères']);
            return;
        }

        try {
            $success = $this->commentModel->updateComment($commentId, $_SESSION['user_id'], $updateData);
            
            if ($success) {
                $updatedComment = $this->commentModel->getCommentById($commentId);
                echo json_encode([
                    'success' => true,
                    'comment' => $updatedComment,
                    'message' => 'Commentaire modifié avec succès'
                ]);
            } else {
                throw new \Exception('Erreur lors de la modification du commentaire');
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function deleteComment($commentId)
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        $comment = $this->commentModel->getCommentById($commentId);
        if (!$comment || $comment['id_membre'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès non autorisé']);
            return;
        }

        try {
            $success = $this->commentModel->deleteComment($commentId, $_SESSION['user_id']);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Commentaire supprimé avec succès'
                ]);
            } else {
                throw new \Exception('Erreur lors de la suppression du commentaire');
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getComments($auctionId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        try {
            $comments = $this->commentModel->getCommentsByAuction($auctionId);
            $averageRating = $this->commentModel->getAverageRating($auctionId);
            $commentCount = $this->commentModel->getCommentCount($auctionId);
            
            echo json_encode([
                'success' => true,
                'comments' => $comments,
                'averageRating' => $averageRating,
                'commentCount' => $commentCount
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
