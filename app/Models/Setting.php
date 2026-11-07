<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Read a system setting with a fallback default. Unset keys resolve to the
     * default so new installs never crash on a missing row.
     */
    public static function getValue(string $key, $default = null)
    {
        $row = self::query()->where('key', $key)->first();

        if (!$row || $row->value === null || trim((string) $row->value) === '') {
            return $default;
        }

        return $row->value;
    }

    /**
     * Persist a system setting. Blank values are stored as null.
     */
    public static function setValue(string $key, $value): void
    {
        $existing = self::query()->where('key', $key)->first();

        if ($existing) {
            $existing->value = ($value === null || $value === '') ? null : (string) $value;
            $existing->save();
            return;
        }

        self::create([
            'key' => $key,
            'value' => ($value === null || $value === '') ? null : (string) $value,
        ]);
    }
}
