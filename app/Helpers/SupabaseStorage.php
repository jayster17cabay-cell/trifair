<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseStorage
{
    private static function baseUrl(): string
    {
        return rtrim((string) config('services.supabase.url'), '/') . '/storage/v1';
    }

    private static function apiKey(): string
    {
        return (string) config('services.supabase.key');
    }

    private static function bucket(): string
    {
        return (string) config('services.supabase.bucket', 'proofs');
    }

    public static function configured(): bool
    {
        return self::baseUrl() !== '/storage/v1' && self::apiKey() !== '';
    }

    public static function upload(UploadedFile $file, string $path): ?string
    {
        if (!self::configured()) {
            Log::warning('SupabaseStorage not configured, upload skipped', ['path' => $path]);
            return null;
        }

        try {
            $url = self::baseUrl() . '/object/' . self::bucket() . '/' . $path;

            $contents = file_get_contents($file->getRealPath());
            $mimeType = $file->getMimeType() ?: 'application/octet-stream';

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . self::apiKey(),
                ])
                ->withBody($contents, $mimeType)
                ->put($url);

            if ($response->successful()) {
                return $path;
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . self::apiKey(),
                ])
                ->withBody($contents, $mimeType)
                ->post($url);

            if ($response->successful()) {
                return $path;
            }

            Log::error('SupabaseStorage upload failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'path' => $path,
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('SupabaseStorage upload exception', [
                'message' => $e->getMessage(),
                'path' => $path,
            ]);
            return null;
        }
    }

    public static function getPublicUrl(string $path): string
    {
        return self::baseUrl() . '/object/public/' . self::bucket() . '/' . $path;
    }

    public static function delete(string $path): bool
    {
        if (!self::configured()) {
            return false;
        }

        try {
            $url = self::baseUrl() . '/object/' . self::bucket() . '/' . $path;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . self::apiKey(),
            ])->delete($url);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
