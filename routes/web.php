<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrgInvitationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DocsController;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Public invitation acceptance landing page (no auth middleware)
Route::get('/invitations/accept', [OrgInvitationController::class, 'acceptPage'])->name('invitations.accept');

// API documentation (Swagger UI)
Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
