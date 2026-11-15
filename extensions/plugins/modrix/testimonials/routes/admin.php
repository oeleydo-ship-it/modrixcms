<?php

use Illuminate\Support\Facades\Route;
use Modrix\Testimonials\Http\Admin\ItemsController;

Route::get('/', [ItemsController::class, 'index'])->name('items.index');
Route::post('items', [ItemsController::class, 'store'])->name('items.store');
Route::put('items/{item}', [ItemsController::class, 'update'])->whereNumber('item')->name('items.update');
Route::delete('items/{item}', [ItemsController::class, 'destroy'])->whereNumber('item')->name('items.destroy');
