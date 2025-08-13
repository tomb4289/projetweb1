<?php
namespace App\Controllers;

use PDO;
use Twig\Environment;
use App\Models\MembreModel;
use App\Providers\Validator;
use App\Providers\View;

class MembreController extends BaseController
{
    protected MembreModel $membreModel;

    public function __construct(PDO $pdo, Environment $twig, array $config)
    {
        parent::__construct($pdo, $twig, $config);
        $this->membreModel = new MembreModel($pdo);
    }

    public function showRegisterForm()
    {
        echo $this->twig->render('membre_register.twig', ['errors' => [], 'old' => []]);
    }

    public function register(array $postData)
    {
        error_log("MembreController::register called with data: " . print_r($postData, true));
        error_log("POST data keys: " . implode(', ', array_keys($postData)));

        $validator = new Validator;
        $validator->field('nom_utilisateur', $postData['nom_utilisateur'])->required()->min(2)->max(50);
        $validator->field('courriel', $postData['courriel'])->required()->email();
        $validator->field('mot_de_passe', $postData['mot_de_passe'])->required()->min(8)->max(20);

        $password = $postData['mot_de_passe'] ?? '';
        $confirmPassword = $postData['confirmation_mot_de_passe'] ?? '';
        
        error_log("Password comparison: '$password' vs '$confirmPassword'");
        
        if ($password !== $confirmPassword) {
            error_log("Password mismatch detected");
            $errors['message'] = "Les mots de passe ne correspondent pas.";
            echo $this->twig->render('membre_register.twig', [
                'errors' => $errors,
                'old' => $postData
            ]);
            return;
        }

        if ($validator->isSuccess()) {
            if (!$this->membreModel->unique('nom_utilisateur', $postData['nom_utilisateur'])) {
                $errors['message'] = "Ce nom d'utilisateur est déjà utilisé.";
                echo $this->twig->render('membre_register.twig', [
                    'errors' => $errors,
                    'old' => $postData
                ]);
                return;
            }

            if (!$this->membreModel->unique('courriel', $postData['courriel'])) {
                $errors['message'] = "Cette adresse e-mail est déjà utilisée.";
                echo $this->twig->render('membre_register.twig', [
                    'errors' => $errors,
                    'old' => $postData
                ]);
                return;
            }

            $userData = [
                'nom_utilisateur' => trim($postData['nom_utilisateur']),
                'courriel' => trim($postData['courriel']),
                'mot_de_passe' => $this->membreModel->hashPassword($postData['mot_de_passe'])
            ];

            try {
                $userId = $this->membreModel->create($userData);
                if ($userId) {
                    error_log("User created successfully with ID: $userId");
                    View::redirect('login');
                } else {
                    $errors['message'] = "Échec de la création du compte. Veuillez réessayer.";
                    echo $this->twig->render('membre_register.twig', [
                        'errors' => $errors,
                        'old' => $postData
                    ]);
                }
            } catch (\PDOException $e) {
                error_log("User creation PDO error: " . $e->getMessage());
                $errors['message'] = "Une erreur de base de données s'est produite. Veuillez réessayer.";
                echo $this->twig->render('membre_register.twig', [
                    'errors' => $errors,
                    'old' => $postData
                ]);
            }
        } else {
            $errors = $validator->getErrors();
            error_log("Validation errors: " . print_r($errors, true));
            
            $errors['message'] = "Veuillez corriger les erreurs dans le formulaire.";
            
            echo $this->twig->render('membre_register.twig', [
                'errors' => $errors,
                'old' => $postData
            ]);
        }
    }
}
