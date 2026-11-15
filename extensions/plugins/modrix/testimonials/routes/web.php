<?php

use Illuminate\Support\Facades\Route;
use Modrix\Testimonials\Http\SubmitController;

Route::post('testimonials/submit', SubmitController::class)
    ->middleware('throttle:5,1')
    ->name('submit');
