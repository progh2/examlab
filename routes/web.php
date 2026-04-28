<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\PracticeController;

Route::get('/', function () {
    return view('home');
});

Route::get('/welcome', function () {
    return view('welcome');
});

// Google OAuth 로그인 시작/콜백
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [PracticeController::class, 'dashboard'])->name('dashboard');
    Route::get('/practice/today-review', [PracticeController::class, 'startTodayReview'])->name('practice.todayReview');
    Route::get('/practice/{mode}', [PracticeController::class, 'startMode'])
        ->whereIn('mode', ['all', 'wrong', 'adaptive', 'review'])
        ->name('practice.start');
    Route::get('/practice/session/{session}', [PracticeController::class, 'showSession'])->name('practice.session');
    Route::post('/practice/session/{session}/answer', [PracticeController::class, 'submitAnswer'])->name('practice.answer');
    Route::post('/practice/session/{session}/next', [PracticeController::class, 'nextQuestion'])->name('practice.next');
    Route::get('/wrong-note', [PracticeController::class, 'wrongNote'])->name('wrong-note');
    Route::get('/review-queue', [PracticeController::class, 'reviewQueue'])->name('review-queue');
});
