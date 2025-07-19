<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

Route::post('/register', [AuthController::class, 'register']);
// Можно авторизоваться по email + пароль от учетки
Route::post('/login/email', [AuthController::class, 'loginByEmail']);
// Можно авторизоваться по телефону + пароль от учетки
// Route::post('/login/phone', [AuthController::class, 'loginByPhone']);

// Запрос на получение кода и верификация кода для номера телефона
Route::post('/login/phone/verify-code', [AuthController::class, 'verifyPhoneCode']);
// Не более 5 запросов в минуту для получения кода
Route::post('/login/phone/request-code', [AuthController::class, 'requestPhoneCode']); //->middleware('throttle:5,1');

Route::apiResource('products', ProductController::class)->only('index');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/all-orders', [OrderController::class, 'index']); // Админ
    Route::get('/orders', [OrderController::class, 'userOrders']); // Пользователь
    Route::post('/orders', [OrderController::class, 'store']); // Новый заказ
});
