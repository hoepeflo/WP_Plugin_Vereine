<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class PostType
{
    public const SLUG = 'ksv_verein';

    public function register(): void
    {
        add_action('init', [$this, 'register_post_type']);
        add_filter('manage_' . self::SLUG . '_posts_columns', [$this, 'admin_columns']);
        add_action('manage_' . self::SLUG . '_posts_custom_column', [$this, 'admin_column_content'], 10, 2);
    }

    public function register_post_type(): void
    {
        register_post_type(self::SLUG, [
            'labels'              => [
                'name'               => __('Vereine', 'ksv-vereine'),
                'singular_name'      => __('Verein', 'ksv-vereine'),
                'add_new'            => __('Neuer Verein', 'ksv-vereine'),
                'add_new_item'       => __('Neuen Verein hinzufügen', 'ksv-vereine'),
                'edit_item'          => __('Verein bearbeiten', 'ksv-vereine'),
                'new_item'           => __('Neuer Verein', 'ksv-vereine'),
                'view_item'          => __('Verein ansehen', 'ksv-vereine'),
                'search_items'       => __('Vereine suchen', 'ksv-vereine'),
                'not_found'          => __('Keine Vereine gefunden.', 'ksv-vereine'),
                'not_found_in_trash' => __('Keine Vereine im Papierkorb.', 'ksv-vereine'),
                'menu_name'          => __('Vereine', 'ksv-vereine'),
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-location-alt',
            'menu_position'       => 26,
            'supports'            => ['title'],
            'capability_type'     => ['ksv_verein', 'ksv_vereine'],
            'map_meta_cap'        => true,
            'show_in_rest'        => false,
            'exclude_from_search' => true,
        ]);
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function admin_columns(array $columns): array
    {
        $new = [];
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'title') {
                $new['ksv_city']    = __('Ort', 'ksv-vereine');
                $new['ksv_active']  = __('Aktiv', 'ksv-vereine');
                $new['ksv_geo']     = __('Koordinaten', 'ksv-vereine');
            }
        }

        return $new;
    }

    public function admin_column_content(string $column, int $post_id): void
    {
        if ($column === 'ksv_city') {
            $city = get_post_meta($post_id, '_ksv_city', true);
            echo esc_html(is_string($city) ? $city : '—');
            return;
        }

        if ($column === 'ksv_active') {
            $active = get_post_meta($post_id, '_ksv_active', true);
            echo ($active === '' || $active === '1')
                ? esc_html__('Ja', 'ksv-vereine')
                : esc_html__('Nein', 'ksv-vereine');
            return;
        }

        if ($column === 'ksv_geo') {
            $lat = get_post_meta($post_id, '_ksv_lat', true);
            $lng = get_post_meta($post_id, '_ksv_lng', true);
            if ($lat !== '' && $lng !== '') {
                echo esc_html((string) $lat . ', ' . (string) $lng);
            } else {
                echo '—';
            }
        }
    }
}
