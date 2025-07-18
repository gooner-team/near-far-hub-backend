<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Images;

use Spatie\DataTransferObject\DataTransferObject;

class ReorderImagesData extends DataTransferObject
{
    public const IMAGEABLE_TYPE = 'imageableType';
    public const IMAGEABLE_ID = 'imageableId';
    public const IMAGE_IDS = 'imageIds';

    public string $imageableType;
    public int $imageableId;
    public array $imageIds;
}