<?php

use App\Http\Controllers\Admin\ContentController;
use Illuminate\Support\Facades\Route;

// Админка
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // Сначала специфические маршруты
    Route::get('/subsections-by-section/{sectionId}', [ContentController::class, 'getSubsections'])
        ->name('subsections.by-section');

    Route::get('/versions/{version}/edit', [ContentController::class, 'editVersion'])
        ->name('versions.edit');

    Route::put('/versions/{version}', [ContentController::class, 'updateVersion'])
        ->name('versions.update');

    Route::delete('versions/{version}', [ContentController::class, 'destroyVersion'])
        ->name('versions.destroy');

    Route::get('/versions/{version}/download', [ContentController::class, 'downloadVersion'])
        ->name('versions.download');

    Route::post('contents/{content}/upload-version', [ContentController::class, 'uploadVersion'])
        ->name('contents.upload-version');

    Route::delete('contents/{content}/force', [ContentController::class, 'forceDestroy'])
        ->name('contents.force-destroy');

    Route::get('/contents/trashed', [ContentController::class, 'trashed'])
        ->name('contents.trashed');

    Route::post('/contents/{id}/restore', [ContentController::class, 'restore'])
        ->name('contents.restore');

    // Затем resource (будет обрабатываться в последнюю очередь)
    Route::resource('contents', ContentController::class);
});
