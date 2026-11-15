<?php

use Illuminate\Support\Facades\Route;
use Modrix\Newsletter\Http\PublicController;

Route::post('newsletter/subscribe', [PublicController::class, 'subscribe'])
    ->middleware('throttle:8,1')
    ->name('subscribe');

Route::get('newsletter/confirm/{token}', [PublicController::class, 'confirm'])
    ->where('token', '[A-Za-z0-9]{20,80}')
    ->name('confirm');

Route::get('newsletter/unsubscribe/{token}', [PublicController::class, 'unsubscribe'])
    ->where('token', '[A-Za-z0-9]{20,80}')
    ->name('unsubscribe');
