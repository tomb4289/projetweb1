<?php
namespace App\Controllers;

use PDO;
use Twig\Environment;
use App\Models\MembreModel;
use App\Providers\View;
use App\Providers\Validator;
use App\Providers\Auth;

class AuthController extends BaseController
{
    protected MembreModel $membreModel;

    public function __construct(PDO $pdo, Environment $twig, array $config)
    {
        parent::__construct($pdo, $twig, $config);
        $this->membreModel = new MembreModel($pdo);
    }

    public function login()
    {
        echo $this->twig->render('auth/login.twig', ['errors' => []]);
    }

    public function authenticate()
    {
        error_log("AuthController::authenticate called with data: " . print_r($_POST, true));
        
        $errors = [];
        
        $username = trim($_POST['nom_utilisateur'] ?? '');
        $password = $_POST['mot_de_passe'] ?? '';
        
        if (empty($username)) {
            $errors['nom_utilisateur'] = 'Le nom d\'utilisateur ou l\'adresse e-mail est requis.';
        } elseif (strlen($username) < 2) {
            $errors['nom_utilisateur'] = 'Le nom d\'utilisateur doit avoir au moins 2 caractères.';
        } elseif (strlen($username) > 50) {
            $errors['nom_utilisateur'] = 'Le nom d\'utilisateur ne peut pas dépasser 50 caractères.';
        } elseif (strpos($username, '@') !== false) {
            if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
                $errors['nom_utilisateur'] = 'Veuillez entrer une adresse e-mail valide.';
            }
        }
        
        if (empty($password)) {
            $errors['mot_de_passe'] = 'Le mot de passe est requis.';
        } elseif (strlen($password) < 6) {
            $errors['mot_de_passe'] = 'Le mot de passe doit avoir au moins 6 caractères.';
        } elseif (strlen($password) > 128) {
            $errors['mot_de_passe'] = 'Le mot de passe ne peut pas dépasser 128 caractères.';
        }
        
        if (!empty($errors)) {
            error_log("Validation errors: " . print_r($errors, true));
            echo $this->twig->render('auth/login.twig', [
                'errors' => $errors,
                'old' => $_POST
            ]);
            return;
        }
        
        error_log("Attempting authentication for username: $username");

        try {
            $user = $this->membreModel->checkUser($username, $password);

            if ($user) {
                error_log("Authentication successful for user: " . print_r($user, true));
                
                header('Location: /projetweb2/public/');
                exit;
            } else {
                error_log("Authentication failed for username: $username");
                $errors['message'] = "Nom d'utilisateur ou mot de passe invalide. Veuillez vérifier vos informations de connexion.";
                echo $this->twig->render('auth/login.twig', [
                    'errors' => $errors,
                    'old' => $_POST
                ]);
            }
        } catch (\Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            $errors['message'] = "Une erreur s'est produite lors de la connexion. Veuillez réessayer.";
            echo $this->twig->render('auth/login.twig', [
                'errors' => $errors,
                'old' => $_POST
            ]);
        }
    }

    public function logout()
    {
        session_start();
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
            session_unset();
            session_destroy();
        }
        
        header('Location: /projetweb2/public/login');
        exit;
    }
}