<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);  
Route::post('/login', [AuthController::class, 'login']);       

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/activities', [ActivityController::class, 'getActivitiesByDateRange']);  
});
