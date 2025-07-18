<?php

declare(strict_types=1);

namespace App\Http\Requests\Images;

use App\Enums\Images\ImageTypeEnum;
use App\Http\DataTransferObjects\Images\UploadImageData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;
use ValueError;

class UploadImageRequest extends FormRequest
{
    private const FILE = 'file';
    private const IMAGEABLE_TYPE = 'imageable_type';
    private const IMAGEABLE_ID = 'imageable_id';
    private const IMAGE_TYPE = 'image_type';
    private const ALT_TEXT = 'alt_text';
    private const IS_PRIMARY = 'is_primary';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $imageType = $this->getImageType();

        return [
            self::FILE => $imageType ? $imageType->getValidationRules() : [
                ValidationRuleHelper::REQUIRED,
                'file',
                'max:10240', // 10MB default
                'mimetypes:image/jpeg,image/jpg,image/png,image/gif,image/webp',
            ],
            self::IMAGEABLE_TYPE => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                'in:' . implode(',', $this->getAllowedImageableTypes()),
            ],
            self::IMAGEABLE_ID => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::INTEGER,
                ValidationRuleHelper::min(1),
            ],
            self::IMAGE_TYPE => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                'in:' . implode(',', ImageTypeEnum::getValues()),
            ],
            self::ALT_TEXT => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(255),
            ],
            self::IS_PRIMARY => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is not valid.',
            'file.max' => 'The file size exceeds the maximum allowed size.',
            'file.mimetypes' => 'The file type is not supported.',
            'imageable_type.required' => 'Entity type is required.',
            'imageable_type.in' => 'Invalid entity type.',
            'imageable_id.required' => 'Entity ID is required.',
            'imageable_id.integer' => 'Entity ID must be a valid number.',
            'image_type.required' => 'Image type is required.',
            'image_type.in' => 'Invalid image type.',
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): UploadImageData
    {
        return new UploadImageData([
            UploadImageData::FILE => $this->file(self::FILE),
            UploadImageData::IMAGEABLE_TYPE => $this->input(self::IMAGEABLE_TYPE),
            UploadImageData::IMAGEABLE_ID => (int) $this->input(self::IMAGEABLE_ID),
            UploadImageData::IMAGE_TYPE => ImageTypeEnum::from($this->input(self::IMAGE_TYPE)),
            UploadImageData::ALT_TEXT => $this->input(self::ALT_TEXT),
            UploadImageData::IS_PRIMARY => $this->boolean(self::IS_PRIMARY, false),
        ]);
    }

    private function getImageType(): ?ImageTypeEnum
    {
        $imageTypeValue = $this->input(self::IMAGE_TYPE);
        if (!$imageTypeValue) {
            return null;
        }

        try {
            return ImageTypeEnum::from($imageTypeValue);
        } catch (ValueError $e) {
            return null;
        }
    }

    private function getAllowedImageableTypes(): array
    {
        return [
            'App\Models\Listings\Listing',
            'App\Models\SellerProfiles\SellerProfile',
            'App\Models\User',
            'App\Models\SellerAppointments\SellerAppointment',
        ];
    }
}