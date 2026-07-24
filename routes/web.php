<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('my-tasks', [TaskController::class, 'myTasks'])->name('my-tasks.index');
    Route::resource('my-tasks', TaskController::class)
        ->except(['create', 'index'])
        ->parameters(['my-tasks' => 'task']);
    Route::patch('my-tasks/{task}/status', [TaskController::class, 'updateStatus'])
        ->name('my-tasks.status.update');
    Route::post('my-tasks/{task}/comments', [TaskCommentController::class, 'store'])
        ->name('my-tasks.comments.store');
    Route::delete('comments/{comment}', [TaskCommentController::class, 'destroy'])
        ->name('comments.destroy');
    Route::post('my-tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])
        ->name('my-tasks.attachments.store');
    Route::get('attachments/{attachment}', [TaskAttachmentController::class, 'show'])
        ->name('attachments.show');
    Route::delete('attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])
        ->name('attachments.destroy');
    Route::resource('projects', ProjectController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('tags', TagController::class)->only(['index', 'store']);
});

require __DIR__.'/settings.php';

Route::middleware(['guest'])->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->name('register.store');

    Route::get('verify-email', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::post('verify-email', [EmailVerificationController::class, 'verify'])
        ->name('verification.verify');
    Route::post('verify-email/resend', [EmailVerificationController::class, 'resend'])
        ->name('verification.resend');

    Route::get('forgot-password', [PasswordResetController::class, 'create'])
        ->name('auth.password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'store'])
        ->name('auth.password.email');
    Route::get('reset-password', [PasswordResetController::class, 'edit'])
        ->name('auth.password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])
        ->name('auth.password.update');
    Route::post('reset-password/resend', [PasswordResetController::class, 'resend'])
        ->name('auth.password.resend');
});
