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
}