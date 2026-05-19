<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class Capabilities
{
    /** @var list<string> */
    private const EDIT_CAPS = [
        'edit_ksv_verein',
        'read_ksv_verein',
        'delete_ksv_verein',
        'edit_ksv_vereins',
        'edit_others_ksv_vereins',
        'publish_ksv_vereins',
        'read_private_ksv_vereins',
        'delete_ksv_vereins',
        'delete_private_ksv_vereins',
        'delete_published_ksv_vereins',
        'delete_others_ksv_vereins',
        'edit_private_ksv_vereins',
        'edit_published_ksv_vereins',
        'create_ksv_vereins',
    ];

    /** @var list<string> */
    private const WHITELIST_CAPS = [
        'edit_ksv_verein',
        'read_ksv_verein',
        'edit_ksv_vereins',
        'edit_others_ksv_vereins',
        'publish_ksv_vereins',
        'read_private_ksv_vereins',
        'edit_private_ksv_vereins',
        'edit_published_ksv_vereins',
        'create_ksv_vereins',
    ];

    public function register(): void
    {
        add_action('init', [self::class, 'assign_role_caps'], 20);
        add_action('init', [self::class, 'sync_whitelist_on_load'], 99);
        add_filter('map_meta_cap', [$this, 'restrict_delete'], 10, 4);
        add_action('update_option_' . Settings::OPTION_KEY, [$this, 'sync_whitelist_from_settings'], 10, 2);
    }

    public static function sync_whitelist_on_load(): void
    {
        $ids = Settings::get()['whitelist_users'] ?? [];
        if (! is_array($ids)) {
            return;
        }

        self::apply_whitelist($ids);
    }

    public static function assign_role_caps(): void
    {
        $admin = get_role('administrator');
        if ($admin) {
            foreach (self::EDIT_CAPS as $cap) {
                $admin->add_cap($cap);
            }
        }

        $editor = get_role('editor');
        if ($editor) {
            foreach (self::WHITELIST_CAPS as $cap) {
                $editor->add_cap($cap);
            }
        }
    }

    /**
     * @param array<string> $caps
     * @param list<int>     $args
     * @return array<string>
     */
    public function restrict_delete(array $caps, string $cap, int $user_id, array $args): array
    {
        if ($cap !== 'delete_post' && $cap !== 'delete_ksv_verein') {
            return $caps;
        }

        $post_id = $args[0] ?? 0;
        if ($post_id <= 0) {
            return $caps;
        }

        $post = get_post($post_id);
        if (! $post instanceof \WP_Post || $post->post_type !== PostType::SLUG) {
            return $caps;
        }

        $user = get_userdata($user_id);
        if (! $user || ! in_array('administrator', (array) $user->roles, true)) {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }

    public function sync_whitelist_from_settings(mixed $old_value, mixed $value): void
    {
        if (! is_array($value)) {
            return;
        }

        $new_ids = array_map('absint', $value['whitelist_users'] ?? []);
        $old_ids = is_array($old_value) ? array_map('absint', $old_value['whitelist_users'] ?? []) : [];

        $removed = array_diff($old_ids, $new_ids);
        $added   = array_diff($new_ids, $old_ids);

        foreach ($removed as $user_id) {
            self::revoke_whitelist_caps((int) $user_id);
        }

        foreach ($added as $user_id) {
            self::grant_whitelist_caps((int) $user_id);
        }
    }

    public static function grant_whitelist_caps(int $user_id): void
    {
        $user = get_user_by('id', $user_id);
        if (! $user instanceof \WP_User) {
            return;
        }

        foreach (self::WHITELIST_CAPS as $cap) {
            $user->add_cap($cap);
        }
    }

    public static function revoke_whitelist_caps(int $user_id): void
    {
        $user = get_user_by('id', $user_id);
        if (! $user instanceof \WP_User) {
            return;
        }

        if (in_array('editor', (array) $user->roles, true)) {
            return;
        }

        foreach (self::WHITELIST_CAPS as $cap) {
            $user->remove_cap($cap);
        }
    }

    public static function apply_whitelist(array $user_ids): void
    {
        foreach ($user_ids as $user_id) {
            self::grant_whitelist_caps((int) $user_id);
        }
    }
}
