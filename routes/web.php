<?php

use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [WorkspaceController::class, 'index'])->name('dashboard');

    Route::resource('workspaces', WorkspaceController::class)->only(['store', 'show', 'update', 'destroy']);

    Route::post('workspaces/{workspace}/invitations', [InvitationController::class, 'store'])
        ->name('workspaces.invitations.store');
    Route::get('invitations/{token}', [InvitationController::class, 'show'])
        ->name('invitations.show');
    Route::post('invitations/{token}/accept', [InvitationController::class, 'accept'])
        ->name('invitations.accept');
    Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy'])
        ->name('invitations.destroy');

    Route::patch('memberships/{membership}', [MembershipController::class, 'update'])
        ->name('memberships.update');
    Route::delete('memberships/{membership}', [MembershipController::class, 'destroy'])
        ->name('memberships.destroy');
});

require __DIR__.'/settings.php';
