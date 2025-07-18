<?php

declare(strict_types=1);

namespace App\Models\ListingFees;

use App\Models\SellerProfiles\SellerProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingFee extends Model
{
    use HasFactory;

    public const ID = 'id';
    public const SELLER_PROFILE_ID = 'seller_profile_id';
    public const LISTING_ID = 'listing_id';
    public const AMOUNT = 'amount';
    public const FEE_TYPE = 'fee_type';
    public const STATUS = 'status';
    public const PAYMENT_METHOD = 'payment_method';
    public const TRANSACTION_ID = 'transaction_id';
    public const PAID_AT = 'paid_at';
    public const TABLE = 'listing_fees';

    protected $fillable = [
        self::SELLER_PROFILE_ID,
        self::LISTING_ID,
        self::AMOUNT,
        self::FEE_TYPE,
        self::STATUS,
        self::PAYMENT_METHOD,
        self::TRANSACTION_ID,
        self::PAID_AT,
    ];

    protected $casts = [
        self::AMOUNT => 'decimal:2',
        self::PAID_AT => 'datetime',
    ];

    /** @see ListingFee::sellerProfileRelation() */
    const SELLER_PROFILE_RELATION = 'sellerProfileRelation';

    public function sellerProfileRelation(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class);
    }

//    public function listing(): BelongsTo
//    {
//        return $this->belongsTo(Listing::class);
//    }

    public function relatedSellerProfile(): SellerProfile
    {
        return $this->{self::SELLER_PROFILE_RELATION};
    }
}