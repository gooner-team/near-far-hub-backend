<?php

declare(strict_types=1);

namespace App\Services\Traits\Models;

use App\Enums\Images\ImageTypeEnum;
use App\Models\Images\Image;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait HasImages
{
    /**
     * Get all images for this model
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    /**
     * Get active images for this model
     */
    public function activeImages(): MorphMany
    {
        return $this->images()->where('is_active', true);
    }

    /**
     * Get images by type
     */
    public function imagesByType(ImageTypeEnum $type): MorphMany
    {
        return $this->images()->where('type', $type->value);
    }

    /**
     * Get active images by type
     */
    public function activeImagesByType(ImageTypeEnum $type): MorphMany
    {
        return $this->activeImages()->where('type', $type->value);
    }

    /**
     * Get primary image
     */
    public function primaryImage(?ImageTypeEnum $type = null): ?Image
    {
        $query = $this->images()->where('is_primary', true)->where('is_active', true);

        if ($type) {
            $query->where('type', $type->value);
        }

        return $query->first();
    }

    /**
     * Get the first image (fallback if no primary)
     */
    public function firstImage(?ImageTypeEnum $type = null): ?Image
    {
        $primary = $this->primaryImage($type);
        if ($primary) {
            return $primary;
        }

        $query = $this->activeImages();
        if ($type) {
            $query->where('type', $type->value);
        }

        return $query->first();
    }

    /**
     * Get primary image URL
     */
    public function primaryImageUrl(?ImageTypeEnum $type = null, string $size = 'medium'): ?string
    {
        $image = $this->primaryImage($type);
        if (!$image) {
            return null;
        }

        return match ($size) {
            'thumbnail' => $image->getThumbnailUrl(),
            'medium' => $image->getMediumUrl(),
            'full', 'original' => $image->getFullUrl(),
            default => $image->getUrl(),
        };
    }

    /**
     * Get first image URL (fallback if no primary)
     */
    public function firstImageUrl(?ImageTypeEnum $type = null, string $size = 'medium'): ?string
    {
        $image = $this->firstImage($type);
        if (!$image) {
            return null;
        }

        return match ($size) {
            'thumbnail' => $image->getThumbnailUrl(),
            'medium' => $image->getMediumUrl(),
            'full', 'original' => $image->getFullUrl(),
            default => $image->getUrl(),
        };
    }

    /**
     * Get all image URLs
     */
    public function allImageUrls(?ImageTypeEnum $type = null, string $size = 'medium'): array
    {
        $query = $this->activeImages();
        if ($type) {
            $query->where('type', $type->value);
        }

        return $query->get()->map(function (Image $image) use ($size) {
            return match ($size) {
                'thumbnail' => $image->getThumbnailUrl(),
                'medium' => $image->getMediumUrl(),
                'full', 'original' => $image->getFullUrl(),
                default => $image->getUrl(),
            };
        })->toArray();
    }

    /**
     * Check if model has images
     */
    public function hasImages(?ImageTypeEnum $type = null): bool
    {
        $query = $this->activeImages();
        if ($type) {
            $query->where('type', $type->value);
        }

        return $query->exists();
    }

    /**
     * Get images count
     */
    public function imagesCount(?ImageTypeEnum $type = null, bool $activeOnly = true): int
    {
        $query = $activeOnly ? $this->activeImages() : $this->images();
        if ($type) {
            $query->where('type', $type->value);
        }

        return $query->count();
    }

    /**
     * Get total file size of all images
     */
    public function totalImageFileSize(?ImageTypeEnum $type = null): int
    {
        $query = $this->activeImages();
        if ($type) {
            $query->where('type', $type->value);
        }

        return $query->sum('file_size');
    }

    /**
     * Get formatted total file size
     */
    public function formattedTotalImageFileSize(?ImageTypeEnum $type = null): string
    {
        $bytes = $this->totalImageFileSize($type);
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get image statistics
     */
    public function imageStats(): array
    {
        $allImages = $this->images;
        $activeImages = $allImages->where('is_active', true);

        return [
            'total_images' => $allImages->count(),
            'active_images' => $activeImages->count(),
            'inactive_images' => $allImages->where('is_active', false)->count(),
            'primary_images' => $allImages->where('is_primary', true)->count(),
            'total_file_size' => $allImages->sum('file_size'),
            'total_file_size_formatted' => $this->formattedTotalImageFileSize(),
            'types' => $allImages->groupBy('type')->map->count(),
            'has_primary' => $this->primaryImage() !== null,
        ];
    }

    /**
     * Get images grouped by type
     */
    public function imagesGroupedByType(bool $activeOnly = true): Collection
    {
        $query = $activeOnly ? $this->activeImages() : $this->images();
        return $query->get()->groupBy('type');
    }

    /**
     * Delete all images
     */
    public function deleteAllImages(?ImageTypeEnum $type = null): bool
    {
        $query = $this->images();
        if ($type) {
            $query->where('type', $type->value);
        }

        $images = $query->get();

        foreach ($images as $image) {
            $image->delete(); // This will trigger the model event to delete the file
        }

        return true;
    }

    /**
     * Scope to eager load images
     */
    public function scopeWithImages($query, ?ImageTypeEnum $type = null)
    {
        if ($type) {
            return $query->with(['images' => function ($q) use ($type) {
                $q->where('type', $type->value)->where('is_active', true);
            }]);
        }

        return $query->with(['images' => function ($q) {
            $q->where('is_active', true);
        }]);
    }

    /**
     * Scope to eager load primary image
     */
    public function scopeWithPrimaryImage($query, ?ImageTypeEnum $type = null)
    {
        if ($type) {
            return $query->with(['images' => function ($q) use ($type) {
                $q->where('type', $type->value)
                    ->where('is_primary', true)
                    ->where('is_active', true);
            }]);
        }

        return $query->with(['images' => function ($q) {
            $q->where('is_primary', true)->where('is_active', true);
        }]);
    }

    /**
     * Boot the trait
     */
    public static function bootHasImages()
    {
        static::deleting(function ($model) {
            // Delete all associated images when the model is deleted
            $model->deleteAllImages();
        });
    }

    /**
     * Get the model's morph class name for images
     */
    public function getImageableMorphClass(): string
    {
        return $this->getMorphClass();
    }

    /**
     * Get available image types for this model
     */
    public function getAvailableImageTypes(): array
    {
        // Override this method in your model to specify which image types are allowed
        return ImageTypeEnum::getValues();
    }

    /**
     * Get the primary image type for this model
     */
    public function getPrimaryImageType(): ?ImageTypeEnum
    {
        // Override this method in your model to specify the primary image type
        return null;
    }

    /**
     * Check if model can have image of given type
     */
    public function canHaveImageType(ImageTypeEnum $type): bool
    {
        return in_array($type->value, $this->getAvailableImageTypes());
    }
}