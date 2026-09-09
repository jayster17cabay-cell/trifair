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
        $qRaw = trim((string) $request->query('q', ''));
        if ($qRaw === '' || mb_strlen($qRaw) > 120) {
            return response()->json(['error' => 'missing query'], 422);
        }
        $q = mb_strtolower($qRaw);

        $cacheKey = 'fwdgeo_v2_' . substr(md5($q), 0, 16);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response()->json($cached);
        }

        // Solano service-area viewport (left, top, right, bottom). Passing it
        // as a hint (bounded=0) makes local places rank first, so a passenger
        // typing a town/street in Nueva Vizcaya gets the right match on top.
        $viewbox = '121.10,16.63,121.33,16.42';

        try {
            $resp = Http::timeout(6)->withHeaders(['User-Agent' => 'TriFair/1.0 (passenger trip rating)'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'json',
                    'q' => $qRaw,
                    'countrycodes' => 'ph',
                    'limit' => 14,
                    'addressdetails' => 1,
                    'viewbox' => $viewbox,
                    'bounded' => 0,
                ]);

            if (!$resp->ok()) {
                return response()->json(['error' => 'geocode failed'], 502);
            }

            // Keep only results whose name actually contains the query (or at
            // least one meaningful query token). Nominatim's free-text search
            // is fuzzy, so drop hits that have nothing to do with the message.
            $tokens = array_values(array_filter(preg_split('/\s+/u', $q), function ($t) {
                return mb_strlen($t) >= 3;
            }));

            $results = array_values(array_filter($resp->json(), function ($item) use ($q, $tokens) {
                if (!isset($item['lat'], $item['lon'], $item['display_name'])) {
                    return false;
                }
                $name = mb_strtolower($item['display_name']);
                if (mb_strpos($name, $q) !== false) {
                    return true;
                }
                foreach ($tokens as $t) {
                    if (mb_strpos($name, $t) !== false) {
                        return true;
                    }
                }
                return false;
            }));

            $list = array_slice(array_map(function ($item) {
                return [
                    'lat' => (string) $item['lat'],
                    'lon' => (string) $item['lon'],
                    'display_name' => $item['display_name'],
                ];
            }, $results), 0, 8);

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