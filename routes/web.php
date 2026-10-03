<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostMediaController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/projects/{slug}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/media/blog/images/{filename}', PostMediaController::class)
    ->where('filename', '[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp|gif)')
    ->name('blog.media.show');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
