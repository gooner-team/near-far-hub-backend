<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Images;

use Spatie\DataTransferObject\DataTransferObject;

class UpdateImageData extends DataTransferObject
{
    public const ALT_TEXT = 'altText';
    public const IS_PRIMARY = 'isPrimary';
    public const IS_ACTIVE = 'isActive';
    public const SORT_ORDER = 'sortOrder';

    public ?string $altText;
    public ?bool $isPrimary;
    public ?bool $isActive;
    public ?int $sortOrder;
}