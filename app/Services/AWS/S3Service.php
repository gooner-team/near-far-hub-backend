<?php

declare(strict_types=1);

namespace App\Services\AWS;

use App\Enums\Images\ImageTypeEnum;
use App\Exceptions\Images\ImageUploadException;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class S3Service
{
    private string $disk;
    private string $bucket;

    public function __construct()
    {
        $this->disk = config('filesystems.default', 'local');
        $this->bucket = config('filesystems.disks.s3.bucket', '');
    }

    /**
     * Upload file to S3 or local storage
     *
     * @throws Exception
     */
    public function uploadFile(
        UploadedFile $file,
        ImageTypeEnum $imageType,
        ?int $entityId = null,
        bool $makePublic = true
    ): array {
        Log::info('File upload started', [
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'image_type' => $imageType->value,
            'entity_id' => $entityId
        ]);

        $this->validateFile($file, $imageType);

        // Check memory limit
        $memoryLimit = $this->getMemoryLimitInBytes();
        if ($memoryLimit > 0 && $file->getSize() > ($memoryLimit * 0.8)) {
            throw ImageUploadException::fileTooLarge($file->getSize());
        }

        $fileName = $this->generateFileName($file, $imageType, $entityId);

        try {
            // Upload original file
            $uploadedPath = Storage::disk($this->disk)->putFileAs(
                $imageType->getStoragePath(),
                $file,
                $fileName,
                $makePublic ? 'public' : 'private'
            );

            if (!$uploadedPath) {
                throw ImageUploadException::storageFailed('Failed to upload file to storage');
            }

            $fileData = [
                'original_name' => $file->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $uploadedPath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'url' => $this->getFileUrl($uploadedPath),
            ];

            // Get image dimensions if it's an image
            if ($this->isImage($file)) {
                $dimensions = $this->getImageDimensions($file);
                $fileData['width'] = $dimensions['width'];
                $fileData['height'] = $dimensions['height'];

                // Store thumbnail paths (actual generation can be done later via queue)
                if ($imageType->requiresImageDimensions()) {
                    $fileData['thumbnails'] = $this->generateThumbnailPaths($uploadedPath);
                }
            }

            Log::info('File upload completed', ['file_path' => $uploadedPath]);
            return $fileData;

        } catch (Exception $e) {
            Log::error('File upload failed', ['error' => $e->getMessage()]);

            // Clean up if upload failed
            if (isset($uploadedPath) && Storage::disk($this->disk)->exists($uploadedPath)) {
                Storage::disk($this->disk)->delete($uploadedPath);
            }

            if ($e instanceof ImageUploadException) {
                throw $e;
            }

            throw ImageUploadException::storageFailed($e->getMessage());
        }
    }

    /**
     * Upload multiple files
     *
     * @param UploadedFile[] $files
     * @throws Exception
     */
    public function uploadMultipleFiles(
        array $files,
        ImageTypeEnum $imageType,
        ?int $entityId = null,
        bool $makePublic = true
    ): array {
        $maxFiles = $imageType->getMaxImagesPerEntity();

        if (count($files) > $maxFiles) {
            throw ImageUploadException::uploadLimitExceeded($maxFiles);
        }

        $uploadedFiles = [];
        $uploadedPaths = [];

        try {
            foreach ($files as $index => $file) {
                $fileData = $this->uploadFile($file, $imageType, $entityId, $makePublic);
                $fileData['sort_order'] = $index + 1;
                $fileData['is_primary'] = $index === 0; // First file is primary

                $uploadedFiles[] = $fileData;
                $uploadedPaths[] = $fileData['file_path'];
            }

            return $uploadedFiles;

        } catch (Exception $e) {
            // Clean up all uploaded files if any upload failed
            foreach ($uploadedPaths as $path) {
                if (Storage::disk($this->disk)->exists($path)) {
                    Storage::disk($this->disk)->delete($path);
                }
            }
            throw $e;
        }
    }

    /**
     * Delete file from storage
     */
    public function deleteFile(string $filePath): bool
    {
        try {
            if (Storage::disk($this->disk)->exists($filePath)) {
                return Storage::disk($this->disk)->delete($filePath);
            }
            return true;
        } catch (Exception $e) {
            Log::error('File deletion failed', ['file_path' => $filePath, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Delete multiple files
     */
    public function deleteMultipleFiles(array $filePaths): bool
    {
        try {
            $existingPaths = array_filter($filePaths, function ($path) {
                return Storage::disk($this->disk)->exists($path);
            });

            if (empty($existingPaths)) {
                return true;
            }

            return Storage::disk($this->disk)->delete($existingPaths);
        } catch (Exception $e) {
            Log::error('Multiple file deletion failed', ['paths' => $filePaths, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get file URL
     */
    public function getFileUrl(string $filePath): string
    {
        // Add CDN support
        $cdnUrl = config('upload.cdn_url');

        if ($this->disk === 's3') {
            if ($cdnUrl) {
                return $cdnUrl . '/' . $filePath;
            }
            return Storage::disk('s3')->url($filePath);
        }

        return Storage::disk($this->disk)->url($filePath);
    }

    /**
     * Get temporary URL for private files
     */
    public function getTemporaryUrl(string $filePath, int $expirationMinutes = 60): string
    {
        if ($this->disk === 's3') {
            return Storage::disk('s3')->temporaryUrl($filePath, now()->addMinutes($expirationMinutes));
        }

        return $this->getFileUrl($filePath);
    }

    /**
     * Check if file exists
     */
    public function fileExists(string $filePath): bool
    {
        return Storage::disk($this->disk)->exists($filePath);
    }

    /**
     * Get file size
     */
    public function getFileSize(string $filePath): int
    {
        return Storage::disk($this->disk)->size($filePath);
    }

    /**
     * Move file to different path
     */
    public function moveFile(string $fromPath, string $toPath): bool
    {
        try {
            return Storage::disk($this->disk)->move($fromPath, $toPath);
        } catch (Exception $e) {
            Log::error('File move failed', ['from' => $fromPath, 'to' => $toPath, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Copy file to different path
     */
    public function copyFile(string $fromPath, string $toPath): bool
    {
        try {
            return Storage::disk($this->disk)->copy($fromPath, $toPath);
        } catch (Exception $e) {
            Log::error('File copy failed', ['from' => $fromPath, 'to' => $toPath, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get current bucket name
     */
    public function getBucket(): string
    {
        return $this->bucket;
    }

    /**
     * Get storage disk instance
     */
    public function getDisk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($this->disk);
    }

    /**
     * Get current storage disk name
     */
    public function getDiskName(): string
    {
        return $this->disk;
    }

    /**
     * Check if using S3
     */
    public function isUsingS3(): bool
    {
        return $this->disk === 's3';
    }

    /**
     * Validate uploaded file
     *
     * @throws ImageUploadException
     */
    private function validateFile(UploadedFile $file, ImageTypeEnum $imageType): void
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw ImageUploadException::securityViolation('Invalid file upload');
        }

        // Additional security validation for images
        if ($this->isImage($file)) {
            $this->validateImageSecurity($file);
        }

        // Check file size
        if ($file->getSize() > $imageType->getMaxFileSize()) {
            throw ImageUploadException::fileTooLarge($imageType->getMaxFileSize());
        }

        // Check mime type
        if (!in_array($file->getMimeType(), $imageType->getAllowedMimeTypes())) {
            throw ImageUploadException::invalidFileType(
                $file->getMimeType(),
                $imageType->getAllowedMimeTypes()
            );
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $imageType->getAllowedExtensions())) {
            throw ImageUploadException::invalidFileType(
                $extension,
                $imageType->getAllowedExtensions()
            );
        }
    }

    /**
     * Additional security validation for images
     *
     * @throws ImageUploadException
     */
    private function validateImageSecurity(UploadedFile $file): void
    {
        // Validate actual image content, not just extension
        $imageInfo = getimagesize($file->getRealPath());
        if (!$imageInfo) {
            throw ImageUploadException::securityViolation('Invalid image file content');
        }

        // Check for suspicious file signatures
        $fileHandle = fopen($file->getRealPath(), 'rb');
        if ($fileHandle) {
            $header = fread($fileHandle, 10);
            fclose($fileHandle);

            // Check for PHP tags or other suspicious content
            if (strpos($header, '<?php') !== false || strpos($header, '<?=') !== false) {
                throw ImageUploadException::securityViolation('Suspicious file content detected');
            }
        }

        // Additional check: ensure the file extension matches the actual image type
        $detectedType = $imageInfo[2];
        $extension = strtolower($file->getClientOriginalExtension());

        $validTypes = [
            'jpg' => [IMAGETYPE_JPEG],
            'jpeg' => [IMAGETYPE_JPEG],
            'png' => [IMAGETYPE_PNG],
            'gif' => [IMAGETYPE_GIF],
            'webp' => [IMAGETYPE_WEBP],
        ];

        if (isset($validTypes[$extension]) && !in_array($detectedType, $validTypes[$extension])) {
            throw ImageUploadException::securityViolation('File extension does not match image type');
        }
    }

    /**
     * Get memory limit in bytes
     */
    private function getMemoryLimitInBytes(): int
    {
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit === false || $memoryLimit === '' || $memoryLimit === '-1') {
            return 0; // No limit or unlimited
        }

        // Convert memory limit to bytes
        $value = (int) $memoryLimit;
        $unit = strtolower(substr($memoryLimit, -1));

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * Generate unique file name
     */
    private function generateFileName(UploadedFile $file, ImageTypeEnum $imageType, ?int $entityId = null): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);

        $prefix = $entityId ? "entity_{$entityId}_" : '';
        $typePrefix = strtolower(str_replace('_', '', $imageType->value));

        return "{$prefix}{$typePrefix}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Check if file is an image
     */
    private function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * Get image dimensions using native PHP
     */
    private function getImageDimensions(UploadedFile $file): array
    {
        try {
            $imageInfo = getimagesize($file->getRealPath());
            return [
                'width' => $imageInfo[0] ?? null,
                'height' => $imageInfo[1] ?? null,
            ];
        } catch (Exception $e) {
            return ['width' => null, 'height' => null];
        }
    }

    /**
     * Generate thumbnail paths (actual generation can be done via queue)
     */
    private function generateThumbnailPaths(string $originalPath): array
    {
        $thumbnails = [];
        $sizes = config('upload.thumbnails.sizes', [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 400, 'height' => 300],
            'large' => ['width' => 800, 'height' => 600],
        ]);

        foreach ($sizes as $sizeName => $dimensions) {
            $thumbnailPath = $this->generateThumbnailPath($originalPath, $sizeName);

            $thumbnails[$sizeName] = [
                'path' => $thumbnailPath,
                'url' => $this->getFileUrl($thumbnailPath),
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'exists' => false, // Will be true after actual generation
            ];
        }

        return $thumbnails;
    }

    /**
     * Generate thumbnail file path
     */
    private function generateThumbnailPath(string $originalPath, string $sizeName): string
    {
        $pathInfo = pathinfo($originalPath);
        return $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['filename'] . "_{$sizeName}." . $pathInfo['extension'];
    }
}