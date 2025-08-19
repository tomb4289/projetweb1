<?php

use App\Controllers\MembreController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\AuctionController;
use App\Routes\Route;

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
Route::post('/auctions/toggle-lord-favorite', [AuctionController::class, 'toggleLordFavorite']);
