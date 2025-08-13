<?php
namespace App\Controllers;

use PDO;
use Twig\Environment;
use App\Providers\Auth;

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
