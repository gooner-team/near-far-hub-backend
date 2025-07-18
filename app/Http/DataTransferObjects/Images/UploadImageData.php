<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Images;

use App\Enums\Images\ImageTypeEnum;
use Illuminate\Http\UploadedFile;
use Spatie\DataTransferObject\DataTransferObject;

class UploadImageData extends DataTransferObject
{
    public const FILE = 'file';
    public const IMAGEABLE_TYPE = 'imageableType';
    public const IMAGEABLE_ID = 'imageableId';
    public const IMAGE_TYPE = 'imageType';
    public const ALT_TEXT = 'altText';
    public const IS_PRIMARY = 'isPrimary';

    public UploadedFile $file;
    public string $imageableType;
    public int $imageableId;
    public ImageTypeEnum $imageType;
    public ?string $altText;
    public bool $isPrimary;
}