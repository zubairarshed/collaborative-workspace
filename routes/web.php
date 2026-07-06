<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\BoardColumnController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->scopeBindings()->group(function () {
    Route::get('dashboard', [WorkspaceController::class, 'index'])->name('dashboard');

    Route::resource('workspaces', WorkspaceController::class)->only(['store', 'show', 'update', 'destroy']);

    Route::get('workspaces/{workspace}/activity', [ActivityController::class, 'index'])
        ->name('workspaces.activity');

    Route::resource('workspaces.boards', BoardController::class)
        ->only(['store', 'show', 'update', 'destroy'])
        ->scoped(['board' => 'slug']);

    Route::patch('workspaces/{workspace}/boards/{board}/archive', [BoardController::class, 'archive'])
        ->name('workspaces.boards.archive');

    Route::post('workspaces/{workspace}/boards/{board}/columns', [BoardColumnController::class, 'store'])
        ->name('workspaces.boards.columns.store');
    Route::patch('workspaces/{workspace}/boards/{board}/columns/reorder', [BoardColumnController::class, 'reorder'])
        ->name('workspaces.boards.columns.reorder');
    Route::patch('workspaces/{workspace}/boards/{board}/columns/{column}', [BoardColumnController::class, 'update'])
        ->name('workspaces.boards.columns.update');
    Route::delete('workspaces/{workspace}/boards/{board}/columns/{column}', [BoardColumnController::class, 'destroy'])
        ->name('workspaces.boards.columns.destroy');

    Route::post('workspaces/{workspace}/boards/{board}/columns/{column}/tasks', [TaskController::class, 'store'])
        ->name('workspaces.boards.columns.tasks.store');
    Route::patch('workspaces/{workspace}/boards/{board}/tasks/{task}', [TaskController::class, 'update'])
        ->name('workspaces.boards.tasks.update');
    Route::patch('workspaces/{workspace}/boards/{board}/tasks/{task}/move', [TaskController::class, 'move'])
        ->name('workspaces.boards.tasks.move');
    Route::patch('workspaces/{workspace}/boards/{board}/tasks/{task}/archive', [TaskController::class, 'archive'])
        ->name('workspaces.boards.tasks.archive');
    Route::delete('workspaces/{workspace}/boards/{board}/tasks/{task}', [TaskController::class, 'destroy'])
        ->name('workspaces.boards.tasks.destroy');

    Route::post('workspaces/{workspace}/boards/{board}/tasks/{task}/comments', [CommentController::class, 'store'])
        ->name('workspaces.boards.tasks.comments.store');

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

    Route::get('notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');
    Route::patch('notifications/{notification}', [NotificationController::class, 'markRead'])
        ->name('notifications.markRead');
});

require __DIR__.'/settings.php';
