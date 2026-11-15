<?php

use Illuminate\Support\Facades\Route;
use Modrix\Newsletter\Http\Admin\CampaignsController;
use Modrix\Newsletter\Http\Admin\SubscribersController;

Route::get('/', [SubscribersController::class, 'index'])->name('subscribers.index');
Route::post('subscribers', [SubscribersController::class, 'store'])->name('subscribers.store');
Route::post('subscribers/import', [SubscribersController::class, 'import'])->name('subscribers.import');
Route::get('subscribers/export', [SubscribersController::class, 'export'])->name('subscribers.export');
Route::put('subscribers/{subscriber}', [SubscribersController::class, 'update'])->whereNumber('subscriber')->name('subscribers.update');
Route::delete('subscribers/{subscriber}', [SubscribersController::class, 'destroy'])->whereNumber('subscriber')->name('subscribers.destroy');

Route::get('campaigns', [CampaignsController::class, 'index'])->name('campaigns.index');
Route::get('campaigns/create', [CampaignsController::class, 'create'])->name('campaigns.create');
Route::post('campaigns', [CampaignsController::class, 'store'])->name('campaigns.store');
Route::get('campaigns/{campaign}/edit', [CampaignsController::class, 'edit'])->whereNumber('campaign')->name('campaigns.edit');
Route::put('campaigns/{campaign}', [CampaignsController::class, 'update'])->whereNumber('campaign')->name('campaigns.update');
Route::post('campaigns/{campaign}/test', [CampaignsController::class, 'sendTest'])->whereNumber('campaign')->name('campaigns.test');
Route::post('campaigns/{campaign}/send', [CampaignsController::class, 'send'])->whereNumber('campaign')->name('campaigns.send');
Route::delete('campaigns/{campaign}', [CampaignsController::class, 'destroy'])->whereNumber('campaign')->name('campaigns.destroy');
