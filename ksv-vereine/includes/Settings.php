<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class Settings
{
    public const OPTION_KEY = 'ksv_vereine_settings';
    public const PAGE_SLUG  = 'ksv-vereine-settings';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . PostType::SLUG,
            __('KSV Vereine – Einstellungen', 'ksv-vereine'),
            __('Einstellungen', 'ksv-vereine'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'ksv_vereine_settings_group',
            self::OPTION_KEY,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
                'default'           => self::defaults(),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'ors_api_key'              => '',
            'map_lat'                  => '51.1657',
            'map_lng'                  => '10.4515',
            'map_zoom'                 => 8,
            'placeholder_logo_id'      => 0,
            'whitelist_users'          => [],
            'suggestion_notify_email'  => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $stored = get_option(self::OPTION_KEY, []);
        if (! is_array($stored)) {
            $stored = [];
        }

        return array_merge(self::defaults(), $stored);
    }

    public static function get_string(string $key): string
    {
        $settings = self::get();
        $value    = $settings[$key] ?? '';

        return is_string($value) ? $value : (string) $value;
    }

    public static function get_int(string $key): int
    {
        $settings = self::get();

        return (int) ($settings[$key] ?? 0);
    }

    public static function get_suggestion_notify_email(): string
    {
        $configured = self::get_string('suggestion_notify_email');
        if ($configured !== '' && is_email($configured)) {
            return $configured;
        }

        $admin = get_option('admin_email');

        return is_string($admin) && is_email($admin) ? $admin : '';
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function sanitize(mixed $input): array
    {
        if (! is_array($input)) {
            $input = [];
        }

        $old      = self::get();
        $defaults = self::defaults();

        $sanitized = [
            'ors_api_key'         => isset($input['ors_api_key']) ? sanitize_text_field((string) $input['ors_api_key']) : '',
            'map_lat'             => isset($input['map_lat']) ? (string) (float) $input['map_lat'] : $defaults['map_lat'],
            'map_lng'             => isset($input['map_lng']) ? (string) (float) $input['map_lng'] : $defaults['map_lng'],
            'map_zoom'            => isset($input['map_zoom']) ? absint($input['map_zoom']) : (int) $defaults['map_zoom'],
            'placeholder_logo_id' => isset($input['placeholder_logo_id']) ? absint($input['placeholder_logo_id']) : 0,
            'whitelist_users'         => [],
            'suggestion_notify_email' => isset($input['suggestion_notify_email'])
                ? sanitize_email((string) $input['suggestion_notify_email'])
                : '',
        ];

        if (isset($input['whitelist_users']) && is_array($input['whitelist_users'])) {
            $sanitized['whitelist_users'] = array_values(array_unique(array_map('absint', $input['whitelist_users'])));
        }

        $removed = array_diff(
            array_map('intval', (array) ($old['whitelist_users'] ?? [])),
            $sanitized['whitelist_users']
        );
        foreach ($removed as $user_id) {
            Capabilities::revoke_whitelist_caps((int) $user_id);
        }

        Capabilities::apply_whitelist($sanitized['whitelist_users']);

        return $sanitized;
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $settings = self::get();
        $users    = get_users(['fields' => ['ID', 'display_name', 'user_login']]);

        include KSV_VEREINE_PATH . 'templates/admin-settings.php';
    }
}
