<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
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

Route::post('users', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/all', [TransactionController::class, 'index']);
    Route::get('/deposit', [TransactionController::class, 'depositIndex']);
    Route::post('/deposit', [TransactionController::class, 'deposit']);
    Route::get('/withdrawal', [TransactionController::class, 'withdrawalIndex']);
    Route::post('/withdrawal', [TransactionController::class, 'withdrawal']);
});
