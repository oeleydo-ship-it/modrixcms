<?php

use Illuminate\Support\Facades\Route;
use Modrix\SocialMedia\Http\Admin\AnalyticsController;
use Modrix\SocialMedia\Http\Admin\ProfilesController;

Route::get('/', [ProfilesController::class, 'index'])->name('profiles.index');
Route::post('profiles', [ProfilesController::class, 'store'])->name('profiles.store');
Route::put('profiles/{profile}', [ProfilesController::class, 'update'])->whereNumber('profile')->name('profiles.update');
Route::delete('profiles/{profile}', [ProfilesController::class, 'destroy'])->whereNumber('profile')->name('profiles.destroy');

Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
