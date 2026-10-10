<?php

declare(strict_types=1);

namespace KSV\Vereine;

/**
 * Verwaltung des App-Vereinscodes im Backend (Metabox „App-Vereinscode“ im Verein-Bearbeiten-Bildschirm).
 *
 * Der Klartext wird nur in der AJAX-Antwort direkt nach dem Erzeugen übertragen und nirgends gespeichert.
 * QR-Code und Druckansicht entstehen im Browser (gebündelte Bibliothek, keine externen Aufrufe).
 */
final class VereinscodeAdmin
{
    public const AJAX_ACTION = 'ksv_vereinscode_erzeugen';

    public function register(): void
    {
        add_action('add_meta_boxes_' . PostType::SLUG, [$this, 'add_meta_box']);
        add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'ajax_erzeugen']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('manage_' . PostType::SLUG . '_posts_columns', [$this, 'admin_columns'], 20);
        add_action('manage_' . PostType::SLUG . '_posts_custom_column', [$this, 'admin_column_content'], 10, 2);
    }

    public static function nonce_action(int $post_id): string
    {
        return 'ksv_vereinscode_' . $post_id;
    }

    public function add_meta_box(): void
    {
        add_meta_box(
            'ksv_verein_app_code',
            __('App-Vereinscode', 'ksv-vereine'),
            [$this, 'render_box'],
            PostType::SLUG,
            'side',
            'default'
        );
    }

    public function render_box(\WP_Post $post): void
    {
        if (! current_user_can('edit_post', $post->ID)) {
            return;
        }

        $post_id   = $post->ID;
        $ist_neu   = $post->post_status === 'auto-draft';
        $version   = Vereinscode::version($post_id);
        $hat_code  = $version > 0 && Vereinscode::hat_code($post_id);
        $erstellt  = Vereinscode::erstellt_am($post_id);
        $erstellt_text = $erstellt > 0
            ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), $erstellt)
            : '';
        $einloesbar = $post->post_status === 'publish'
            && (string) get_post_meta($post_id, '_ksv_active', true) === '1';
        $verein_name = html_entity_decode(wp_strip_all_tags($post->post_title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $nonce       = wp_create_nonce(self::nonce_action($post_id));

        include KSV_VEREINE_PATH . 'templates/admin-vereinscode.php';
    }

    public function enqueue_assets(string $hook): void
    {
        if ($hook !== 'post.php') {
            return;
        }

        $screen = get_current_screen();
        if ($screen === null || $screen->post_type !== PostType::SLUG) {
            return;
        }

        wp_enqueue_style(
            'ksv-vereine-admin',
            KSV_VEREINE_URL . 'assets/css/admin.css',
            [],
            KSV_VEREINE_VERSION
        );
        wp_enqueue_script(
            'ksv-qrcode-generator',
            KSV_VEREINE_URL . 'assets/vendor/qrcode-generator/qrcode.js',
            [],
            '1.4.4',
            true
        );
        wp_enqueue_script(
            'ksv-vereine-vereinscode',
            KSV_VEREINE_URL . 'assets/js/vereinscode.js',
            ['ksv-qrcode-generator'],
            KSV_VEREINE_VERSION,
            true
        );

        wp_localize_script('ksv-vereine-vereinscode', 'ksvVereinscode', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'action'  => self::AJAX_ACTION,
            'i18n'    => [
                'bestaetigung'  => __(
                    "Vereinscode wirklich zurücksetzen?\n\nDer bisherige Code wird sofort ungültig. Alle Geräte, die in der App mit diesem Verein verknüpft sind, werden abgemeldet. Die Mitglieder müssen anschließend den neuen Code eingeben.",
                    'ksv-vereine'
                ),
                'laedt'         => __('Code wird erzeugt …', 'ksv-vereine'),
                'fehler'        => __('Der Code konnte nicht erzeugt werden. Bitte versuchen Sie es erneut.', 'ksv-vereine'),
                'erzeugt'       => __('Neuer Code erzeugt. Bitte jetzt notieren oder drucken.', 'ksv-vereine'),
                'status'        => __('Aktiver Code: Version %1$s, erzeugt am %2$s.', 'ksv-vereine'),
                'zuruecksetzen' => __('Code zurücksetzen', 'ksv-vereine'),
                'popup'         => __('Die Druckansicht konnte nicht geöffnet werden. Bitte erlauben Sie Pop-ups für diese Seite.', 'ksv-vereine'),
                'druckTitel'    => __('Vereinscode für die App „KSV Fallingbostel“', 'ksv-vereine'),
                'druckHinweis'  => __('So verknüpfen Sie die App mit unserem Verein: App „KSV Fallingbostel“ öffnen → Profil → Mein Verein → QR-Code scannen oder Code eingeben.', 'ksv-vereine'),
                'druckVertraulich' => __('Bitte nur an Vereinsmitglieder weitergeben.', 'ksv-vereine'),
                'qrAlt'         => __('QR-Code mit dem Vereinscode', 'ksv-vereine'),
            ],
        ]);
    }

    public function ajax_erzeugen(): void
    {
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

        if ($post_id <= 0 || ! check_ajax_referer(self::nonce_action($post_id), 'nonce', false)) {
            wp_send_json_error(['message' => __('Die Sitzung ist abgelaufen. Bitte laden Sie die Seite neu.', 'ksv-vereine')], 403);
        }

        $post = get_post($post_id);
        if (! $post instanceof \WP_Post || $post->post_type !== PostType::SLUG) {
            wp_send_json_error(['message' => __('Der Verein wurde nicht gefunden.', 'ksv-vereine')], 404);
        }

        if (! current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'ksv-vereine')], 403);
        }

        if (in_array($post->post_status, ['auto-draft', 'trash'], true)) {
            wp_send_json_error(['message' => __('Bitte speichern Sie den Verein zuerst.', 'ksv-vereine')], 400);
        }

        // Schutz vor doppeltem Zurücksetzen (Doppelklick, zweiter Tab): erwartete Version muss stimmen.
        $erwartet = isset($_POST['version']) ? (int) $_POST['version'] : -1;
        if ($erwartet !== Vereinscode::version($post_id)) {
            wp_send_json_error(
                ['message' => __('Der Vereinscode wurde inzwischen geändert. Bitte laden Sie die Seite neu.', 'ksv-vereine')],
                409
            );
        }

        $ergebnis = Vereinscode::neu_erzeugen($post_id);

        nocache_headers();
        wp_send_json_success([
            'code'     => $ergebnis['code'],
            'version'  => $ergebnis['version'],
            'erstellt' => wp_date(get_option('date_format') . ' ' . get_option('time_format')),
        ]);
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function admin_columns(array $columns): array
    {
        $columns['ksv_app_code'] = __('App-Code', 'ksv-vereine');

        return $columns;
    }

    public function admin_column_content(string $column, int $post_id): void
    {
        if ($column !== 'ksv_app_code') {
            return;
        }

        $version = Vereinscode::version($post_id);
        if ($version > 0 && Vereinscode::hat_code($post_id)) {
            /* translators: %d: Code-Version */
            echo esc_html(sprintf(__('Version %d', 'ksv-vereine'), $version));
        } else {
            echo '—';
        }
    }
}
