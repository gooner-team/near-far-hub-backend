<?php

declare(strict_types=1);

namespace App\Http\DataTransferObjects\Images;

use App\Enums\Images\ImageTypeEnum;
use Illuminate\Http\UploadedFile;
use Spatie\DataTransferObject\DataTransferObject;

class UploadMultipleImagesData extends DataTransferObject
{
    public const FILES = 'files';
    public const IMAGEABLE_TYPE = 'imageableType';
    public const IMAGEABLE_ID = 'imageableId';
    public const IMAGE_TYPE = 'imageType';
    public const ALT_TEXTS = 'altTexts';

    /** @var UploadedFile[] */
    public array $files;
    public string $imageableType;
    public int $imageableId;
    public ImageTypeEnum $imageType;
    public array $altTexts;
}