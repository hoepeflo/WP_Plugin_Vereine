<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class Frontend
{
    private static bool $assets_enqueued = false;

    public static function render(): string
    {
        self::enqueue_assets();

        $settings = Settings::get();

        ob_start();
        include KSV_VEREINE_PATH . 'templates/frontend.php';

        return (string) ob_get_clean();
    }

    private static function enqueue_assets(): void
    {
        if (self::$assets_enqueued) {
            return;
        }

        self::$assets_enqueued = true;

        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );
        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );

        wp_enqueue_style(
            'ksv-vereine-frontend',
            KSV_VEREINE_URL . 'assets/css/frontend.css',
            ['leaflet'],
            KSV_VEREINE_VERSION
        );
        wp_enqueue_script(
            'ksv-vereine-frontend',
            KSV_VEREINE_URL . 'assets/js/frontend.js',
            ['leaflet'],
            KSV_VEREINE_VERSION,
            true
        );

        $settings = Settings::get();

        wp_localize_script('ksv-vereine-frontend', 'ksvVereine', [
            'restUrl'   => rest_url('ksv/v1/vereine'),
            'geocodeUrl'=> rest_url('ksv/v1/geocode'),
            'nonce'     => wp_create_nonce('wp_rest'),
            'map'       => [
                'lat'  => (float) ($settings['map_lat'] ?? 51.1657),
                'lng'  => (float) ($settings['map_lng'] ?? 10.4515),
                'zoom' => (int) ($settings['map_zoom'] ?? 8),
            ],
            'i18n'      => [
                'searchLabel'    => __('Vereine in Ihrer Nähe finden', 'ksv-vereine'),
                'searchPlaceholder'=> __('Ort, PLZ oder Adresse eingeben …', 'ksv-vereine'),
                'filterLegend'   => __('Nach Disziplin filtern', 'ksv-vereine'),
                'search'         => __('Suchen', 'ksv-vereine'),
                'noResults'      => __('Keine Vereine gefunden.', 'ksv-vereine'),
                'searchNotFound' => __('Der eingegebene Ort konnte nicht gefunden werden. Es wird alphabetisch sortiert.', 'ksv-vereine'),
                'toCard'         => __('Zur Vereinsinfo', 'ksv-vereine'),
                'openWebsite'    => __('Webseite von %s öffnen', 'ksv-vereine'),
                'mapHint'        => __('Die Karte ergänzt die Liste. Alle Vereinsinformationen finden Sie in der Liste.', 'ksv-vereine'),
            ],
            'disciplines' => array_map(
                static fn (string $slug, string $name): array => ['slug' => $slug, 'name' => $name],
                array_keys(Taxonomy::TERMS),
                array_values(Taxonomy::TERMS)
            ),
        ]);
    }
}
