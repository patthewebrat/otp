<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OTP;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

class OTPController extends Controller
{

    // Encryption method and options
    private $cipher = 'AES-256-CBC';

    public function create(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'expiry' => 'required|integer|min:1',
        ]);

        // Generate a unique token and encryption key
        $token = bin2hex(random_bytes(16)); // 32 characters hex token
        $expiryTime = Carbon::now()->addMinutes($request->expiry);

        // Generate a unique encryption key for this OTP
        $encryptionKey = bin2hex(random_bytes(32)); // 64 characters hex key
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher)); // Generate an IV

        // Encrypt the password using the derived encryption key and IV
        $encryptedPassword = openssl_encrypt($request->password, $this->cipher, hex2bin($encryptionKey), 0, $iv);
        $encryptedPasswordBase64 = base64_encode($encryptedPassword); // Encode the encrypted data
        $ivBase64 = base64_encode($iv); // Encode the IV for storage

        // Store the token, IV, and encrypted password in the database
        $otp = OTP::create([
            'token' => $token,
            'password' => $encryptedPasswordBase64, // Store as base64
            'iv' => $ivBase64, // Store IV as base64 for later use
            'expires_at' => $expiryTime,
        ]);

        // Return the token and encryption key in the URL for the client
        $combinedKey = $token . $encryptionKey; // Concatenate token and encryption key
        return response()->json(['url' => url("/otp?key={$combinedKey}")]);
    }

    public function show($combinedKey)
    {
        $token = substr($combinedKey, 0, 32); // First 32 characters (16 bytes hex token)
        $encryptionKey = substr($combinedKey, 32); // Remaining 64 characters (32 bytes hex key)

        $otp = OTP::where('token', $token)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($otp) {
            try {
                // Decode the IV and encrypted password from base64
                $iv = base64_decode($otp->iv);
                $encryptedPassword = base64_decode($otp->password);

                // Decrypt the password using the provided encryption key and stored IV
                $decryptedPassword = openssl_decrypt($encryptedPassword, $this->cipher, hex2bin($encryptionKey), 0, $iv);

                // Check if decryption was successful
                if ($decryptedPassword === false) {
                    return response()->json(['error' => 'Decryption failed. Invalid key or corrupted data.'], 400);
                }

                // Delete the OTP record after successful decryption
                $otp->delete();

                return response()->json(['password' => $decryptedPassword]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Decryption failed. Invalid key or corrupted data.'], 400);
            }
        } else {
            return response()->json(['error' => 'Sorry, this password doesn\'t exist. It has either expired or has already been accessed.'], 404);
        }
    }

    public function check($combinedKey)
    {
        $token = substr($combinedKey, 0, 32); // First 32 characters (16 bytes hex token)
        $otp = OTP::where('token', $token)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($otp) {
            return response()->json(['exists' => true]);
        } else {
            return response()->json(['exists' => false, 'message' => 'Sorry, this password doesn\'t exist. It has either expired or has already been accessed.']);
        }
    }


}
