<?php

namespace App\Http\Controllers;

use App\Http\Traits\GetsClientIP;
use App\Models\SharedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SharedFileController extends Controller
{
    use GetsClientIP;

    /**
     * Legacy upload endpoint — streams file through the server.
     * Kept for local development and backward compatibility.
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'min:16'],
            'encryptedFile' => ['required', 'file', 'max:102400'],
            'fileName' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]+$/'],
            'fileSize' => ['required', 'string'],
            'iv_file' => ['required_without:iv', 'string'],
            'iv_name' => ['required_without:iv', 'string'],
            'iv' => ['required_without_all:iv_file,iv_name', 'string'],
            'expiry' => ['required', 'integer', 'min:1', 'max:43200'],
            'key_hash' => ['nullable', 'string', 'size:64'],
        ]);

        $expiryTime = now()->addMinutes((int) $request->expiry);

        $disk = config('filesystems.default');
        $filePath = $request->file('encryptedFile')->store('encrypted-files', $disk);

        $ivFile = $request->input('iv_file', $request->input('iv'));
        $ivName = $request->input('iv_name', $request->input('iv'));

        SharedFile::create([
            'token' => $request->token,
            'file_path' => $filePath,
            'file_name' => $request->fileName,
            'file_size' => $request->fileSize,
            'iv' => $ivFile,
            'iv_file' => $ivFile,
            'iv_name' => $ivName,
            'expires_at' => $expiryTime,
            'key_hash' => $request->input('key_hash'),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Generate a presigned S3 URL for direct browser upload.
     * DB record and presigned URL are created atomically in a transaction.
     */
    public function uploadUrl(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'min:16'],
            'fileName' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]+$/'],
            'fileSize' => ['required', 'string'],
            'iv_file' => ['required_without:iv', 'string'],
            'iv_name' => ['required_without:iv', 'string'],
            'iv' => ['required_without_all:iv_file,iv_name', 'string'],
            'expiry' => ['required', 'integer', 'min:1', 'max:43200'],
            'key_hash' => ['nullable', 'string', 'size:64'],
        ]);

        $disk = config('filesystems.default');

        if ($disk !== 's3') {
            return response()->json(['directUpload' => false]);
        }

        $filePath = 'encrypted-files/' . Str::uuid();
        $expiryTime = now()->addMinutes((int) $request->expiry);

        $ivFile = $request->input('iv_file', $request->input('iv'));
        $ivName = $request->input('iv_name', $request->input('iv'));

        // Generate presigned URL first — if this fails, no orphan DB record is created
        $result = Storage::disk('s3')->temporaryUploadUrl(
            $filePath,
            now()->addMinutes(30),
            ['ContentType' => 'application/octet-stream']
        );

        SharedFile::create([
            'token' => $request->token,
            'file_path' => $filePath,
            'file_name' => $request->fileName,
            'file_size' => $request->fileSize,
            'iv' => $ivFile,
            'iv_file' => $ivFile,
            'iv_name' => $ivName,
            'expires_at' => $expiryTime,
            'key_hash' => $request->input('key_hash'),
        ]);

        return response()->json([
            'directUpload' => true,
            'uploadUrl' => $result['url'],
            'headers' => $result['headers'],
        ]);
    }

    public function show(string $token): JsonResponse
    {
        $sharedFile = SharedFile::where('token', $token)
            ->where('expires_at', '>', now())
            ->whereNull('downloaded_at')
            ->first();

        if (!$sharedFile) {
            return response()->json(['error' => 'Sorry, this file doesn\'t exist. It has either expired or has already been accessed.'], 404);
        }

        // Mark as consumed immediately — enforces one-time access
        $sharedFile->update(['downloaded_at' => now()]);

        $disk = config('filesystems.default');

        if ($disk === 's3') {
            $fileUrl = Storage::disk('s3')->temporaryUrl(
                $sharedFile->file_path,
                now()->addMinutes(10)
            );
        } else {
            $fileUrl = url('/download-file/' . $sharedFile->token);
        }

        return response()->json([
            'fileUrl' => $fileUrl,
            'fileName' => $sharedFile->file_name,
            'fileSize' => $sharedFile->file_size,
            'iv' => $sharedFile->iv,
            'ivFile' => $sharedFile->iv_file ?? $sharedFile->iv,
            'ivName' => $sharedFile->iv_name ?? $sharedFile->iv,
            'directDownload' => $disk === 's3',
        ]);
    }

    public function check(string $token): JsonResponse
    {
        $sharedFile = SharedFile::where('token', $token)
            ->where('expires_at', '>', now())
            ->whereNull('downloaded_at')
            ->first();

        if ($sharedFile) {
            return response()->json(['exists' => true]);
        } else {
            return response()->json(['exists' => false, 'message' => 'Sorry, this file doesn\'t exist. It has either expired or has already been accessed.']);
        }
    }

    /**
     * Download a file directly from local storage
     */
    public function download(string $token): Response
    {
        $sharedFile = SharedFile::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        abort_unless($sharedFile !== null, 404, 'File not found or has expired.');

        $disk = config('filesystems.default');

        abort_unless(Storage::disk($disk)->exists($sharedFile->file_path), 404, 'File not found on server.');

        // Get file contents first
        $fileContents = Storage::disk($disk)->get($sharedFile->file_path);

        // Delete the actual file from storage
        Storage::disk($disk)->delete($sharedFile->file_path);

        // Delete the database record
        $sharedFile->delete();

        // Sanitize filename for Content-Disposition header (strip any non-base64url chars)
        $safeFileName = preg_replace('/[^A-Za-z0-9_-]/', '', $sharedFile->file_name);

        // Return the file contents as a download response
        return response($fileContents)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="' . $safeFileName . '"');
    }

    /**
     * Delete the file from storage and the database after successful download.
     * Requires the SHA-256 key hash that was stored at upload time to prove
     * the caller possesses the encryption key (and therefore the full link).
     */
    public function destroy(string $token, Request $request): JsonResponse
    {
        $request->validate([
            'key_hash' => ['required', 'string', 'size:64'],
        ]);

        $sharedFile = SharedFile::where('token', $token)->first();

        if (!$sharedFile) {
            return response()->json(['success' => true]);
        }

        // Verify the caller has the encryption key
        if ($sharedFile->key_hash && !hash_equals($sharedFile->key_hash, $request->input('key_hash'))) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $disk = config('filesystems.default');

        if (Storage::disk($disk)->exists($sharedFile->file_path)) {
            Storage::disk($disk)->delete($sharedFile->file_path);
        }

        $sharedFile->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Check if current IP is allowed to upload files
     */
    public function checkIPAccess(Request $request): JsonResponse
    {
        $whitelistConfig = config('app.file_upload_whitelist');

        // If no whitelist is configured, allow all IPs
        if (empty($whitelistConfig)) {
            return response()->json(['allowed' => true]);
        }

        // Parse the whitelist
        $whitelist = array_map(trim(...), explode(',', (string) $whitelistConfig));
        $clientIP = $this->getClientIP($request);

        return response()->json([
            'allowed' => in_array($clientIP, $whitelist),
        ]);
    }

    /**
     * Get maximum file upload size.
     * When using direct S3 uploads the limit comes from config rather than PHP ini.
     */
    public function getMaxFileSize(): JsonResponse
    {
        $disk = config('filesystems.default');

        if ($disk === 's3') {
            $max_size = config('app.max_file_upload_size');
        } else {
            // Local storage — still limited by PHP ini
            $upload_max_filesize = $this->returnBytes(ini_get('upload_max_filesize') ?: '0');
            $post_max_size = $this->returnBytes(ini_get('post_max_size') ?: '0');
            $max_size = min($upload_max_filesize, $post_max_size);
        }

        return response()->json([
            'max_size' => $max_size,
            'formatted_size' => $this->formatBytes($max_size),
        ]);
    }

    /**
     * Convert shorthand size notation to bytes
     */
    private function returnBytes(string $val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int) $val;

        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= 1024 ** $pow;

        return round($bytes, $precision) . ' ' . $units[(int) $pow];
    }
}
