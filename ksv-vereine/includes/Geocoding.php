<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class Geocoding
{
    private const ORS_URL = 'https://api.openrouteservice.org/geocode/search';

    public function register(): void
    {
        add_action('rest_api_init', static function (): void {
            register_rest_route('ksv/v1', '/geocode', [
                'methods'             => 'GET',
                'callback'            => [self::class, 'rest_geocode_search'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'q' => [
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]);
        });
    }

    public static function build_address(int $post_id): string
    {
        $street = (string) get_post_meta($post_id, '_ksv_street', true);
        $zip    = (string) get_post_meta($post_id, '_ksv_zip', true);
        $city   = (string) get_post_meta($post_id, '_ksv_city', true);

        return self::format_address($street, $zip, $city);
    }

    public static function format_address(string $street, string $zip, string $city): string
    {
        $parts = array_filter([trim($street), trim($zip . ' ' . $city)]);

        return implode(', ', $parts);
    }

    public static function geocode_post(int $post_id): bool
    {
        $address = self::build_address($post_id);
        if ($address === '') {
            update_post_meta($post_id, '_ksv_geo_error', __('Keine Adresse für Geocoding vorhanden.', 'ksv-vereine'));

            return false;
        }

        $result = self::geocode($address);
        if ($result === null) {
            update_post_meta(
                $post_id,
                '_ksv_geo_error',
                __('Geocoding fehlgeschlagen. Bitte Adresse prüfen oder API-Key in den Einstellungen hinterlegen.', 'ksv-vereine')
            );

            return false;
        }

        update_post_meta($post_id, '_ksv_lat', (string) $result['lat']);
        update_post_meta($post_id, '_ksv_lng', (string) $result['lng']);
        delete_post_meta($post_id, '_ksv_geo_error');

        return true;
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    public static function geocode(string $query): ?array
    {
        $api_key = Settings::get_string('ors_api_key');
        if ($api_key === '') {
            return null;
        }

        $url = add_query_arg(
            [
                'text'         => $query,
                'size'         => 1,
                'boundary.country' => 'DE',
            ],
            self::ORS_URL
        );

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Authorization' => $api_key,
                'Accept'        => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (! is_array($body)) {
            return null;
        }

        $features = $body['features'] ?? [];
        if (! is_array($features) || $features === []) {
            return null;
        }

        $first = $features[0];
        if (! is_array($first)) {
            return null;
        }

        $coords = $first['geometry']['coordinates'] ?? null;
        if (! is_array($coords) || count($coords) < 2) {
            return null;
        }

        return [
            'lng' => (float) $coords[0],
            'lat' => (float) $coords[1],
        ];
    }

    public static function rest_geocode_search(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $query  = (string) $request->get_param('q');
        $result = self::geocode($query);

        if ($result === null) {
            return new \WP_Error(
                'ksv_geocode_failed',
                __('Ort konnte nicht gefunden werden.', 'ksv-vereine'),
                ['status' => 404]
            );
        }

        return new \WP_REST_Response($result);
    }

    public static function haversine_km(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth_radius = 6371.0;
        $d_lat        = deg2rad($lat2 - $lat1);
        $d_lng        = deg2rad($lng2 - $lng1);
        $a            = sin($d_lat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($d_lng / 2) ** 2;

        return $earth_radius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
