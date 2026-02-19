<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ClientsController;

Route::get('/', fn () => redirect()->route('login'));

// AUTH
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ADMIN AREA
Route::middleware(['mybank.auth', 'mybank.admin'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Users (Cobradores)
    Route::get('/users', [UsersController::class, 'index'])->name('users.index');
    Route::post('/users', [UsersController::class, 'store'])->name('users.store');
    Route::patch('/users/{userId}/toggle', [UsersController::class, 'toggleActive'])->name('users.toggle');
    Route::delete('/users/{userId}', [UsersController::class, 'destroy'])->name('users.destroy');

    // Clients
    Route::get('/clients', [ClientsController::class, 'index'])->name('clients.index');
    Route::post('/clients', [ClientsController::class, 'store'])->name('clients.store');
    Route::patch('/clients/{clientId}', [ClientsController::class, 'update'])->name('clients.update');
    Route::post('/clients/{clientId}/assign', [ClientsController::class, 'assign'])->name('clients.assign');
});

