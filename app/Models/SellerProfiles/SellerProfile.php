<?php

declare(strict_types=1);

namespace App\Models\SellerProfiles;

use App\Enums\Images\ImageTypeEnum;
use App\Models\Images\Image;
use App\Models\ListingFees\ListingFee;
use App\Models\SellerAppointments\SellerAppointment;
use App\Models\SellerAvailabilities\SellerAvailability;
use App\Models\User;
use App\Services\Traits\Models\HasImages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class SellerProfile extends Model
{
    use HasFactory, HasImages;

    public const ID = 'id';
    public const USER_ID = 'user_id';
    public const BUSINESS_NAME = 'business_name';
    public const BUSINESS_DESCRIPTION = 'business_description';
    public const BUSINESS_TYPE = 'business_type';
    public const PHONE = 'phone';
    public const ADDRESS = 'address';
    public const CITY = 'city';
    public const POSTAL_CODE = 'postal_code';
    public const COUNTRY = 'country';
    public const LISTING_FEE_BALANCE = 'listing_fee_balance';
    public const IS_ACTIVE = 'is_active';
    public const IS_VERIFIED = 'is_verified';
    public const VERIFICATION_DOCUMENTS = 'verification_documents';
    public const VERIFIED_AT = 'verified_at';
    public const DAY_OF_WEEK = 'day_of_week';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const TABLE = 'seller_profiles';

    protected $fillable = [
        self::USER_ID,
        self::BUSINESS_NAME,
        self::BUSINESS_DESCRIPTION,
        self::BUSINESS_TYPE,
        self::PHONE,
        self::ADDRESS,
        self::CITY,
        self::POSTAL_CODE,
        self::COUNTRY,
        self::LISTING_FEE_BALANCE,
        self::IS_ACTIVE,
        self::IS_VERIFIED,
        self::VERIFICATION_DOCUMENTS,
        self::VERIFIED_AT,
    ];

    protected $casts = [
        self::VERIFICATION_DOCUMENTS => 'array',
        self::VERIFIED_AT => 'datetime',
        self::IS_ACTIVE => 'boolean',
        self::IS_VERIFIED => 'boolean',
        self::LISTING_FEE_BALANCE => 'decimal:2',
    ];

    /** @see SellerProfile::userRelation() */
    const USER_RELATION = 'userRelation';
    /** @see SellerProfile::availabilityRelation() */
    const AVAILABILITY_RELATION = 'availabilityRelation';
    /** @see SellerProfile::appointmentsRelation() */
    const APPOINTMENTS_RELATION = 'appointmentsRelation';
    /** @see SellerProfile::listingFeesRelation() */
    const LISTING_FEES_RELATION = 'listingFeesRelation';
    /** @see SellerProfile::avatarImageRelation() */
    const AVATAR_IMAGE_RELATION = 'avatarImageRelation';
    /** @see SellerProfile::coverImageRelation() */
    const COVER_IMAGE_RELATION = 'coverImageRelation';
    /** @see SellerProfile::verificationImagesRelation() */
    const VERIFICATION_IMAGES_RELATION = 'verificationImagesRelation';

    public function userRelation(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function availabilityRelation(): HasMany
    {
        return $this->hasMany(SellerAvailability::class);
    }

    public function appointmentsRelation(): HasMany
    {
        return $this->hasMany(SellerAppointment::class);
    }

    public function listingFeesRelation(): HasMany
    {
        return $this->hasMany(ListingFee::class);
    }

    // Image relationships
    public function avatarImageRelation(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')
            ->where('type', ImageTypeEnum::SELLER_PROFILE_AVATAR->value)
            ->where('is_active', true)
            ->latest();
    }

    public function coverImageRelation(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')
            ->where('type', ImageTypeEnum::SELLER_PROFILE_COVER->value)
            ->where('is_active', true)
            ->latest();
    }

    public function verificationImagesRelation(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')
            ->where('type', ImageTypeEnum::SELLER_PROFILE_VERIFICATION->value)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    public function getFullAddressAttribute(): string
    {
        return collect([$this->getAddress(), $this->getCity(), $this->getPostalCode(), $this->getCountry()])
            ->filter()
            ->implode(', ');
    }

    public function isAvailableOn(string $dayOfWeek): bool
    {
        return $this->availabilityRelation()
            ->where(self::DAY_OF_WEEK, strtolower($dayOfWeek))
            ->where(self::IS_ACTIVE, true)
            ->exists();
    }

    public function getAvailabilityFor(string $dayOfWeek): ?SellerAvailability
    {
        return $this->relatedAvailability()
            ->where(self::DAY_OF_WEEK, strtolower($dayOfWeek))
            ->where(self::IS_ACTIVE, true)
            ->first();
    }

    public function relatedUser(): ?User
    {
        return $this->{self::USER_RELATION};
    }

    /**
     * @return Collection<SellerAvailability>
     */
    public function relatedAvailability(): Collection
    {
        return $this->{self::AVAILABILITY_RELATION};
    }

    /**
     * @return Collection<SellerAppointment>
     */
    public function relatedAppointments(): Collection
    {
        return $this->{self::APPOINTMENTS_RELATION};
    }

    /**
     * @return Collection<ListingFee>
     */
    public function relatedListingFees(): Collection
    {
        return $this->{self::LISTING_FEES_RELATION};
    }

    // Image-related methods
    public function getAvatarImage(): ?Image
    {
        return $this->{self::AVATAR_IMAGE_RELATION}->first();
    }

    public function getCoverImage(): ?Image
    {
        return $this->{self::COVER_IMAGE_RELATION}->first();
    }

    /**
     * @return Collection<Image>
     */
    public function getVerificationImages(): Collection
    {
        return $this->{self::VERIFICATION_IMAGES_RELATION};
    }

    public function getAvatarUrl(string $size = 'medium'): ?string
    {
        return $this->primaryImageUrl(ImageTypeEnum::SELLER_PROFILE_AVATAR, $size);
    }

    public function getCoverUrl(string $size = 'medium'): ?string
    {
        return $this->primaryImageUrl(ImageTypeEnum::SELLER_PROFILE_COVER, $size);
    }

    public function getVerificationImageUrls(string $size = 'medium'): array
    {
        return $this->allImageUrls(ImageTypeEnum::SELLER_PROFILE_VERIFICATION, $size);
    }

    public function hasAvatar(): bool
    {
        return $this->hasImages(ImageTypeEnum::SELLER_PROFILE_AVATAR);
    }

    public function hasCover(): bool
    {
        return $this->hasImages(ImageTypeEnum::SELLER_PROFILE_COVER);
    }

    public function hasVerificationImages(): bool
    {
        return $this->hasImages(ImageTypeEnum::SELLER_PROFILE_VERIFICATION);
    }

    public function getVerificationImagesCount(): int
    {
        return $this->imagesCount(ImageTypeEnum::SELLER_PROFILE_VERIFICATION);
    }

    // Override HasImages trait methods
    public function getAvailableImageTypes(): array
    {
        return [
            ImageTypeEnum::SELLER_PROFILE_AVATAR->value,
            ImageTypeEnum::SELLER_PROFILE_COVER->value,
            ImageTypeEnum::SELLER_PROFILE_VERIFICATION->value,
        ];
    }

    public function getPrimaryImageType(): ?ImageTypeEnum
    {
        return ImageTypeEnum::SELLER_PROFILE_AVATAR;
    }

    public function getIsVerified(): bool
    {
        return $this->getAttribute(self::IS_VERIFIED);
    }

    public function getIsActive(): bool
    {
        return $this->getAttribute(self::IS_ACTIVE);
    }

    public function getAddress(): string
    {
        return $this->getAttribute(self::ADDRESS);
    }

    public function getCity(): string
    {
        return $this->getAttribute(self::CITY);
    }

    public function getPostalCode(): string
    {
        return $this->getAttribute(self::POSTAL_CODE);
    }

    public function getCountry(): string
    {
        return $this->getAttribute(self::COUNTRY);
    }

    public function getId(): int
    {
        return $this->getAttribute(self::ID);
    }

    public function getListingFeeBalance(): float
    {
        return $this->getAttribute(self::LISTING_FEE_BALANCE);
    }

    public function getBusinessName(): string
    {
        return $this->getAttribute(self::BUSINESS_NAME);
    }

    public function getBusinessDescription(): ?string
    {
        return $this->getAttribute(self::BUSINESS_DESCRIPTION);
    }

    public function getBusinessType(): string
    {
        return $this->getAttribute(self::BUSINESS_TYPE);
    }

    public function getPhone(): string
    {
        return $this->getAttribute(self::PHONE);
    }

    public function getVerifiedAt(): ?\Carbon\Carbon
    {
        return $this->getAttribute(self::VERIFIED_AT);
    }

    public function getUserId(): int
    {
        return $this->getAttribute(self::USER_ID);
    }

    // Enhanced profile completeness check
    public function getProfileCompleteness(): array
    {
        $checks = [
            'business_name' => !empty($this->getBusinessName()),
            'business_description' => !empty($this->getBusinessDescription()),
            'phone' => !empty($this->getPhone()),
            'address' => !empty($this->getAddress()),
            'avatar' => $this->hasAvatar(),
            'cover' => $this->hasCover(),
            'verification_documents' => $this->hasVerificationImages(),
        ];

        $completed = array_sum($checks);
        $total = count($checks);
        $percentage = round(($completed / $total) * 100, 1);

        return [
            'checks' => $checks,
            'completed' => $completed,
            'total' => $total,
            'percentage' => $percentage,
            'is_complete' => $percentage >= 80, // Consider profile complete at 80%
        ];
    }

    // Check if profile is ready for verification
    public function isReadyForVerification(): bool
    {
        $completeness = $this->getProfileCompleteness();
        return $completeness['is_complete'] && $this->hasVerificationImages();
    }

    // Get profile quality score
    public function getProfileQualityScore(): int
    {
        $score = 0;

        // Basic information (40 points)
        if (!empty($this->getBusinessName())) $score += 10;
        if (!empty($this->getBusinessDescription())) $score += 10;
        if (!empty($this->getPhone())) $score += 10;
        if (!empty($this->getAddress())) $score += 10;

        // Visual elements (30 points)
        if ($this->hasAvatar()) $score += 15;
        if ($this->hasCover()) $score += 15;

        // Verification (20 points)
        if ($this->hasVerificationImages()) $score += 10;
        if ($this->getIsVerified()) $score += 10;

        // Activity (10 points)
        if ($this->getIsActive()) $score += 10;

        return $score;
    }
}