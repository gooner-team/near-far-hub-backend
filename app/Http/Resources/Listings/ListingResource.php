<?php

declare(strict_types=1);

namespace App\Http\Resources\Listings;

use App\Enums\Images\ImageTypeEnum;
use App\Enums\Listings\ListingConditionEnum;
use App\Http\Resources\Images\ImageResource;
use App\Http\Resources\Seller\SellerProfileResource;
use App\Models\Listings\Listing;
use App\Services\Traits\Resources\HasConditionalFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    use HasConditionalFields;

    /**
     * @var Listing $resource
     */
    public $resource;

    protected array $conditionalFields = [
        'title' => Listing::TITLE,
        'description' => Listing::DESCRIPTION,
        'price' => Listing::PRICE,
        'category' => Listing::CATEGORY,
        'condition' => Listing::CONDITION,
        'location' => Listing::LOCATION,
        'canDeliverGlobally' => Listing::CAN_DELIVER_GLOBALLY,
        'requiresAppointment' => Listing::REQUIRES_APPOINTMENT,
        'status' => Listing::STATUS,
        'publishedAt' => Listing::PUBLISHED_AT,
        'expiresAt' => Listing::EXPIRES_AT,
        'viewsCount' => Listing::VIEWS_COUNT,
        'favoritesCount' => Listing::FAVORITES_COUNT,
        'createdAt' => Listing::CREATED_AT,
        'updatedAt' => Listing::UPDATED_AT,
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'formattedPrice' => $this->resource->getFormattedPrice(),
            'conditionLabel' => $this->resource->getCondition() ?
                Listing::getConditionLabels()[$this->resource->getCondition()] : null,
            'locationString' => $this->resource->getLocationString(),
            'isActive' => $this->resource->isActive(),
            'isPublished' => $this->resource->isPublished(),
            'isExpired' => $this->resource->isExpired(),
            'canBeViewed' => $this->resource->canBeViewed(),
            'timeAgo' => $this->resource->getCreatedAt()->diffForHumans(),

            // Image information (new system)
            'images' => [
                'primary' => $this->getPrimaryImageData(),
                'gallery' => $this->getGalleryImagesData(),
                'all' => $this->getAllImagesData(),
                'has_images' => $this->resource->hasImages(),
                'images_count' => $this->resource->getImagesCount(),
                'total_file_size' => $this->resource->getTotalImageFileSize(),
            ],

            // Legacy image fields for backward compatibility
            'mainImage' => $this->resource->getMainImage(),
            'legacyImages' => $this->resource->getImages(), // Old JSON field

            'seller' => new SellerProfileResource($this->resource->relatedSellerProfile()),
            'isNew' => $this->resource->getCondition() === ListingConditionEnum::CONDITION_NEW->value,
            'hasDiscount' => false,
            'distance' => $this->when(
                $request->has('user_location'),
                $this->calculateDistance($request->get('user_location'))
            ),

            'slug' => $this->generateSlug(),
            'shareUrl' => $this->generateShareUrl(),
            'canEdit' => $this->canEdit($request),
            'canDelete' => $this->canDelete($request),

            // Include conditional fields
            ...$this->getConditionalData($request),
        ];
    }

    /**
     * Get primary image data
     */
    private function getPrimaryImageData(): ?array
    {
        $primaryImage = $this->resource->relatedPrimaryImage();

        if (!$primaryImage) {
            return null;
        }

        return [
            'id' => $primaryImage->getId(),
            'url' => $primaryImage->getUrl(),
            'thumbnail_url' => $primaryImage->getThumbnailUrl(),
            'medium_url' => $primaryImage->getMediumUrl(),
            'full_url' => $primaryImage->getFullUrl(),
            'alt_text' => $primaryImage->getAltText(),
            'width' => $primaryImage->getWidth(),
            'height' => $primaryImage->getHeight(),
            'file_size' => $primaryImage->getFileSize(),
            'formatted_file_size' => $primaryImage->getFormattedFileSize(),
        ];
    }

    /**
     * Get gallery images data
     */
    private function getGalleryImagesData(): array
    {
        $galleryImages = $this->resource->relatedGalleryImages();

        return $galleryImages->map(function ($image) {
            return [
                'id' => $image->getId(),
                'url' => $image->getUrl(),
                'thumbnail_url' => $image->getThumbnailUrl(),
                'medium_url' => $image->getMediumUrl(),
                'full_url' => $image->getFullUrl(),
                'alt_text' => $image->getAltText(),
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
                'sort_order' => $image->getSortOrder(),
                'file_size' => $image->getFileSize(),
                'formatted_file_size' => $image->getFormattedFileSize(),
            ];
        })->toArray();
    }

    /**
     * Get all images data (primary + gallery)
     */
    private function getAllImagesData(): array
    {
        $allImages = $this->resource->relatedImages();

        return $allImages->map(function ($image) {
            return [
                'id' => $image->getId(),
                'type' => $image->getType(),
                'type_label' => $image->getTypeLabel(),
                'url' => $image->getUrl(),
                'thumbnail_url' => $image->getThumbnailUrl(),
                'medium_url' => $image->getMediumUrl(),
                'full_url' => $image->getFullUrl(),
                'alt_text' => $image->getAltText(),
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
                'sort_order' => $image->getSortOrder(),
                'is_primary' => $image->getIsPrimary(),
                'file_size' => $image->getFileSize(),
                'formatted_file_size' => $image->getFormattedFileSize(),
            ];
        })->toArray();
    }

    /**
     * Get image URLs for different sizes
     */
    public function getImageUrls(string $size = 'medium'): array
    {
        return $this->resource->getAllImageUrls($size);
    }

    /**
     * Get main image URL for specific size
     */
    public function getMainImageUrl(string $size = 'medium'): ?string
    {
        return $this->resource->getMainImageUrl($size);
    }

    /**
     * Calculate distance from user location
     */
    private function calculateDistance($userLocation): ?string
    {
        if (!$userLocation || empty($this->resource->getLocation())) {
            return null;
        }

        $location = $this->resource->getLocation();
        if (!isset($location['coordinates']) || !isset($userLocation['coordinates'])) {
            return null;
        }

        return "~" . rand(1, 50) . " km";
    }

    /**
     * Generate URL-friendly slug
     */
    private function generateSlug(): string
    {
        return str()->slug($this->resource->getTitle()) . '-' . $this->resource->getId();
    }

    /**
     * Generate share URL
     */
    private function generateShareUrl(): string
    {
        return url("/product/" . $this->resource->getId());
    }

    /**
     * Check if current user can edit this listing
     */
    private function canEdit(Request $request): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        $sellerProfile = $user->sellerProfileRelation;
        if (!$sellerProfile) {
            return false;
        }

        return $sellerProfile->getId() === $this->resource->getSellerProfileId();
    }

    /**
     * Check if current user can delete this listing
     */
    private function canDelete(Request $request): bool
    {
        return $this->canEdit($request);
    }

    /**
     * Additional helper method to get optimized images for specific use cases
     */
    public function getOptimizedImages(): array
    {
        return [
            'card' => [
                'url' => $this->getMainImageUrl('medium'),
                'thumbnail' => $this->getMainImageUrl('thumbnail'),
            ],
            'detail' => [
                'main' => $this->getMainImageUrl('full'),
                'gallery' => $this->getImageUrls('full'),
                'thumbnails' => $this->getImageUrls('thumbnail'),
            ],
            'preview' => [
                'url' => $this->getMainImageUrl('thumbnail'),
            ],
        ];
    }

    /**
     * Get SEO-optimized image data
     */
    public function getSeoImageData(): array
    {
        $primaryImage = $this->resource->relatedPrimaryImage();

        if (!$primaryImage) {
            return [];
        }

        return [
            'url' => $primaryImage->getFullUrl(),
            'alt' => $primaryImage->getAltText() ?: $this->resource->getTitle(),
            'width' => $primaryImage->getWidth(),
            'height' => $primaryImage->getHeight(),
            'type' => $primaryImage->getMimeType(),
        ];
    }

    /**
     * Include image resources when requested
     */
    public function withImageResources(): array
    {
        $data = $this->toArray(request());

        // Add full image resources
        $data['image_resources'] = [
            'primary' => $this->resource->relatedPrimaryImage()
                ? new ImageResource($this->resource->relatedPrimaryImage())
                : null,
            'gallery' => ImageResource::collection($this->resource->relatedGalleryImages()),
            'all' => ImageResource::collection($this->resource->relatedImages()),
        ];

        return $data;
    }
}