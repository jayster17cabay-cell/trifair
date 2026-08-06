<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DesktopOnly
{
    public function handle(Request $request, Closure $next)
    {
        $agent = strtolower($request->userAgent() ?? '');

        $mobileKeywords = [
            'android', 'iphone', 'ipad', 'ipod',
            'mobile', 'phone', 'windows phone',
            'blackberry', 'opera mini', 'opera mobi',
        ];

        foreach ($mobileKeywords as $keyword) {
            if (str_contains($agent, $keyword)) {
                return response()->view('errors.desktop-only', [], 403);
            }
        }

        return $next($request);
    }
}
