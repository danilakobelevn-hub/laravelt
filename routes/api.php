<?php

use App\Http\Controllers\API\V1\ContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('contents')->group(function () {
        // Public endpoints
        Route::get('/all', [ContentController::class, 'all']);
        Route::get('/by_alias', [ContentController::class, 'byAlias']);
        Route::get('/download', [ContentController::class, 'download']);
        Route::get('/downloadLocalization', [ContentController::class, 'downloadLocalization']);

        // Protected endpoints (will add auth later)
        Route::post('/uploadVersion', [ContentController::class, 'uploadVersion']);
        Route::post('/uploadLocalizationVersion', [ContentController::class, 'uploadLocalizationVersion']);

        // QA endpoints
        Route::get('/qa', [ContentController::class, 'qaIndex']);
        Route::get('/untested', [ContentController::class, 'untestedVersions']);
        Route::patch('/versions/{version}/test', [ContentController::class, 'markAsTested']);
    });
});
