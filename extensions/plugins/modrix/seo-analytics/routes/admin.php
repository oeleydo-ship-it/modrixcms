<?php

use Illuminate\Support\Facades\Route;
use Modrix\SeoAnalytics\Http\Admin\AnalyticsController;
use Modrix\SeoAnalytics\Http\Admin\RedirectsController;
use Modrix\SeoAnalytics\Http\Admin\SeoAuditController;

Route::get('/', [AnalyticsController::class, 'index'])->name('analytics.index');

Route::get('redirects', [RedirectsController::class, 'index'])->name('redirects.index');
Route::post('redirects', [RedirectsController::class, 'store'])->name('redirects.store');
Route::put('redirects/{redirect}', [RedirectsController::class, 'update'])->whereNumber('redirect')->name('redirects.update');
Route::delete('redirects/{redirect}', [RedirectsController::class, 'destroy'])->whereNumber('redirect')->name('redirects.destroy');

Route::get('seo', [SeoAuditController::class, 'index'])->name('seo.index');
