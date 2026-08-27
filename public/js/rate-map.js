/* TriFair route map core — reusable Leaflet map with real road-following routing.

   Uses Leaflet Routing Machine (LRM) wired to a custom OSRM-backed router that
   calls the app's server-side `/route` proxy (which snaps off-road points to
   the nearest road, caches results, and enforces the service area around
   Solano, Nueva Vizcaya). The turn-by-turn instructions panel is hidden
   (`show: false`) — passengers only see the route line.

   Rendering: Google Maps–style basemaps from Esri — a Transportation
   (World Street Map) basemap with a Satellite (World Imagery) alternative,
   switchable via the bottom-left layer control. Overlaid on top is a navy
   route polyline with a subtle white
   dashed overlay + directional arrows (leaflet-polylinedecorator), custom
   pickup (navy circle + white center dot) and dropoff (red teardrop) markers,
   and styled zoom controls at the bottom-right.

   If routing fails the caller falls back to a straight line — this module just
   reports the error through the `onRouteError` option.
*/
(function (L, window) {
    'use strict';

    if (!window.TripRouteMap) {
        window.TripRouteMap = {};
    }
    var API = window.TripRouteMap;

    var SOLANO_CENTER = [16.52, 121.19];
    var SOLANO_SW = [16.45, 121.12];
    var SOLANO_NE = [16.59, 121.26];
    var SERVICE_RADIUS_DEG = 0.25;

    API.SOLANO_CENTER = SOLANO_CENTER;
    API.serviceBounds = L.latLngBounds(
        [SOLANO_SW[0] - SERVICE_RADIUS_DEG, SOLANO_SW[1] - SERVICE_RADIUS_DEG],
        [SOLANO_NE[0] + SERVICE_RADIUS_DEG, SOLANO_NE[1] + SERVICE_RADIUS_DEG]
    );

    function fetchWithTimeout(url, opts, ms) {
        var ctrl = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var timer = setTimeout(function () { if (ctrl) { ctrl.abort(); } }, ms || 15000);
        var o = opts || {};
        if (ctrl) { o.signal = ctrl.signal; }
        return fetch(url, o)
            .then(function (r) {
                if (!r.ok) { throw new Error('http ' + r.status); }
                return r.json();
            })
            .then(function (data) {
                clearTimeout(timer);
                return data;
            })
            .catch(function (err) {
                clearTimeout(timer);
                throw err;
            });
    }

    /* LRM router backed by the app's OSRM proxy (/route). LRM 3.2.12 exposes
       the base router as `L.Routing.OSRMv1` (the `L.Routing.Router` class is not
       present in the dist bundle), so we extend that and override `route()`. */
    var TriFairRouter = L.Routing.OSRMv1.extend({
        route: function (waypoints, callback, context) {
            var latLngs = [];
            for (var i = 0; i < waypoints.length; i++) {
                if (waypoints[i] && waypoints[i].latLng) { latLngs.push(waypoints[i].latLng); }
            }
            var start = latLngs[0];
            var end = latLngs[latLngs.length - 1];

            if (!start || !end) {
                callback.call(context, new Error('Missing route points'));
                return;
            }

            var qs = 'slat=' + start.lat.toFixed(6) + '&slng=' + start.lng.toFixed(6)
                + '&elat=' + end.lat.toFixed(6) + '&elng=' + end.lng.toFixed(6);

            fetchWithTimeout('/route?' + qs, { credentials: 'same-origin' }, 15000)
                .then(function (data) {
                    if (!data || !data.coords || data.coords.length < 2) {
                        throw new Error('Route unavailable');
                    }
                    var coordinates = data.coords.map(function (c) { return L.latLng(c[0], c[1]); });
                    callback.call(context, null, [{
                        name: 'route',
                        coordinates: coordinates,
                        instructions: [],
                        summary: {
                            totalDistance: data.distanceMeters || 0,
                            totalTime: data.durationSeconds || 0
                        },
                        inputWaypoints: waypoints,
                        waypoints: waypoints,
                        actualWaypoints: waypoints,
                        properties: { isSimplified: false }
                    }]);
                })
                .catch(function (err) {
                    callback.call(context, err);
                });
        }
    });

    /* Pickup marker: small filled navy circle with a white center dot (also the
       live tracking dot). */
    API.pickupIcon = function () {
        return L.divIcon({
            html: '<div class="marker-pickup"><span class="marker-core"></span></div>',
            className: '',
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });
    };

    /* Dropoff marker: red teardrop pin with a white center dot. */
    API.dropoffIcon = function () {
        return L.divIcon({
            html: '<div class="marker-dropoff">'
                + '<svg width="30" height="38" viewBox="0 0 30 38" aria-hidden="true" focusable="false">'
                + '<path d="M15 2 C 7.5 2 2 7.6 2 15 C 2 25 15 36 15 36 C 15 36 28 25 28 15 C 28 7.6 22.5 2 15 2 Z" fill="#dc2626" stroke="#ffffff" stroke-width="2"/>'
                + '<circle cx="15" cy="15" r="4.5" fill="#ffffff"/>'
                + '</svg></div>',
            className: '',
            iconSize: [30, 38],
            iconAnchor: [15, 37]
        });
    };

    API.create = function (elId, opts) {
        opts = opts || {};
        var mapEl = document.getElementById(elId);
        if (!mapEl || mapEl._tfReady) { return null; }

        var bounds = opts.serviceBounds || API.serviceBounds;

        var map = L.map(elId, {
            center: opts.center || SOLANO_CENTER,
            zoom: opts.zoom || 14,
            minZoom: 11,
            maxZoom: 18,
            zoomControl: false,
            attributionControl: true,
            zoomSnap: 1,
            zoomDelta: 1
        });

        setTimeout(function () { map.invalidateSize(); }, 100);
        setTimeout(function () { map.invalidateSize(); }, 500);
        window.addEventListener('resize', function () { map.invalidateSize(); });

        var loadingEl = document.getElementById(elId + 'Loading');
        var pendingTiles = 0;
        var loadingTimer = null;

        function showLoading() {
            if (loadingEl) { loadingEl.classList.remove('hidden'); }
            clearTimeout(loadingTimer);
            loadingTimer = setTimeout(hideLoading, 6000);
        }
        function hideLoading() {
            clearTimeout(loadingTimer);
            if (loadingEl) { loadingEl.classList.add('hidden'); }
        }

        function tileRetryLayer(urlTemplate, opts) {
            var defaults = {
                maxZoom: 20,
                updateWhenIdle: false,
                updateWhenZooming: true,
                keepBuffer: 2,
                crossOrigin: 'anonymous'
            };
            var merged = L.Util.extend({}, defaults, opts || {});
            var layer = L.tileLayer(urlTemplate, merged);

            layer.on('tileloadstart', function () {
                pendingTiles++;
                showLoading();
            });
            layer.on('tileload', function () {
                pendingTiles = Math.max(0, pendingTiles - 1);
                if (pendingTiles === 0) { hideLoading(); }
            });
            layer.on('tileerror', function (e) {
                var tile = e.tile;
                if (!tile) return;
                if (tile._tfRetries == null) { tile._tfRetries = 0; }
                if (tile._tfRetries < 3) {
                    tile._tfRetries++;
                    setTimeout(function () {
                        var src = tile.src;
                        if (src) { tile.src = ''; tile.src = src; }
                    }, 400 * tile._tfRetries);
                } else {
                    tile.style.opacity = '0';
                    pendingTiles = Math.max(0, pendingTiles - 1);
                    if (pendingTiles === 0) { hideLoading(); }
                }
            });

            return layer;
        }

        var transportationLayer = tileRetryLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; <a href="https://www.esri.com">Esri</a>, &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        });

        var satelliteLayer = tileRetryLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; <a href="https://www.esri.com">Esri</a>, &copy; <a href="https://www.arcgis.com/home/index.html">ArcGIS</a>'
        });

        transportationLayer.addTo(map);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        L.control.layers({
            'Transportation': transportationLayer,
            'Satellite': satelliteLayer
        }, null, { position: 'bottomleft', collapsed: true }).addTo(map);

        /* LRM computes the route but draws nothing itself (`styles: []`) so the
           module fully controls route-line lifecycle (validation clears,
           approximate fallbacks, reroutes). */
        var lrm = L.Routing.control({
            router: new TriFairRouter(),
            show: false,
            addWaypoints: false,
            routeWhileDragging: false,
            fitSelectedRoutes: false,
            showAlternatives: false,
            autoRoute: false,
            createMarker: function () { return null; },
            lineOptions: { styles: [] }
        }).addTo(map);

        var routeLine = null;
        var dashedOverlay = null;
        var arrowsLayer = null;

        function clearOverlays() {
            if (routeLine) { map.removeLayer(routeLine); routeLine = null; }
            if (dashedOverlay) { map.removeLayer(dashedOverlay); dashedOverlay = null; }
            if (arrowsLayer) { map.removeLayer(arrowsLayer); arrowsLayer = null; }
        }

        function drawRouteGeometry(coordinates) {
            clearOverlays();

            routeLine = L.polyline(coordinates, {
                color: '#0f2a4a',
                weight: 5,
                opacity: 0.9,
                lineCap: 'round',
                lineJoin: 'round'
            }).addTo(map);

            dashedOverlay = L.polyline(coordinates, {
                color: '#ffffff',
                weight: 2,
                opacity: 0.9,
                dashArray: '2 8',
                lineCap: 'round',
                lineJoin: 'round',
                interactive: false
            }).addTo(map);

            if (typeof L.polylineDecorator === 'function' && typeof L.Symbol === 'object') {
                arrowsLayer = L.polylineDecorator(coordinates, {
                    patterns: [{
                        offset: 0,
                        repeat: 160,
                        symbol: L.Symbol.arrowHead({
                            pixelSize: 9,
                            polygon: false,
                            pathOptions: { color: '#ffffff', weight: 2.5, opacity: 0.95 }
                        })
                    }]
                }).addTo(map);
            }
        }

        lrm.on('routeselected', function (e) {
            var route = e.route;
            if (!route || !route.coordinates || route.coordinates.length < 2) { return; }
            drawRouteGeometry(route.coordinates);
            if (opts.onRouteSelected) { opts.onRouteSelected(route); }
            if (api._fitNext !== false) {
                var pts = route.coordinates.slice();
                if (route.inputWaypoints) {
                    for (var i = 0; i < route.inputWaypoints.length; i++) {
                        var wp = route.inputWaypoints[i];
                        if (wp && wp.latLng) { pts.push(wp.latLng); }
                    }
                }
                api.fitBounds(pts);
            }
        });

        lrm.on('routingerror', function (e) {
            if (opts.onRouteError) {
                opts.onRouteError(e.error || new Error('Route unavailable'));
            }
        });

        var api = {
            map: map,
            bounds: bounds,
            _fitNext: true,
            clearOverlays: clearOverlays,
            route: function (startLatLng, endLatLng, fit) {
                this._fitNext = fit !== false;
                /* LRM's `route()` reads waypoints from its internal plan, so
                   set them explicitly before routing (the plan UI is disabled). */
                lrm.setWaypoints([startLatLng, endLatLng]);
                lrm.route();
            },
            fitBounds: function (latlngs) {
                if (latlngs && latlngs.length) {
                    var target = L.latLngBounds(latlngs);
                    if (!target.isValid()) return;
                    if (target.getNorth() === target.getSouth() && target.getEast() === target.getWest()) {
                        map.setView(target.getNorthWest(), 16, { animate: true, duration: 0.6 });
                        return;
                    }
                    if (bounds) {
                        var clamped = target.intersect(bounds);
                        if (clamped && clamped.isValid()) { target = clamped; }
                    }
                    map.fitBounds(target.pad(0.35), { maxZoom: 16, animate: true, duration: 0.6 });
                }
            },
            setView: function (latlng, zoom) {
                map.setView(latlng, zoom);
            },
            getMap: function () { return map; }
        };

        mapEl._tfReady = true;
        return api;
    };

    /* Auto-initialize static (display-only) maps for reusability — e.g. admin /
       TFRB trip-history views:
         <div class="map-shell" data-trip-route-map data-map-id="tripMap"
              data-mode="static"
              data-start-coords='[16.52,121.19]' data-end-coords='[16.53,121.20]'></div>
       Call TripRouteMap.autoInit(document) after the DOM is ready. */
    API.autoInit = function (root) {
        var containers = (root || document).querySelectorAll('[data-trip-route-map]');
        for (var i = 0; i < containers.length; i++) {
            var el = containers[i];
            var id = el.getAttribute('data-map-id');
            var mode = el.getAttribute('data-mode');
            var startRaw = el.getAttribute('data-start-coords');
            var endRaw = el.getAttribute('data-end-coords');
            if (mode !== 'static' || !id || !startRaw || !endRaw) { continue; }

            var start = parseCoords(startRaw);
            var end = parseCoords(endRaw);
            if (!start || !end) { continue; }

            var mapApi = API.create(id, {
                onRouteSelected: function (route) {
                    var summaryEl = document.getElementById(id + 'Summary');
                    if (summaryEl && route.summary) {
                        summaryEl.textContent = fmtDistance(route.summary.totalDistance)
                            + ' · ' + fmtDuration(route.summary.totalTime);
                    }
                },
                onRouteError: function () {
                    drawApprox(id, mapApi, start, end);
                }
            });
            if (!mapApi) { continue; }

            L.marker(start, { icon: API.pickupIcon(), zIndexOffset: 1000 }).addTo(mapApi.map);
            L.marker(end, { icon: API.dropoffIcon() }).addTo(mapApi.map);
            mapApi.route(start, end, true);
        }
    };

    function parseCoords(raw) {
        try {
            var arr = JSON.parse(raw);
            if (Array.isArray(arr) && arr.length === 2 && isFinite(arr[0]) && isFinite(arr[1])) {
                return L.latLng(arr[0], arr[1]);
            }
        } catch (e) { /* ignore */ }
        return null;
    }

    function drawApprox(id, mapApi, start, end) {
        mapApi.clearOverlays();
        L.polyline([start, end], {
            color: '#0f2a4a', weight: 4, opacity: 0.5, dashArray: '4 6', interactive: false
        }).addTo(mapApi.map);
        mapApi.fitBounds([start, end]);
        var noteEl = document.getElementById(id + 'Note');
        if (noteEl) {
            noteEl.hidden = false;
            noteEl.className = 'map-note';
            noteEl.textContent = 'Showing approximate route';
        }
    }

    function fmtDistance(m) {
        m = Number(m) || 0;
        return m >= 1000 ? (m / 1000).toFixed(1) + ' km' : Math.round(m) + ' m';
    }

    function fmtDuration(s) {
        s = Math.max(1, Math.round(Number(s) || 0));
        return s >= 3600
            ? Math.floor(s / 3600) + 'h ' + Math.floor((s % 3600) / 60) + 'm'
            : s >= 60
                ? Math.round(s / 60) + ' min'
                : s + ' sec';
    }
})(L, window);
