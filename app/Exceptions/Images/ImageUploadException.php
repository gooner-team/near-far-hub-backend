<?php

declare(strict_types=1);

namespace App\Exceptions\Images;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageUploadException extends Exception
{
    private string $errorCode;
    private array $context;

    public function __construct(
        string $message = 'Image upload failed',
        string $errorCode = 'UPLOAD_FAILED',
        array $context = [],
        int $code = 422,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => 'Image upload failed',
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'details' => $this->context,
        ], $this->getCode());
    }

    /**
     * Create exception for file too large
     */
    public static function fileTooLarge(int $maxSize): self
    {
        $maxSizeMB = round($maxSize / 1024 / 1024, 2);
        return new self(
            "File size exceeds maximum allowed size of {$maxSizeMB}MB",
            'FILE_TOO_LARGE',
            ['max_size_bytes' => $maxSize, 'max_size_mb' => $maxSizeMB]
        );
    }

    /**
     * Create exception for invalid file type
     */
    public static function invalidFileType(string $mimeType, array $allowedTypes): self
    {
        return new self(
            'File type not allowed',
            'INVALID_FILE_TYPE',
            ['provided_type' => $mimeType, 'allowed_types' => $allowedTypes]
        );
    }

    /**
     * Create exception for security violation
     */
    public static function securityViolation(string $reason): self
    {
        return new self(
            'File contains suspicious content',
            'SECURITY_VIOLATION',
            ['reason' => $reason],
            400
        );
    }

    /**
     * Create exception for upload limit exceeded
     */
    public static function uploadLimitExceeded(int $limit): self
    {
        return new self(
            "Upload limit of {$limit} files exceeded",
            'UPLOAD_LIMIT_EXCEEDED',
            ['limit' => $limit],
            429
        );
    }

    /**
     * Create exception for storage failure
     */
    public static function storageFailed(string $reason): self
    {
        return new self(
            'Failed to store file',
            'STORAGE_FAILED',
            ['reason' => $reason],
            500
        );
    }
}