<?php

declare(strict_types=1);

namespace App\Http\Requests\Listings;

use App\Http\DataTransferObjects\Listings\CreateListingData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use App\Models\Listings\Listing;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class CreateListingRequest extends FormRequest
{
    private const TITLE = 'title';
    private const DESCRIPTION = 'description';
    private const PRICE = 'price';
    private const CATEGORY = 'category';
    private const CONDITION = 'condition';
    private const IMAGES = 'images';
    private const LOCATION = 'location';
    private const CAN_DELIVER_GLOBALLY = 'can_deliver_globally';
    private const REQUIRES_APPOINTMENT = 'requires_appointment';
    private const STATUS = 'status';
    private const EXPIRES_AT = 'expires_at';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::TITLE => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(3),
                ValidationRuleHelper::max(255)
            ],
            self::DESCRIPTION => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(5000)
            ],
            self::PRICE => [
                ValidationRuleHelper::REQUIRED,
                'numeric',
                'min:0.01',
                'max:999999.99'
            ],
            self::CATEGORY => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                'in:electronics,vehicles,real-estate,fashion,home-garden,services,gaming,books,sports,photography,music,fitness'
            ],
            self::CONDITION => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                'in:' . implode(',', Listing::getAvailableConditions())
            ],
            self::IMAGES => [
                ValidationRuleHelper::NULLABLE,
                'array',
                'max:10'
            ],
            self::IMAGES . '.*' => [
                ValidationRuleHelper::STRING,
                'url'
            ],
            self::LOCATION => [
                ValidationRuleHelper::NULLABLE,
                'array'
            ],
            self::LOCATION . '.city' => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(100)
            ],
            self::LOCATION . '.country' => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(100)
            ],
            self::LOCATION . '.coordinates' => [
                ValidationRuleHelper::NULLABLE,
                'array'
            ],
            self::CAN_DELIVER_GLOBALLY => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN
            ],
            self::REQUIRES_APPOINTMENT => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN
            ],
            self::STATUS => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                'in:' . implode(',', Listing::getAvailableStatuses())
            ],
            self::EXPIRES_AT => [
                ValidationRuleHelper::NULLABLE,
                'date',
                'after:now'
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): CreateListingData
    {
        return new CreateListingData([
            CreateListingData::TITLE => $this->input(self::TITLE),
            CreateListingData::DESCRIPTION => $this->input(self::DESCRIPTION),
            CreateListingData::PRICE => (float) $this->input(self::PRICE),
            CreateListingData::CATEGORY => $this->input(self::CATEGORY),
            CreateListingData::CONDITION => $this->input(self::CONDITION),
            CreateListingData::IMAGES => $this->input(self::IMAGES, []),
            CreateListingData::LOCATION => $this->input(self::LOCATION, []),
            CreateListingData::CAN_DELIVER_GLOBALLY => $this->boolean(self::CAN_DELIVER_GLOBALLY, false),
            CreateListingData::REQUIRES_APPOINTMENT => $this->boolean(self::REQUIRES_APPOINTMENT, false),
            CreateListingData::STATUS => $this->input(self::STATUS, Listing::STATUS_DRAFT),
            CreateListingData::EXPIRES_AT => $this->input(self::EXPIRES_AT),
        ]);
    }
}