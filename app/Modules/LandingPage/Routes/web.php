<?php

use Illuminate\Support\Facades\Route;
use App\Modules\LandingPage\Controllers\LandingPageController;

Route::get('/', [LandingPageController::class, 'index'])->name('home');
Route::get('/landing', [LandingPageController::class, 'landing'])->name('landing');
