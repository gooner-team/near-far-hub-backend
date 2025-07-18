<?php

declare(strict_types=1);

namespace App\Http\Requests\Images;

use App\Http\DataTransferObjects\Images\ReorderImagesData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class ReorderImagesRequest extends FormRequest
{
    private const IMAGEABLE_TYPE = 'imageable_type';
    private const IMAGEABLE_ID = 'imageable_id';
    private const IMAGE_IDS = 'image_ids';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::IMAGEABLE_TYPE => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
            ],
            self::IMAGEABLE_ID => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::INTEGER,
                ValidationRuleHelper::min(1),
            ],
            self::IMAGE_IDS => [
                ValidationRuleHelper::REQUIRED,
                'array',
                ValidationRuleHelper::min(1),
            ],
            self::IMAGE_IDS . '.*' => [
                ValidationRuleHelper::INTEGER,
                ValidationRuleHelper::min(1),
            ],
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): ReorderImagesData
    {
        return new ReorderImagesData([
            ReorderImagesData::IMAGEABLE_TYPE => $this->input(self::IMAGEABLE_TYPE),
            ReorderImagesData::IMAGEABLE_ID => (int) $this->input(self::IMAGEABLE_ID),
            ReorderImagesData::IMAGE_IDS => array_map('intval', $this->input(self::IMAGE_IDS, [])),
        ]);
    }
}