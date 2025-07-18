<?php

declare(strict_types=1);

namespace App\Http\Requests\Listings;

use App\Http\DataTransferObjects\Listings\UpdateListingData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use App\Models\Listings\Listing;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class UpdateListingRequest extends FormRequest
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
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(3),
                ValidationRuleHelper::max(255)
            ],
            self::DESCRIPTION => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(5000)
            ],
            self::PRICE => [
                ValidationRuleHelper::SOMETIMES,
                'numeric',
                'min:0.01',
                'max:999999.99'
            ],
            self::CATEGORY => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::STRING,
                'in:electronics,vehicles,real-estate,fashion,home-garden,services,gaming,books,sports,photography,music,fitness'
            ],
            self::CONDITION => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                'in:' . implode(',', Listing::getAvailableConditions())
            ],
            self::IMAGES => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                'array',
                'max:10'
            ],
            self::IMAGES . '.*' => [
                ValidationRuleHelper::STRING,
                'url'
            ],
            self::LOCATION => [
                ValidationRuleHelper::SOMETIMES,
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
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                'date',
                'after:now'
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): UpdateListingData
    {
        $data = [];

        if ($this->has(self::TITLE)) {
            $data[UpdateListingData::TITLE] = $this->input(self::TITLE);
        }
        if ($this->has(self::DESCRIPTION)) {
            $data[UpdateListingData::DESCRIPTION] = $this->input(self::DESCRIPTION);
        }
        if ($this->has(self::PRICE)) {
            $data[UpdateListingData::PRICE] = (float) $this->input(self::PRICE);
        }
        if ($this->has(self::CATEGORY)) {
            $data[UpdateListingData::CATEGORY] = $this->input(self::CATEGORY);
        }
        if ($this->has(self::CONDITION)) {
            $data[UpdateListingData::CONDITION] = $this->input(self::CONDITION);
        }
        if ($this->has(self::IMAGES)) {
            $data[UpdateListingData::IMAGES] = $this->input(self::IMAGES);
        }
        if ($this->has(self::LOCATION)) {
            $data[UpdateListingData::LOCATION] = $this->input(self::LOCATION);
        }
        if ($this->has(self::CAN_DELIVER_GLOBALLY)) {
            $data[UpdateListingData::CAN_DELIVER_GLOBALLY] = $this->boolean(self::CAN_DELIVER_GLOBALLY);
        }
        if ($this->has(self::REQUIRES_APPOINTMENT)) {
            $data[UpdateListingData::REQUIRES_APPOINTMENT] = $this->boolean(self::REQUIRES_APPOINTMENT);
        }
        if ($this->has(self::STATUS)) {
            $data[UpdateListingData::STATUS] = $this->input(self::STATUS);
        }
        if ($this->has(self::EXPIRES_AT)) {
            $data[UpdateListingData::EXPIRES_AT] = $this->input(self::EXPIRES_AT);
        }

        return new UpdateListingData($data);
    }
}