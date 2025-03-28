<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    
    //User Routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'userProfile']);
 
    //account Routes
    Route::post('/accounts', [AccountController::class, 'store']);
   
    Route::get('/accounts/{account_number}', [AccountController::class, 'show']);
    Route::put('/accounts/{account_number}', [AccountController::class, 'update']);
    Route::delete('/accounts/{account_number}', [AccountController::class, 'destroy']);

    //transjaction Routes
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions', [TransactionController::class, 'index']);
});