<?php

use App\Http\Routes\Api\Auth\AuthRoutes;
use App\Http\Routes\Api\Roles\RoleRoutes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Routes\Api\Seller\SellerRoutes;
use App\Http\Routes\Api\Listings\ListingRoutes;
use App\Http\Routes\Api\Images\ImageRoutes;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'service' => 'api',
        'laravel_version' => app()->version(),
        'storage_disk' => config('filesystems.default'),
        'image_upload_enabled' => true,
    ]);
});

/** Authentication routes */
AuthRoutes::api();

/** Role routes */
RoleRoutes::api();

/** Seller routes */
SellerRoutes::api();

/** Listing routes */
ListingRoutes::api();

/** Image routes */
ImageRoutes::api();