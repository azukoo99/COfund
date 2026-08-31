<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignTierController;
use App\Http\Controllers\CampaignUpdateController;
use App\Http\Controllers\BackingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\CampaignAdminController;
use App\Http\Controllers\Admin\UserAdminController;

/*
|--------------------------------------------------------------------------
| API Routes - CoFund Crowdfunding Platform
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. AUTHENTICATION (Modul 3 & 2)
// ==========================================
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
    ->name('verification.verify');

Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);
    Route::post('/me/upgrade-creator', [LoginController::class, 'upgradeToCreator']);
    Route::get('/me/balance', [TransactionController::class, 'index']);
    Route::post('/me/withdraw', [DashboardController::class, 'withdraw']);

    // Notifications (Modul 8)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

    // Dashboards (Modul 9)
    Route::get('/dashboard/creator', [DashboardController::class, 'creatorDashboard']);
    Route::get('/dashboard/backer', [DashboardController::class, 'backerDashboard']);
});

Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// ==========================================
// 2. PUBLIC CAMPAIGN & CATEGORY (Modul 4.3, 5.1 & Categories)
// ==========================================
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/campaigns', [CampaignController::class, 'index']);
Route::get('/campaigns/{campaign}', [CampaignController::class, 'show']);
Route::get('/campaigns/{campaign}/tiers', [CampaignTierController::class, 'index']);
Route::get('/campaigns/{campaign}/updates', [CampaignUpdateController::class, 'index']);

// ==========================================
// 3. CREATOR & BACKER PROTECTED (Modul 4, 5)
// ==========================================
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Campaign Management (Creator)
    Route::post('/campaigns', [CampaignController::class, 'store']);
    Route::put('/campaigns/{campaign}', [CampaignController::class, 'update']);
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy']);
    Route::post('/campaigns/{campaign}/images', [CampaignController::class, 'uploadImages']);
    Route::delete('/campaigns/{campaign}/images/{image}', [CampaignController::class, 'deleteImage']);
    Route::patch('/campaigns/{campaign}/images/{image}/primary', [CampaignController::class, 'setPrimaryImage']);
    Route::post('/campaigns/{campaign}/submit-review', [CampaignController::class, 'submitReview']);
    Route::post('/campaigns/{campaign}/updates', [CampaignUpdateController::class, 'store']);

    // Tier Management (Creator)
    Route::post('/campaigns/{campaign}/tiers', [CampaignTierController::class, 'store']);
    Route::put('/campaigns/{campaign}/tiers/{tier}', [CampaignTierController::class, 'update']);
    Route::delete('/campaigns/{campaign}/tiers/{tier}', [CampaignTierController::class, 'destroy']);

    // Backing (Backer)
    Route::post('/campaigns/{campaign}/back', [BackingController::class, 'store']);
    Route::get('/my-backings', [BackingController::class, 'myBackings']);
});

// ==========================================
// 4. ADMIN MANAGEMENT (Modul 4.1 & 10)
// ==========================================
Route::middleware(['auth:sanctum', 'verified'])->prefix('admin')->group(function () {
    // Overview & Platform Analytics (Modul 10.4)
    Route::get('/overview', [CampaignAdminController::class, 'overview']);

    // Campaign Management & Review Queue (Modul 10.1 & 10.2)
    Route::get('/campaigns', [CampaignAdminController::class, 'index']);
    Route::get('/campaigns/{campaign}', [CampaignAdminController::class, 'show']);
    Route::post('/campaigns/{campaign}/approve', [CampaignAdminController::class, 'approve']);
    Route::post('/campaigns/{campaign}/reject', [CampaignAdminController::class, 'reject']);
    Route::post('/campaigns/{campaign}/force-fail', [CampaignAdminController::class, 'forceFail']);

    // User Management (Modul 10.3)
    Route::get('/users', [UserAdminController::class, 'index']);
    Route::get('/users/{user}', [UserAdminController::class, 'show']);
    Route::patch('/users/{user}/suspend', [UserAdminController::class, 'toggleSuspend']);
});
