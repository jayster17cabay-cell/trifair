<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Server-side geocoding proxy for the passenger map.
 *
 * The browser cannot reliably call the public Nominatim instance from mobile
 * networks (CORS, rate limits, and it rejects requests that look like scrapers),
 * so the app proxies both reverse ("what place is at this coordinate?") and
 * forward ("search for destination name") lookups through the server, cached.
 */
class GeocodeController extends Controller
{
    /**
     * Reverse geocode: lat/lng -> display address.
     */
    public function reverse(Request $request)
    {
        $lat = $this->coord($request->query('lat'), 90);
        $lng = $this->coord($request->query('lng'), 180);

        if ($lat === null || $lng === null) {
            return response()->json(['error' => 'invalid coordinates'], 422);
        }

        $cacheKey = 'revgeo_' . round($lat, 4) . '_' . round($lng, 4);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response()->json($cached);
        }

        try {
            $resp = Http::timeout(6)->withHeaders(['User-Agent' => 'TriFair/1.0 (passenger trip rating)'])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'json',
                    'lat' => $lat,
                    'lon' => $lng,
                    'zoom' => 18,
                    'addressdetails' => 1,
                ]);

            if (!$resp->ok()) {
                return response()->json(['error' => 'geocode failed'], 502);
            }

            $data = $resp->json();
            $result = ['display_name' => $data['display_name'] ?? null];

            Cache::put($cacheKey, $result, 60 * 60 * 24 * 30);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'geocode unavailable'], 502);
        }
    }

    /**
     * Forward geocode: query text -> list of matching places.
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '' || mb_strlen($q) > 120) {
            return response()->json(['error' => 'missing query'], 422);
        }

        $cacheKey = 'fwdgeo_' . substr(md5(mb_strtolower($q)), 0, 16);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response()->json($cached);
        }

        try {
            $resp = Http::timeout(6)->withHeaders(['User-Agent' => 'TriFair/1.0 (passenger trip rating)'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'json',
                    'q' => $q,
                    'countrycodes' => 'ph',
                    'limit' => 10,
                    'addressdetails' => 1,
                ]);

            if (!$resp->ok()) {
                return response()->json(['error' => 'geocode failed'], 502);
            }

            $results = array_values(array_filter($resp->json(), function ($item) {
                return isset($item['lat'], $item['lon'], $item['display_name']);
            }));
            $list = array_map(function ($item) {
                return [
                    'lat' => (string) $item['lat'],
                    'lon' => (string) $item['lon'],
                    'display_name' => $item['display_name'],
                ];
            }, $results);

            Cache::put($cacheKey, $list, 60 * 60 * 24 * 30);

            return response()->json($list);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'geocode unavailable'], 502);
        }
    }

    private function coord($value, float $max): ?float
    {
        if ($value === null || !is_numeric($value)) {
            return null;
        }
        $f = (float) $value;
        if ($f < -$max || $f > $max) {
            return null;
        }
        return $f;
    }
}