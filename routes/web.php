<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Visitors are sent to the dashboard; the "auth" middleware redirects
// anyone who is not logged in to the login page.
Route::redirect('/', '/dashboard');

// Only for visitors who are NOT logged in
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

// Only for logged-in users
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Students (these two must come before the resource routes)
    Route::get('/students/export', [StudentController::class, 'export'])->name('students.export');
    Route::get('/students/{student}/transcript', [StudentController::class, 'transcript'])->name('students.transcript');
    Route::resource('students', StudentController::class);

    // Results belong to a student: /students/{student}/results/...
    Route::resource('students.results', ResultController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->scoped();

    Route::resource('programmes', ProgrammeController::class)->except('show');
    Route::resource('subjects', SubjectController::class)->except('show');

    // My profile and password
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Staff accounts: admins only
    Route::resource('users', UserController::class)->except('show')->middleware('can:admin');
});
