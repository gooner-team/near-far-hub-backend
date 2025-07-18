<?php

declare(strict_types=1);

namespace App\Models\SellerAvailabilities;

use App\Models\SellerProfiles\SellerProfile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerAvailability extends Model
{
    use HasFactory;

    public const ID = 'id';
    public const SELLER_PROFILE_ID = 'seller_profile_id';
    public const DAY_OF_WEEK = 'day_of_week';
    public const START_TIME = 'start_time';
    public const END_TIME = 'end_time';
    public const IS_ACTIVE = 'is_active';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const TABLE = 'seller_availabilities';

    protected $fillable = [
        self::SELLER_PROFILE_ID,
        self::DAY_OF_WEEK,
        self::START_TIME,
        self::END_TIME,
        self::IS_ACTIVE,
    ];

    protected $casts = [
        self::START_TIME => 'datetime:H:i',
        self::END_TIME => 'datetime:H:i',
        self::IS_ACTIVE => 'boolean',
    ];

    /** @see SellerAvailability::sellerProfileRelation() */
    const SELLER_PROFILE_RELATION = 'sellerProfileRelation';

    public function sellerProfileRelation(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class);
    }

    public function relatedSellerProfile(): SellerProfile
    {
        return $this->{self::SELLER_PROFILE_RELATION};
    }

    public function getId(): int
    {
        return $this->getAttribute(self::ID);
    }

    public function getStartTime(): Carbon
    {
        return $this->getAttribute(self::START_TIME);
    }

    public function getEndTime(): Carbon
    {
        return $this->getAttribute(self::END_TIME);
    }
}