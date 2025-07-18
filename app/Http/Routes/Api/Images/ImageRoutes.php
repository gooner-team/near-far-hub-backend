<?php

declare(strict_types=1);

namespace App\Http\Routes\Api\Images;

use App\Contracts\Http\Routes\RouteContract;
use App\Http\Controllers\Images\ImageController;
use Illuminate\Support\Facades\Route;

class ImageRoutes implements RouteContract
{
    public static function api(): void
    {
        Route::prefix('images')->middleware(['auth:sanctum'])->group(function () {

            Route::middleware([
                'throttle:10,1',
                'App\Http\Middleware\ImageUploadSecurityMiddleware'
            ])->group(function () {
                Route::post('/upload', [ImageController::class, 'uploadImage']);
                Route::post('/upload-multiple', [ImageController::class, 'uploadMultipleImages']);
            });

            Route::middleware('throttle:100,1')->group(function () {
                Route::get('/', [ImageController::class, 'getImages']);
                Route::get('/primary', [ImageController::class, 'getPrimaryImage']);
                Route::get('/stats', [ImageController::class, 'getImageStats']);
                Route::get('/{id}', [ImageController::class, 'getImage'])->where('id', '[0-9]+');
            });

            Route::middleware('throttle:60,1')->group(function () {
                Route::put('/{id}', [ImageController::class, 'updateImage'])->where('id', '[0-9]+');
                Route::delete('/{id}', [ImageController::class, 'deleteImage'])->where('id', '[0-9]+');
                Route::delete('/bulk', [ImageController::class, 'deleteMultipleImages']);
                Route::delete('/entity', [ImageController::class, 'deleteAllImagesForEntity']);
            });

            Route::middleware('throttle:30,1')->group(function () {
                Route::post('/{id}/set-primary', [ImageController::class, 'setPrimaryImage'])->where('id', '[0-9]+');
                Route::post('/{id}/toggle-status', [ImageController::class, 'toggleImageStatus'])->where('id', '[0-9]+');
                Route::post('/reorder', [ImageController::class, 'reorderImages']);
            });

        });

        Route::prefix('images')->middleware('throttle:200,1')->group(function () {

            Route::get('/types', [ImageController::class, 'getImageTypes']);
            Route::get('/upload-config/{imageType}', [ImageController::class, 'getUploadConfig']);
        });
    }
}