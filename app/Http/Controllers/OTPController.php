<?php

namespace App\Http\Controllers;

use App\Models\OTP;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OTPController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'encryptedPassword' => ['required', 'string'],
            'iv' => ['required', 'string'],
            'expiry' => ['required', 'integer', 'min:1'],
        ]);

        $expiryTime = now()->addMinutes($request->expiry);

        // Store the Base64-encoded token directly
        $otp = OTP::create([
            'token' => $request->token,
            'password' => $request->encryptedPassword,
            'iv' => $request->iv,
            'expires_at' => $expiryTime,
        ]);

        // Return success response (token is already included in the URL fragment)
        return response()->json(['success' => true]);
    }

    public function show(string $tokenBase64): JsonResponse
    {
        $otp = OTP::where('token', $tokenBase64)
            ->where('expires_at', '>', now())
            ->first();

        if ($otp) {
            // Delete the OTP record after retrieval
            $otp->delete();

            return response()->json([
                'encryptedPassword' => $otp->password,
                'iv' => $otp->iv,
            ]);
        } else {
            return response()->json(['error' => 'Sorry, this password doesn\'t exist. It has either expired or has already been accessed.'], 404);
        }
    }

    public function check(string $token): JsonResponse
    {
        $otp = OTP::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if ($otp) {
            return response()->json(['exists' => true]);
        } else {
            return response()->json(['exists' => false, 'message' => 'Sorry, this password doesn\'t exist. It has either expired or has already been accessed.']);
        }
    }
}
