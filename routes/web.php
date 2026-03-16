<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RouteShareController;

Route::get('/', function () {
    return view('welcome');
});

// Fallback login route - redirects to admin login
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Public route share link
Route::get('/route/{token}', [RouteShareController::class, 'show'])->name('route.share');

// Load Rider Module Routes
require __DIR__.'/../app/Modules/Rider/Routes/web.php';
