<?php

declare(strict_types=1);

namespace App\Http\Resources\Images;

use App\Models\Images\Image;
use App\Services\Traits\Resources\HasConditionalFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    use HasConditionalFields;

    /**
     * @var Image $resource
     */
    public $resource;

    protected array $conditionalFields = [
        'originalName' => Image::ORIGINAL_NAME,
        'fileName' => Image::FILE_NAME,
        'filePath' => Image::FILE_PATH,
        'fileSize' => Image::FILE_SIZE,
        'mimeType' => Image::MIME_TYPE,
        'width' => Image::WIDTH,
        'height' => Image::HEIGHT,
        'altText' => Image::ALT_TEXT,
        'sortOrder' => Image::SORT_ORDER,
        'isPrimary' => Image::IS_PRIMARY,
        'isActive' => Image::IS_ACTIVE,
        'uploadedBy' => Image::UPLOADED_BY,
        'createdAt' => Image::CREATED_AT,
        'updatedAt' => Image::UPDATED_AT,
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'type' => $this->resource->getType(),
            'typeLabel' => $this->resource->getTypeLabel(),
            'url' => $this->resource->getUrl(),
            'thumbnailUrl' => $this->resource->getThumbnailUrl(),
            'mediumUrl' => $this->resource->getMediumUrl(),
            'fullUrl' => $this->resource->getFullUrl(),
            'formattedFileSize' => $this->resource->getFormattedFileSize(),
            'dimensions' => $this->resource->getDimensions(),
            'isImage' => $this->resource->isImage(),
            'timeAgo' => $this->resource->getCreatedAt()->diffForHumans(),

            'meta' => [
                'imageableType' => $this->resource->getImageableType(),
                'imageableId' => $this->resource->getImageableId(),
                'canBeSetAsPrimary' => $this->resource->getTypeEnum()->canBeSetAsPrimary(),
                'isPubliclyVisible' => $this->resource->getTypeEnum()->isPubliclyVisible(),
                'requiresModeration' => $this->resource->getTypeEnum()->requiresModeration(),
            ],

            ...$this->getConditionalData($request),
        ];
    }
}