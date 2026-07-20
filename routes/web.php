<?php

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
    Route::resource('projects', ProjectController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('tags', TagController::class)->only(['index', 'store']);
    Route::resource('tasks', TaskController::class)->except(['create']);
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])
        ->name('tasks.status.update');
    Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store'])
        ->name('tasks.comments.store');
    Route::delete('comments/{comment}', [TaskCommentController::class, 'destroy'])
        ->name('comments.destroy');
    Route::post('tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])
        ->name('tasks.attachments.store');
    Route::delete('attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])
        ->name('attachments.destroy');
});

require __DIR__.'/settings.php';
