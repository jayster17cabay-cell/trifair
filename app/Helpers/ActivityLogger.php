<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ActivityLogger
{
    const CATEGORY_AUTH = 'auth';
    const CATEGORY_ADMIN = 'admin';
    const CATEGORY_DRIVER = 'driver';
    const CATEGORY_REVIEW = 'review';
    const CATEGORY_SYSTEM = 'system';

    public static function log(string $action, ?string $description = null, $model = null, string $category = 'system', ?Request $request = null)
    {
        $request = $request ?: request();

        try {
            return ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'category' => $category,
                'description' => $description,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model ? $model->id : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }
}
