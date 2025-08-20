<?php
namespace App\Controllers;

use PDO;
use Twig\Environment;
use App\Models\AuctionModel;
use App\Models\TimbreModel;
use App\Models\OffreModel;
use App\Models\FavorisModel;

class AuctionController extends BaseController
{
    private AuctionModel $auctionModel;
    private TimbreModel $timbreModel;
    private OffreModel $offreModel;
    private FavorisModel $favorisModel;

    public function __construct(PDO $pdo, Environment $twig, array $config)
    {
        parent::__construct($pdo, $twig, $config);
        $this->auctionModel = new AuctionModel($pdo);
        $this->timbreModel = new TimbreModel($pdo);
        $this->offreModel = new OffreModel($pdo);
        $this->favorisModel = new FavorisModel($pdo);
    }

    public function index()
    {
        $auctions = $this->auctionModel->getAllActiveAuctions();
        echo $this->twig->render('auctions/index.twig', [
            'auctions' => $auctions,
            'session' => $_SESSION ?? []
        ]);
    }

    public function search()
    {
        $filters = [
            'pays' => $_GET['pays'] ?? null,
            'annee' => $_GET['annee'] ?? null,
            'condition' => $_GET['condition'] ?? null,
            'prix_min' => $_GET['prix_min'] ?? null,
            'prix_max' => $_GET['prix_max'] ?? null,
            'recherche' => $_GET['recherche'] ?? null
        ];

        $auctions = $this->auctionModel->searchAuctions($filters);
        
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode($auctions);
            return;
        }

        echo $this->twig->render('auctions/search.twig', [
            'auctions' => $auctions,
            'filters' => $filters,
            'session' => $_SESSION ?? []
        ]);
    }

    public function show($id)
    {
        $auction = $this->auctionModel->getAuctionById($id);
        $offres = $this->offreModel->getOffresByAuction($id);
        $isFavorite = false;
        
        if (isset($_SESSION['user_id'])) {
            $isFavorite = $this->favorisModel->isFavorite($_SESSION['user_id'], $id);
        }

        if (!$auction) {
            http_response_code(404);
            echo $this->twig->render('errors/404.twig');
            return;
        }

        $images = $this->timbreModel->getImages($auction['id_timbre']);
        $mainImage = $this->timbreModel->getMainImage($auction['id_timbre']);

        echo $this->twig->render('auctions/show.twig', [
            'auction' => $auction,
            'offres' => $offres,
            'isFavorite' => $isFavorite,
            'images' => $images,
            'mainImage' => $mainImage,
            'session' => $_SESSION ?? []
        ]);
    }

    public function create()
    {
        error_log("AuctionController::create() called");
        error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . print_r($_POST, true));
        
        if (!isset($_SESSION['user_id'])) {
            error_log("User not logged in, redirecting to login");
            header('Location: /projetweb1/public/login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log("POST request detected, calling handleCreateAuction");
            $this->handleCreateAuction();
            return;
        }

        error_log("GET request, rendering create form");
        echo $this->twig->render('auctions/create.twig', [
            'session' => $_SESSION ?? []
        ]);
    }

    private function handleCreateAuction()
    {
        try {
            error_log("Starting auction creation process...");
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));
            
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                error_log("Images detected in upload");
                error_log("Number of images: " . count($_FILES['images']['name']));
                
                for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                    if (!empty($_FILES['images']['name'][$i])) {
                        error_log("Image $i: " . $_FILES['images']['name'][$i] . 
                                " (Size: " . $_FILES['images']['size'][$i] . 
                                " bytes, Type: " . $_FILES['images']['type'][$i] . 
                                ", Error: " . $_FILES['images']['error'][$i] . ")");
                    }
                }
            } else {
                error_log("No images detected in upload");
            }
            
            $requiredFields = ['nom', 'pays_origine', 'prix_plancher', 'date_fin'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new \Exception("Le champ $field est requis");
                }
            }

            error_log("Input validation passed");

            try {
                $images = $this->handleImageUploads($_FILES['images'] ?? []);
                error_log("Images processed successfully: " . print_r($images, true));
            } catch (\Exception $e) {
                error_log("Image processing failed: " . $e->getMessage());
                throw new \Exception("Erreur lors du traitement des images: " . $e->getMessage());
            }

            $timbreData = [
                'nom' => $_POST['nom'],
                'date_creation' => !empty($_POST['date_creation']) ? $_POST['date_creation'] . '-01-01' : null,
                'couleurs' => !empty($_POST['couleurs']) ? $_POST['couleurs'] : null,
                'pays_origine' => $_POST['pays_origine'],
                'condition' => $_POST['condition'] ?? 'Bonne',
                'tirage' => !empty($_POST['tirage']) ? (int)$_POST['tirage'] : null,
                'dimensions' => !empty($_POST['dimensions']) ? $_POST['dimensions'] : null,
                'certifie' => isset($_POST['certifie']) ? 1 : 0
            ];
            
            error_log("Creating timbre with data: " . print_r($timbreData, true));
            $timbreId = $this->timbreModel->create($timbreData);
            error_log("Timbre created with ID: $timbreId");

            if (!$timbreId) {
                throw new \Exception("Failed to create timbre");
            }

            $auctionData = [
                'id_timbre' => $timbreId,
                'id_membre' => $_SESSION['user_id'],
                'date_debut' => date('Y-m-d H:i:s'),
                'date_fin' => $_POST['date_fin'],
                'prix_plancher' => $_POST['prix_plancher'],
                'coup_de_coeur_lord' => 0,
                'statut' => 'Active'
            ];
            
            error_log("Creating auction with data: " . print_r($auctionData, true));
            $auctionId = $this->auctionModel->create($auctionData);
            error_log("Auction created with ID: $auctionId");

            if (!$auctionId) {
                throw new \Exception("Failed to create auction");
            }

            if (!empty($images)) {
                error_log("Saving " . count($images) . " images to database");
                foreach ($images as $index => $imagePath) {
                    $isMain = ($index === 0);
                    error_log("Saving image $index: $imagePath (Main: " . ($isMain ? 'Yes' : 'No') . ")");
                    $result = $this->timbreModel->saveImage($timbreId, $imagePath, $isMain);
                    if (!$result) {
                        error_log("Warning: Failed to save image $imagePath to database");
                    }
                }
            } else {
                error_log("No images to save for timbre ID: $timbreId");
            }

            error_log("Auction creation completed successfully. Redirecting to: /projetweb1/public/auctions/$auctionId");
            header('Location: /projetweb1/public/auctions/' . $auctionId);
            exit;

        } catch (\Exception $e) {
            error_log("Error in auction creation: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            if (headers_sent()) {
                error_log("Headers already sent, cannot redirect");
            }
            
            echo $this->twig->render('auctions/create.twig', [
                'error' => 'Erreur lors de la création de l\'enchère: ' . $e->getMessage(),
                'old' => $_POST,
                'session' => $_SESSION ?? []
            ]);
        } catch (\Error $e) {
            error_log("Fatal error in auction creation: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            echo $this->twig->render('auctions/create.twig', [
                'error' => 'Erreur fatale lors de la création de l\'enchère: ' . $e->getMessage(),
                'old' => $_POST,
                'session' => $_SESSION ?? []
            ]);
        }
    }

    private function handleImageUploads($files)
    {
        $uploadedImages = [];
        
        if (empty($files) || !isset($files['name']) || !is_array($files['name']) || empty($files['name'][0])) {
            error_log("No images uploaded, continuing without images");
            return $uploadedImages;
        }
        
        $uploadDir = ROOT_PATH . 'public/uploads/';
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new \Exception("Impossible de créer le dossier de téléchargement");
            }
        }
        
        if (!is_writable($uploadDir)) {
            throw new \Exception("Le dossier de téléchargement n'est pas accessible en écriture");
        }

        $fileCount = count($files['name']);
        if ($fileCount > MAX_UPLOAD_COUNT) {
            throw new \Exception("Maximum " . MAX_UPLOAD_COUNT . " images autorisées. Vous avez sélectionné $fileCount images.");
        }

        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = $files['name'][$i];
            error_log("Processing image $i: $fileName");
            
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errorMessage = $this->getUploadErrorMessage($files['error'][$i]);
                throw new \Exception("Erreur lors du téléchargement de l'image '$fileName': $errorMessage");
            }

            $tmpName = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];

            if ($fileSize > MAX_FILE_SIZE) {
                $maxSizeMB = MAX_FILE_SIZE / (1024 * 1024);
                throw new \Exception("L'image '$fileName' est trop volumineuse. Taille maximum: {$maxSizeMB}MB");
            }

            $mimeType = $this->getMimeType($tmpName, $fileName);
            error_log("File: $fileName, MIME type: $mimeType");
            
            if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
                $allowedTypes = implode(', ', array_map(function($type) {
                    return pathinfo($type, PATHINFO_EXTENSION) ?: $type;
                }, ALLOWED_IMAGE_TYPES));
                throw new \Exception("Type de fichier non autorisé pour '$fileName'. Types autorisés: $allowedTypes");
            }

            $uniqueFileName = uniqid() . '_' . basename($fileName);
            $filePath = $uploadDir . $uniqueFileName;

            if (!move_uploaded_file($tmpName, $filePath)) {
                throw new \Exception("Erreur lors du déplacement de l'image '$fileName'");
            }

            error_log("Attempting to resize image: $filePath");
            if (!$this->resizeImage($filePath)) {
                unlink($filePath);
                throw new \Exception("Erreur lors du traitement de l'image '$fileName'. Vérifiez que l'image n'est pas corrompue et qu'elle est dans un format supporté.");
            }

            $webPath = 'public/uploads/' . $uniqueFileName;
            $uploadedImages[] = $webPath;
            error_log("Successfully processed image: $fileName -> $webPath");
        }

        return $uploadedImages;
    }

    private function getUploadErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return "Fichier trop volumineux (limite serveur)";
            case UPLOAD_ERR_FORM_SIZE:
                return "Fichier trop volumineux (limite formulaire)";
            case UPLOAD_ERR_PARTIAL:
                return "Téléchargement partiel";
            case UPLOAD_ERR_NO_FILE:
                return "Aucun fichier téléchargé";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Dossier temporaire manquant";
            case UPLOAD_ERR_CANT_WRITE:
                return "Erreur d'écriture sur le disque";
            case UPLOAD_ERR_EXTENSION:
                return "Extension PHP bloquée";
            default:
                return "Erreur inconnue";
        }
    }

    private function getMimeType($filePath, $fileName)
    {
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filePath);
            if ($mimeType && $mimeType !== 'application/octet-stream') {
                return $mimeType;
            }
        }

        if (class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($filePath);
            if ($mimeType && $mimeType !== 'application/octet-stream') {
                return $mimeType;
            }
        }

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $extensionMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];

        return $extensionMap[$extension] ?? 'application/octet-stream';
    }

    private function resizeImage($filePath)
    {
        $targetWidth = 800;
        $targetHeight = 600;
        $quality = 85;
        
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            error_log("Could not get image info for: $filePath");
            return false;
        }
        
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        $source = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($filePath);
                break;
            default:
                error_log("Unsupported image type: $mimeType");
                return false;
        }
        
        if (!$source) {
            error_log("Could not create image resource for: $filePath");
            return false;
        }
        
        $ratio = min($targetWidth / $originalWidth, $targetHeight / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);
        
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        
        $offsetX = (int)(($targetWidth - $newWidth) / 2);
        $offsetY = (int)(($targetHeight - $newHeight) / 2);
        
        imagecopyresampled(
            $target, $source,
            $offsetX, $offsetY, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );
        
        $success = imagejpeg($target, $filePath, $quality);
        
        imagedestroy($source);
        imagedestroy($target);
        
        if (!$success) {
            error_log("Failed to save processed image: $filePath");
            return false;
        }
        
        $this->createThumbnail($filePath);
        
        $newFileSize = filesize($filePath);
        $originalFileSize = $originalWidth * $originalHeight * 3;
        $compressionRatio = round(($newFileSize / $originalFileSize) * 100, 1);
        
        error_log("Image processed: $filePath - Original: {$originalWidth}x{$originalHeight}, New: {$targetWidth}x{$targetHeight}, Compression: {$compressionRatio}%");
        
        return true;
    }
    
    /**
     * Create a thumbnail version of the image for faster loading
     */
    private function createThumbnail($filePath)
    {
        $thumbnailPath = str_replace('.jpg', '_thumb.jpg', $filePath);
        $thumbnailPath = str_replace('.png', '_thumb.jpg', $thumbnailPath);
        $thumbnailPath = str_replace('.gif', '_thumb.jpg', $thumbnailPath);
        
        $thumbWidth = 200;
        $thumbHeight = 150;
        $quality = 80;
        
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            return false;
        }
        
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        $source = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($filePath);
                break;
            default:
                return false;
        }
        
        if (!$source) {
            return false;
        }
        
        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
        
        $white = imagecolorallocate($thumb, 255, 255, 255);
        imagefill($thumb, 0, 0, $white);
        
        $ratio = min($thumbWidth / $originalWidth, $thumbHeight / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);
        
        $offsetX = (int)(($thumbWidth - $newWidth) / 2);
        $offsetY = (int)(($thumbHeight - $newHeight) / 2);
        
        imagecopyresampled(
            $thumb, $source,
            $offsetX, $offsetY, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );
        
        $success = imagejpeg($thumb, $thumbnailPath, $quality);
        
        imagedestroy($source);
        imagedestroy($thumb);
        
        return $success;
    }

    public function placeBid()
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
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
        
        try {
            $auctionId = $data['auction_id'] ?? null;
            $montant = $data['montant'] ?? null;

            if (!$auctionId || !$montant) {
                throw new \Exception('Paramètres manquants');
            }

            $auction = $this->auctionModel->getAuctionById($auctionId);
            if (!$auction) {
                throw new \Exception('Enchère non trouvée');
            }

            $highestBid = $this->offreModel->getHighestBid($auctionId);
            $currentPrice = $highestBid ? $highestBid['montant'] : $auction['prix_plancher'];
            
            if ($montant <= $currentPrice) {
                throw new \Exception('L\'offre doit être supérieure au prix actuel (' . number_format($currentPrice, 2) . ' $)');
            }

            if ($auction['statut'] !== 'Active') {
                throw new \Exception('Cette enchère n\'est plus active');
            }

            if (strtotime($auction['date_fin']) < time()) {
                throw new \Exception('Cette enchère est terminée');
            }

            $offreId = $this->offreModel->create([
                'id_enchere' => $auctionId,
                'id_membre' => $_SESSION['user_id'],
                'montant' => $montant,
                'date_offre' => date('Y-m-d H:i:s')
            ]);

            echo json_encode([
                'success' => true,
                'offre_id' => $offreId,
                'message' => 'Offre placée avec succès'
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function toggleFavorite()
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
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
        $auctionId = $data['auction_id'] ?? null;

        if (!$auctionId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID d\'enchère manquant']);
            return;
        }

        try {
            $isFavorite = $this->favorisModel->isFavorite($_SESSION['user_id'], $auctionId);
            
            if ($isFavorite) {
                $this->favorisModel->removeFavorite($_SESSION['user_id'], $auctionId);
                $message = 'Retiré des favoris';
                $isFavorite = false;
            } else {
                $this->favorisModel->addFavorite($_SESSION['user_id'], $auctionId);
                $message = 'Ajouté aux favoris';
                $isFavorite = true;
            }

            echo json_encode([
                'success' => true,
                'isFavorite' => $isFavorite,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function favorites()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /projetweb1/public/login');
            return;
        }

        $favorites = $this->favorisModel->getUserFavorites($_SESSION['user_id']);

        echo $this->twig->render('auctions/favorites.twig', [
            'favorites' => $favorites,
            'session' => $_SESSION ?? []
        ]);
    }

    public function edit($id)
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /projetweb1/public/login');
            return;
        }

        $auction = $this->auctionModel->getAuctionById($id);
        
        if (!$auction) {
            http_response_code(404);
            echo $this->twig->render('errors/404.twig');
            return;
        }

        if ($auction['id_membre'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo $this->twig->render('errors/403.twig');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUpdateAuction($id);
            return;
        }

        $images = $this->timbreModel->getImages($auction['id_timbre']);

        echo $this->twig->render('auctions/edit.twig', [
            'auction' => $auction,
            'images' => $images,
            'session' => $_SESSION ?? []
        ]);
    }

    private function handleUpdateAuction($auctionId)
    {
        try {
            $auction = $this->auctionModel->getAuctionById($auctionId);
            
            if (!$auction || $auction['id_membre'] != $_SESSION['user_id']) {
                throw new \Exception("Accès non autorisé");
            }

            $requiredFields = ['nom', 'pays_origine', 'prix_plancher', 'date_fin'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new \Exception("Le champ $field est requis");
                }
            }

            $timbreData = [
                'nom' => $_POST['nom'],
                'date_creation' => !empty($_POST['date_creation']) ? $_POST['date_creation'] . '-01-01' : null,
                'couleurs' => !empty($_POST['couleurs']) ? $_POST['couleurs'] : null,
                'pays_origine' => $_POST['pays_origine'],
                'condition' => $_POST['condition'] ?? 'Bonne',
                'tirage' => !empty($_POST['tirage']) ? (int)$_POST['tirage'] : null,
                'dimensions' => !empty($_POST['dimensions']) ? $_POST['dimensions'] : null,
                'certifie' => isset($_POST['certifie']) ? 1 : 0
            ];

            $this->timbreModel->update($auction['id_timbre'], $timbreData);

            $auctionData = [
                'date_fin' => $_POST['date_fin'],
                'prix_plancher' => $_POST['prix_plancher']
            ];

            $this->auctionModel->update($auctionId, $auctionData);

            if (!empty($_FILES['images']['name'][0])) {
                $images = $this->handleImageUploads($_FILES['images']);
                foreach ($images as $index => $imagePath) {
                    $this->timbreModel->saveImage($auction['id_timbre'], $imagePath, false);
                }
            }

            header('Location: /projetweb1/public/auctions/' . $auctionId);
            exit;

        } catch (\Exception $e) {
            $auction = $this->auctionModel->getAuctionById($auctionId);
            $images = $this->timbreModel->getImages($auction['id_timbre']);
            
            echo $this->twig->render('auctions/edit.twig', [
                'auction' => $auction,
                'images' => $images,
                'error' => 'Erreur lors de la mise à jour: ' . $e->getMessage(),
                'old' => $_POST,
                'session' => $_SESSION ?? []
            ]);
        }
    }

    public function delete($id)
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

        try {
            $auction = $this->auctionModel->getAuctionById($id);
            
            if (!$auction) {
                throw new \Exception('Enchère non trouvée');
            }

            if ($auction['id_membre'] != $_SESSION['user_id']) {
                throw new \Exception('Accès non autorisé');
            }

            if ($auction['nombre_offres'] > 0) {
                throw new \Exception('Impossible de supprimer une enchère qui a reçu des offres');
            }

            $this->auctionModel->delete($id);

            echo json_encode([
                'success' => true,
                'message' => 'Enchère supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function deleteImage($imageId)
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

        try {
            $sql = "SELECT i.id_image, i.chemin, t.id_timbre, e.id_membre 
                    FROM images i 
                    JOIN timbre t ON i.id_timbre = t.id_timbre 
                    JOIN enchere e ON t.id_timbre = e.id_timbre 
                    WHERE i.id_image = :image_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':image_id' => $imageId]);
            $imageData = $stmt->fetch();

            if (!$imageData) {
                throw new \Exception('Image non trouvée');
            }

            if ($imageData['id_membre'] != $_SESSION['user_id']) {
                throw new \Exception('Accès non autorisé');
            }

            $result = $this->timbreModel->deleteImage($imageId);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Image supprimée avec succès'
                ]);
            } else {
                throw new \Exception('Erreur lors de la suppression de l\'image');
            }

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function setMainImage($imageId)
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

        try {
            $sql = "SELECT i.id_image, t.id_timbre, e.id_membre
                    FROM images i
                    JOIN timbre t ON i.id_timbre = t.id_timbre
                    JOIN enchere e ON t.id_timbre = e.id_timbre
                    WHERE i.id_image = :image_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':image_id' => $imageId]);
            $imageData = $stmt->fetch();

            if (!$imageData) {
                throw new \Exception('Image non trouvée');
            }

            if ($imageData['id_membre'] != $_SESSION['user_id']) {
                throw new \Exception('Accès non autorisé');
            }

            $result = $this->timbreModel->setMainImage((int)$imageId);
            if (!$result) {
                throw new \Exception("Impossible de définir l'image principale");
            }

            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function toggleLordFavorite()
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }

        if (!$this->isAdmin($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $auctionId = $data['auction_id'] ?? null;

        if (!$auctionId) {
            echo json_encode(['success' => false, 'error' => 'Auction ID required']);
            return;
        }

        try {
            $success = $this->auctionModel->toggleLordFavorite($auctionId);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Lord favorite status updated']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update status']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function isAdmin($userId)
    {
        return $userId == 20;
    }
}
