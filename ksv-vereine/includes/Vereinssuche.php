<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class Vereinssuche
{
    private static bool $assets_enqueued = false;

    /**
     * @param array<string, mixed> $atts
     */
    public static function render(array $atts = []): string
    {
        $settings = Settings::get();

        $heading = isset($atts['heading']) && is_string($atts['heading']) && $atts['heading'] !== ''
            ? $atts['heading']
            : __('Schützenverein in Ihrer Nähe finden', 'ksv-vereine');

        $description = isset($atts['description']) && is_string($atts['description']) && $atts['description'] !== ''
            ? $atts['description']
            : __(
                'Nutzen Sie unsere dynamische Vereinssuche und finden Sie Schützenvereine in Ihrer Umgebung.',
                'ksv-vereine'
            );

        $image_url = self::resolve_image_url($atts, $settings);
        $vereine_page_url = self::resolve_vereine_page_url($atts, $settings);
        $show_link = ! isset($atts['show_link']) || filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);

        self::enqueue_assets($vereine_page_url);

        ob_start();
        include KSV_VEREINE_PATH . 'templates/vereinssuche.php';

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $atts
     * @param array<string, mixed> $settings
     */
    private static function resolve_image_url(array $atts, array $settings): string
    {
        if (isset($atts['image_url']) && is_string($atts['image_url']) && $atts['image_url'] !== '') {
            return esc_url($atts['image_url']);
        }

        if (isset($atts['image_id'])) {
            $image_id = absint($atts['image_id']);
            if ($image_id > 0) {
                $url = wp_get_attachment_image_url($image_id, 'large');
                if (is_string($url) && $url !== '') {
                    return esc_url($url);
                }
            }
        }

        $map_image_id = absint($settings['vereinssuche_image_id'] ?? 0);
        if ($map_image_id > 0) {
            $url = wp_get_attachment_image_url($map_image_id, 'large');
            if (is_string($url) && $url !== '') {
                return esc_url($url);
            }
        }

        return esc_url(KSV_VEREINE_URL . 'assets/images/vereinssuche.png');
    }

    /**
     * @param array<string, mixed> $atts
     * @param array<string, mixed> $settings
     */
    private static function resolve_vereine_page_url(array $atts, array $settings): string
    {
        if (isset($atts['vereine_page_url']) && is_string($atts['vereine_page_url']) && $atts['vereine_page_url'] !== '') {
            return esc_url($atts['vereine_page_url']);
        }

        $page_id = absint($settings['vereine_page_id'] ?? 0);
        if ($page_id > 0) {
            $permalink = get_permalink($page_id);
            if (is_string($permalink) && $permalink !== '') {
                return esc_url($permalink);
            }
        }

        return '';
    }

    private static function enqueue_assets(string $vereine_page_url): void
    {
        if (self::$assets_enqueued) {
            return;
        }

        self::$assets_enqueued = true;

        wp_enqueue_style(
            'ksv-vereinssuche',
            KSV_VEREINE_URL . 'assets/css/vereinssuche.css',
            [],
            KSV_VEREINE_VERSION
        );

        wp_enqueue_script(
            'ksv-vereinssuche',
            KSV_VEREINE_URL . 'assets/js/vereinssuche.js',
            [],
            KSV_VEREINE_VERSION,
            true
        );

        wp_localize_script('ksv-vereinssuche', 'ksvVereinssuche', [
            'vereinePageUrl' => $vereine_page_url,
            'searchParam'    => 'ksv_search',
            'i18n'           => [
                'searchLabel'       => __('Vereinssuche', 'ksv-vereine'),
                'searchPlaceholder' => __('Ort, PLZ oder Vereinsname', 'ksv-vereine'),
                'search'            => __('Suchen', 'ksv-vereine'),
                'toSearch'          => __('Zur Vereinssuche', 'ksv-vereine'),
                'missingPage'       => __('Bitte hinterlegen Sie unter Vereine → Einstellungen die Zielseite für die Vereinssuche.', 'ksv-vereine'),
            ],
        ]);
    }
}
