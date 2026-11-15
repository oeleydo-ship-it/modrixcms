<?php

use Illuminate\Support\Facades\Route;
use Modrix\Faq\Http\VoteController;

Route::post('faq/{item}/vote', VoteController::class)
    ->whereNumber('item')
    ->middleware('throttle:30,1')
    ->name('vote');
