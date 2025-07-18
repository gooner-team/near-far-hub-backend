<?php

declare(strict_types=1);

namespace App\Http\Controllers\Images;

use App\Enums\Images\ImageTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Images\UploadImageRequest;
use App\Http\Requests\Images\UploadMultipleImagesRequest;
use App\Http\Requests\Images\UpdateImageRequest;
use App\Http\Requests\Images\ReorderImagesRequest;
use App\Http\Resources\Images\ImageResource;
use App\Http\Resources\Images\ImageResourceCollection;
use App\Services\Repositories\Images\ImageRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function __construct(
        private readonly ImageRepository $imageRepository
    ) {
    }

    /**
     * Upload a single image
     */
    public function uploadImage(UploadImageRequest $request): JsonResponse|ImageResource
    {
        try {
            $data = $request->dto();

            $image = $this->imageRepository->uploadImage(
                $data->file,
                $data->imageableType,
                $data->imageableId,
                $data->imageType,
                auth()->id(),
                $data->altText,
                $data->isPrimary
            );

            return new ImageResource($image);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to upload image',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Upload multiple images
     */
    public function uploadMultipleImages(UploadMultipleImagesRequest $request): JsonResponse|ImageResourceCollection
    {
        try {
            $data = $request->dto();

            $images = $this->imageRepository->uploadMultipleImages(
                $data->files,
                $data->imageableType,
                $data->imageableId,
                $data->imageType,
                auth()->id(),
                $data->altTexts
            );

            return new ImageResourceCollection($images);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to upload images',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get images for an entity
     */
    public function getImages(Request $request): ImageResourceCollection
    {
        $imageableType = $request->get('imageable_type');
        $imageableId = (int) $request->get('imageable_id');
        $imageType = $request->get('image_type') ? ImageTypeEnum::from($request->get('image_type')) : null;
        $activeOnly = $request->boolean('active_only', true);

        $images = $this->imageRepository->getImages($imageableType, $imageableId, $imageType, $activeOnly);

        return new ImageResourceCollection($images);
    }

    /**
     * Get a specific image
     */
    public function getImage(int $id): JsonResponse|ImageResource
    {
        $image = $this->imageRepository->findById($id);

        if (!$image) {
            return response()->json([
                'message' => 'Image not found'
            ], 404);
        }

        return new ImageResource($image);
    }

    /**
     * Update image details
     */
    public function updateImage(int $id, UpdateImageRequest $request): JsonResponse|ImageResource
    {
        try {
            $data = $request->dto();

            $image = $this->imageRepository->updateImage($id, [
                'alt_text' => $data->altText,
                'is_primary' => $data->isPrimary,
                'is_active' => $data->isActive,
                'sort_order' => $data->sortOrder,
            ]);

            return new ImageResource($image);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update image',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete an image
     */
    public function deleteImage(int $id): JsonResponse
    {
        try {
            $this->imageRepository->deleteImage($id);

            return response()->json([
                'message' => 'Image deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to delete image',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete multiple images
     */
    public function deleteMultipleImages(Request $request): JsonResponse
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'message' => 'No image IDs provided'
                ], 400);
            }

            $this->imageRepository->deleteMultipleImages($ids);

            return response()->json([
                'message' => 'Images deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to delete images',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Set image as primary
     */
    public function setPrimaryImage(int $id): JsonResponse|ImageResource
    {
        try {
            $image = $this->imageRepository->setPrimaryImage($id);

            return new ImageResource($image);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to set primary image',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reorder images
     */
    public function reorderImages(ReorderImagesRequest $request): JsonResponse
    {
        try {
            $data = $request->dto();

            $this->imageRepository->reorderImages(
                $data->imageableType,
                $data->imageableId,
                $data->imageIds
            );

            return response()->json([
                'message' => 'Images reordered successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to reorder images',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Toggle image active status
     */
    public function toggleImageStatus(int $id): JsonResponse|ImageResource
    {
        try {
            $image = $this->imageRepository->toggleImageStatus($id);

            return new ImageResource($image);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to toggle image status',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get image statistics for an entity
     */
    public function getImageStats(Request $request): JsonResponse
    {
        $imageableType = $request->get('imageable_type');
        $imageableId = (int) $request->get('imageable_id');

        if (!$imageableType || !$imageableId) {
            return response()->json([
                'message' => 'imageable_type and imageable_id are required'
            ], 400);
        }

        $stats = $this->imageRepository->getImageStats($imageableType, $imageableId);

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get primary image for an entity
     */
    public function getPrimaryImage(Request $request): JsonResponse|ImageResource
    {
        $imageableType = $request->get('imageable_type');
        $imageableId = (int) $request->get('imageable_id');
        $imageType = $request->get('image_type') ? ImageTypeEnum::from($request->get('image_type')) : null;

        if (!$imageableType || !$imageableId) {
            return response()->json([
                'message' => 'imageable_type and imageable_id are required'
            ], 400);
        }

        $image = $this->imageRepository->getPrimaryImage($imageableType, $imageableId, $imageType);

        if (!$image) {
            return response()->json([
                'message' => 'No primary image found'
            ], 404);
        }

        return new ImageResource($image);
    }

    /**
     * Delete all images for an entity
     */
    public function deleteAllImagesForEntity(Request $request): JsonResponse
    {
        try {
            $imageableType = $request->get('imageable_type');
            $imageableId = (int) $request->get('imageable_id');
            $imageType = $request->get('image_type') ? ImageTypeEnum::from($request->get('image_type')) : null;

            if (!$imageableType || !$imageableId) {
                return response()->json([
                    'message' => 'imageable_type and imageable_id are required'
                ], 400);
            }

            $this->imageRepository->deleteAllImagesForEntity($imageableType, $imageableId, $imageType);

            return response()->json([
                'message' => 'All images deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to delete images',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get available image types
     */
    public function getImageTypes(): JsonResponse
    {
        $types = [];

        foreach (ImageTypeEnum::cases() as $type) {
            $types[] = [
                'value' => $type->value,
                'label' => $type->getLabel(),
                'icon' => $type->getIcon(),
                'max_file_size' => $type->getMaxFileSize(),
                'max_images_per_entity' => $type->getMaxImagesPerEntity(),
                'allowed_mime_types' => $type->getAllowedMimeTypes(),
                'allowed_extensions' => $type->getAllowedExtensions(),
                'recommended_dimensions' => $type->getRecommendedDimensions(),
                'requires_moderation' => $type->requiresModeration(),
                'can_be_set_as_primary' => $type->canBeSetAsPrimary(),
                'is_publicly_visible' => $type->isPubliclyVisible(),
            ];
        }

        return response()->json([
            'data' => $types
        ]);
    }

    /**
     * Get upload configuration for image type
     */
    public function getUploadConfig(string $imageType): JsonResponse
    {
        try {
            $type = ImageTypeEnum::from($imageType);

            return response()->json([
                'data' => [
                    'type' => $type->value,
                    'label' => $type->getLabel(),
                    'max_file_size' => $type->getMaxFileSize(),
                    'max_file_size_mb' => round($type->getMaxFileSize() / 1024 / 1024, 2),
                    'max_images_per_entity' => $type->getMaxImagesPerEntity(),
                    'allowed_mime_types' => $type->getAllowedMimeTypes(),
                    'allowed_extensions' => $type->getAllowedExtensions(),
                    'recommended_dimensions' => $type->getRecommendedDimensions(),
                    'validation_rules' => $type->getValidationRules(),
                    'can_be_set_as_primary' => $type->canBeSetAsPrimary(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Invalid image type'
            ], 400);
        }
    }
}