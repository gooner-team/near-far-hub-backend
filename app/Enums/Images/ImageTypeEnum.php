<?php

declare(strict_types=1);

namespace App\Enums\Images;

enum ImageTypeEnum: string
{
    case LISTING_PRIMARY = 'listing_primary';
    case LISTING_GALLERY = 'listing_gallery';
    case SELLER_PROFILE_AVATAR = 'seller_profile_avatar';
    case SELLER_PROFILE_COVER = 'seller_profile_cover';
    case SELLER_PROFILE_VERIFICATION = 'seller_profile_verification';
    case USER_AVATAR = 'user_avatar';
    case USER_COVER = 'user_cover';
    case APPOINTMENT_ATTACHMENT = 'appointment_attachment';

    public static function getLabels(): array
    {
        return [
            self::LISTING_PRIMARY->value => 'Listing Primary Image',
            self::LISTING_GALLERY->value => 'Listing Gallery Image',
            self::SELLER_PROFILE_AVATAR->value => 'Seller Profile Avatar',
            self::SELLER_PROFILE_COVER->value => 'Seller Profile Cover',
            self::SELLER_PROFILE_VERIFICATION->value => 'Seller Verification Document',
            self::USER_AVATAR->value => 'User Avatar',
            self::USER_COVER->value => 'User Cover Image',
            self::APPOINTMENT_ATTACHMENT->value => 'Appointment Attachment',
        ];
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): string
    {
        return self::getLabels()[$this->value];
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::LISTING_PRIMARY => 'image',
            self::LISTING_GALLERY => 'images',
            self::SELLER_PROFILE_AVATAR => 'user-circle',
            self::SELLER_PROFILE_COVER => 'layout-template',
            self::SELLER_PROFILE_VERIFICATION => 'file-check',
            self::USER_AVATAR => 'user',
            self::USER_COVER => 'layout-template',
            self::APPOINTMENT_ATTACHMENT => 'paperclip',
        };
    }

    public function getStoragePath(): string
    {
        return match ($this) {
            self::LISTING_PRIMARY => 'listings/primary',
            self::LISTING_GALLERY => 'listings/gallery',
            self::SELLER_PROFILE_AVATAR => 'sellers/avatars',
            self::SELLER_PROFILE_COVER => 'sellers/covers',
            self::SELLER_PROFILE_VERIFICATION => 'sellers/verification',
            self::USER_AVATAR => 'users/avatars',
            self::USER_COVER => 'users/covers',
            self::APPOINTMENT_ATTACHMENT => 'appointments/attachments',
        };
    }

    public function getMaxFileSize(): int
    {
        return match ($this) {
            self::LISTING_PRIMARY,
            self::LISTING_GALLERY => 5 * 1024 * 1024, // 5MB
            self::SELLER_PROFILE_AVATAR,
            self::USER_AVATAR => 2 * 1024 * 1024, // 2MB
            self::SELLER_PROFILE_COVER,
            self::USER_COVER => 5 * 1024 * 1024, // 5MB
            self::SELLER_PROFILE_VERIFICATION => 10 * 1024 * 1024, // 10MB
            self::APPOINTMENT_ATTACHMENT => 5 * 1024 * 1024, // 5MB
        };
    }

    public function getAllowedMimeTypes(): array
    {
        return match ($this) {
            self::SELLER_PROFILE_VERIFICATION => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp',
                'application/pdf',
            ],
            default => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp',
            ],
        };
    }

    public function getAllowedExtensions(): array
    {
        return match ($this) {
            self::SELLER_PROFILE_VERIFICATION => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'],
            default => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        };
    }

    public function requiresImageDimensions(): bool
    {
        return match ($this) {
            self::SELLER_PROFILE_VERIFICATION,
            self::APPOINTMENT_ATTACHMENT => false,
            default => true,
        };
    }

    public function getRecommendedDimensions(): ?array
    {
        return match ($this) {
            self::LISTING_PRIMARY => ['width' => 800, 'height' => 600],
            self::LISTING_GALLERY => ['width' => 800, 'height' => 600],
            self::SELLER_PROFILE_AVATAR => ['width' => 200, 'height' => 200],
            self::USER_AVATAR => ['width' => 200, 'height' => 200],
            self::SELLER_PROFILE_COVER => ['width' => 1200, 'height' => 400],
            self::USER_COVER => ['width' => 1200, 'height' => 400],
            default => null,
        };
    }

    public function getMaxImagesPerEntity(): int
    {
        return match ($this) {
            self::LISTING_PRIMARY => 1,
            self::LISTING_GALLERY => 10,
            self::SELLER_PROFILE_AVATAR => 1,
            self::SELLER_PROFILE_COVER => 1,
            self::SELLER_PROFILE_VERIFICATION => 5,
            self::USER_AVATAR => 1,
            self::USER_COVER => 1,
            self::APPOINTMENT_ATTACHMENT => 3,
        };
    }

    public function isPubliclyVisible(): bool
    {
        return match ($this) {
            self::SELLER_PROFILE_VERIFICATION => false,
            self::APPOINTMENT_ATTACHMENT => false,
            default => true,
        };
    }

    public function requiresModeration(): bool
    {
        return match ($this) {
            self::LISTING_PRIMARY,
            self::LISTING_GALLERY,
            self::SELLER_PROFILE_AVATAR,
            self::SELLER_PROFILE_COVER => true,
            default => false,
        };
    }

    public function canBeSetAsPrimary(): bool
    {
        return match ($this) {
            self::LISTING_PRIMARY,
            self::LISTING_GALLERY => true,
            default => false,
        };
    }

    public function getValidationRules(): array
    {
        $rules = [
            'required',
            'file',
            'max:' . ($this->getMaxFileSize() / 1024), // Convert to KB
            'mimetypes:' . implode(',', $this->getAllowedMimeTypes()),
        ];

        if ($this->requiresImageDimensions()) {
            $dimensions = $this->getRecommendedDimensions();
            if ($dimensions) {
                $rules[] = "dimensions:max_width={$dimensions['width']},max_height={$dimensions['height']}";
            }
        }

        return $rules;
    }

    public static function getListingTypes(): array
    {
        return [
            self::LISTING_PRIMARY,
            self::LISTING_GALLERY,
        ];
    }

    public static function getSellerProfileTypes(): array
    {
        return [
            self::SELLER_PROFILE_AVATAR,
            self::SELLER_PROFILE_COVER,
            self::SELLER_PROFILE_VERIFICATION,
        ];
    }

    public static function getUserTypes(): array
    {
        return [
            self::USER_AVATAR,
            self::USER_COVER,
        ];
    }

    public static function getAppointmentTypes(): array
    {
        return [
            self::APPOINTMENT_ATTACHMENT,
        ];
    }
}