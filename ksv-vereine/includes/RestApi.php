<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class RestApi
{
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('ksv/v1', '/disziplinen', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_disziplinen'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('ksv/v1', '/vereine', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_vereine'],
            'permission_callback' => '__return_true',
            'args'                => [
                'search' => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'disziplinen' => [
                    'type'              => 'array',
                    'sanitize_callback' => static function ($param): array {
                        if (! is_array($param)) {
                            return [];
                        }

                        return array_map('sanitize_title', $param);
                    },
                ],
            ],
        ]);
    }

    public function get_disziplinen(\WP_REST_Request $request): \WP_REST_Response
    {
        unset($request);

        return new \WP_REST_Response([
            'disziplinen' => Taxonomy::get_all_for_frontend(),
        ]);
    }

    public function get_vereine(\WP_REST_Request $request): \WP_REST_Response
    {
        $search      = trim((string) $request->get_param('search'));
        $disciplines = $request->get_param('disziplinen');
        $disciplines = is_array($disciplines) ? $disciplines : [];

        $items        = VereinRepository::get_vereine($search, $disciplines);
        $search_point = $search !== '' ? Geocoding::geocode($search) : null;

        return new \WP_REST_Response([
            'vereine'       => $items,
            'search_found'  => $search === '' || $search_point !== null,
            'search_point'  => $search_point,
        ]);
    }
}
