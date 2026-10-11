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
        add_action('admin_notices', [$this, 'hinweis_schluessel_fehlt']);
        add_action('admin_notices', [$this, 'hinweis_hash_doppelt']);

        // Kopierte Vereine dürfen den Code nicht erben: Yoast Duplicate Post ausschließen …
        add_filter('duplicate_post_excludelist_filter', [$this, 'duplicate_post_excludelist']);
        // … und bei anderen Kopier-Plugins einen bereits vergebenen Hash vom neuen Beitrag entfernen.
        add_action('wp_insert_post', [$this, 'nach_insert'], 10, 3);
        add_action('added_post_meta', [$this, 'nach_meta_hinzugefuegt'], 10, 3);
    }

    /**
     * Dauerhafter Hinweis für Administratoren, solange KSV_VEREINE_CODE_KEY fehlt oder zu kurz ist.
     */
    public function hinweis_schluessel_fehlt(): void
    {
        if (Vereinscode::schluessel_vorhanden() || ! current_user_can('manage_options')) {
            return;
        }

        printf(
            '<div class="notice notice-error"><p><strong>%s</strong> %s</p><p><code>%s</code></p></div>',
            esc_html__('KSV Vereine: App-Vereinscodes sind nicht konfiguriert.', 'ksv-vereine'),
            esc_html(sprintf(
                /* translators: %d: Mindestlänge */
                __('Bitte die Konstante KSV_VEREINE_CODE_KEY mit einer zufälligen Zeichenkette von mindestens %d Zeichen in der wp-config.php setzen. Bis dahin können keine Vereinscodes erzeugt oder in der App eingelöst werden. Der Schlüssel darf später nicht mehr geändert werden, sonst werden alle Codes ungültig.', 'ksv-vereine'),
                Vereinscode::SCHLUESSEL_MINDESTLAENGE
            )),
            esc_html("define('KSV_VEREINE_CODE_KEY', '…mindestens " . Vereinscode::SCHLUESSEL_MINDESTLAENGE . " zufällige Zeichen…');")
        );
    }

    /**
     * Warnung im Bearbeiten-Bildschirm, wenn ein anderer Verein denselben Code-Hash trägt.
     */
    public function hinweis_hash_doppelt(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen === null || $screen->base !== 'post' || $screen->post_type !== PostType::SLUG) {
            return;
        }

        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
        if ($post_id <= 0 || ! current_user_can('edit_post', $post_id) || ! Vereinscode::hash_doppelt($post_id)) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
            esc_html__('App-Vereinscode doppelt vergeben.', 'ksv-vereine'),
            esc_html__('Mindestens ein weiterer Verein trägt denselben Vereinscode (vermutlich wurde der Beitrag kopiert). Der Code kann in der App nicht eingelöst werden. Bitte den Vereinscode in der Metabox „App-Vereinscode“ zurücksetzen.', 'ksv-vereine')
        );
    }

    /**
     * Yoast Duplicate Post: Code-Metadaten nicht mitkopieren.
     *
     * @param array<int, string>|mixed $liste
     * @return array<int, string>|mixed
     */
    public function duplicate_post_excludelist(mixed $liste): mixed
    {
        if (! is_array($liste)) {
            return $liste;
        }

        return array_values(array_unique(array_merge(
            $liste,
            [Vereinscode::META_HASH, Vereinscode::META_VERSION, Vereinscode::META_ERSTELLT]
        )));
    }

    /**
     * Neuer Verein (kein Update) mit einem Hash, den bereits ein anderer Beitrag trägt: Code entfernen.
     */
    public function nach_insert(int $post_id, \WP_Post $post, bool $update): void
    {
        if ($update || $post->post_type !== PostType::SLUG) {
            return;
        }

        if (Vereinscode::hash_doppelt($post_id)) {
            Vereinscode::entfernen($post_id);
        }
    }

    /**
     * Kopier-Plugins übertragen Metadaten meist erst nach wp_insert_post(): auch dann prüfen.
     */
    public function nach_meta_hinzugefuegt(int $meta_id, int $post_id, string $meta_key): void
    {
        if ($meta_key !== Vereinscode::META_HASH || get_post_type($post_id) !== PostType::SLUG) {
            return;
        }

        if (Vereinscode::hash_doppelt($post_id)) {
            Vereinscode::entfernen($post_id);
        }
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
        $schluessel_fehlt = ! Vereinscode::schluessel_vorhanden();
        $hash_doppelt     = $hat_code && Vereinscode::hash_doppelt($post_id);

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

        if (! Vereinscode::schluessel_vorhanden()) {
            wp_send_json_error(
                ['message' => __('Vereinscodes sind nicht konfiguriert: KSV_VEREINE_CODE_KEY (mind. 32 Zeichen) fehlt in der wp-config.php.', 'ksv-vereine')],
                503
            );
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
