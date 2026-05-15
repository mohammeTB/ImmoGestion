<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);

Route::middleware('auth:sanctum')->group(function(){
    Route::post('/logout',[AuthController::class, 'logout']);
    Route::prefix('notifications')->group(function(){
        Route::get('/',[NotificationController::class, 'all']);
        Route::get('/unread',[NotificationController::class, 'unread']);
        Route::post('/{id}/read',[NotificationController::class, 'markAsRead']);
        Route::post('/read-all',[NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}',[NotificationController::class, 'deleteOne']);
        Route::delete('/',[NotificationController::class, 'deleteAll']);
    });
});