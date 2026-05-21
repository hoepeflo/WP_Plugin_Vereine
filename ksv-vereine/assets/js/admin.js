(function ($) {
    'use strict';

    let frame;

    $('#ksv_logo_select').on('click', function (e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Logo auswählen',
            button: { text: 'Übernehmen' },
            multiple: false,
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#ksv_logo_id').val(attachment.id);
            const url = attachment.sizes && attachment.sizes.thumbnail
                ? attachment.sizes.thumbnail.url
                : attachment.url;
            $('.ksv-logo-preview').html(
                '<img src="' + url + '" alt="" width="100" height="100" />'
            );
        });

        frame.open();
    });

    $('#ksv_logo_remove').on('click', function (e) {
        e.preventDefault();
        $('#ksv_logo_id').val('');
        $('.ksv-logo-preview').html(
            '<span class="ksv-logo-placeholder">Kein Logo</span>'
        );
    });

    const $manual = $('#ksv_manual_coords');
    const $picker = $('#ksv-coords-picker');
    const $auto = $('#ksv-coords-auto');

    if (!$manual.length || typeof L === 'undefined' || !window.ksvAdminMap) {
        return;
    }

    let map = null;
    let marker = null;
    const cfg = window.ksvAdminMap;

    function parseCoord(value, fallback) {
        const normalized = String(value).replace(',', '.').trim();
        const n = parseFloat(normalized);
        return Number.isFinite(n) ? n : fallback;
    }

    function formatCoord(value) {
        return value.toFixed(6);
    }

    function updateCoordinateFields(lat, lng) {
        $('#ksv_lat').val(formatCoord(lat));
        $('#ksv_lng').val(formatCoord(lng));
        $('#ksv-coords-display').text(formatCoord(lat) + ', ' + formatCoord(lng));
    }

    function getInitialPosition() {
        const lat = parseCoord($('#ksv_lat').val(), cfg.lat);
        const lng = parseCoord($('#ksv_lng').val(), cfg.lng);
        const hasStored = $('#ksv_lat').val() !== '' && $('#ksv_lng').val() !== '';

        return {
            lat,
            lng,
            zoom: hasStored ? 14 : cfg.zoom,
        };
    }

    function setMarkerPosition(latLng) {
        if (!marker) {
            return;
        }
        marker.setLatLng(latLng);
        updateCoordinateFields(latLng.lat, latLng.lng);
    }

    function initMap() {
        if (map) {
            return;
        }

        const initial = getInitialPosition();
        const mapEl = document.getElementById('ksv-coords-map');

        if (!mapEl) {
            return;
        }

        map = L.map(mapEl).setView([initial.lat, initial.lng], initial.zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);

        marker = L.marker([initial.lat, initial.lng], { draggable: true }).addTo(map);

        marker.on('dragend', function () {
            const pos = marker.getLatLng();
            updateCoordinateFields(pos.lat, pos.lng);
        });

        map.on('click', function (event) {
            setMarkerPosition(event.latlng);
        });

        if ($('#ksv_lat').val() === '' && $('#ksv_lng').val() === '') {
            updateCoordinateFields(initial.lat, initial.lng);
        }

        window.setTimeout(function () {
            map.invalidateSize();
        }, 150);
    }

    function toggleCoordinateMode() {
        const manual = $manual.is(':checked');

        $picker.prop('hidden', !manual);
        $auto.prop('hidden', manual);

        if (manual) {
            initMap();
            window.setTimeout(function () {
                if (map) {
                    map.invalidateSize();
                }
            }, 200);
        }
    }

    $manual.on('change', toggleCoordinateMode);
    toggleCoordinateMode();
})(jQuery);
