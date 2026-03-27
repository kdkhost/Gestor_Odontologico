<?php

namespace App\Support;

use Illuminate\Http\Request;

class DeviceFingerprint
{
    public static function fromRequest(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->ip(),
            (string) $request->userAgent(),
            (string) $request->header('accept-language'),
        ]));
    }
}
