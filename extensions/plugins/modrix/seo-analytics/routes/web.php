<?php

use Illuminate\Support\Facades\Route;
use Modrix\SeoAnalytics\Http\HitController;
use Modrix\SeoAnalytics\Http\SitemapController;

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::post('seo-analytics/hit', HitController::class)->middleware('throttle:60,1')->name('hit');
