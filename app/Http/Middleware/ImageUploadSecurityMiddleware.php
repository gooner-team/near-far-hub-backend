<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\Images\ImageUploadException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ImageUploadSecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Rate limiting per user
        $this->checkRateLimit($user->id);

        // Check for file uploads
        if ($request->hasFile('file') || $request->hasFile('files')) {
            $this->validateFileUploads($request);
        }

        return $next($request);
    }

    /**
     * Check rate limit for uploads
     */
    private function checkRateLimit(int $userId): void
    {
        $cacheKey = "upload_count_{$userId}";
        $maxUploads = config('upload.max_uploads_per_hour', 50);

        $uploadCount = Cache::get($cacheKey, 0);

        if ($uploadCount >= $maxUploads) {
            Log::warning('Upload rate limit exceeded', [
                'user_id' => $userId,
                'count' => $uploadCount,
                'limit' => $maxUploads
            ]);

            throw ImageUploadException::uploadLimitExceeded($maxUploads);
        }

        // Increment counter
        Cache::put($cacheKey, $uploadCount + 1, 3600); // 1 hour
    }

    /**
     * Validate file uploads for security
     */
    private function validateFileUploads(Request $request): void
    {
        $files = [];

        // Get all uploaded files
        if ($request->hasFile('file')) {
            $files[] = $request->file('file');
        }

        if ($request->hasFile('files')) {
            $uploadedFiles = $request->file('files');
            if (is_array($uploadedFiles)) {
                $files = array_merge($files, $uploadedFiles);
            } else {
                $files[] = $uploadedFiles;
            }
        }

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $this->validateFileContent($file);
        }
    }

    /**
     * Validate file content for security threats
     */
    private function validateFileContent($file): void
    {
        $filePath = $file->getRealPath();

        // Check file size (additional check)
        $maxSize = config('upload.max_file_size', 10 * 1024 * 1024); // 10MB default
        if ($file->getSize() > $maxSize) {
            throw ImageUploadException::fileTooLarge($maxSize);
        }

        // Check for executable file extensions
        $dangerousExtensions = [
            'php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'bat', 'cmd',
            'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'sh', 'py', 'pl', 'rb'
        ];

        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, $dangerousExtensions)) {
            Log::warning('Dangerous file extension detected', [
                'extension' => $extension,
                'filename' => $file->getClientOriginalName()
            ]);

            throw ImageUploadException::securityViolation("Dangerous file extension: {$extension}");
        }

        // Read file header to check for suspicious content
        $fileHandle = fopen($filePath, 'rb');
        if (!$fileHandle) {
            throw ImageUploadException::securityViolation('Cannot read file content');
        }

        $header = fread($fileHandle, 512); // Read first 512 bytes
        fclose($fileHandle);

        // Check for script tags and PHP code
        $suspiciousPatterns = [
            '<?php',
            '<?=',
            '<script',
            'javascript:',
            'vbscript:',
            'onclick=',
            'onerror=',
            'onload=',
            '<%',
            'eval(',
            'base64_decode(',
            'shell_exec(',
            'system(',
            'exec(',
            'passthru(',
        ];

        $headerLower = strtolower($header);
        foreach ($suspiciousPatterns as $pattern) {
            if (strpos($headerLower, strtolower($pattern)) !== false) {
                Log::warning('Suspicious content detected in file', [
                    'pattern' => $pattern,
                    'filename' => $file->getClientOriginalName()
                ]);

                throw ImageUploadException::securityViolation("Suspicious content detected: {$pattern}");
            }
        }

        // For images, validate using getimagesize
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $imageInfo = getimagesize($filePath);
            if (!$imageInfo) {
                throw ImageUploadException::securityViolation('Invalid image file');
            }

            // Check image dimensions
            $maxWidth = config('upload.max_image_width', 5000);
            $maxHeight = config('upload.max_image_height', 5000);

            if ($imageInfo[0] > $maxWidth || $imageInfo[1] > $maxHeight) {
                throw ImageUploadException::securityViolation(
                    "Image dimensions too large: {$imageInfo[0]}x{$imageInfo[1]}"
                );
            }
        }
    }
}