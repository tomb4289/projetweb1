<?php
namespace App\Controllers;

use App\Providers\Auth;
use PDO;
use Twig\Environment;

class HomeController extends BaseController
{
    public function __construct(PDO $pdo, Environment $twig, array $config)
    {
        parent::__construct($pdo, $twig, $config);
    }

    public function index()
    {
        echo $this->twig->render('index.twig', ['session' => $_SESSION ?? []]);
    }
}
