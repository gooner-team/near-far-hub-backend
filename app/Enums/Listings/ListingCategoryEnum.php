<?php

declare(strict_types=1);

namespace App\Enums\Listings;

enum ListingCategoryEnum: string
{
    case ELECTRONICS = 'electronics';
    case VEHICLES = 'vehicles';
    case REAL_ESTATE = 'real-estate';
    case FASHION = 'fashion';
    case HOME_GARDEN = 'home-garden';
    case SERVICES = 'services';
    case GAMING = 'gaming';
    case BOOKS = 'books';
    case SPORTS = 'sports';
    case PHOTOGRAPHY = 'photography';
    case MUSIC = 'music';
    case FITNESS = 'fitness';

    public static function getLabels(): array
    {
        return [
            self::ELECTRONICS->value => 'Electronics',
            self::VEHICLES->value => 'Vehicles',
            self::REAL_ESTATE->value => 'Real Estate',
            self::FASHION->value => 'Fashion',
            self::HOME_GARDEN->value => 'Home & Garden',
            self::SERVICES->value => 'Services',
            self::GAMING->value => 'Gaming',
            self::BOOKS->value => 'Books & Media',
            self::SPORTS->value => 'Sports & Outdoors',
            self::PHOTOGRAPHY->value => 'Photography',
            self::MUSIC->value => 'Musical Instruments',
            self::FITNESS->value => 'Health & Fitness',
        ];
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): string
    {
        return self::getLabels()[$this->value];
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ELECTRONICS => 'smartphone',
            self::VEHICLES => 'car',
            self::REAL_ESTATE => 'home',
            self::FASHION => 'shirt',
            self::HOME_GARDEN => 'sofa',
            self::SERVICES => 'wrench',
            self::GAMING => 'gamepad-2',
            self::BOOKS => 'book',
            self::SPORTS => 'bike',
            self::PHOTOGRAPHY => 'camera',
            self::MUSIC => 'music',
            self::FITNESS => 'dumbbell',
        };
    }

    public function requiresCondition(): bool
    {
        return $this !== self::SERVICES;
    }
}