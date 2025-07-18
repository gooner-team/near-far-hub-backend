<?php

declare(strict_types=1);

namespace App\Models\Images;

use App\Enums\Images\ImageTypeEnum;
use App\Models\Listings\Listing;
use App\Models\SellerProfiles\SellerProfile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory;

    public const ID = 'id';
    public const IMAGEABLE_TYPE = 'imageable_type';
    public const IMAGEABLE_ID = 'imageable_id';
    public const TYPE = 'type';
    public const ORIGINAL_NAME = 'original_name';
    public const FILE_NAME = 'file_name';
    public const FILE_PATH = 'file_path';
    public const FILE_SIZE = 'file_size';
    public const MIME_TYPE = 'mime_type';
    public const WIDTH = 'width';
    public const HEIGHT = 'height';
    public const ALT_TEXT = 'alt_text';
    public const SORT_ORDER = 'sort_order';
    public const IS_PRIMARY = 'is_primary';
    public const IS_ACTIVE = 'is_active';
    public const UPLOADED_BY = 'uploaded_by';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const TABLE = 'images';

    protected $fillable = [
        self::IMAGEABLE_TYPE,
        self::IMAGEABLE_ID,
        self::TYPE,
        self::ORIGINAL_NAME,
        self::FILE_NAME,
        self::FILE_PATH,
        self::FILE_SIZE,
        self::MIME_TYPE,
        self::WIDTH,
        self::HEIGHT,
        self::ALT_TEXT,
        self::SORT_ORDER,
        self::IS_PRIMARY,
        self::IS_ACTIVE,
        self::UPLOADED_BY,
    ];

    protected $casts = [
        self::FILE_SIZE => 'integer',
        self::WIDTH => 'integer',
        self::HEIGHT => 'integer',
        self::SORT_ORDER => 'integer',
        self::IS_PRIMARY => 'boolean',
        self::IS_ACTIVE => 'boolean',
        self::UPLOADED_BY => 'integer',
    ];

    /** @see Image::imageableRelation() */
    const IMAGEABLE_RELATION = 'imageableRelation';

    public function imageableRelation(): MorphTo
    {
        return $this->morphTo('imageable');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where(self::IS_ACTIVE, true);
    }

    public function scopePrimary($query)
    {
        return $query->where(self::IS_PRIMARY, true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where(self::TYPE, $type);
    }

    public function scopeOrderedBySortOrder($query)
    {
        return $query->orderBy(self::SORT_ORDER)->orderBy(self::CREATED_AT);
    }

    // Getters
    public function getId(): int
    {
        return $this->getAttribute(self::ID);
    }

    public function getImageableType(): string
    {
        return $this->getAttribute(self::IMAGEABLE_TYPE);
    }

    public function getImageableId(): int
    {
        return $this->getAttribute(self::IMAGEABLE_ID);
    }

    public function getType(): string
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getOriginalName(): string
    {
        return $this->getAttribute(self::ORIGINAL_NAME);
    }

    public function getFileName(): string
    {
        return $this->getAttribute(self::FILE_NAME);
    }

    public function getFilePath(): string
    {
        return $this->getAttribute(self::FILE_PATH);
    }

    public function getFileSize(): int
    {
        return $this->getAttribute(self::FILE_SIZE);
    }

    public function getMimeType(): string
    {
        return $this->getAttribute(self::MIME_TYPE);
    }

    public function getWidth(): ?int
    {
        return $this->getAttribute(self::WIDTH);
    }

    public function getHeight(): ?int
    {
        return $this->getAttribute(self::HEIGHT);
    }

    public function getAltText(): ?string
    {
        return $this->getAttribute(self::ALT_TEXT);
    }

    public function getSortOrder(): int
    {
        return $this->getAttribute(self::SORT_ORDER);
    }

    public function getIsPrimary(): bool
    {
        return $this->getAttribute(self::IS_PRIMARY);
    }

    public function getIsActive(): bool
    {
        return $this->getAttribute(self::IS_ACTIVE);
    }

    public function getUploadedBy(): ?int
    {
        return $this->getAttribute(self::UPLOADED_BY);
    }

    public function getCreatedAt(): Carbon
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->getAttribute(self::UPDATED_AT);
    }

    // Helper methods
    public function getUrl(string $size = 'original'): string
    {
        $baseUrl = config('app.cdn_url') ?? config('app.url');

        if ($this->disk === 's3') {
            return config('app.cdn_url')
                ? $baseUrl . '/' . $this->getFilePath()
                : Storage::disk('s3')->url($this->getFilePath());
        }

        return Storage::url($this->getFilePath());
    }

    public function getThumbnailUrl(): string
    {
        return $this->getUrl('thumbnail');
    }

    public function getMediumUrl(): string
    {
        return $this->getUrl('medium');
    }

    public function getFullUrl(): string
    {
        return $this->getUrl('original');
    }

    public function getFormattedFileSize(): string
    {
        $bytes = $this->getFileSize();
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDimensions(): ?string
    {
        if ($this->getWidth() && $this->getHeight()) {
            return $this->getWidth() . ' x ' . $this->getHeight();
        }

        return null;
    }

    public function isImage(): bool
    {
        return str_starts_with($this->getMimeType(), 'image/');
    }

    public function isPrimary(): bool
    {
        return $this->getIsPrimary();
    }

    public function isActive(): bool
    {
        return $this->getIsActive();
    }

    public function getTypeEnum(): ImageTypeEnum
    {
        return ImageTypeEnum::from($this->getType());
    }

    public function getTypeLabel(): string
    {
        return $this->getTypeEnum()->getLabel();
    }

    // Static methods
    public static function getMaxFileSize(): int
    {
        return 10 * 1024 * 1024; // 10MB
    }

    public static function getAllowedMimeTypes(): array
    {
        return [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
        ];
    }

    public static function getAllowedExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    }

    // Model events
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (Image $image) {
            // Delete file from storage when image record is deleted
            if (Storage::exists($image->getFilePath())) {
                Storage::delete($image->getFilePath());
            }
        });

        static::creating(function (Image $image) {
            // Set sort order if not provided
            if (!$image->getSortOrder()) {
                $maxOrder = static::where(self::IMAGEABLE_TYPE, $image->getImageableType())
                    ->where(self::IMAGEABLE_ID, $image->getImageableId())
                    ->max(self::SORT_ORDER) ?? 0;

                $image->setAttribute(self::SORT_ORDER, $maxOrder + 1);
            }
        });

        static::saved(function (Image $image) {
            // Ensure only one primary image per imageable
            if ($image->getIsPrimary()) {
                static::where(self::IMAGEABLE_TYPE, $image->getImageableType())
                    ->where(self::IMAGEABLE_ID, $image->getImageableId())
                    ->where(self::ID, '!=', $image->getId())
                    ->update([self::IS_PRIMARY => false]);
            }
        });
    }
}