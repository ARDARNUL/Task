<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

$statefulMiddleware = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    SubstituteBindings::class,
];

Route::middleware($statefulMiddleware)->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('api.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

        Route::get('/organizations', [OrganizationController::class, 'index'])->name('api.organizations.index');
        Route::post('/organizations', [OrganizationController::class, 'store'])->name('api.organizations.store');
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('api.organizations.show');
        Route::post('/organizations/{organization}/sync', [OrganizationController::class, 'sync'])->name('api.organizations.sync');

        Route::get('/organizations/{organization}/reviews', [ReviewController::class, 'index'])->name('api.organizations.reviews.index');
    });
});