<?php

use App\Http\Controllers\admin\StatsController;
use App\Http\Controllers\admin\ManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\proprietaire\ManagementController as ProprietaireManagementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);

Route::middleware('auth:sanctum')->group(function(){
    Route::post('/logout',[AuthController::class, 'logout']);
    Route::get('/profile',[UserController::class, 'profile']);
    Route::prefix('notifications')->group(function(){
        Route::get('/',[NotificationController::class, 'all']);
        Route::get('/unread',[NotificationController::class, 'unread']);
        Route::post('/{id}/read',[NotificationController::class, 'markAsRead']);
        Route::post('/read-all',[NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}',[NotificationController::class, 'deleteOne']);
        Route::delete('/',[NotificationController::class, 'deleteAll']);
    });
    Route::middleware('role:admin')->prefix('admin')->group(function (){
        Route::patch('/users/{id}/status',[ManagementController::class, 'changeStatus']);
        Route::get('/proprietaires',[StatsController::class, 'proprietaires']);
        Route::get('/locataires',[StatsController::class, 'locataires']);
        Route::delete('/locataires/{id}',[ManagementController::class, 'deleteLocataire']);
        Route::delete('/proprietaires/{id}',[ManagementController::class, 'deleteProprietaire']);
        Route::get('/reservations',[StatsController::class, 'reservations']);
        Route::prefix('appartements')->group(function(){
            Route::get('/',[StatsController::class, 'allAppartements']);
            Route::get('/pending',[StatsController::class, 'pendingAppartements']);
            Route::get('/pending/{id}',[StatsController::class, 'showPendingAppartement']);
            Route::patch('/{id}/reject',[ManagementController::class, 'rejectAppartement']);
            Route::patch('/{id}/approve',[ManagementController::class, 'approveAppartement']);
        });
    });
    Route::middleware('role:proprietaire')->prefix('proprietaire')->group(function (){
        Route::post('/appartements',[ProprietaireManagementController::class, 'request_to_addAppartement']);
    });
});
