<?php

use App\Http\Controllers\API\V1\ContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('contents')->group(function () {
        Route::get('all', [ContentController::class, 'all']);
        Route::get('by_alias', [ContentController::class, 'byAlias']);
        Route::post('upload_version', [ContentController::class, 'uploadVersion']);
        Route::post('upload_localization', [ContentController::class, 'uploadLocalization']);
        Route::get('download', [ContentController::class, 'download']);
        Route::get('download_localization', [ContentController::class, 'downloadLocalization']);
        Route::get('qa', [ContentController::class, 'qaIndex']);
        Route::patch('versions/{version}/test', [ContentController::class, 'markAsTested']);
        Route::get('untested', [ContentController::class, 'untestedVersions']);
    });
});
