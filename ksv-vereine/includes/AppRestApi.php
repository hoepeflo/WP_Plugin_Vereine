<?php

declare(strict_types=1);

namespace KSV\Vereine;

/**
 * App-Endpunkte im Namespace ksv-app/v1 (API-Vertrag, Abschnitte 1.1 und 3.1).
 *
 * Verbindlicher Vertrag: Repo APP_KSV-Fallingbostel, docs/API-VERTRAG.md.
 */
final class AppRestApi
{
    public const NAMESPACE = 'ksv-app/v1';

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route(self::NAMESPACE, '/vereine', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_vereine'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NAMESPACE, '/vereine/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_verein'],
            'permission_callback' => '__return_true',
            'args'                => [
                'id' => [
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/disziplinen', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_disziplinen'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NAMESPACE, '/vereine/code-pruefen', [
            'methods'             => 'POST',
            'callback'            => [$this, 'code_pruefen'],
            'permission_callback' => [AppSignatur::class, 'pruefe'],
        ]);
    }

    public function get_vereine(\WP_REST_Request $request): \WP_REST_Response
    {
        $items = array_map([self::class, 'verein'], VereinRepository::query_posts([]));
        usort($items, static fn (array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name']));

        return $this->liste($request, $items);
    }

    public function get_verein(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $post = get_post((int) $request->get_param('id'));

        if (
            ! $post instanceof \WP_Post
            || $post->post_type !== PostType::SLUG
            || $post->post_status !== 'publish'
            || (string) get_post_meta($post->ID, '_ksv_active', true) !== '1'
        ) {
            return new \WP_Error(
                'ksv_app_nicht_gefunden',
                __('Verein nicht gefunden.', 'ksv-vereine'),
                ['status' => 404]
            );
        }

        $daten = self::verein($post);

        return $this->mit_caching($request, $daten, $daten);
    }

    public function get_disziplinen(\WP_REST_Request $request): \WP_REST_Response
    {
        $items = array_map(
            static fn (array $d): array => [
                'slug' => (string) $d['slug'],
                'name' => self::text((string) $d['name']),
            ],
            Taxonomy::get_all_for_frontend()
        );

        return $this->liste($request, $items);
    }

    public function code_pruefen(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $json = $request->get_json_params();
        $code = is_array($json) && isset($json['code']) ? $json['code'] : $request->get_param('code');

        $post = is_string($code) ? Vereinscode::finde_verein($code) : null;
        if ($post === null) {
            return new \WP_Error(
                'ksv_app_code_ungueltig',
                __('Der Vereinscode ist ungültig.', 'ksv-vereine'),
                ['status' => 404]
            );
        }

        $response = new \WP_REST_Response([
            'verein_id'    => $post->ID,
            'name'         => self::text($post->post_title),
            'code_version' => Vereinscode::version($post->ID),
        ]);
        $response->header('Cache-Control', 'no-store');

        return $response;
    }

    /**
     * Datensatz `Verein` gemäß Vertrag. Enthält nur öffentliche Vereinsdaten.
     *
     * @return array<string, mixed>
     */
    public static function verein(\WP_Post $post): array
    {
        $basis   = VereinRepository::format_post($post);
        $website = (string) $basis['website'];

        $disziplinen = [];
        foreach ((array) $basis['disziplinen'] as $tag) {
            if (is_array($tag) && isset($tag['slug'])) {
                $disziplinen[] = (string) $tag['slug'];
            }
        }
        sort($disziplinen);

        return [
            'id'          => $post->ID,
            'name'        => self::text($post->post_title),
            'strasse'     => (string) $basis['street'],
            'plz'         => (string) $basis['zip'],
            'ort'         => (string) $basis['city'],
            'adresse'     => (string) $basis['address'],
            'website'     => $website !== '' ? $website : null,
            'logo'        => self::logo($post->ID),
            'lat'         => $basis['lat'],
            'lng'         => $basis['lng'],
            'disziplinen' => $disziplinen,
            'updated_at'  => self::zeit_aus_gmt($post->post_modified_gmt, $post->post_modified),
        ];
    }

    /**
     * Vereinslogo bzw. Platzhalter-Bild (Anhang) in drei Größen. SVG wird nicht ausgeliefert.
     *
     * @return array{thumbnail: string, medium: string, full: string, platzhalter: bool}|null
     */
    public static function logo(int $post_id): ?array
    {
        $logo = self::bild((int) get_post_meta($post_id, '_ksv_logo_id', true), false);
        if ($logo !== null) {
            return $logo;
        }

        return self::bild(Settings::get_int('placeholder_logo_id'), true);
    }

    /**
     * @return array{thumbnail: string, medium: string, full: string, platzhalter: bool}|null
     */
    private static function bild(int $attachment_id, bool $platzhalter): ?array
    {
        if ($attachment_id <= 0 || ! wp_attachment_is_image($attachment_id)) {
            return null;
        }

        $mime = (string) get_post_mime_type($attachment_id);
        if ($mime === '' || str_contains($mime, 'svg')) {
            return null;
        }

        $urls = [];
        foreach (['thumbnail', 'medium', 'full'] as $size) {
            $url = wp_get_attachment_image_url($attachment_id, $size);
            if (! is_string($url) || $url === '') {
                return null;
            }
            $urls[$size] = $url;
        }

        return [
            'thumbnail'   => $urls['thumbnail'],
            'medium'      => $urls['medium'],
            'full'        => $urls['full'],
            'platzhalter' => $platzhalter,
        ];
    }

    /**
     * ISO 8601 in der Zeitzone der Website.
     */
    public static function zeit_aus_gmt(string $gmt, string $lokal = ''): string
    {
        $zone = wp_timezone();

        try {
            if ($gmt !== '' && ! str_starts_with($gmt, '0000-00-00')) {
                $zeit = new \DateTimeImmutable($gmt, new \DateTimeZone('UTC'));
            } elseif ($lokal !== '' && ! str_starts_with($lokal, '0000-00-00')) {
                $zeit = new \DateTimeImmutable($lokal, $zone);
            } else {
                $zeit = new \DateTimeImmutable('now', $zone);
            }
        } catch (\Exception) {
            $zeit = new \DateTimeImmutable('now', $zone);
        }

        return $zeit->setTimezone($zone)->format(DATE_ATOM);
    }

    private static function text(string $wert): string
    {
        return html_entity_decode(wp_strip_all_tags($wert), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    private function liste(\WP_REST_Request $request, array $items): \WP_REST_Response
    {
        $daten = [
            'items'        => $items,
            'generated_at' => current_datetime()->format(DATE_ATOM),
        ];

        // generated_at fließt nicht in das ETag ein, sonst wäre es bei jedem Aufruf anders.
        return $this->mit_caching($request, $daten, $items);
    }

    /**
     * ETag (Hash über den Inhalt) und Cache-Control setzen; bei passendem If-None-Match → 304.
     *
     * @param array<mixed> $daten
     * @param array<mixed> $hash_basis
     */
    private function mit_caching(\WP_REST_Request $request, array $daten, array $hash_basis): \WP_REST_Response
    {
        $etag = '"' . hash('sha256', (string) wp_json_encode($hash_basis)) . '"';

        $response = self::etag_passt((string) $request->get_header('if_none_match'), $etag)
            ? new \WP_REST_Response(null, 304)
            : new \WP_REST_Response($daten, 200);

        $response->header('ETag', $etag);
        $response->header('Cache-Control', 'public, max-age=300');

        return $response;
    }

    private static function etag_passt(string $if_none_match, string $etag): bool
    {
        if (trim($if_none_match) === '') {
            return false;
        }

        foreach (explode(',', $if_none_match) as $kandidat) {
            $kandidat = trim($kandidat);
            if (str_starts_with($kandidat, 'W/')) {
                $kandidat = substr($kandidat, 2);
            }
            if ($kandidat === '*' || $kandidat === $etag) {
                return true;
            }
        }

        return false;
    }
}
