<?php

declare(strict_types=1);

namespace App\Http\Requests\Images;

use App\Enums\Images\ImageTypeEnum;
use App\Http\DataTransferObjects\Images\UploadMultipleImagesData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class UploadMultipleImagesRequest extends FormRequest
{
    private const FILES = 'files';
    private const IMAGEABLE_TYPE = 'imageable_type';
    private const IMAGEABLE_ID = 'imageable_id';
    private const IMAGE_TYPE = 'image_type';
    private const ALT_TEXTS = 'alt_texts';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $imageType = $this->getImageType();
        $maxFiles = $imageType ? $imageType->getMaxImagesPerEntity() : 10;

        return [
            self::FILES => [
                ValidationRuleHelper::REQUIRED,
                'array',
                ValidationRuleHelper::min(1),
                ValidationRuleHelper::max($maxFiles),
            ],
            self::FILES . '.*' => $imageType ? $imageType->getValidationRules() : [
                ValidationRuleHelper::REQUIRED,
                'file',
                'max:10240',
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
            self::ALT_TEXTS => [
                ValidationRuleHelper::NULLABLE,
                'array',
            ],
            self::ALT_TEXTS . '.*' => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(255),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Please select files to upload.',
            'files.array' => 'Files must be provided as an array.',
            'files.min' => 'At least one file is required.',
            'files.max' => 'Too many files selected.',
            'files.*.required' => 'Each file is required.',
            'files.*.file' => 'Each upload must be a valid file.',
            'files.*.max' => 'One or more files exceed the maximum allowed size.',
            'files.*.mimetypes' => 'One or more files have unsupported file types.',
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): UploadMultipleImagesData
    {
        return new UploadMultipleImagesData([
            UploadMultipleImagesData::FILES => $this->file(self::FILES, []),
            UploadMultipleImagesData::IMAGEABLE_TYPE => $this->input(self::IMAGEABLE_TYPE),
            UploadMultipleImagesData::IMAGEABLE_ID => (int) $this->input(self::IMAGEABLE_ID),
            UploadMultipleImagesData::IMAGE_TYPE => ImageTypeEnum::from($this->input(self::IMAGE_TYPE)),
            UploadMultipleImagesData::ALT_TEXTS => $this->input(self::ALT_TEXTS, []),
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
        } catch (\ValueError $e) {
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