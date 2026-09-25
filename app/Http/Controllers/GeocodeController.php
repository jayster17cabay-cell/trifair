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
 * and cloud hosts such as Render get their IP blocks by Nominatim's anti-abuse
 * rules, so the app proxies lookups through the server. Photon (komoot) is
 * tried first because it tolerates datacenter IPs; Nominatim runs as a fallback
 * for when Photon is unavailable. Responses are cached aggressively.
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

        $cacheKey = 'revgeo3_' . round($lat, 4) . '_' . round($lng, 4);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $this->reply($cached);
        }

        // Photon first (cloud-friendly), Nominatim as fallback.
        try {
            $resp = Http::timeout(6)->acceptJson()
                ->get('https://photon.komoot.io/reverse', [
                    'lat' => $lat,
                    'lon' => $lng,
                ]);

            if ($resp->ok()) {
                $feature = array_values((array) $resp->json('features', []))[0] ?? null;
                $name = $feature ? $this->photonLabel($feature['properties'] ?? []) : null;
                if ($name) {
                    $result = ['display_name' => $name];
                    Cache::put($cacheKey, $result, 60 * 60 * 24 * 30);
                    return $this->reply($result);
                }
            }
        } catch (\Throwable $e) {
            // Fall through to Nominatim.
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

            if ($resp->ok()) {
                $data = $resp->json();
                $result = ['display_name' => $data['display_name'] ?? null];
                Cache::put($cacheKey, $result, 60 * 60 * 24 * 30);
                return $this->reply($result);
            }
        } catch (\Throwable $e) {
            // Fall through.
        }

        return response()->json(['error' => 'geocode unavailable'], 502);
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

        $cacheKey = 'fwdgeo_v3_' . substr(md5($q), 0, 16);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $this->reply($cached);
        }

        // Solano center so matches in the service area rank first.
        $lat = 16.52;
        $lon = 121.22;

        $results = $this->photonSearch($qRaw, $lat, $lon);
        if ($results === null) {
            $results = $this->nominatimSearch($qRaw);
        }

        if ($results === null) {
            return response()->json(['error' => 'geocode unavailable'], 502);
        }

        $list = $this->filterResults($results, $qRaw);
        Cache::put($cacheKey, $list, 60 * 60 * 24 * 30);

        return $this->reply($list);
    }

    /**
     * @return array<int, array{lat: string, lon: string, display_name: string}>|null
     */
    private function photonSearch(string $q, float $lat, float $lon): ?array
    {
        try {
            $resp = Http::timeout(6)->acceptJson()
                ->get('https://photon.komoot.io/api/', [
                    'q' => $q,
                    'limit' => 12,
                    'lat' => $lat,
                    'lon' => $lon,
                ]);

            if (!$resp->ok()) {
                return null;
            }

            $out = [];
            foreach ($resp->json('features', []) as $feature) {
                $props = $feature['properties'] ?? [];
                $geo = $feature['geometry'] ?? [];
                $coords = $geo['coordinates'] ?? null;
                if (!is_array($coords) || count($coords) < 2) {
                    continue;
                }
                $out[] = [
                    'lat' => (string) $coords[1],
                    'lon' => (string) $coords[0],
                    'display_name' => $this->photonLabel($props),
                ];
            }

            return $out;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array<int, array{lat: string, lon: string, display_name: string}>|null
     */
    private function nominatimSearch(string $q): ?array
    {
        try {
            $resp = Http::timeout(6)->withHeaders(['User-Agent' => 'TriFair/1.0 (passenger trip rating)'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'json',
                    'q' => $q,
                    'countrycodes' => 'ph',
                    'limit' => 14,
                    'addressdetails' => 1,
                ]);

            if (!$resp->ok()) {
                return null;
            }

            return array_values(array_map(function ($item) {
                return [
                    'lat' => (string) $item['lat'],
                    'lon' => (string) $item['lon'],
                    'display_name' => $item['display_name'],
                ];
            }, $resp->json() ?: []));
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Drop hits whose label has nothing to do with the query, then cap the list.
     *
     * @param array<int, array{lat: string, lon: string, display_name: string}> $results
     * @return array<int, array{lat: string, lon: string, display_name: string}>
     */
    private function filterResults(array $results, string $query): array
    {
        $q = mb_strtolower(trim($query));
        $tokens = array_values(array_filter(preg_split('/\s+/u', $q), function ($t) {
            return mb_strlen($t) >= 3;
        }));

        $accepted = array_values(array_filter($results, function ($item) use ($q, $tokens) {
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

        return array_slice($accepted, 0, 8);
    }

    /**
     * Build a compact human label from Photon's structured fields,
     * e.g. "J. P. Rizal Street, Solano, Nueva Vizcaya, Philippines".
     */
    private function photonLabel(array $p): string
    {
        $name = (string) ($p['name'] ?? '');
        $street = (string) ($p['street'] ?? '');
        $postcode = (string) ($p['postcode'] ?? '');

        $area = [];
        foreach (['district', 'city', 'county', 'state', 'country'] as $k) {
            $v = (string) ($p[$k] ?? '');
            if ($v !== '' && !in_array($v, $area, true)) {
                $area[] = $v;
            }
        }

        $leading = $name;
        if ($street !== '' && $street !== $name) {
            $leading = trim($name . ', ' . $street, ', ');
        }

        if ($postcode !== '' && $postcode !== end($area)) {
            $area[] = $postcode;
        }

        return trim(implode(', ', array_values(array_filter(array_merge([$leading], $area)))), ' ,');
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

    /**
     * Normalize responses: an empty list (search finds nothing valid) must be
     * sent as `[]`, and a null display_name as null — never `["error"]`, which
     * the map JS would otherwise misinterpret.
     */
    private function reply($payload)
    {
        if (is_array($payload) && isset($payload['display_name']) && $payload['display_name'] === null) {
            return response()->json(['display_name' => null]);
        }
        return response()->json($payload);
    }
}