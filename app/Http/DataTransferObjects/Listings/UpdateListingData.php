<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Listings;

use Spatie\DataTransferObject\DataTransferObject;

class UpdateListingData extends DataTransferObject
{
    public const TITLE = 'title';
    public const DESCRIPTION = 'description';
    public const PRICE = 'price';
    public const CATEGORY = 'category';
    public const CONDITION = 'condition';
    public const IMAGES = 'images';
    public const LOCATION = 'location';
    public const CAN_DELIVER_GLOBALLY = 'canDeliverGlobally';
    public const REQUIRES_APPOINTMENT = 'requiresAppointment';
    public const STATUS = 'status';
    public const EXPIRES_AT = 'expiresAt';

    public ?string $title;
    public ?string $description;
    public ?float $price;
    public ?string $category;
    public ?string $condition;
    public ?array $images;
    public ?array $location;
    public ?bool $canDeliverGlobally;
    public ?bool $requiresAppointment;
    public ?string $status;
    public ?string $expiresAt;
}