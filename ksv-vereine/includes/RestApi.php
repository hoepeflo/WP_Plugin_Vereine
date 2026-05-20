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

    public function get_vereine(\WP_REST_Request $request): \WP_REST_Response
    {
        $search      = trim((string) $request->get_param('search'));
        $disciplines = $request->get_param('disziplinen');
        $disciplines = is_array($disciplines) ? $disciplines : [];

        $posts = $this->query_posts($disciplines);
        $items = array_map([$this, 'format_post'], $posts);

        $search_point = null;
        if ($search !== '') {
            $search_point = Geocoding::geocode($search);
        }

        if ($search_point !== null) {
            usort($items, static function (array $a, array $b) use ($search_point): int {
                $a_has = $a['lat'] !== null && $a['lng'] !== null;
                $b_has = $b['lat'] !== null && $b['lng'] !== null;

                if ($a_has && ! $b_has) {
                    return -1;
                }
                if (! $a_has && $b_has) {
                    return 1;
                }
                if (! $a_has && ! $b_has) {
                    return strcasecmp((string) $a['name'], (string) $b['name']);
                }

                $dist_a = Geocoding::haversine_km(
                    $search_point['lat'],
                    $search_point['lng'],
                    (float) $a['lat'],
                    (float) $a['lng']
                );
                $dist_b = Geocoding::haversine_km(
                    $search_point['lat'],
                    $search_point['lng'],
                    (float) $b['lat'],
                    (float) $b['lng']
                );

                return $dist_a <=> $dist_b;
            });
        } else {
            usort($items, static fn (array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name']));
        }

        return new \WP_REST_Response([
            'vereine'       => $items,
            'search_found'  => $search === '' || $search_point !== null,
            'search_point'  => $search_point,
        ]);
    }

    /**
     * @param list<string> $discipline_slugs
     * @return list<\WP_Post>
     */
    private function query_posts(array $discipline_slugs): array
    {
        $tax_query = [];
        if ($discipline_slugs !== []) {
            $tax_query[] = [
                'taxonomy' => Taxonomy::SLUG,
                'field'    => 'slug',
                'terms'    => $discipline_slugs,
                'operator' => 'IN',
            ];
        }

        $query = new \WP_Query([
            'post_type'      => PostType::SLUG,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'meta_query'     => [
                [
                    'key'     => '_ksv_active',
                    'value'   => '1',
                    'compare' => '=',
                ],
            ],
            'tax_query'      => $tax_query,
        ]);

        $posts = $query->posts;
        if (! is_array($posts)) {
            return [];
        }

        return array_values(array_filter($posts, static fn ($p): bool => $p instanceof \WP_Post));
    }

    /**
     * @return array<string, mixed>
     */
    private function format_post(\WP_Post $post): array
    {
        $logo_id  = (int) get_post_meta($post->ID, '_ksv_logo_id', true);
        $logo_url = $logo_id > 0 ? wp_get_attachment_image_url($logo_id, 'thumbnail') : '';
        if (! is_string($logo_url) || $logo_url === '') {
            $placeholder_id = Settings::get_int('placeholder_logo_id');
            $logo_url       = $placeholder_id > 0
                ? (string) (wp_get_attachment_image_url($placeholder_id, 'thumbnail') ?: '')
                : KSV_VEREINE_URL . 'assets/images/placeholder-logo.svg';
        }

        $terms = wp_get_post_terms($post->ID, Taxonomy::SLUG);
        $tags  = [];
        if (is_array($terms)) {
            foreach ($terms as $term) {
                if ($term instanceof \WP_Term) {
                    $tags[] = [
                        'slug' => $term->slug,
                        'name' => $term->name,
                    ];
                }
            }
        }

        $lat = get_post_meta($post->ID, '_ksv_lat', true);
        $lng = get_post_meta($post->ID, '_ksv_lng', true);

        $street  = (string) get_post_meta($post->ID, '_ksv_street', true);
        $zip     = (string) get_post_meta($post->ID, '_ksv_zip', true);
        $city    = (string) get_post_meta($post->ID, '_ksv_city', true);
        $website = (string) get_post_meta($post->ID, '_ksv_website', true);

        return [
            'id'          => $post->ID,
            'name'        => get_the_title($post),
            'street'      => $street,
            'zip'         => $zip,
            'city'        => $city,
            'address'     => Geocoding::format_address($street, $zip, $city),
            'website'     => $website,
            'logo_url'    => $logo_url,
            'lat'         => $lat !== '' ? (float) $lat : null,
            'lng'         => $lng !== '' ? (float) $lng : null,
            'disziplinen' => $tags,
        ];
    }
}
