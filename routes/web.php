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
});

Route::get('/admin/subsections-by-section/{sectionId}', function($sectionId) {
    $subsections = Subsection::where('section_id', $sectionId)
        ->pluck('default_name', 'id');
    return response()->json($subsections);
})->name('admin.subsections.by-section');
