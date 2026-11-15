<?php

use Illuminate\Support\Facades\Route;
use Modrix\SocialMedia\Http\TrackController;

Route::post('social-media/track', [TrackController::class, 'track'])
    ->middleware('throttle:30,1')
    ->name('track');

Route::get('social-media/go/{profile}', [TrackController::class, 'go'])
    ->whereNumber('profile')
    ->name('go');
