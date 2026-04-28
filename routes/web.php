<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RouteShareController;
use App\Http\Controllers\ClientShareController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

Route::get('/route/{riderId}', [RouteShareController::class, 'show'])->name('route.share');
Route::get('/client/{token}', [ClientShareController::class, 'show'])->name('client.share');

require __DIR__.'/../app/Modules/Rider/Routes/web.php';
