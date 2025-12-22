<?php

use App\Http\Controllers\OrderController;
use App\Http\Middleware\AddRequestContext;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(AddRequestContext::class)->group(function () {
    Route::post('/order', [OrderController::class, 'create']);
});
