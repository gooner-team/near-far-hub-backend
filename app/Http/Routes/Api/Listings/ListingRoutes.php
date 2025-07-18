<?php

declare(strict_types=1);

namespace App\Http\Routes\Api\Listings;

use App\Contracts\Http\Routes\RouteContract;
use App\Http\Controllers\Listings\ListingController;
use Illuminate\Support\Facades\Route;

class ListingRoutes implements RouteContract
{
    public static function api(): void
    {
        Route::prefix('listings')->group(function () {

            Route::get('/', [ListingController::class, 'getAllListingsWithFiltering']);
            Route::get('/search', [ListingController::class, 'searchListings']);
            Route::get('/featured', [ListingController::class, 'getFeaturedListings']);
            Route::get('/categories', [ListingController::class, 'getCategoriesWithListingCounts']);
            Route::get('/stats', [ListingController::class, 'getListingStats']);
            Route::get('/category/{category}', [ListingController::class, 'getListingsByCategory']);
            Route::get('/{id}', [ListingController::class, 'getListingById'])->where('id', '[0-9]+');

            Route::middleware('auth:sanctum')->group(function () {

                Route::get('/my/listings', [ListingController::class, 'getCurrentUserListings']);
                Route::post('/', [ListingController::class, 'createListing']);
                Route::put('/{id}', [ListingController::class, 'updateListing'])->where('id', '[0-9]+');
                Route::delete('/{id}', [ListingController::class, 'deleteListing'])->where('id', '[0-9]+');

                Route::post('/{id}/publish', [ListingController::class, 'publishListing'])->where('id', '[0-9]+');
                Route::post('/{id}/unpublish', [ListingController::class, 'unpublishListing'])->where('id', '[0-9]+');
                Route::post('/{id}/mark-sold', [ListingController::class, 'markAsSold'])->where('id', '[0-9]+');

            });
        });
    }
}