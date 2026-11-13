<?php

use Illuminate\Support\Facades\Route;
use Modrix\ContactForm\Http\SubmitController;

Route::post('contact-form/send', SubmitController::class)->middleware('throttle:5,1')->name('send');
