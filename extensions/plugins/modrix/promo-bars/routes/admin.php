<?php

use Illuminate\Support\Facades\Route;
use Modrix\PromoBars\Http\Admin\CampaignsController;

Route::get('/', [CampaignsController::class, 'index'])->name('campaigns.index');
Route::post('/', [CampaignsController::class, 'store'])->name('campaigns.store');
Route::put('{campaign}', [CampaignsController::class, 'update'])->whereNumber('campaign')->name('campaigns.update');
Route::post('{campaign}/toggle', [CampaignsController::class, 'toggle'])->whereNumber('campaign')->name('campaigns.toggle');
Route::delete('{campaign}', [CampaignsController::class, 'destroy'])->whereNumber('campaign')->name('campaigns.destroy');
