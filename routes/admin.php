<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\PostImageController;
use App\Http\Controllers\Admin\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::match(['put', 'post'], 'projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::middleware('can:manage-posts')->group(function (): void {
        Route::get('blog', [PostController::class, 'index'])->name('posts.index');
        Route::get('blog/create', [PostController::class, 'create'])->name('posts.create');
        Route::post('blog', [PostController::class, 'store'])->name('posts.store');
        Route::post('blog/preview', [PostController::class, 'preview'])->name('posts.preview');
        Route::post('blog/images', PostImageController::class)->name('posts.images.store');
        Route::get('blog/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
        Route::put('blog/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('blog/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    });
});
