<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Fallback login route - redirects to admin login
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Load Rider Module Routes
require __DIR__.'/../app/Modules/Rider/Routes/web.php';
