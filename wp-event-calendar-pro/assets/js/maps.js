/**
 * Maps Handler JavaScript
 * Universal map handler for multiple providers
 */

(function($) {
    'use strict';

    const WECPMaps = {
        /**
         * Initialize
         */
        init: function() {
            if (typeof wecpMaps === 'undefined') {
                return;
            }

            this.provider = wecpMaps.provider;
            this.initMaps();
        },

        /**
         * Initialize all maps on page
         */
        initMaps: function() {
            const self = this;

            $('.wecp-event-map').each(function() {
                const $map = $(this);
                const data = {
                    lat: parseFloat($map.data('lat')),
                    lng: parseFloat($map.data('lng')),
                    zoom: parseInt($map.data('zoom')) || 15,
                    venue: $map.data('venue') || '',
                    address: $map.data('address') || ''
                };

                self.createMap($map[0], data);
            });
        },

        /**
         * Create map based on provider
         */
        createMap: function(container, data) {
            switch (this.provider) {
                case 'google':
                    this.createGoogleMap(container, data);
                    break;
                case 'openstreetmap':
                    this.createLeafletMap(container, data);
                    break;
                case 'mapbox':
                    this.createMapboxMap(container, data);
                    break;
                case 'here':
                    this.createHereMap(container, data);
                    break;
                case 'bing':
                    this.createBingMap(container, data);
                    break;
            }
        },

        /**
         * Google Maps
         */
        createGoogleMap: function(container, data) {
            if (typeof google === 'undefined') return;

            const position = { lat: data.lat, lng: data.lng };

            const map = new google.maps.Map(container, {
                zoom: data.zoom,
                center: position
            });

            const marker = new google.maps.Marker({
                position: position,
                map: map,
                title: data.venue
            });

            if (data.venue) {
                const infoWindow = new google.maps.InfoWindow({
                    content: '<div class="wecp-map-info"><strong>' + data.venue + '</strong><br>' + data.address + '</div>'
                });

                marker.addListener('click', function() {
                    infoWindow.open(map, marker);
                });
            }
        },

        /**
         * OpenStreetMap with Leaflet (FREE!)
         */
        createLeafletMap: function(container, data) {
            if (typeof L === 'undefined') return;

            const map = L.map(container).setView([data.lat, data.lng], data.zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            const marker = L.marker([data.lat, data.lng]).addTo(map);

            if (data.venue) {
                marker.bindPopup('<div class="wecp-map-info"><strong>' + data.venue + '</strong><br>' + data.address + '</div>');
            }
        },

        /**
         * Mapbox GL
         */
        createMapboxMap: function(container, data) {
            if (typeof mapboxgl === 'undefined') return;

            mapboxgl.accessToken = wecpMaps.apiKey;

            const map = new mapboxgl.Map({
                container: container,
                style: 'mapbox://styles/mapbox/streets-v12',
                center: [data.lng, data.lat],
                zoom: data.zoom
            });

            new mapboxgl.Marker()
                .setLngLat([data.lng, data.lat])
                .setPopup(
                    new mapboxgl.Popup().setHTML(
                        '<div class="wecp-map-info"><strong>' + data.venue + '</strong><br>' + data.address + '</div>'
                    )
                )
                .addTo(map);
        },

        /**
         * HERE Maps
         */
        createHereMap: function(container, data) {
            if (typeof H === 'undefined') return;

            const platform = new H.service.Platform({
                apikey: wecpMaps.apiKey
            });

            const defaultLayers = platform.createDefaultLayers();

            const map = new H.Map(
                container,
                defaultLayers.vector.normal.map,
                {
                    zoom: data.zoom,
                    center: { lat: data.lat, lng: data.lng }
                }
            );

            const behavior = new H.mapevents.Behavior(new H.mapevents.MapEvents(map));
            const ui = H.ui.UI.createDefault(map, defaultLayers);

            const marker = new H.map.Marker({ lat: data.lat, lng: data.lng });
            map.addObject(marker);

            if (data.venue) {
                marker.addEventListener('tap', function() {
                    const bubble = new H.ui.InfoBubble({ lat: data.lat, lng: data.lng }, {
                        content: '<div class="wecp-map-info"><strong>' + data.venue + '</strong><br>' + data.address + '</div>'
                    });
                    ui.addBubble(bubble);
                });
            }
        },

        /**
         * Bing Maps
         */
        createBingMap: function(container, data) {
            if (typeof Microsoft === 'undefined') return;

            const map = new Microsoft.Maps.Map(container, {
                credentials: wecpMaps.apiKey,
                center: new Microsoft.Maps.Location(data.lat, data.lng),
                zoom: data.zoom
            });

            const pushpin = new Microsoft.Maps.Pushpin(map.getCenter(), {
                title: data.venue
            });

            map.entities.push(pushpin);

            if (data.venue) {
                const infobox = new Microsoft.Maps.Infobox(map.getCenter(), {
                    title: data.venue,
                    description: data.address,
                    visible: false
                });

                infobox.setMap(map);

                Microsoft.Maps.Events.addHandler(pushpin, 'click', function() {
                    infobox.setOptions({ visible: true });
                });
            }
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        WECPMaps.init();
    });

    // Expose globally
    window.WECPMaps = WECPMaps;

})(jQuery);
