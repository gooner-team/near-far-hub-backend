<?php

namespace App\Providers;

use App\Services\AWS\S3Service;
use App\Services\Repositories\Auth\AuthDbRepository;
use App\Services\Repositories\Auth\AuthLogicRepository;
use App\Services\Repositories\Images\ImageRepository;
use App\Services\Repositories\Listings\ListingRepository;
use App\Services\Repositories\Seller\AppointmentRepository;
use App\Services\Repositories\Seller\SellerProfileRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthDbRepository::class);
        $this->app->bind(AuthLogicRepository::class);

        $this->app->bind(SellerProfileRepository::class);
        $this->app->bind(AppointmentRepository::class);

        $this->app->bind(ListingRepository::class);

        $this->app->bind(S3Service::class);
        $this->app->bind(ImageRepository::class);

        $this->app->singleton(S3Service::class, function ($app) {
            return new S3Service();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureFileStorage();
    }

    /**
     * Configure file storage settings
     */
    private function configureFileStorage(): void
    {
        ini_set('upload_max_filesize', '10M');
        ini_set('post_max_size', '50M');
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '256M');

        if (class_exists('Intervention\Image\ImageManager')) {
            config(['image.driver' => 'gd']);
        }
    }
}