<?php

namespace App\Http\Controllers;

use App\Http\Traits\GetsClientIP;
use Illuminate\Http\Request;

class PageController extends Controller
{
    use GetsClientIP;

    public function app(Request $request)
    {
        $whitelistConfig = config('app.file_upload_whitelist');
        $fileUploadAllowed = false;

        if (empty($whitelistConfig)) {
            $fileUploadAllowed = true;
        } else {
            $whitelist = array_map('trim', explode(',', $whitelistConfig));
            $clientIP = $this->getClientIP($request);
            $fileUploadAllowed = in_array($clientIP, $whitelist);
        }

        return view('app', [
            'file_upload_allowed' => $fileUploadAllowed,
            'whitelist_configured' => !empty($whitelistConfig),
        ]);
    }
}
