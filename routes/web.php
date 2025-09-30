<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrgInvitationController;

Route::get('/', function () {
    return view('welcome');
});

// Public invitation acceptance landing page (no auth middleware)
Route::get('/invitations/accept', [OrgInvitationController::class, 'acceptPage'])->name('invitations.accept');
