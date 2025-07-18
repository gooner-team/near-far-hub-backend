<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Images\ImageTypeEnum;
use App\Models\Images\Image;
use App\Models\SellerAppointments\SellerAppointment;
use App\Models\SellerProfiles\SellerProfile;
use App\Services\Traits\Models\HasImages;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use App\Enums\Roles\RoleEnum;
use App\Models\Roles\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin Builder
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasImages;

    public const ID = 'id';
    public const NAME = 'name';
    public const EMAIL = 'email';
    public const PASSWORD = 'password';
    public const EMAIL_VERIFIED_AT = 'email_verified_at';
    public const REMEMBER_TOKEN = 'remember_token';
    public const ROLE_ID = 'role_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const TABLE = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        self::NAME,
        self::EMAIL,
        self::PASSWORD,
        self::ROLE_ID,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        self::PASSWORD,
        self::REMEMBER_TOKEN,
    ];

    /** @see User::sellerProfileRelation() */
    const SELLER_PROFILE_RELATION = 'sellerProfileRelation';
    /** @see User::buyerAppointmentsRelation() */
    const BUYER_APPOINTMENTS_RELATION = 'buyerAppointmentsRelation';
    /** @see User::avatarImageRelation() */
    const AVATAR_IMAGE_RELATION = 'avatarImageRelation';
    /** @see User::coverImageRelation() */
    const COVER_IMAGE_RELATION = 'coverImageRelation';
    /** @see User::roleRelation() */
    const ROLE_RELATION = 'roleRelation';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::EMAIL_VERIFIED_AT => 'datetime',
            self::PASSWORD => 'hashed',
        ];
    }

    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class,User::ROLE_ID, Role::ID);
    }

    public function getId(): int
    {
        return $this->getAttribute(self::ID);
    }

    public function getName(): string
    {
        return $this->getAttribute(self::NAME);
    }

    public function getEmail(): string
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getEmailVerifiedAt(): ?Carbon
    {
        return $this->getAttribute(self::EMAIL_VERIFIED_AT);
    }

    public function getCreatedAt(): Carbon
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->getAttribute(self::UPDATED_AT);
    }

    public function getRoleId(): int
    {
        return $this->getAttribute(self::ROLE_ID);
    }

    public function getRoleName(): string
    {
        return $this->relatedRole()->getName();
    }

    public function getRoleDisplayName(): string
    {
        return $this->relatedRole()->getDisplayName();
    }

    // Role checking methods
    public function hasRole(string $roleName): bool
    {
        return $this->getRoleName() === $roleName;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->getRoleName(), $roles);
    }

    public function isBuyer(): bool
    {
        return $this->hasRole(RoleEnum::BUYER->value);
    }

    public function isSeller(): bool
    {
        return $this->hasRole(RoleEnum::SELLER->value);
    }

    public function isModerator(): bool
    {
        return $this->hasRole(RoleEnum::MODERATOR->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleEnum::ADMIN->value);
    }

    // Permission checking methods
    public function canSell(): bool
    {
        return $this->relatedRole()->canSell();
    }

    public function canModerate(): bool
    {
        return $this->relatedRole()->canModerate();
    }

    public function canAccessAdmin(): bool
    {
        return $this->relatedRole()->canAccessAdmin();
    }

    public function canUpgradeToSeller(): bool
    {
        return $this->isBuyer();
    }

    public function upgradeToSeller(): void
    {
        if ($this->canUpgradeToSeller()) {
            $sellerRole = Role::where(Role::NAME, RoleEnum::SELLER->value)->first();
            if ($sellerRole) {
                $this->update([self::ROLE_ID => $sellerRole->getId()]);
            }
        }
    }

    public function relatedRole(): Role
    {
        return $this->{self::ROLE_RELATION};
    }

    public function getPassword(): string
    {
        return $this->getAttribute(self::PASSWORD);
    }

    public function getTable(): string
    {
        return 'users';
    }

    public function sellerProfileRelation(): HasOne
    {
        return $this->hasOne(SellerProfile::class);
    }

    /**
     * Alias for sellerProfileRelation() to work with Laravel factories
     */
    public function sellerProfile(): HasOne
    {
        return $this->sellerProfileRelation();
    }

    public function buyerAppointmentsRelation(): HasMany
    {
        return $this->hasMany(SellerAppointment::class, 'buyer_id');
    }

    // Image relationships
    public function avatarImageRelation(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')
            ->where('type', ImageTypeEnum::USER_AVATAR->value)
            ->where('is_active', true)
            ->latest();
    }

    public function coverImageRelation(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')
            ->where('type', ImageTypeEnum::USER_COVER->value)
            ->where('is_active', true)
            ->latest();
    }

    public function isVerifiedSeller(): bool
    {
        return $this->relatedSellerProfile() && $this->relatedSellerProfile()->getIsVerified();
    }

    public function hasActiveSellerAccount(): bool
    {
        return $this->relatedSellerProfile() && $this->relatedSellerProfile()->getIsActive();
    }

    public function relatedSellerProfile(): ?SellerProfile
    {
        return $this->{self::SELLER_PROFILE_RELATION};
    }

    /**
     * @return Collection<SellerAppointment>
     */
    public function relatedAppointments(): Collection
    {
        return $this->{self::BUYER_APPOINTMENTS_RELATION};
    }

    // Image-related methods
    public function getAvatarImage(): ?Image
    {
        return $this->avatarImageRelation->first();
    }

    public function getCoverImage(): ?Image
    {
        return $this->coverImageRelation->first();
    }

    public function getAvatarUrl(string $size = 'medium'): ?string
    {
        return $this->primaryImageUrl(ImageTypeEnum::USER_AVATAR, $size);
    }

    public function getCoverUrl(string $size = 'medium'): ?string
    {
        return $this->primaryImageUrl(ImageTypeEnum::USER_COVER, $size);
    }

    public function hasAvatar(): bool
    {
        return $this->hasImages(ImageTypeEnum::USER_AVATAR);
    }

    public function hasCover(): bool
    {
        return $this->hasImages(ImageTypeEnum::USER_COVER);
    }

    // Generate avatar initials if no avatar image
    public function getAvatarInitials(): string
    {
        $name = $this->getName();
        $words = explode(' ', $name);

        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }

        return strtoupper(substr($name, 0, 2));
    }

    // Get avatar URL or fallback to initials-based avatar
    public function getAvatarUrlOrFallback(string $size = 'medium'): string
    {
        $avatarUrl = $this->getAvatarUrl($size);

        if ($avatarUrl) {
            return $avatarUrl;
        }

        // Generate fallback avatar URL (could be a service like UI Avatars)
        $initials = $this->getAvatarInitials();
        $backgroundColor = $this->generateAvatarColor();

        return "https://ui-avatars.com/api/?name={$initials}&size=200&background={$backgroundColor}&color=ffffff&format=png";
    }

    // Generate consistent color for user avatar
    private function generateAvatarColor(): string
    {
        $colors = [
            '3B82F6', '10B981', 'F59E0B', 'EF4444', '8B5CF6',
            '06B6D4', 'F97316', 'EC4899', '84CC16', '6366F1'
        ];

        $index = crc32($this->getEmail()) % count($colors);
        return $colors[$index];
    }

    // Override HasImages trait methods
    public function getAvailableImageTypes(): array
    {
        return [
            ImageTypeEnum::USER_AVATAR->value,
            ImageTypeEnum::USER_COVER->value,
        ];
    }

    public function getPrimaryImageType(): ?ImageTypeEnum
    {
        return ImageTypeEnum::USER_AVATAR;
    }

    // User profile completeness
    public function getProfileCompleteness(): array
    {
        $checks = [
            'name' => !empty($this->getName()),
            'email_verified' => $this->getEmailVerifiedAt() !== null,
            'avatar' => $this->hasAvatar(),
        ];

        // Add seller-specific checks if user is a seller
        if ($this->isSeller()) {
            $sellerProfile = $this->relatedSellerProfile();
            $sellerChecks = $sellerProfile->getProfileCompleteness();
            $checks = array_merge($checks, $sellerChecks['checks']);
        }

        $completed = array_sum($checks);
        $total = count($checks);
        $percentage = round(($completed / $total) * 100, 1);

        return [
            'checks' => $checks,
            'completed' => $completed,
            'total' => $total,
            'percentage' => $percentage,
            'is_complete' => $percentage >= 75, // Consider profile complete at 75%
        ];
    }

    // Get user's full display name
    public function getFullDisplayName(): string
    {
        if ($this->isSeller() && $this->relatedSellerProfile()) {
            $businessName = $this->relatedSellerProfile()->getBusinessName();
            if (!empty($businessName)) {
                return $businessName;
            }
        }

        return $this->getName();
    }

    // Check if user account is complete
    public function isAccountComplete(): bool
    {
        $completeness = $this->getProfileCompleteness();
        return $completeness['is_complete'];
    }

    // Get user's reputation score (placeholder for future implementation)
    public function getReputationScore(): int
    {
        // This could be calculated based on:
        // - Completed transactions
        // - Reviews received
        // - Account age
        // - Verification status
        // - Profile completeness

        $score = 0;

        // Base score for account existence
        $score += 10;

        // Email verification
        if ($this->getEmailVerifiedAt()) {
            $score += 20;
        }

        // Profile completeness
        $completeness = $this->getProfileCompleteness();
        $score += (int) ($completeness['percentage'] * 0.3); // Max 30 points

        // Seller verification
        if ($this->isVerifiedSeller()) {
            $score += 40;
        }

        return min($score, 100); // Cap at 100
    }

    public function isTrustworthy(): bool
    {
        return $this->getReputationScore() >= 60;
    }

    public static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}