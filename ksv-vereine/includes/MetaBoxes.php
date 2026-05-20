<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class MetaBoxes
{
    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_' . PostType::SLUG, [$this, 'save'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_meta_boxes(): void
    {
        add_meta_box(
            'ksv_verein_details',
            __('Vereinsdaten', 'ksv-vereine'),
            [$this, 'render_details_box'],
            PostType::SLUG,
            'normal',
            'high'
        );

        add_meta_box(
            'ksv_verein_disziplinen',
            __('Disziplinen', 'ksv-vereine'),
            [$this, 'render_disciplines_box'],
            PostType::SLUG,
            'side',
            'default'
        );
    }

    public function enqueue_admin_assets(string $hook): void
    {
        if (! in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        $screen = get_current_screen();
        if ($screen === null || $screen->post_type !== PostType::SLUG) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style(
            'ksv-vereine-admin',
            KSV_VEREINE_URL . 'assets/css/admin.css',
            [],
            KSV_VEREINE_VERSION
        );
        wp_enqueue_script(
            'ksv-vereine-admin',
            KSV_VEREINE_URL . 'assets/js/admin.js',
            ['jquery'],
            KSV_VEREINE_VERSION,
            true
        );
    }

    public function render_details_box(\WP_Post $post): void
    {
        wp_nonce_field('ksv_verein_save', 'ksv_verein_nonce');

        $street  = (string) get_post_meta($post->ID, '_ksv_street', true);
        $zip     = (string) get_post_meta($post->ID, '_ksv_zip', true);
        $city    = (string) get_post_meta($post->ID, '_ksv_city', true);
        $website = (string) get_post_meta($post->ID, '_ksv_website', true);
        $logo_id = (int) get_post_meta($post->ID, '_ksv_logo_id', true);
        $active  = get_post_meta($post->ID, '_ksv_active', true);
        $active  = ($active === '' || $active === '1');
        $lat     = (string) get_post_meta($post->ID, '_ksv_lat', true);
        $lng     = (string) get_post_meta($post->ID, '_ksv_lng', true);
        $geo_err = (string) get_post_meta($post->ID, '_ksv_geo_error', true);

        $logo_url = $logo_id > 0 ? wp_get_attachment_image_url($logo_id, 'thumbnail') : '';

        include KSV_VEREINE_PATH . 'templates/admin-metabox.php';
    }

    public function render_disciplines_box(\WP_Post $post): void
    {
        $terms     = get_terms(['taxonomy' => Taxonomy::SLUG, 'hide_empty' => false]);
        $selected  = wp_get_object_terms($post->ID, Taxonomy::SLUG, ['fields' => 'ids']);
        $selected  = is_array($selected) ? array_map('intval', $selected) : [];

        if (is_wp_error($terms)) {
            echo '<p>' . esc_html__('Disziplinen konnten nicht geladen werden.', 'ksv-vereine') . '</p>';
            return;
        }

        echo '<fieldset class="ksv-disciplines-fieldset">';
        echo '<legend class="screen-reader-text">' . esc_html__('Disziplinen', 'ksv-vereine') . '</legend>';

        foreach ($terms as $term) {
            if (! $term instanceof \WP_Term) {
                continue;
            }
            $checked = in_array((int) $term->term_id, $selected, true) ? 'checked' : '';
            printf(
                '<label class="ksv-discipline-label"><input type="checkbox" name="ksv_disziplinen[]" value="%d" %s /> %s</label>',
                (int) $term->term_id,
                esc_attr($checked),
                esc_html($term->name)
            );
        }

        echo '</fieldset>';
    }

    public function save(int $post_id, \WP_Post $post): void
    {
        if (
            ! isset($_POST['ksv_verein_nonce'])
            || ! wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['ksv_verein_nonce'])), 'ksv_verein_save')
        ) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $street  = isset($_POST['ksv_street']) ? sanitize_text_field(wp_unslash((string) $_POST['ksv_street'])) : '';
        $zip     = isset($_POST['ksv_zip']) ? sanitize_text_field(wp_unslash((string) $_POST['ksv_zip'])) : '';
        $city    = isset($_POST['ksv_city']) ? sanitize_text_field(wp_unslash((string) $_POST['ksv_city'])) : '';
        $website = isset($_POST['ksv_website']) ? esc_url_raw(wp_unslash((string) $_POST['ksv_website'])) : '';
        $logo_id = isset($_POST['ksv_logo_id']) ? absint($_POST['ksv_logo_id']) : 0;
        $active  = isset($_POST['ksv_active']) ? '1' : '0';

        $address_changed = $this->address_changed($post_id, $street, $zip, $city);

        update_post_meta($post_id, '_ksv_street', $street);
        update_post_meta($post_id, '_ksv_zip', $zip);
        update_post_meta($post_id, '_ksv_city', $city);
        update_post_meta($post_id, '_ksv_website', $website);
        update_post_meta($post_id, '_ksv_logo_id', $logo_id);
        update_post_meta($post_id, '_ksv_active', $active);

        $discipline_ids = [];
        if (isset($_POST['ksv_disziplinen']) && is_array($_POST['ksv_disziplinen'])) {
            $discipline_ids = array_map('absint', wp_unslash($_POST['ksv_disziplinen']));
        }
        wp_set_object_terms($post_id, $discipline_ids, Taxonomy::SLUG);

        if ($address_changed) {
            delete_post_meta($post_id, '_ksv_geo_error');
            Geocoding::geocode_post($post_id);
        }
    }

    private function address_changed(int $post_id, string $street, string $zip, string $city): bool
    {
        return get_post_meta($post_id, '_ksv_street', true) !== $street
            || get_post_meta($post_id, '_ksv_zip', true) !== $zip
            || get_post_meta($post_id, '_ksv_city', true) !== $city;
    }
}
