<?php
namespace App\controllers;

use PDO;
use Twig\Environment;
use App\providers\Auth;

abstract class BaseController
{
    protected PDO $pdo;
    protected Environment $twig;
    protected array $config;

    public function __construct(PDO $pdo, Environment $twig, array $config)
    {
        $this->pdo = $pdo;
        $this->twig = $twig;
        $this->config = $config; 

        $this->twig->addGlobal('auth', new Auth());
    }
}
