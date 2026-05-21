<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class Suggestions
{
    private const RATE_LIMIT_MAX = 5;
    private const RATE_LIMIT_WINDOW = 3600;

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('ksv/v1', '/vereine/(?P<id>\d+)/aenderungsvorschlag', [
            'methods'             => 'POST',
            'callback'            => [$this, 'submit'],
            'permission_callback' => [$this, 'permission_check'],
            'args'                => [
                'id' => [
                    'validate_callback' => static fn ($param): bool => is_numeric($param) && (int) $param > 0,
                ],
                'name' => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'street' => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'zip' => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'city' => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'website' => [
                    'sanitize_callback' => 'esc_url_raw',
                ],
                'disziplinen' => [
                    'type'              => 'array',
                    'sanitize_callback' => static function ($param): array {
                        if (! is_array($param)) {
                            return [];
                        }

                        return array_values(array_unique(array_map('sanitize_title', $param)));
                    },
                ],
                'comment' => [
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'contact_email' => [
                    'sanitize_callback' => 'sanitize_email',
                ],
                'website_honeypot' => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    public function permission_check(\WP_REST_Request $request): bool|\WP_Error
    {
        $nonce = $request->get_header('X-WP-Nonce');
        if (! is_string($nonce) || $nonce === '' || ! wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error(
                'ksv_invalid_nonce',
                __('Sicherheitsprüfung fehlgeschlagen. Bitte laden Sie die Seite neu.', 'ksv-vereine'),
                ['status' => 403]
            );
        }

        return true;
    }

    public function submit(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        if ($this->is_rate_limited()) {
            return new \WP_Error(
                'ksv_rate_limited',
                __('Zu viele Anfragen. Bitte versuchen Sie es später erneut.', 'ksv-vereine'),
                ['status' => 429]
            );
        }

        $honeypot = trim((string) $request->get_param('website_honeypot'));
        if ($honeypot !== '') {
            return new \WP_REST_Response([
                'success' => true,
                'message' => __('Ihr Änderungsvorschlag wurde übermittelt. Vielen Dank!', 'ksv-vereine'),
            ]);
        }

        $post_id = (int) $request->get_param('id');
        $post    = get_post($post_id);

        if (
            ! $post instanceof \WP_Post
            || $post->post_type !== PostType::SLUG
            || $post->post_status !== 'publish'
            || get_post_meta($post_id, '_ksv_active', true) !== '1'
        ) {
            return new \WP_Error(
                'ksv_invalid_verein',
                __('Der ausgewählte Verein wurde nicht gefunden.', 'ksv-vereine'),
                ['status' => 404]
            );
        }

        $proposed = [
            'name'        => trim((string) $request->get_param('name')),
            'street'      => trim((string) $request->get_param('street')),
            'zip'         => trim((string) $request->get_param('zip')),
            'city'        => trim((string) $request->get_param('city')),
            'website'     => trim((string) $request->get_param('website')),
            'disziplinen' => $request->get_param('disziplinen'),
        ];
        $proposed['disziplinen'] = is_array($proposed['disziplinen']) ? $proposed['disziplinen'] : [];

        $comment       = trim((string) $request->get_param('comment'));
        $contact_email = sanitize_email((string) $request->get_param('contact_email'));

        $current = $this->get_current_data($post);
        $changes = $this->build_changes($current, $proposed);

        if ($changes === [] && $comment === '') {
            return new \WP_Error(
                'ksv_no_changes',
                __('Bitte ändern Sie mindestens ein Feld oder geben Sie eine Anmerkung ein.', 'ksv-vereine'),
                ['status' => 400]
            );
        }

        $sent = $this->send_notification_email($post, $changes, $comment, $contact_email);

        if (! $sent) {
            return new \WP_Error(
                'ksv_mail_failed',
                __('Die Benachrichtigung konnte nicht versendet werden. Bitte kontaktieren Sie den Verband direkt.', 'ksv-vereine'),
                ['status' => 500]
            );
        }

        $this->record_submission();

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Ihr Änderungsvorschlag wurde übermittelt. Vielen Dank!', 'ksv-vereine'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function get_current_data(\WP_Post $post): array
    {
        $terms = wp_get_post_terms($post->ID, Taxonomy::SLUG);
        $slugs = [];
        if (is_array($terms)) {
            foreach ($terms as $term) {
                if ($term instanceof \WP_Term) {
                    $slugs[] = $term->slug;
                }
            }
        }
        sort($slugs);

        return [
            'name'        => get_the_title($post),
            'street'      => (string) get_post_meta($post->ID, '_ksv_street', true),
            'zip'         => (string) get_post_meta($post->ID, '_ksv_zip', true),
            'city'        => (string) get_post_meta($post->ID, '_ksv_city', true),
            'website'     => (string) get_post_meta($post->ID, '_ksv_website', true),
            'disziplinen' => $slugs,
        ];
    }

    /**
     * @param array<string, mixed> $current
     * @param array<string, mixed> $proposed
     * @return list<array{label: string, current: string, proposed: string}>
     */
    private function build_changes(array $current, array $proposed): array
    {
        $changes = [];

        $fields = [
            'name'    => __('Vereinsname', 'ksv-vereine'),
            'street'  => __('Straße', 'ksv-vereine'),
            'zip'     => __('PLZ', 'ksv-vereine'),
            'city'    => __('Ort', 'ksv-vereine'),
            'website' => __('Webseite', 'ksv-vereine'),
        ];

        foreach ($fields as $key => $label) {
            $cur = (string) ($current[$key] ?? '');
            $new = (string) ($proposed[$key] ?? '');
            if ($cur !== $new) {
                $changes[] = [
                    'label'    => $label,
                    'current'  => $cur !== '' ? $cur : '—',
                    'proposed' => $new !== '' ? $new : '—',
                ];
            }
        }

        $cur_slugs = $current['disziplinen'] ?? [];
        $new_slugs = $proposed['disziplinen'] ?? [];
        if (! is_array($cur_slugs)) {
            $cur_slugs = [];
        }
        if (! is_array($new_slugs)) {
            $new_slugs = [];
        }

        $cur_slugs = array_values(array_unique(array_map('strval', $cur_slugs)));
        $new_slugs = array_values(array_unique(array_map('strval', $new_slugs)));
        sort($cur_slugs);
        sort($new_slugs);

        if ($cur_slugs !== $new_slugs) {
            $changes[] = [
                'label'    => __('Disziplinen', 'ksv-vereine'),
                'current'  => $this->format_discipline_slugs($cur_slugs),
                'proposed' => $this->format_discipline_slugs($new_slugs),
            ];
        }

        return $changes;
    }

    /**
     * @param list<string> $slugs
     */
    private function format_discipline_slugs(array $slugs): string
    {
        if ($slugs === []) {
            return '—';
        }

        $names = [];
        foreach ($slugs as $slug) {
            $term = get_term_by('slug', $slug, Taxonomy::SLUG);
            $names[] = $term instanceof \WP_Term ? $term->name : $slug;
        }

        return implode(', ', $names);
    }

    /**
     * @param list<array{label: string, current: string, proposed: string}> $changes
     */
    private function send_notification_email(
        \WP_Post $post,
        array $changes,
        string $comment,
        string $contact_email
    ): bool {
        $to = Settings::get_suggestion_notify_email();
        if ($to === '') {
            return false;
        }

        $verein_name = get_the_title($post);
        $edit_link   = admin_url('post.php?post=' . $post->ID . '&action=edit');
        $site_name   = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);

        $subject = sprintf(
            /* translators: 1: site name, 2: club name */
            __('[%1$s] Änderungsvorschlag für Verein: %2$s', 'ksv-vereine'),
            $site_name,
            $verein_name
        );

        $lines = [
            __('Es wurde ein Änderungsvorschlag für Vereinsdaten eingereicht.', 'ksv-vereine'),
            '',
            __('Verein:', 'ksv-vereine') . ' ' . $verein_name,
            __('Bearbeiten im Backend:', 'ksv-vereine') . ' ' . $edit_link,
            '',
        ];

        if ($changes !== []) {
            $lines[] = __('Vorgeschlagene Änderungen:', 'ksv-vereine');
            $lines[] = '';
            foreach ($changes as $change) {
                $lines[] = $change['label'] . ':';
                $lines[] = '  ' . __('Aktuell:', 'ksv-vereine') . ' ' . $change['current'];
                $lines[] = '  ' . __('Vorschlag:', 'ksv-vereine') . ' ' . $change['proposed'];
                $lines[] = '';
            }
        }

        if ($comment !== '') {
            $lines[] = __('Anmerkung:', 'ksv-vereine');
            $lines[] = $comment;
            $lines[] = '';
        }

        if ($contact_email !== '') {
            $lines[] = __('Kontakt-E-Mail des Absenders:', 'ksv-vereine') . ' ' . $contact_email;
        }

        $lines[] = '';
        $lines[] = __('Datum:', 'ksv-vereine') . ' ' . wp_date('d.m.Y H:i');

        $body = implode("\n", $lines);

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        if ($contact_email !== '') {
            $headers[] = 'Reply-To: ' . $contact_email;
        }

        return (bool) wp_mail($to, $subject, $body, $headers);
    }

    private function is_rate_limited(): bool
    {
        $key = 'ksv_suggest_' . md5($this->client_ip());
        $count = (int) get_transient($key);

        return $count >= self::RATE_LIMIT_MAX;
    }

    private function record_submission(): void
    {
        $key   = 'ksv_suggest_' . md5($this->client_ip());
        $count = (int) get_transient($key);
        set_transient($key, $count + 1, self::RATE_LIMIT_WINDOW);
    }

    private function client_ip(): string
    {
        $ip = '';
        if (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', (string) wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
            $ip    = trim($parts[0]);
        } elseif (! empty($_SERVER['REMOTE_ADDR'])) {
            $ip = (string) wp_unslash($_SERVER['REMOTE_ADDR']);
        }

        return sanitize_text_field($ip);
    }
}
