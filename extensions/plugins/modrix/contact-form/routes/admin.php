<?php

use Illuminate\Support\Facades\Route;
use Modrix\ContactForm\Http\Admin\MessagesController;

Route::get('/', [MessagesController::class, 'index'])->name('messages.index');
Route::delete('{message}', [MessagesController::class, 'destroy'])->whereNumber('message')->name('messages.destroy');
