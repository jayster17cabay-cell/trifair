{{--
    TripRouteMap — reusable route map card with real road-following routing.

    The map is driven by resources/js -> public/js/rate-map.js (Leaflet Routing
    Machine + the app's server-side OSRM proxy). Styles live in
    resources/css/tailwind.css (`.trip-card`, `.route-timeline`, `.route-stop`,
    `.route-node`, `.route-dot`, `.route-pin`, `.route-connector`,
    `.route-body`, `.route-label`, `.route-text`, `.search-results`,
    `.map-shell`, `.route-map`, `.tracking-banner`, `.map-info`, `.map-note`,
    plus the Leaflet zoom-control and marker styles).

    Usage:
        @include('partials.rate.trip-route-map', [
            'mapId' => 'rateMap',
            'mode' => 'track',                 // 'track' (live) | 'static' (display-only)
            'startAddress' => 'Detecting location...',
            'endAddress' => 'Type destination or tap map',
            'startCoords' => null,             // [lat, lng] — static mode only
            'endCoords' => null,               // [lat, lng] — static mode only
            'summaryText' => 'Select destination',
        ])

    In 'track' mode the FROM/TO rows are form inputs (`start_location` /
    `end_location`) so they submit with the form; the TO row doubles as the
    destination search field. In 'static' mode plain text is rendered and the
    JS reads `data-start-coords` / `data-end-coords` to draw the route.
--}}
@php
    $mapId = $mapId ?? 'rateMap';
    $mode = $mode ?? 'track';
    $startCoordsAttr = (!empty($startCoords)) ? ' data-start-coords="' . e(is_string($startCoords) ? $startCoords : json_encode($startCoords)) . '"' : '';
    $endCoordsAttr = (!empty($endCoords)) ? ' data-end-coords="' . e(is_string($endCoords) ? $endCoords : json_encode($endCoords)) . '"' : '';
@endphp
<div class="rate-card trip-card">
    <div class="route-timeline">
        <div class="route-stop">
            <span class="route-node"><span class="route-dot" aria-hidden="true"></span></span>
            <div class="route-body">
                <span class="route-label">From</span>
                @if ($mode === 'track')
                    <input type="text" name="start_location" id="{{ $mapId }}Start" class="route-text from" value="{{ $startAddress }}" readonly tabindex="-1" aria-label="Pickup location">
                @else
                    <span class="route-text from" id="{{ $mapId }}StartText">{{ $startAddress }}</span>
                @endif
            </div>
        </div>
        <div class="route-connector" aria-hidden="true"><span class="route-connector-line"></span></div>
        <div class="route-stop">
            <span class="route-node"><span class="route-pin" aria-hidden="true"></span></span>
            <div class="route-body">
                <span class="route-label">To</span>
                @if ($mode === 'track')
                    <input type="text" name="end_location" id="{{ $mapId }}End" class="route-text to" value="" placeholder="{{ $endAddress }}" autocomplete="off" aria-label="Destination">
                    <div class="search-results" id="{{ $mapId }}SearchResults" aria-live="polite"></div>
                @else
                    <span class="route-text to" id="{{ $mapId }}EndText">{{ $endAddress }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="map-shell" data-trip-route-map data-map-id="{{ $mapId }}" data-mode="{{ $mode }}"{!! $startCoordsAttr !!}{!! $endCoordsAttr !!}>
        <div id="{{ $mapId }}" class="route-map" role="application" aria-label="Trip route map"></div>
    </div>

    <div class="tracking-banner" id="{{ $mapId }}Tracking" hidden>
        <span id="{{ $mapId }}LocStatus" class="tracking-status"></span>
    </div>

    <div class="map-info">
        <span class="map-attribution">© OpenStreetMap · © CARTO</span>
        <span class="map-summary" id="{{ $mapId }}Summary">{{ $summaryText ?? 'Select destination' }}</span>
    </div>

    <div class="map-note" id="{{ $mapId }}Note" role="status" hidden></div>
</div>
