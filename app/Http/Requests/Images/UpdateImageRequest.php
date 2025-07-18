<?php

declare(strict_types=1);

namespace App\Http\Requests\Images;

use App\Http\DataTransferObjects\Images\UpdateImageData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class UpdateImageRequest extends FormRequest
{
    private const ALT_TEXT = 'alt_text';
    private const IS_PRIMARY = 'is_primary';
    private const IS_ACTIVE = 'is_active';
    private const SORT_ORDER = 'sort_order';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::ALT_TEXT => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(255),
            ],
            self::IS_PRIMARY => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN,
            ],
            self::IS_ACTIVE => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN,
            ],
            self::SORT_ORDER => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::INTEGER,
                ValidationRuleHelper::min(1),
            ],
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): UpdateImageData
    {
        return new UpdateImageData([
            UpdateImageData::ALT_TEXT => $this->input(self::ALT_TEXT),
            UpdateImageData::IS_PRIMARY => $this->has(self::IS_PRIMARY) ? $this->boolean(self::IS_PRIMARY) : null,
            UpdateImageData::IS_ACTIVE => $this->has(self::IS_ACTIVE) ? $this->boolean(self::IS_ACTIVE) : null,
            UpdateImageData::SORT_ORDER => $this->has(self::SORT_ORDER) ? (int) $this->input(self::SORT_ORDER) : null,
        ]);
    }
}