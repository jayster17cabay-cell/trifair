<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RouteController extends Controller
{
    /**
     * Server-side route lookup for the passenger map.
     *
     * The browser cannot reliably reach public OSRM/Valhalla instances from
     * mobile networks (rate limits, CORS, latency), so the app proxies the
     * routing request through the server, caches results keyed on the
     * rounded coordinates, and snaps off-road points to the nearest road
     * (like Google Maps snapping the blue dot) before routing.
     */
    public function fetch(Request $request)
    {
        $slat = $this->coord($request->query('slat'), 90);
        $slng = $this->coord($request->query('slng'), 180);
        $elat = $this->coord($request->query('elat'), 90);
        $elng = $this->coord($request->query('elng'), 180);

        if ($slat === null || $slng === null || $elat === null || $elng === null) {
            return response()->json(['error' => 'invalid coordinates'], 422);
        }

        // The passenger map only ever routes within ~16 km of Solano, Nueva
        // Vizcaya. Rejecting far-away coordinates stops the proxy from being
        // abused to hammer the public OSRM servers with arbitrary worldwide
        // lookups (bandwidth/rate-limit exhaustion).
        if (!$this->withinServiceArea($slat, $slng, $elat, $elng)) {
            return response()->json(['error' => 'coordinates outside supported area'], 422);
        }

        $cacheKey = 'route_'
            . round($slat, 3) . '_' . round($slng, 3) . '_'
            . round($elat, 3) . '_' . round($elng, 3);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response()->json($cached);
        }

        $route = $this->lookup($slat, $slng, $elat, $elng);

        if ($route === null) {
            return response()->json(['error' => 'route unavailable'], 502);
        }

        Cache::put($cacheKey, $route, 60 * 60 * 24 * 7);

        return response()->json($route);
    }

    /**
     * Solano is centered near 16.52 N, 121.19 E. Allow a generous ~0.8 degree
     * box around it (covers Nueva Vizcaya and all neighboring towns) while
     * blocking anything farther away.
     */
    private function withinServiceArea(float $slat, float $slng, float $elat, float $elng): bool
    {
        $center = ['lat' => 16.52, 'lng' => 121.19];
        $maxDelta = 0.8;

        foreach ([['lat' => $slat, 'lng' => $slng], ['lat' => $elat, 'lng' => $elng]] as $point) {
            if (abs($point['lat'] - $center['lat']) > $maxDelta || abs($point['lng'] - $center['lng']) > $maxDelta) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hard ceiling for the whole lookup so the browser never hangs.
     * Success path is ~1s; snapping fallback stays under this budget.
     */
    private function lookup(float $slat, float $slng, float $elat, float $elng): ?array
    {
        $deadline = microtime(true) + 7.5;

        $route = $this->routeBetween($slat, $slng, $elat, $elng, $deadline);
        if ($route !== null) {
            return $route;
        }

        if (microtime(true) > $deadline) {
            return null;
        }

        $start = $this->snap($slat, $slng, $deadline);
        if (microtime(true) > $deadline) {
            return null;
        }
        $end = $this->snap($elat, $elng, $deadline);

        if ($start === null || $end === null || microtime(true) > $deadline) {
            return null;
        }

        return $this->routeBetween($start[0], $start[1], $end[0], $end[1], $deadline);
    }

    /**
     * @return array|null shape: ['coords' => [[lat,lng],...], 'distanceMeters' => int, 'durationSeconds' => int]
     */
    private function routeBetween(float $slat, float $slng, float $elat, float $elng, float $deadline): ?array
    {
        $coords = "{$slng},{$slat};{$elng},{$elat}";

        $osrmUrls = [
            'https://routing.openstreetmap.de/routed-car/route/v1/driving/' . $coords . '?overview=full&geometries=geojson&steps=false',
            'https://router.project-osrm.org/route/v1/driving/' . $coords . '?overview=full&geometries=geojson&steps=false',
        ];

        foreach ($osrmUrls as $url) {
            if (microtime(true) > $deadline) {
                break;
            }
            try {
                $resp = Http::timeout(3)->withHeaders(['User-Agent' => 'TriFair/1.0 (passenger trip rating)'])->get($url);
                if ($resp->ok()) {
                    $data = $resp->json();
                    if (($data['code'] ?? '') === 'Ok' && !empty($data['routes'][0])) {
                        $route = $data['routes'][0];
                        $points = $route['geometry']['coordinates'] ?? [];
                        if (count($points) >= 2) {
                            return [
                                'coords' => array_map(fn ($c) => [$c[1], $c[0]], $points),
                                'distanceMeters' => (int) round($route['distance'] ?? 0),
                                'durationSeconds' => (int) round($route['duration'] ?? 0),
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // try next provider
            }
        }

        return null;
    }

    /**
     * Snap a raw GPS point to the nearest road (Google-Maps-like blue-dot snap).
     * @return array|null [lat, lng]
     */
    private function snap(float $lat, float $lng, float $deadline): ?array
    {
        $urls = [
            'https://routing.openstreetmap.de/routed-car/nearest/v1/driving/' . $lng . ',' . $lat . '?number=1',
            'https://router.project-osrm.org/nearest/v1/driving/' . $lng . ',' . $lat . '?number=1',
        ];

        foreach ($urls as $url) {
            if (microtime(true) > $deadline) {
                break;
            }
            try {
                $resp = Http::timeout(2)->withHeaders(['User-Agent' => 'TriFair/1.0 (passenger trip rating)'])->get($url);
                if ($resp->ok()) {
                    $data = $resp->json();
                    if (($data['code'] ?? '') === 'Ok' && !empty($data['waypoints'][0]['location'])) {
                        $loc = $data['waypoints'][0]['location']; // [lng, lat]
                        return [(float) $loc[1], (float) $loc[0]];
                    }
                }
            } catch (\Throwable $e) {
                // try next provider
            }
        }

        return null;
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
