<?php
namespace App\controllers;

use PDO;
use Twig\Environment;

class AboutController extends BaseController
{
    public function __construct(PDO $pdo, Environment $twig, array $config)
    {
        parent::__construct($pdo, $twig, $config);
    }

    public function index()
    {
        echo $this->twig->render('about/index.twig', [
            'session' => $_SESSION ?? []
        ]);
    }
}
