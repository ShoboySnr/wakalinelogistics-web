<?php

use Illuminate\Support\Facades\Route;
use App\Modules\PublicApi\Controllers\NewsletterController;
use App\Modules\PublicApi\Controllers\ContactFormController;

Route::prefix('v1/public')->name('api.public.')->middleware('api.token:frontend')->group(function () {
    Route::post('newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
    Route::post('contact/enquiry', [ContactFormController::class, 'send'])->name('contact.send');
});
