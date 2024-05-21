<?php

use App\Http\Controllers\BulkOrderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\JWTMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'api'], function () {
    Route::withoutMiddleware([JWTMiddleware::class])->group(function () {
        Route::post('user-register', [UserController::class, 'userRegister']);
        Route::post('user-login', [UserController::class, 'userLogin']);
    });
    Route::get('user-info', [UserController::class, 'userInfo']);
    Route::post('send-funds', [OrderController::class, 'sendFundsToUser']);
    Route::post('bulk-send-funds', [BulkOrderController::class, 'bulkSendFundsToUser']);
    Route::post('transaction-history', [TransactionController::class, 'transactionHistory']);
});
