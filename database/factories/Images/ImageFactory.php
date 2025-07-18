<?php

declare(strict_types=1);

namespace Database\Factories\Images;

use App\Enums\Images\ImageTypeEnum;
use App\Models\Images\Image;
use App\Models\Listings\Listing;
use App\Models\SellerProfiles\SellerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageFactory extends Factory
{
    protected $model = Image::class;

    public function definition(): array
    {
        $imageTypes = ImageTypeEnum::getValues();
        $imageType = $this->faker->randomElement($imageTypes);
        $typeEnum = ImageTypeEnum::from($imageType);

        return [
            'imageable_type' => $this->getRandomImageableType($typeEnum),
            'imageable_id' => 1, // Will be overridden when used
            'type' => $imageType,
            'original_name' => $this->faker->word() . '.jpg',
            'file_name' => $this->generateFileName(),
            'file_path' => $this->generateFilePath($typeEnum),
            'file_size' => $this->faker->numberBetween(50000, 5000000), // 50KB to 5MB
            'mime_type' => $this->faker->randomElement(['image/jpeg', 'image/png', 'image/gif', 'image/webp']),
            'width' => $this->faker->numberBetween(400, 2000),
            'height' => $this->faker->numberBetween(300, 1500),
            'alt_text' => $this->faker->optional(0.7)->sentence(3),
            'sort_order' => $this->faker->numberBetween(1, 10),
            'is_primary' => false, // Will be set explicitly when needed
            'is_active' => true,
            'uploaded_by' => null, // Will be set when used
        ];
    }

    /**
     * Create listing images
     */
    public function forListing(Listing $listing, bool $isPrimary = false): static
    {
        $imageType = $isPrimary
            ? ImageTypeEnum::LISTING_PRIMARY
            : ImageTypeEnum::LISTING_GALLERY;

        return $this->state(fn (array $attributes) => [
            'imageable_type' => Listing::class,
            'imageable_id' => $listing->getId(),
            'type' => $imageType->value,
            'is_primary' => $isPrimary,
            'file_path' => $this->generateFilePath($imageType),
            'uploaded_by' => $listing->getSellerProfileId(),
        ]);
    }

    /**
     * Create seller profile avatar
     */
    public function forSellerAvatar(SellerProfile $sellerProfile): static
    {
        return $this->state(fn (array $attributes) => [
            'imageable_type' => SellerProfile::class,
            'imageable_id' => $sellerProfile->getId(),
            'type' => ImageTypeEnum::SELLER_PROFILE_AVATAR->value,
            'is_primary' => true,
            'file_path' => $this->generateFilePath(ImageTypeEnum::SELLER_PROFILE_AVATAR),
            'uploaded_by' => $sellerProfile->getUserId(),
        ]);
    }

    /**
     * Create seller profile cover
     */
    public function forSellerCover(SellerProfile $sellerProfile): static
    {
        return $this->state(fn (array $attributes) => [
            'imageable_type' => SellerProfile::class,
            'imageable_id' => $sellerProfile->getId(),
            'type' => ImageTypeEnum::SELLER_PROFILE_COVER->value,
            'is_primary' => false,
            'file_path' => $this->generateFilePath(ImageTypeEnum::SELLER_PROFILE_COVER),
            'uploaded_by' => $sellerProfile->getUserId(),
            'width' => 1200,
            'height' => 400,
        ]);
    }

    /**
     * Create seller verification documents
     */
    public function forSellerVerification(SellerProfile $sellerProfile): static
    {
        return $this->state(fn (array $attributes) => [
            'imageable_type' => SellerProfile::class,
            'imageable_id' => $sellerProfile->getId(),
            'type' => ImageTypeEnum::SELLER_PROFILE_VERIFICATION->value,
            'is_primary' => false,
            'file_path' => $this->generateFilePath(ImageTypeEnum::SELLER_PROFILE_VERIFICATION),
            'uploaded_by' => $sellerProfile->getUserId(),
            'mime_type' => $this->faker->randomElement(['image/jpeg', 'image/png', 'application/pdf']),
        ]);
    }

    /**
     * Create user avatar
     */
    public function forUserAvatar(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'imageable_type' => User::class,
            'imageable_id' => $user->getId(),
            'type' => ImageTypeEnum::USER_AVATAR->value,
            'is_primary' => true,
            'file_path' => $this->generateFilePath(ImageTypeEnum::USER_AVATAR),
            'uploaded_by' => $user->getId(),
            'width' => 200,
            'height' => 200,
        ]);
    }

    /**
     * Create user cover
     */
    public function forUserCover(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'imageable_type' => User::class,
            'imageable_id' => $user->getId(),
            'type' => ImageTypeEnum::USER_COVER->value,
            'is_primary' => false,
            'file_path' => $this->generateFilePath(ImageTypeEnum::USER_COVER),
            'uploaded_by' => $user->getId(),
            'width' => 1200,
            'height' => 400,
        ]);
    }

    /**
     * Create appointment attachment
     */
    public function forAppointmentAttachment($appointment): static
    {
        return $this->state(fn (array $attributes) => [
            'imageable_type' => get_class($appointment),
            'imageable_id' => $appointment->getId(),
            'type' => ImageTypeEnum::APPOINTMENT_ATTACHMENT->value,
            'is_primary' => false,
            'file_path' => $this->generateFilePath(ImageTypeEnum::APPOINTMENT_ATTACHMENT),
            'uploaded_by' => $appointment->getBuyerId(),
        ]);
    }

    /**
     * Create primary image
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * Create inactive image
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create large image
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'width' => $this->faker->numberBetween(1500, 3000),
            'height' => $this->faker->numberBetween(1000, 2000),
            'file_size' => $this->faker->numberBetween(2000000, 10000000), // 2MB to 10MB
        ]);
    }

    /**
     * Create small image
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'width' => $this->faker->numberBetween(200, 600),
            'height' => $this->faker->numberBetween(150, 400),
            'file_size' => $this->faker->numberBetween(10000, 500000), // 10KB to 500KB
        ]);
    }

    /**
     * Create PNG image
     */
    public function png(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'image/png',
            'original_name' => $this->faker->word() . '.png',
            'file_name' => str_replace('.jpg', '.png', $attributes['file_name'] ?? $this->generateFileName()),
        ]);
    }

    /**
     * Create GIF image
     */
    public function gif(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'image/gif',
            'original_name' => $this->faker->word() . '.gif',
            'file_name' => str_replace('.jpg', '.gif', $attributes['file_name'] ?? $this->generateFileName()),
        ]);
    }

    /**
     * Create WebP image
     */
    public function webp(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'image/webp',
            'original_name' => $this->faker->word() . '.webp',
            'file_name' => str_replace('.jpg', '.webp', $attributes['file_name'] ?? $this->generateFileName()),
        ]);
    }

    /**
     * Generate a unique file name
     */
    private function generateFileName(): string
    {
        return 'img_' . now()->format('YmdHis') . '_' . $this->faker->uuid() . '.jpg';
    }

    /**
     * Generate file path based on image type
     */
    private function generateFilePath(ImageTypeEnum $imageType): string
    {
        $fileName = $this->generateFileName();
        return $imageType->getStoragePath() . '/' . $fileName;
    }

    /**
     * Get random imageable type based on image type
     */
    private function getRandomImageableType(ImageTypeEnum $imageType): string
    {
        return match ($imageType) {
            ImageTypeEnum::LISTING_PRIMARY,
            ImageTypeEnum::LISTING_GALLERY => Listing::class,

            ImageTypeEnum::SELLER_PROFILE_AVATAR,
            ImageTypeEnum::SELLER_PROFILE_COVER,
            ImageTypeEnum::SELLER_PROFILE_VERIFICATION => SellerProfile::class,

            ImageTypeEnum::USER_AVATAR,
            ImageTypeEnum::USER_COVER => User::class,

            ImageTypeEnum::APPOINTMENT_ATTACHMENT => 'App\Models\SellerAppointments\SellerAppointment',
        };
    }

    /**
     * Create placeholder images for testing
     */
    public function placeholder(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_path' => 'https://via.placeholder.com/800x600/FF6B6B/FFFFFF?text=Placeholder',
            'original_name' => 'placeholder.jpg',
            'file_name' => 'placeholder_' . $this->faker->uuid() . '.jpg',
            'mime_type' => 'image/jpeg',
            'width' => 800,
            'height' => 600,
            'file_size' => 150000, // ~150KB
        ]);
    }

    /**
     * Create a set of listing images (1 primary + multiple gallery)
     */
    public function listingSet(Listing $listing, int $galleryCount = 3): array
    {
        $images = [];

        // Create primary image
        $images[] = $this->forListing($listing, true)->create();

        // Create gallery images
        for ($i = 0; $i < $galleryCount; $i++) {
            $images[] = $this->forListing($listing, false)->state([
                'sort_order' => $i + 2, // Start from 2 since primary is 1
            ])->create();
        }

        return $images;
    }

    /**
     * Create a complete seller profile image set
     */
    public function sellerProfileSet(SellerProfile $sellerProfile): array
    {
        return [
            'avatar' => $this->forSellerAvatar($sellerProfile)->create(),
            'cover' => $this->forSellerCover($sellerProfile)->create(),
            'verification' => $this->forSellerVerification($sellerProfile)->count(2)->create(),
        ];
    }
}