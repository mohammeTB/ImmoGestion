<?php

use App\Http\Controllers\AdminAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/admin/login', [AdminAuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
    return $request->user();});

    // --- مهام Yacine المضافة ---
    Route::get('/admin/users', [AdminAuthController::class, 'index']); // عرض قائمة المستخدمين
    Route::post('/admin/users/{user}/toggle-status', [AdminAuthController::class, 'toggleStatus']);
});

