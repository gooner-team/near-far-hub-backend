<?php

declare(strict_types=1);

namespace App\Models\SellerAppointments;

use App\Enums\Images\ImageTypeEnum;
use App\Models\Images\Image;
use App\Models\SellerProfiles\SellerProfile;
use App\Models\User;
use App\Services\Traits\Models\HasImages;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class SellerAppointment extends Model
{
    use HasFactory, HasImages;

    public const ID = 'id';
    public const SELLER_PROFILE_ID = 'seller_profile_id';
    public const BUYER_ID = 'buyer_id';
    public const LISTING_ID = 'listing_id';
    public const APPOINTMENT_DATETIME = 'appointment_datetime';
    public const DURATION_MINUTES = 'duration_minutes';
    public const STATUS = 'status';
    public const BUYER_MESSAGE = 'buyer_message';
    public const SELLER_RESPONSE = 'seller_response';
    public const MEETING_LOCATION = 'meeting_location';
    public const MEETING_NOTES = 'meeting_notes';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const TABLE = 'seller_appointments';

    protected $fillable = [
        self::SELLER_PROFILE_ID,
        self::BUYER_ID,
        self::LISTING_ID,
        self::APPOINTMENT_DATETIME,
        self::DURATION_MINUTES,
        self::STATUS,
        self::BUYER_MESSAGE,
        self::SELLER_RESPONSE,
        self::MEETING_LOCATION,
        self::MEETING_NOTES,
    ];

    protected $casts = [
        self::APPOINTMENT_DATETIME => 'datetime',
        self::DURATION_MINUTES => 'integer',
    ];

    /** @see SellerAppointment::sellerProfileRelation() */
    const SELLER_PROFILE_RELATION = 'sellerProfileRelation';
    /** @see SellerAppointment::buyerRelation() */
    const BUYER_RELATION = 'buyerRelation';
    /** @see SellerAppointment::attachmentImagesRelation() */
    const ATTACHMENT_IMAGES_RELATION = 'attachmentImagesRelation';

    public function sellerProfileRelation(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class);
    }

    public function buyerRelation(): BelongsTo
    {
        return $this->belongsTo(User::class, self::BUYER_ID);
    }

    // Image relationships
    public function attachmentImagesRelation(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')
            ->where('type', ImageTypeEnum::APPOINTMENT_ATTACHMENT->value)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    public function getEndTimeAttribute(): \Carbon\Carbon
    {
        return $this->getAppointmentDatetime()->addMinutes($this->getDurationMinutes());
    }

    public function relatedSellerProfile(): SellerProfile
    {
        return $this->{self::SELLER_PROFILE_RELATION};
    }

    public function relatedBuyer(): User
    {
        return $this->{self::BUYER_RELATION};
    }

    /**
     * @return Collection<Image>
     */
    public function getAttachmentImages(): Collection
    {
        return $this->attachmentImagesRelation;
    }

    public function getAttachmentImageUrls(string $size = 'medium'): array
    {
        return $this->allImageUrls(ImageTypeEnum::APPOINTMENT_ATTACHMENT, $size);
    }

    public function hasAttachments(): bool
    {
        return $this->hasImages(ImageTypeEnum::APPOINTMENT_ATTACHMENT);
    }

    public function getAttachmentsCount(): int
    {
        return $this->imagesCount(ImageTypeEnum::APPOINTMENT_ATTACHMENT);
    }

    // Override HasImages trait methods
    public function getAvailableImageTypes(): array
    {
        return [
            ImageTypeEnum::APPOINTMENT_ATTACHMENT->value,
        ];
    }

    public function getPrimaryImageType(): ?ImageTypeEnum
    {
        return ImageTypeEnum::APPOINTMENT_ATTACHMENT;
    }

    public function getAppointmentDatetime(): Carbon
    {
        return $this->getAttribute(self::APPOINTMENT_DATETIME);
    }

    public function getDurationMinutes(): int
    {
        return $this->getAttribute(self::DURATION_MINUTES);
    }

    public function getStatus(): string
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getId(): int
    {
        return $this->getAttribute(self::ID);
    }

    public function getSellerProfileId(): int
    {
        return $this->getAttribute(self::SELLER_PROFILE_ID);
    }

    public function getBuyerId(): int
    {
        return $this->getAttribute(self::BUYER_ID);
    }

    public function getListingId(): ?int
    {
        return $this->getAttribute(self::LISTING_ID);
    }

    public function getBuyerMessage(): ?string
    {
        return $this->getAttribute(self::BUYER_MESSAGE);
    }

    public function getSellerResponse(): ?string
    {
        return $this->getAttribute(self::SELLER_RESPONSE);
    }

    public function getMeetingLocation(): ?string
    {
        return $this->getAttribute(self::MEETING_LOCATION);
    }

    public function getMeetingNotes(): ?string
    {
        return $this->getAttribute(self::MEETING_NOTES);
    }

    public function getCreatedAt(): Carbon
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->getAttribute(self::UPDATED_AT);
    }

    // Status check methods
    public function isPending(): bool
    {
        return $this->getStatus() === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->getStatus() === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->getStatus() === 'rejected';
    }

    public function isCompleted(): bool
    {
        return $this->getStatus() === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->getStatus() === 'cancelled';
    }

    // Time-related methods
    public function isUpcoming(): bool
    {
        return $this->getAppointmentDatetime()->isFuture();
    }

    public function isPast(): bool
    {
        return $this->getAppointmentDatetime()->isPast();
    }

    public function isToday(): bool
    {
        return $this->getAppointmentDatetime()->isToday();
    }

    public function isTomorrow(): bool
    {
        return $this->getAppointmentDatetime()->isTomorrow();
    }

    public function isThisWeek(): bool
    {
        return $this->getAppointmentDatetime()->isCurrentWeek();
    }

    public function getEndTime(): Carbon
    {
        return $this->getAppointmentDatetime()->addMinutes($this->getDurationMinutes());
    }

    public function getTimeUntilAppointment(): string
    {
        if ($this->isPast()) {
            return 'Past';
        }

        return $this->getAppointmentDatetime()->diffForHumans();
    }

    public function getFormattedDateTime(): string
    {
        return $this->getAppointmentDatetime()->format('M j, Y \a\t g:i A');
    }

    public function getFormattedDuration(): string
    {
        $minutes = $this->getDurationMinutes();

        if ($minutes < 60) {
            return "{$minutes} minutes";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return $hours === 1 ? "1 hour" : "{$hours} hours";
        }

        return $hours === 1
            ? "1 hour {$remainingMinutes} minutes"
            : "{$hours} hours {$remainingMinutes} minutes";
    }

    // Check if appointment can be modified
    public function canBeModified(): bool
    {
        return in_array($this->getStatus(), ['pending', 'approved']) && $this->isUpcoming();
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->getStatus(), ['pending', 'approved']) && $this->isUpcoming();
    }

    public function canBeCompleted(): bool
    {
        return $this->getStatus() === 'approved' && $this->isPast();
    }

    // Get appointment summary
    public function getSummary(): array
    {
        return [
            'id' => $this->getId(),
            'status' => $this->getStatus(),
            'datetime' => $this->getFormattedDateTime(),
            'duration' => $this->getFormattedDuration(),
            'time_until' => $this->getTimeUntilAppointment(),
            'location' => $this->getMeetingLocation(),
            'has_attachments' => $this->hasAttachments(),
            'attachments_count' => $this->getAttachmentsCount(),
            'can_be_modified' => $this->canBeModified(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_completed' => $this->canBeCompleted(),
        ];
    }
}