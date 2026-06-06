<?php

use App\Http\Controllers\InfoController;
use Illuminate\Support\Facades\Route;

// Определяем три GET-маршрута
Route::get('/info/server', [InfoController::class, 'serverInfo']);
Route::get('/info/client', [InfoController::class, 'clientInfo']);
Route::get('/info/database', [InfoController::class, 'databaseInfo']);