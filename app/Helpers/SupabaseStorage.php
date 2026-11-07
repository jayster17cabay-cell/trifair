<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

    public static function getAccessUrl(string $path): ?string
    {
        if ((bool) config('services.supabase.bucket_public', true)) {
            return self::getPublicUrl($path);
        }

        if (!self::configured()) {
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => 'Bearer ' . self::apiKey()])
                ->post(self::baseUrl() . '/object/sign/' . self::bucket() . '/' . $path, [
                    'expiresIn' => 300,
                ]);

            if (!$response->successful()) {
                Log::error('SupabaseStorage signed URL failed', [
                    'status' => $response->status(),
                    'path' => $path,
                ]);
                return null;
            }

            $signedPath = $response->json('signedURL');
            if (!is_string($signedPath) || $signedPath === '') {
                return null;
            }

            if (str_starts_with($signedPath, 'http')) {
                return $signedPath;
            }

            // Supabase returns a path relative to /storage/v1 (for example
            // /object/sign/proofs/...); keep the storage API prefix when
            // turning that path into an absolute URL.
            $prefix = str_starts_with($signedPath, '/storage/v1/')
                ? rtrim((string) config('services.supabase.url'), '/')
                : rtrim(self::baseUrl(), '/');

            return $prefix . '/' . ltrim($signedPath, '/');
        } catch (\Throwable $e) {
            Log::error('SupabaseStorage signed URL exception', [
                'message' => $e->getMessage(),
                'path' => $path,
            ]);
            return null;
        }
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

    /**
     * Remove a proof regardless of whether it was stored remotely or on the
     * local public disk. Both calls are intentional: legacy rows do not record
     * which backend originally accepted the upload.
     */
    public static function deleteStored(string $path): bool
    {
        $remoteDeleted = self::delete($path);
        $localDeleted = Storage::disk('public')->delete($path);

        return $remoteDeleted || $localDeleted;
    }
}
