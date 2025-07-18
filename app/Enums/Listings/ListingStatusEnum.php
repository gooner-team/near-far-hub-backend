<?php

declare(strict_types=1);

namespace App\Enums\Listings;

enum ListingStatusEnum: string
{
    case STATUS_DRAFT = 'draft';
    case STATUS_ACTIVE = 'active';
    case STATUS_SOLD = 'sold';
    case STATUS_EXPIRED = 'expired';
    case STATUS_SUSPENDED = 'suspended';

    public static function getLabels(): array
    {
        return [
            self::STATUS_DRAFT->value => 'Draft',
            self::STATUS_ACTIVE->value => 'Active',
            self::STATUS_SOLD->value => 'Sold',
            self::STATUS_EXPIRED->value => 'Expired',
            self::STATUS_SUSPENDED->value => 'Suspended',
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

    public function isPublishable(): bool
    {
        return in_array($this, [self::STATUS_DRAFT, self::STATUS_SUSPENDED]);
    }

    public function isActive(): bool
    {
        return $this === self::STATUS_ACTIVE;
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::STATUS_DRAFT, self::STATUS_ACTIVE]);
    }
}