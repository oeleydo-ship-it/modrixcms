<?php

use Illuminate\Support\Facades\Route;
use Modrix\PromoBars\Http\TrackController;

Route::post('promo-bars/{campaign}/track', TrackController::class)
    ->whereNumber('campaign')
    ->middleware('throttle:60,1')
    ->name('track');
