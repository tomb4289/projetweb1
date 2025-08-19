<?php
namespace App\Controllers;

use App\Providers\Auth;
use App\Models\AuctionModel;
use PDO;
use Twig\Environment;

class HomeController extends BaseController
{
    private AuctionModel $auctionModel;

    public function __construct(PDO $pdo, Environment $twig, array $config)
    {
        parent::__construct($pdo, $twig, $config);
        $this->auctionModel = new AuctionModel($pdo);
    }

    public function index()
    {
        if (isset($_SESSION) && !empty($_SESSION)) {
            error_log("Session data in HomeController: " . print_r($_SESSION, true));
        } else {
            error_log("No session data in HomeController");
        }
        
        $featuredAuctions = $this->auctionModel->getFeaturedAuctions();
        $endingSoonAuctions = $this->auctionModel->getEndingSoonAuctions(3);
        
        error_log("Featured auctions data: " . print_r($featuredAuctions, true));
        error_log("Ending soon auctions data: " . print_r($endingSoonAuctions, true));
        
        echo $this->twig->render('index.twig', [
            'session' => $_SESSION ?? [],
            'featuredAuctions' => $featuredAuctions,
            'endingSoonAuctions' => $endingSoonAuctions
        ]);
    }
}
