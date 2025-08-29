<?php

use App\controllers\MembreController;
use App\controllers\AuthController;
use App\controllers\HomeController;
use App\controllers\AuctionController;
use App\controllers\CommentController;
use App\controllers\ProfileController;
use App\controllers\AboutController;
use App\routes\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/register', [MembreController::class, 'showRegisterForm']);

Route::post('/register', [MembreController::class, 'register']);

Route::get('/login', [AuthController::class, 'login']);

Route::post('/authenticate', [AuthController::class, 'authenticate']);

Route::get('/logout', [AuthController::class, 'logout']);

Route::get('/auctions', [AuctionController::class, 'index']);
Route::get('/auctions/search', [AuctionController::class, 'search']);
Route::get('/auctions/create', [AuctionController::class, 'create']);
Route::post('/auctions/create', [AuctionController::class, 'create']);
Route::get('/auctions/{id}', [AuctionController::class, 'show']);
Route::get('/auctions/{id}/edit', [AuctionController::class, 'edit']);
Route::post('/auctions/{id}/edit', [AuctionController::class, 'edit']);
Route::post('/auctions/{id}/delete', [AuctionController::class, 'delete']);
Route::post('/images/{id}/delete', [AuctionController::class, 'deleteImage']);
Route::post('/images/{id}/set-main', [AuctionController::class, 'setMainImage']);
Route::post('/auctions/bid', [AuctionController::class, 'placeBid']);
Route::post('/auctions/favorite', [AuctionController::class, 'toggleFavorite']);
Route::get('/auctions/favorites', [AuctionController::class, 'favorites']);
Route::get('/auctions/archives', [AuctionController::class, 'archives']);
Route::post('/auctions/toggle-lord-favorite', [AuctionController::class, 'toggleLordFavorite']);

Route::post('/comments/create', [CommentController::class, 'createComment']);
Route::put('/comments/{id}/update', [CommentController::class, 'updateComment']);
Route::delete('/comments/{id}/delete', [CommentController::class, 'deleteComment']);
Route::get('/comments/{auction_id}', [CommentController::class, 'getComments']);

Route::get('/profile', [ProfileController::class, 'showProfile']);
Route::get('/profile/personal-info', [ProfileController::class, 'getPersonalInfo']);
Route::put('/profile/update', [ProfileController::class, 'updateProfile']);
Route::get('/profile/offer-history', [ProfileController::class, 'getOfferHistory']);
Route::get('/profile/published-auctions', [ProfileController::class, 'getPublishedAuctions']);

Route::get('/about', [AboutController::class, 'index']);

Route::get('/help', [HomeController::class, 'help']);
