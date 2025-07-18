<?php

declare(strict_types=1);

namespace App\Enums\Listings;

enum ListingConditionEnum: string
{
    case CONDITION_NEW = 'new';
    case CONDITION_LIKE_NEW = 'like_new';
    case CONDITION_EXCELLENT = 'excellent';
    case CONDITION_GOOD = 'good';
    case CONDITION_FAIR = 'fair';
    case CONDITION_POOR = 'poor';

    public static function getLabels(): array
    {
        return [
            self::CONDITION_NEW->value => 'New',
            self::CONDITION_LIKE_NEW->value => 'Like New',
            self::CONDITION_EXCELLENT->value => 'Excellent',
            self::CONDITION_GOOD->value => 'Good',
            self::CONDITION_FAIR->value => 'Fair',
            self::CONDITION_POOR->value => 'Poor',
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

    public function getQualityScore(): int
    {
        return match ($this) {
            self::CONDITION_NEW => 100,
            self::CONDITION_LIKE_NEW => 90,
            self::CONDITION_EXCELLENT => 80,
            self::CONDITION_GOOD => 70,
            self::CONDITION_FAIR => 50,
            self::CONDITION_POOR => 30,
        };
    }

    public function isGoodCondition(): bool
    {
        return $this->getQualityScore() >= 70;
    }
}