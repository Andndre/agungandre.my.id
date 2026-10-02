<?php

use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
