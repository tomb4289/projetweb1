<?php

use App\Controllers\MembreController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Routes\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/register', [MembreController::class, 'showRegisterForm']);

Route::post('/register', [MembreController::class, 'register']);

Route::get('/login', [AuthController::class, 'login']);

Route::post('/authenticate', [AuthController::class, 'authenticate']);

Route::get('/logout', [AuthController::class, 'logout']);

Route::get('/dashboard', [DashboardController::class, 'index']);
