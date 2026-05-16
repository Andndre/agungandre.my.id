<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::inertia('projects', 'Admin/Projects/Index')->name('projects.index');
    Route::inertia('projects/create', 'Admin/Projects/Create')->name('projects.create');
    Route::inertia('projects/{project}/edit', 'Admin/Projects/Edit')->name('projects.edit');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::post('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
});
