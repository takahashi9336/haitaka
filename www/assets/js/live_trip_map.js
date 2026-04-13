/* global google */
(function () {
  'use strict';

  // #region agent log
  function ltDbg(hypothesisId, message, data) {
    try {
      fetch('http://127.0.0.1:7242/ingest/a55992ae-4f4b-4b96-a011-9dd68f0025d2', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '572306' },
        body: JSON.stringify({
          sessionId: '572306',
          runId: 'pre-fix',
          hypothesisId: hypothesisId,
          location: 'www/assets/js/live_trip_map.js',
          message: message,
          data: data || {},
          timestamp: Date.now(),
        }),
      }).catch(function () {});
    } catch (e) {}
  }
  // #endregion agent log

  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  function waitForGoogleMaps(maxWaitMs) {
    var start = Date.now();
    return new Promise(function (resolve, reject) {
      (function tick() {
        if (window.google && google.maps && typeof google.maps.Map === 'function') {
          resolve();
          return;
        }
        if (Date.now() - start > maxWaitMs) {
          reject(new Error('Google Maps JS API not available'));
          return;
        }
        setTimeout(tick, 120);
      })();
    });
  }

  function safeFloat(v) {
    var n = parseFloat(String(v || '').trim());
    return Number.isFinite(n) ? n : null;
  }

  function markerKey(type, id) {
    return String(type) + ':' + String(id);
  }

  function scrollToTimelineItem(type, id) {
    var selector = '';
    if (type === 'timeline') {
      selector = '.timeline-row.timeline-item[data-lt-id="' + String(id) + '"]';
    }
    if (!selector) return;
    var el = document.querySelector(selector);
    if (!el) return;
    el.scrollIntoView({ block: 'center', behavior: 'smooth' });
    el.classList.add('ring-2', 'ring-sky-300');
    setTimeout(function () {
      el.classList.remove('ring-2', 'ring-sky-300');
    }, 1200);
  }

  onReady(function () {
    var mapEl = document.getElementById('ltMap');
    var data = window.__LT_MAP_DATA__ || null;
    // #region agent log
    ltDbg('H2', 'bootstrap', {
      hasMapEl: !!mapEl,
      hasData: !!data,
      tripId: data && data.tripId ? data.tripId : null,
      venue: data && data.venue ? { has: true, lat: data.venue.lat, lng: data.venue.lng } : { has: false },
      counts: data
        ? {
            hotels: Array.isArray(data.hotels) ? data.hotels.length : null,
            destinations: Array.isArray(data.destinations) ? data.destinations.length : null,
            timeline: Array.isArray(data.timeline) ? data.timeline.length : null,
            transportLegs: Array.isArray(data.transportLegs) ? data.transportLegs.length : null,
          }
        : null,
    });
    // #endregion agent log
    if (!mapEl || !data) return;

    waitForGoogleMaps(12000)
      .then(function () {
        // #region agent log
        ltDbg('H2', 'googleMapsReady', {
          hasGoogle: !!window.google,
          hasMap: !!(window.google && google.maps && typeof google.maps.Map === 'function'),
          hasGeometry: !!(window.google && google.maps && google.maps.geometry),
        });
        // #endregion agent log
        var markersByKey = new Map();
        var layers = {
          venue: [],
          hotels: [],
          destinations: [],
          timeline: [],
          transport: [],
        };

        var theme = String((data && data.theme) || '#10b981');
        var defaultCenter = { lat: 35.681236, lng: 139.767125 }; // Tokyo
        if (data.venue && typeof data.venue.lat === 'number' && typeof data.venue.lng === 'number') {
          defaultCenter = { lat: data.venue.lat, lng: data.venue.lng };
        } else if (Array.isArray(data.destinations) && data.destinations[0]) {
          var d0 = data.destinations[0];
          if (typeof d0.lat === 'number' && typeof d0.lng === 'number') defaultCenter = { lat: d0.lat, lng: d0.lng };
        } else if (Array.isArray(data.hotels) && data.hotels[0]) {
          var h0 = data.hotels[0];
          if (typeof h0.lat === 'number' && typeof h0.lng === 'number') defaultCenter = { lat: h0.lat, lng: h0.lng };
        }

        var map = new google.maps.Map(mapEl, {
          center: defaultCenter,
          zoom: 12,
          gestureHandling: 'greedy',
          mapTypeControl: false,
          streetViewControl: false,
          fullscreenControl: false,
        });

        function createMarker(opts) {
          var m = new google.maps.Marker({
            map: map,
            position: opts.position,
            title: opts.title || '',
            label: opts.label || undefined,
            icon: opts.icon || undefined,
            zIndex: opts.zIndex || undefined,
          });
          if (opts.key) markersByKey.set(opts.key, m);
          if (opts.layer && layers[opts.layer]) layers[opts.layer].push(m);
          if (opts.onClick) {
            m.addListener('click', opts.onClick);
          }
          return m;
        }

        function setLayerVisible(layerName, visible) {
          (layers[layerName] || []).forEach(function (obj) {
            if (obj && typeof obj.setMap === 'function') {
              obj.setMap(visible ? map : null);
            }
          });
        }

        function hideAllLayers() {
          Object.keys(layers).forEach(function (k) {
            setLayerVisible(k, false);
          });
        }

        function fitToMarkers(list) {
          if (!list || list.length === 0) return;
          var bounds = new google.maps.LatLngBounds();
          var added = 0;
          list.forEach(function (m) {
            if (m && m.getPosition) {
              bounds.extend(m.getPosition());
              added++;
            }
          });
          if (added > 0) {
            map.fitBounds(bounds, { top: 40, right: 30, bottom: 40, left: 30 });
            // 近距離の複数点だと寄りすぎるので、上限ズームを設ける
            var MAX_FIT_ZOOM = 13;
            google.maps.event.addListenerOnce(map, 'idle', function () {
              try {
                if (typeof map.getZoom === 'function' && typeof map.setZoom === 'function') {
                  var z = map.getZoom();
                  if (typeof z === 'number' && z > MAX_FIT_ZOOM) map.setZoom(MAX_FIT_ZOOM);
                }
              } catch (e) {}
            });
          }
        }

        var info = new google.maps.InfoWindow();
        function focusMarker(key, zoom) {
          var m = markersByKey.get(key);
          if (!m) return;
          map.panTo(m.getPosition());
          if (zoom) map.setZoom(zoom);
          var title = m.getTitle && m.getTitle();
          if (title) {
            info.setContent('<div style="font-weight:700;">' + String(title) + '</div>');
            info.open({ map: map, anchor: m });
          }
        }

        // Venue
        if (data.venue && typeof data.venue.lat === 'number' && typeof data.venue.lng === 'number') {
          createMarker({
            key: markerKey('venue', data.venue.id || 'venue'),
            layer: 'venue',
            position: { lat: data.venue.lat, lng: data.venue.lng },
            title: data.venue.name || '会場',
            icon: {
              path: google.maps.SymbolPath.CIRCLE,
              scale: 9,
              fillColor: theme,
              fillOpacity: 1,
              strokeColor: '#ffffff',
              strokeWeight: 2,
            },
            zIndex: 1000,
            onClick: function () {
              scrollToTimelineItem('timeline', 0);
            },
          });
        }

        // Hotels
        (Array.isArray(data.hotels) ? data.hotels : []).forEach(function (h) {
          if (typeof h.lat !== 'number' || typeof h.lng !== 'number') return;
          createMarker({
            key: markerKey('hotel', h.id),
            layer: 'hotels',
            position: { lat: h.lat, lng: h.lng },
            title: h.name || '宿泊',
            label: { text: 'H', color: '#111827', fontSize: '12px', fontWeight: '700' },
          });
        });

        // Destinations
        (Array.isArray(data.destinations) ? data.destinations : []).forEach(function (d) {
          if (typeof d.lat !== 'number' || typeof d.lng !== 'number') return;
          createMarker({
            key: markerKey('destination', d.id),
            layer: 'destinations',
            position: { lat: d.lat, lng: d.lng },
            title: d.name || '目的地',
            label: { text: 'D', color: '#111827', fontSize: '12px', fontWeight: '700' },
          });
        });

        // Timeline
        (Array.isArray(data.timeline) ? data.timeline : []).forEach(function (t) {
          if (typeof t.lat !== 'number' || typeof t.lng !== 'number') return;
          createMarker({
            key: markerKey('timeline', t.id),
            layer: 'timeline',
            position: { lat: t.lat, lng: t.lng },
            title: (t.label || '') + (t.location_label ? ' · ' + t.location_label : ''),
            label: { text: '●', color: theme, fontSize: '14px', fontWeight: '900' },
            onClick: function () {
              scrollToTimelineItem('timeline', t.id);
            },
          });
        });

        // #region agent log
        ltDbg('H1', 'layersAfterMarkerCreate', {
          venue: layers.venue.length,
          hotels: layers.hotels.length,
          destinations: layers.destinations.length,
          timeline: layers.timeline.length,
          transport: layers.transport.length,
        });
        // #endregion agent log

        // Default view
        hideAllLayers();
        setLayerVisible('venue', true);
        setLayerVisible('timeline', true);
        fitToMarkers([].concat(layers.venue, layers.timeline));

        function modeFromTransportType(tt) {
          var s = String(tt || '');
          if (s.match(/徒歩/)) return 'walking';
          if (s.match(/自転車/)) return 'bicycling';
          if (s.match(/車|タクシー|レンタカー/)) return 'driving';
          if (s.match(/電車|新幹線|バス|地下鉄|モノレール|飛行機/)) return 'transit';
          return 'transit';
        }

        var transportLoaded = false;
        var transportLoading = null;
        function ensureTransportRoutes() {
          // #region agent log
          ltDbg('H3', 'ensureTransportRoutes_enter', {
            transportLoaded: transportLoaded,
            hasTransportLoading: !!transportLoading,
            legsCount: Array.isArray(data.transportLegs) ? data.transportLegs.length : null,
            currentPolylines: layers.transport.length,
          });
          // #endregion agent log
          // "Loaded" should mean we actually have something to show.
          // If previous attempt resulted in 0 polylines, allow retry on next click.
          if (transportLoaded && layers.transport.length > 0) return Promise.resolve(true);
          if (transportLoading) return transportLoading;

          var legs = Array.isArray(data.transportLegs) ? data.transportLegs : [];
          if (legs.length === 0) {
            transportLoaded = true;
            return Promise.resolve(true);
          }

          // reset state for (re)try
          transportLoaded = false;

          transportLoading = Promise.all(
            legs.map(function (leg) {
              var id = leg && leg.id ? leg.id : 0;
              var origin = (leg && leg.departure) || '';
              var destination = (leg && leg.arrival) || '';
              if (!origin || !destination) {
                // #region agent log
                ltDbg('H3', 'transportLeg_skip_empty', {
                  legId: id,
                  originLen: origin ? origin.length : 0,
                  destinationLen: destination ? destination.length : 0,
                });
                // #endregion agent log
                return Promise.resolve(null);
              }

              var mode = modeFromTransportType(leg.transport_type);
              var depDate = (leg && leg.departure_date) || '';
              var url =
                '/live_trip/api/directions_polyline.php?origin=' +
                encodeURIComponent(origin) +
                '&destination=' +
                encodeURIComponent(destination) +
                '&mode=' +
                encodeURIComponent(mode) +
                (depDate ? '&departure_date=' + encodeURIComponent(depDate) : '') +
                '&debug=1';

              // #region agent log
              ltDbg('H3', 'transportFetchAttempt_ltDbg', {
                legId: id,
                originLen: origin.length,
                destinationLen: destination.length,
                mode: mode,
                hasDepDate: !!depDate,
              });
              // #endregion agent log

              // #region agent log
              try {
                fetch(
                  '/live_trip/api/debug_log.php?sessionId=572306&msg=transportFetchAttempt&layer=transport&legId=' +
                    encodeURIComponent(String(id)) +
                    '&originLen=' +
                    encodeURIComponent(String(origin.length)) +
                    '&destinationLen=' +
                    encodeURIComponent(String(destination.length)) +
                    '&mode=' +
                    encodeURIComponent(String(mode)),
                  { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } }
                ).catch(function () {});
              } catch (e) {}
              // #endregion agent log

              return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) {
                  return r
                    .text()
                    .then(function (t) {
                      var json = null;
                      try {
                        json = JSON.parse(t);
                      } catch (e) {
                        // #region agent log
                        ltDbg('H3', 'transportFetch_parseError', {
                          legId: id,
                          httpStatus: r && typeof r.status === 'number' ? r.status : null,
                          contentType: r && r.headers && typeof r.headers.get === 'function' ? String(r.headers.get('content-type') || '') : null,
                          textHead: String(t || '').slice(0, 200),
                        });
                        // #endregion agent log
                        return null;
                      }
                      // #region agent log
                      ltDbg('H3', 'transportFetch_http', {
                        legId: id,
                        httpStatus: r && typeof r.status === 'number' ? r.status : null,
                        ok: !!(r && r.ok),
                      });
                      // #endregion agent log
                      return json;
                    })
                    .catch(function () {
                      return null;
                    });
                })
                .then(function (json) {
                  // #region agent log
                  ltDbg('H3', 'transportFetchJson_ltDbg', {
                    legId: id,
                    ok: !!(json && json.status === 'ok' && json.route && json.route.polyline),
                    status: json && json.status ? String(json.status) : null,
                    message: json && json.message ? String(json.message) : null,
                    debugDirectionsStatus:
                      json && json.debug && json.debug.directions && json.debug.directions.status
                        ? String(json.debug.directions.status)
                        : null,
                    debugDirectionsErrorMessage:
                      json && json.debug && json.debug.directions && json.debug.directions.error_message
                        ? String(json.debug.directions.error_message)
                        : null,
                    hasRoute: !!(json && json.route),
                    hasPolyline: !!(json && json.route && json.route.polyline),
                  });
                  // #endregion agent log
                  // #region agent log
                  try {
                    fetch(
                      '/live_trip/api/debug_log.php?sessionId=572306&msg=transportFetchResult&layer=transport&legId=' +
                        encodeURIComponent(String(id)) +
                        '&originLen=' +
                        encodeURIComponent(String(origin.length)) +
                        '&destinationLen=' +
                        encodeURIComponent(String(destination.length)) +
                        '&mode=' +
                        encodeURIComponent(String(mode)),
                      { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } }
                    ).catch(function () {});
                  } catch (e) {}
                  // #endregion agent log
                  if (!json || json.status !== 'ok' || !json.route || !json.route.polyline) return null;
                  if (!google.maps.geometry || !google.maps.geometry.encoding) return null;
                  var path = google.maps.geometry.encoding.decodePath(String(json.route.polyline));
                  var poly = new google.maps.Polyline({
                    path: path,
                    strokeColor: theme,
                    strokeOpacity: 0.85,
                    strokeWeight: 4,
                    map: null,
                  });
                  poly.__lt_leg_id = id;
                  layers.transport.push(poly);
                  return poly;
                })
                .catch(function () {
                  return null;
                });
            })
          ).then(function () {
            // Mark loaded only if we actually drew any route.
            transportLoaded = layers.transport.length > 0;
            transportLoading = null;
            return true;
          }).catch(function () {
            transportLoaded = false;
            transportLoading = null;
            return false;
          });

          return transportLoading;
        }

        // Layer buttons
        document.querySelectorAll('[data-lt-map-layer]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var layer = this.getAttribute('data-lt-map-layer');
            // #region agent log
            ltDbg('H3', 'layerButton', {
              layer: layer,
              layerCounts: {
                venue: layers.venue.length,
                hotels: layers.hotels.length,
                destinations: layers.destinations.length,
                timeline: layers.timeline.length,
                transport: layers.transport.length,
              },
            });
            // #endregion agent log
            // #region agent log
            try {
              fetch('/live_trip/api/debug_log.php?sessionId=572306&msg=layerButton&layer=' + encodeURIComponent(layer || ''), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
              }).catch(function () {});
            } catch (e) {}
            // #endregion agent log
            if (!layer) return;
            if (layer === 'transport') {
              ensureTransportRoutes().finally(function () {
                hideAllLayers();
                setLayerVisible('transport', true);
                setLayerVisible('venue', true);
                setLayerVisible('timeline', true);
              });
              return;
            }
            hideAllLayers();
            if (layers[layer]) setLayerVisible(layer, true);
            fitToMarkers(layers[layer]);
          });
        });

        // Tab hook
        if (typeof window.switchTab === 'function' && !window.__lt_switchTabWrapped) {
          var original = window.switchTab;
          window.switchTab = function (tab) {
            original(tab);
            // #region agent log
            ltDbg('H3', 'switchTabHook', { tab: tab });
            // #endregion agent log
            try {
              if (tab === 'destination') {
                hideAllLayers();
                setLayerVisible('destinations', true);
                setLayerVisible('venue', true);
                fitToMarkers([].concat(layers.venue, layers.destinations));
              } else if (tab === 'hotel') {
                hideAllLayers();
                setLayerVisible('hotels', true);
                setLayerVisible('venue', true);
                fitToMarkers([].concat(layers.venue, layers.hotels));
              } else if (tab === 'timeline') {
                hideAllLayers();
                setLayerVisible('timeline', true);
                setLayerVisible('venue', true);
                fitToMarkers([].concat(layers.venue, layers.timeline));
              } else if (tab === 'transport') {
                ensureTransportRoutes().finally(function () {
                  hideAllLayers();
                  setLayerVisible('transport', true);
                  setLayerVisible('venue', true);
                });
              } else {
                // summary/others
                hideAllLayers();
                setLayerVisible('venue', true);
                setLayerVisible('timeline', true);
              }
            } catch (e) {}
          };
          window.__lt_switchTabWrapped = true;
        }

        // Timeline click -> map
        document.addEventListener('click', function (e) {
          var row = e.target && e.target.closest ? e.target.closest('.timeline-row.timeline-item') : null;
          if (row) {
            if (e.target.closest('button') || e.target.closest('form') || e.target.closest('a') || e.target.closest('input') || e.target.closest('select')) return;
            var id = parseInt(row.getAttribute('data-lt-id') || '0', 10) || 0;
            var lat = safeFloat(row.getAttribute('data-lt-lat'));
            var lng = safeFloat(row.getAttribute('data-lt-lng'));
            hideAllLayers();
            setLayerVisible('timeline', true);
            setLayerVisible('venue', true);
            if (id) focusMarker(markerKey('timeline', id), 16);
            if (!id && lat !== null && lng !== null) {
              map.panTo({ lat: lat, lng: lng });
              map.setZoom(16);
            }
            return;
          }

          var ev = e.target && e.target.closest ? e.target.closest('.lt-event[data-lt-type="timeline"]') : null;
          if (!ev) return;
          if (e.target.closest('button') || e.target.closest('form') || e.target.closest('a')) return;
          var id2 = parseInt(ev.getAttribute('data-lt-id') || '0', 10) || 0;
          hideAllLayers();
          setLayerVisible('timeline', true);
          setLayerVisible('venue', true);
          if (id2) focusMarker(markerKey('timeline', id2), 16);
        });
      })
      .catch(function () {
        // no-op（キー未設定や読み込み失敗時）
        // #region agent log
        ltDbg('H2', 'googleMapsNotReady', {});
        // #endregion agent log
      });
  });
})();

