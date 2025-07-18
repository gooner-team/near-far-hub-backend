<?php

declare(strict_types=1);

namespace App\Services\Repositories\Images;

use App\Enums\Images\ImageTypeEnum;
use App\Models\Images\Image;
use App\Services\AWS\S3Service;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;

class ImageRepository
{
    public function __construct(
        private readonly Image $image,
        private readonly S3Service $s3Service
    ) {
    }

    /**
     * Upload and store a single image
     *
     * @throws Exception
     */
    public function uploadImage(
        UploadedFile $file,
        string $imageableType,
        int $imageableId,
        ImageTypeEnum $imageType,
        ?int $uploadedBy = null,
        ?string $altText = null,
        bool $isPrimary = false
    ): Image {
        try {
            // Upload file to storage
            $fileData = $this->s3Service->uploadFile($file, $imageType, $imageableId, $imageType->isPubliclyVisible());

            // If this should be primary, unset other primary images
            if ($isPrimary || $imageType->canBeSetAsPrimary()) {
                $this->unsetPrimaryImages($imageableType, $imageableId, $imageType);
            }

            // Create image record
            $imageData = [
                Image::IMAGEABLE_TYPE => $imageableType,
                Image::IMAGEABLE_ID => $imageableId,
                Image::TYPE => $imageType->value,
                Image::ORIGINAL_NAME => $fileData['original_name'],
                Image::FILE_NAME => $fileData['file_name'],
                Image::FILE_PATH => $fileData['file_path'],
                Image::FILE_SIZE => $fileData['file_size'],
                Image::MIME_TYPE => $fileData['mime_type'],
                Image::WIDTH => $fileData['width'] ?? null,
                Image::HEIGHT => $fileData['height'] ?? null,
                Image::ALT_TEXT => $altText,
                Image::IS_PRIMARY => $isPrimary,
                Image::IS_ACTIVE => true,
                Image::UPLOADED_BY => $uploadedBy,
            ];

            $image = $this->image->create($imageData);

            return $image;

        } catch (Exception $e) {
            // Clean up uploaded file if database insert fails
            if (isset($fileData['file_path'])) {
                $this->s3Service->deleteFile($fileData['file_path']);
            }
            throw new Exception('Failed to upload image: ' . $e->getMessage());
        }
    }

    /**
     * Upload multiple images
     *
     * @param UploadedFile[] $files
     * @throws Exception
     */
    public function uploadMultipleImages(
        array $files,
        string $imageableType,
        int $imageableId,
        ImageTypeEnum $imageType,
        ?int $uploadedBy = null,
        array $altTexts = []
    ): Collection {
        try {
            // Upload all files to storage first
            $filesData = $this->s3Service->uploadMultipleFiles($files, $imageType, $imageableId, $imageType->isPubliclyVisible());

            $images = new Collection();
            $createdImages = [];

            foreach ($filesData as $index => $fileData) {
                $altText = $altTexts[$index] ?? null;
                $isPrimary = $fileData['is_primary'] ?? false;

                // If this should be primary, unset other primary images
                if ($isPrimary && $imageType->canBeSetAsPrimary()) {
                    $this->unsetPrimaryImages($imageableType, $imageableId, $imageType);
                }

                $imageData = [
                    Image::IMAGEABLE_TYPE => $imageableType,
                    Image::IMAGEABLE_ID => $imageableId,
                    Image::TYPE => $imageType->value,
                    Image::ORIGINAL_NAME => $fileData['original_name'],
                    Image::FILE_NAME => $fileData['file_name'],
                    Image::FILE_PATH => $fileData['file_path'],
                    Image::FILE_SIZE => $fileData['file_size'],
                    Image::MIME_TYPE => $fileData['mime_type'],
                    Image::WIDTH => $fileData['width'] ?? null,
                    Image::HEIGHT => $fileData['height'] ?? null,
                    Image::ALT_TEXT => $altText,
                    Image::SORT_ORDER => $fileData['sort_order'],
                    Image::IS_PRIMARY => $isPrimary,
                    Image::IS_ACTIVE => true,
                    Image::UPLOADED_BY => $uploadedBy,
                ];

                $image = $this->image->create($imageData);
                $images->push($image);
                $createdImages[] = $image;
            }

            return $images;

        } catch (Exception $e) {
            // Clean up uploaded files if database insert fails
            foreach ($filesData ?? [] as $fileData) {
                $this->s3Service->deleteFile($fileData['file_path']);
            }

            // Clean up any created database records
            foreach ($createdImages ?? [] as $image) {
                $image->delete();
            }

            throw new Exception('Failed to upload images: ' . $e->getMessage());
        }
    }

    /**
     * Get images for an entity
     */
    public function getImages(
        string $imageableType,
        int $imageableId,
        ?ImageTypeEnum $imageType = null,
        bool $activeOnly = true
    ): Collection {
        $query = $this->image->query()
            ->where(Image::IMAGEABLE_TYPE, $imageableType)
            ->where(Image::IMAGEABLE_ID, $imageableId)
            ->orderedBySortOrder();

        if ($imageType) {
            $query->byType($imageType->value);
        }

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get primary image for an entity
     */
    public function getPrimaryImage(
        string $imageableType,
        int $imageableId,
        ?ImageTypeEnum $imageType = null
    ): ?Image {
        $query = $this->image->query()
            ->where(Image::IMAGEABLE_TYPE, $imageableType)
            ->where(Image::IMAGEABLE_ID, $imageableId)
            ->primary()
            ->active();

        if ($imageType) {
            $query->byType($imageType->value);
        }

        return $query->first();
    }

    /**
     * Get image by ID
     */
    public function findById(int $id): ?Image
    {
        return $this->image->find($id);
    }

    /**
     * Get image by ID or fail
     *
     * @throws ModelNotFoundException
     */
    public function findByIdOrFail(int $id): Image
    {
        return $this->image->findOrFail($id);
    }

    /**
     * Update image
     */
    public function updateImage(int $id, array $data): Image
    {
        $image = $this->findByIdOrFail($id);

        // Handle primary image setting
        if (isset($data['is_primary']) && $data['is_primary']) {
            $imageType = ImageTypeEnum::from($image->getType());
            if ($imageType->canBeSetAsPrimary()) {
                $this->unsetPrimaryImages(
                    $image->getImageableType(),
                    $image->getImageableId(),
                    $imageType,
                    $id
                );
            }
        }

        $image->update($data);
        return $image->fresh();
    }

    /**
     * Delete image
     */
    public function deleteImage(int $id): bool
    {
        $image = $this->findByIdOrFail($id);

        // Delete file from storage
        $this->s3Service->deleteFile($image->getFilePath());

        // Delete database record
        return $image->delete();
    }

    /**
     * Delete multiple images
     */
    public function deleteMultipleImages(array $ids): bool
    {
        $images = $this->image->whereIn(Image::ID, $ids)->get();

        if ($images->isEmpty()) {
            return true;
        }

        $filePaths = $images->pluck(Image::FILE_PATH)->toArray();

        // Delete files from storage
        $this->s3Service->deleteMultipleFiles($filePaths);

        // Delete database records
        return $this->image->whereIn(Image::ID, $ids)->delete();
    }

    /**
     * Delete all images for an entity
     */
    public function deleteAllImagesForEntity(
        string $imageableType,
        int $imageableId,
        ?ImageTypeEnum $imageType = null
    ): bool {
        $query = $this->image->query()
            ->where(Image::IMAGEABLE_TYPE, $imageableType)
            ->where(Image::IMAGEABLE_ID, $imageableId);

        if ($imageType) {
            $query->byType($imageType->value);
        }

        $images = $query->get();

        if ($images->isEmpty()) {
            return true;
        }

        $filePaths = $images->pluck(Image::FILE_PATH)->toArray();
        $ids = $images->pluck(Image::ID)->toArray();

        // Delete files from storage
        $this->s3Service->deleteMultipleFiles($filePaths);

        // Delete database records
        return $this->image->whereIn(Image::ID, $ids)->delete();
    }

    /**
     * Set image as primary
     */
    public function setPrimaryImage(int $id): Image
    {
        $image = $this->findByIdOrFail($id);
        $imageType = ImageTypeEnum::from($image->getType());

        if (!$imageType->canBeSetAsPrimary()) {
            throw new Exception('This image type cannot be set as primary');
        }

        // Unset other primary images
        $this->unsetPrimaryImages(
            $image->getImageableType(),
            $image->getImageableId(),
            $imageType,
            $id
        );

        // Set this image as primary
        $image->update([Image::IS_PRIMARY => true]);

        return $image->fresh();
    }

    /**
     * Reorder images
     */
    public function reorderImages(string $imageableType, int $imageableId, array $imageIds): bool
    {
        foreach ($imageIds as $index => $imageId) {
            $this->image->where(Image::ID, $imageId)
                ->where(Image::IMAGEABLE_TYPE, $imageableType)
                ->where(Image::IMAGEABLE_ID, $imageableId)
                ->update([Image::SORT_ORDER => $index + 1]);
        }

        return true;
    }

    /**
     * Get image statistics for an entity
     */
    public function getImageStats(string $imageableType, int $imageableId): array
    {
        $images = $this->getImages($imageableType, $imageableId, null, false);

        return [
            'total_images' => $images->count(),
            'active_images' => $images->where(Image::IS_ACTIVE, true)->count(),
            'inactive_images' => $images->where(Image::IS_ACTIVE, false)->count(),
            'primary_images' => $images->where(Image::IS_PRIMARY, true)->count(),
            'total_file_size' => $images->sum(Image::FILE_SIZE),
            'types' => $images->groupBy(Image::TYPE)->map->count(),
        ];
    }

    /**
     * Check if entity has images
     */
    public function hasImages(
        string $imageableType,
        int $imageableId,
        ?ImageTypeEnum $imageType = null
    ): bool {
        $query = $this->image->query()
            ->where(Image::IMAGEABLE_TYPE, $imageableType)
            ->where(Image::IMAGEABLE_ID, $imageableId)
            ->active();

        if ($imageType) {
            $query->byType($imageType->value);
        }

        return $query->exists();
    }

    /**
     * Get images count for entity
     */
    public function getImagesCount(
        string $imageableType,
        int $imageableId,
        ?ImageTypeEnum $imageType = null,
        bool $activeOnly = true
    ): int {
        $query = $this->image->query()
            ->where(Image::IMAGEABLE_TYPE, $imageableType)
            ->where(Image::IMAGEABLE_ID, $imageableId);

        if ($imageType) {
            $query->byType($imageType->value);
        }

        if ($activeOnly) {
            $query->active();
        }

        return $query->count();
    }

    /**
     * Toggle image active status
     */
    public function toggleImageStatus(int $id): Image
    {
        $image = $this->findByIdOrFail($id);
        $image->update([Image::IS_ACTIVE => !$image->getIsActive()]);
        return $image->fresh();
    }

    /**
     * Unset primary images for an entity (except the specified one)
     */
    private function unsetPrimaryImages(
        string $imageableType,
        int $imageableId,
        ImageTypeEnum $imageType,
        ?int $exceptId = null
    ): void {
        $query = $this->image->query()
            ->where(Image::IMAGEABLE_TYPE, $imageableType)
            ->where(Image::IMAGEABLE_ID, $imageableId)
            ->where(Image::TYPE, $imageType->value)
            ->where(Image::IS_PRIMARY, true);

        if ($exceptId) {
            $query->where(Image::ID, '!=', $exceptId);
        }

        $query->update([Image::IS_PRIMARY => false]);
    }
}