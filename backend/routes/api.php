<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ComuneController;
use App\Http\Controllers\Api\CookieConsentStatController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\MarketingConsentController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ShippingRateController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])
    ->middleware(['throttle:register', 'bot.guard:3']);
Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['throttle:login', 'bot.guard']);
Route::get('/comuni', ComuneController::class);
Route::post('/cookie-consent-stats', [CookieConsentStatController::class, 'store'])
    ->middleware('throttle:20,1');

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware('throttle:6,1');

Route::post('/unsubscribe/{user}', [MarketingConsentController::class, 'unsubscribe'])
    ->middleware(['signed:relative', 'throttle:6,1'])
    ->name('marketing.unsubscribe');

Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
    ->middleware(['throttle:6,1', 'bot.guard']);
Route::post('/reset-password', [PasswordResetController::class, 'reset'])
    ->middleware('throttle:6,1');

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/availability', [ProductController::class, 'availability']);
Route::get('/products/{product:slug}', [ProductController::class, 'show']);
Route::get('/shipping-rates', ShippingRateController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load('addresses.comune');
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::patch('/user/marketing-consent', [MarketingConsentController::class, 'update']);
    Route::put('/user/password', [AccountController::class, 'updatePassword'])->middleware('throttle:6,1');
    Route::delete('/user', [AccountController::class, 'destroy'])->middleware('throttle:6,1');

    Route::apiResource('addresses', AddressController::class)->except(['show']);
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);

    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/{product}', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy']);

    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::patch('/cart/{product}', [CartController::class, 'update']);
    Route::delete('/cart/{product}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);
});
