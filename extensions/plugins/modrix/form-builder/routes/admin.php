<?php

use Illuminate\Support\Facades\Route;
use Modrix\FormBuilder\Http\Admin\FormsController;
use Modrix\FormBuilder\Http\Admin\SubmissionsController;

Route::get('/', [FormsController::class, 'index'])->name('forms.index');
Route::get('forms/create', [FormsController::class, 'create'])->name('forms.create');
Route::post('forms', [FormsController::class, 'store'])->name('forms.store');
Route::get('forms/{form}/edit', [FormsController::class, 'edit'])->whereNumber('form')->name('forms.edit');
Route::put('forms/{form}', [FormsController::class, 'update'])->whereNumber('form')->name('forms.update');
Route::post('forms/{form}/duplicate', [FormsController::class, 'duplicate'])->whereNumber('form')->name('forms.duplicate');
Route::post('forms/{form}/toggle-status', [FormsController::class, 'toggleStatus'])->whereNumber('form')->name('forms.toggle-status');
Route::delete('forms/{form}', [FormsController::class, 'destroy'])->whereNumber('form')->name('forms.destroy');

Route::get('submissions', [SubmissionsController::class, 'index'])->name('submissions.index');
Route::get('submissions/export', [SubmissionsController::class, 'export'])->name('submissions.export');
Route::post('submissions/bulk', [SubmissionsController::class, 'bulk'])->name('submissions.bulk');
Route::get('submissions/{submission}', [SubmissionsController::class, 'show'])->whereNumber('submission')->name('submissions.show');
Route::put('submissions/{submission}', [SubmissionsController::class, 'update'])->whereNumber('submission')->name('submissions.update');
Route::delete('submissions/{submission}', [SubmissionsController::class, 'destroy'])->whereNumber('submission')->name('submissions.destroy');
