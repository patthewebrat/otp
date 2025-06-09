<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SharedFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class SharedFileController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'encryptedFile' => 'required|file',
            'fileName' => 'required|string',
            'fileSize' => 'required|string',
            'iv' => 'required|string',
            'expiry' => 'required|integer|min:1',
        ]);

        $expiryTime = Carbon::now()->addMinutes((int) $request->expiry);
        
        // Use S3 if configured, otherwise fallback to local storage
        $disk = config('filesystems.default');
        $filePath = $request->file('encryptedFile')->store('encrypted-files', $disk);

        $sharedFile = SharedFile::create([
            'token' => $request->token,
            'file_path' => $filePath,
            'file_name' => $request->fileName,
            'file_size' => $request->fileSize,
            'iv' => $request->iv,
            'expires_at' => $expiryTime,
        ]);

        return response()->json(['success' => true]);
    }

    public function show($token)
    {
        $sharedFile = SharedFile::where('token', $token)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($sharedFile) {
            // Always use server-side proxy download regardless of storage provider
            $fileUrl = url('/download-file/' . $sharedFile->token);
            
            return response()->json([
                'fileUrl' => $fileUrl,
                'fileName' => $sharedFile->file_name,
                'fileSize' => $sharedFile->file_size,
                'iv' => $sharedFile->iv,
            ]);
        } else {
            return response()->json(['error' => 'Sorry, this file doesn\'t exist. It has either expired or has already been accessed.'], 404);
        }
    }

    public function check($token)
    {
        $sharedFile = SharedFile::where('token', $token)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($sharedFile) {
            return response()->json([
                'exists' => true,
                'fileName' => $sharedFile->file_name,
                'fileSize' => $sharedFile->file_size,
            ]);
        } else {
            return response()->json(['exists' => false, 'message' => 'Sorry, this file doesn\'t exist. It has either expired or has already been accessed.']);
        }
    }
    
    /**
     * Download a file directly from local storage
     */
    public function download($token)
    {
        $sharedFile = SharedFile::where('token', $token)
            ->where('expires_at', '>', Carbon::now())
            ->first();
            
        if (!$sharedFile) {
            abort(404, 'File not found or has expired.');
        }
        
        $disk = config('filesystems.default');
        
        if (!Storage::disk($disk)->exists($sharedFile->file_path)) {
            abort(404, 'File not found on server.');
        }
        
        // Mark as downloaded
        $sharedFile->delete();
        
        // Return the file as a download response
        return Storage::disk($disk)->download(
            $sharedFile->file_path, 
            $sharedFile->file_name,
            ['Content-Type' => 'application/octet-stream']
        );
    }

    /**
     * Check if current IP is allowed to upload files
     */
    public function checkIPAccess(Request $request)
    {
        $whitelistConfig = config('app.file_upload_whitelist');
        
        // If no whitelist is configured, allow all IPs
        if (empty($whitelistConfig)) {
            return response()->json(['allowed' => true]);
        }
        
        // Parse the whitelist
        $whitelist = array_map('trim', explode(',', $whitelistConfig));
        $clientIP = $request->ip();
        
        return response()->json([
            'allowed' => in_array($clientIP, $whitelist),
            'ip' => $clientIP
        ]);
    }

    /**
     * Get maximum file upload size from PHP configuration
     */
    public function getMaxFileSize()
    {
        // Get the upload_max_filesize from php.ini and convert to bytes
        $upload_max_filesize = $this->returnBytes(ini_get('upload_max_filesize'));
        
        // Get the post_max_size from php.ini and convert to bytes
        $post_max_size = $this->returnBytes(ini_get('post_max_size'));
        
        // Use the smallest of the two values
        $max_size = min($upload_max_filesize, $post_max_size);
        
        return response()->json([
            'max_size' => $max_size,
            'formatted_size' => $this->formatBytes($max_size)
        ]);
    }
    
    /**
     * Convert shorthand size notation to bytes
     */
    private function returnBytes($val) 
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        
        switch($last) {
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
    private function formatBytes($bytes, $precision = 2) 
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}