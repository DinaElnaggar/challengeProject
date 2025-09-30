<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrgInvitationController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\GdprController;
use App\Http\Controllers\GdprDeleteController;
use App\Http\Controllers\UsersAnalyticsController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:email');
Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifySigned'])
    ->name('verification.verify')
    ->middleware(['signed', 'throttle:email']);
Route::post('magic', [AuthController::class, 'magicLink'])->middleware('throttle:email');
Route::get('magic/consume/{token}', [AuthController::class, 'magicConsume']);

// Public API endpoint for invitation acceptance returns JSON (no redirect to login)
Route::get('orgs/invitations/accept', [OrgInvitationController::class, 'accept']);

Route::middleware(['auth:api'])->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);
    // 2FA endpoints
    Route::post('2fa/setup', [AuthController::class, 'twoFactorSetup']);
    Route::post('2fa/enable', [AuthController::class, 'twoFactorEnable']);
    Route::post('2fa/disable', [AuthController::class, 'twoFactorDisable']);
    // Invitation endpoints
    Route::post('orgs/{org}/invite', [OrgInvitationController::class, 'invite']);
    Route::post('orgs/invitations/accept', [OrgInvitationController::class, 'accept']);

    // Users CRUD
    Route::get('users', [UsersController::class, 'index']);
    Route::post('users', [UsersController::class, 'store']);
    Route::get('users/{id}', [UsersController::class, 'show']);
    Route::put('users/{id}', [UsersController::class, 'update']);
    Route::delete('users/{id}', [UsersController::class, 'destroy']);
    Route::post('users/{id}/restore', [UsersController::class, 'restore']);

    // GDPR export
    Route::post('users/{id}/export', [GdprController::class, 'requestExport']);
    Route::get('users/{id}/export/download', [GdprController::class, 'downloadExport']);

    // GDPR delete request
    Route::post('users/{id}/gdpr-delete', [GdprDeleteController::class, 'create']);
    Route::post('gdpr-delete/{requestId}/approve', [GdprDeleteController::class, 'approve']);
    Route::post('gdpr-delete/{requestId}/reject', [GdprDeleteController::class, 'reject']);
    Route::post('gdpr-delete/{requestId}/process', [GdprDeleteController::class, 'process']);

    // Login analytics
    Route::get('users/top-logins', [UsersAnalyticsController::class, 'topLogins']);
    Route::get('users/inactive', [UsersAnalyticsController::class, 'inactive']);
});

 