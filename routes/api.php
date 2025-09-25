<?php

use App\Http\Controllers\API\V1\ContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('contents')->group(function () {
        // Основные методы API
        Route::get('all', [ContentController::class, 'all']);
        Route::get('by_alias', [ContentController::class, 'byAlias']);
        Route::post('uploadVersion', [ContentController::class, 'uploadVersion']);
        Route::post('uploadLocalizationVersion', [ContentController::class, 'uploadLocalizationVersion']);
        Route::get('download', [ContentController::class, 'download']);
        Route::get('downloadLocalization', [ContentController::class, 'downloadLocalization']);

        // QA методы
        Route::get('qa', [ContentController::class, 'qaIndex']);
        Route::patch('versions/{version}/test', [ContentController::class, 'markAsTested']);
        Route::get('untested', [ContentController::class, 'untestedVersions']);
    });
});
