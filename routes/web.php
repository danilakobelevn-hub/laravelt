<?php

use App\Http\Controllers\Admin\ContentController;
use Illuminate\Support\Facades\Route;

// Админка
Route::prefix('admin')->name('admin.')->group(function () {
    // Главная
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // Управление контентом
    Route::resource('contents', ContentController::class);

    Route::post('contents/{content}/upload-version', [ContentController::class, 'uploadVersion'])
        ->name('contents.upload-version');

    Route::delete('versions/{version}', [ContentController::class, 'destroyVersion'])
        ->name('versions.destroy');

    Route::get('/admin/versions/{version}/download', [ContentController::class, 'downloadVersion'])
        ->name('versions.download');

    Route::delete('contents/{content}/force', [ContentController::class, 'forceDestroy'])
        ->name('contents.force-destroy');

    Route::get('/subsections-by-section/{sectionId}', [ContentController::class, 'getSubsections'])
        ->name('subsections.by-section');
});
