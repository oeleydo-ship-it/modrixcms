<?php

use Illuminate\Support\Facades\Route;
use Modrix\FormBuilder\Http\PublicFormController;
use Modrix\FormBuilder\Http\SubmitController;

Route::post('forms/{slug}/submit', SubmitController::class)
    ->where('slug', '[a-z0-9-]+')
    ->middleware('throttle:10,1')
    ->name('submit');

// The form on a page of its own (and the iframe the embed code points at).
Route::get('forms/{slug}', PublicFormController::class)
    ->where('slug', '[a-z0-9-]+')
    ->name('public');
