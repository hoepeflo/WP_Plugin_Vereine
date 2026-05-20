<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class DisziplinAdmin
{
    public const PAGE_SLUG = 'ksv-vereine-disziplinen';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_ksv_disziplin_create', [$this, 'handle_create']);
        add_action('admin_post_ksv_disziplin_update', [$this, 'handle_update']);
        add_action('admin_post_ksv_disziplin_delete', [$this, 'handle_delete']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . PostType::SLUG,
            __('Kategorien (Disziplinen)', 'ksv-vereine'),
            __('Kategorien', 'ksv-vereine'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_page']
        );
    }

    public function enqueue_assets(string $hook): void
    {
        if ($hook !== PostType::SLUG . '_page_' . self::PAGE_SLUG) {
            return;
        }

        wp_enqueue_style(
            'ksv-vereine-admin',
            KSV_VEREINE_URL . 'assets/css/admin.css',
            [],
            KSV_VEREINE_VERSION
        );
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $editing = null;
        if ($edit_id > 0) {
            $editing = get_term($edit_id, Taxonomy::SLUG);
            if (! $editing instanceof \WP_Term) {
                $editing = null;
            }
        }

        $terms = Taxonomy::get_all_terms();
        $notice = $this->notice_from_query();

        include KSV_VEREINE_PATH . 'templates/admin-disziplinen.php';
    }

    public function handle_create(): void
    {
        $this->assert_permission();
        check_admin_referer('ksv_disziplin_create');

        $name = $this->sanitize_name_from_post();
        if ($name === '') {
            $this->redirect_with_error('empty_name');
        }

        $slug = $this->unique_slug(sanitize_title($name));
        $result = wp_insert_term($name, Taxonomy::SLUG, ['slug' => $slug]);

        if (is_wp_error($result)) {
            $this->redirect_with_error($this->error_code($result));
        }

        $this->redirect_with_success('created');
    }

    public function handle_update(): void
    {
        $this->assert_permission();
        check_admin_referer('ksv_disziplin_update');

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        if ($term_id <= 0) {
            $this->redirect_with_error('invalid_term');
        }

        $term = get_term($term_id, Taxonomy::SLUG);
        if (! $term instanceof \WP_Term) {
            $this->redirect_with_error('invalid_term');
        }

        $name = $this->sanitize_name_from_post();
        if ($name === '') {
            $this->redirect_with_error('empty_name', $term_id);
        }

        $slug_input = isset($_POST['slug']) ? sanitize_title(wp_unslash((string) $_POST['slug'])) : '';
        $slug       = $slug_input !== '' ? $this->unique_slug($slug_input, $term_id) : $term->slug;

        $result = wp_update_term($term_id, Taxonomy::SLUG, [
            'name' => $name,
            'slug' => $slug,
        ]);

        if (is_wp_error($result)) {
            $this->redirect_with_error($this->error_code($result), $term_id);
        }

        $this->redirect_with_success('updated');
    }

    public function handle_delete(): void
    {
        $this->assert_permission();
        check_admin_referer('ksv_disziplin_delete');

        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        if ($term_id <= 0) {
            $this->redirect_with_error('invalid_term');
        }

        $term = get_term($term_id, Taxonomy::SLUG);
        if (! $term instanceof \WP_Term) {
            $this->redirect_with_error('invalid_term');
        }

        $usage = Taxonomy::count_vereine_for_term($term_id);
        if ($usage > 0) {
            $this->redirect_with_error('in_use');
        }

        $result = wp_delete_term($term_id, Taxonomy::SLUG);
        if (is_wp_error($result) || $result === false) {
            $this->redirect_with_error('delete_failed');
        }

        $this->redirect_with_success('deleted');
    }

    private function assert_permission(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'ksv-vereine'));
        }
    }

    private function sanitize_name_from_post(): string
    {
        if (! isset($_POST['name'])) {
            return '';
        }

        return sanitize_text_field(wp_unslash((string) $_POST['name']));
    }

    private function unique_slug(string $base_slug, int $exclude_term_id = 0): string
    {
        $slug = $base_slug !== '' ? $base_slug : 'kategorie';
        $candidate = $slug;
        $suffix    = 2;

        while ($this->slug_taken($candidate, $exclude_term_id)) {
            $candidate = $slug . '-' . $suffix;
            ++$suffix;
        }

        return $candidate;
    }

    private function slug_taken(string $slug, int $exclude_term_id = 0): bool
    {
        $existing = get_term_by('slug', $slug, Taxonomy::SLUG);
        if (! $existing instanceof \WP_Term) {
            return false;
        }

        return $exclude_term_id <= 0 || (int) $existing->term_id !== $exclude_term_id;
    }

    private function error_code(\WP_Error $error): string
    {
        $code = $error->get_error_code();

        return is_string($code) && $code !== '' ? $code : 'unknown';
    }

    /**
     * @return array{type: string, message: string}|null
     */
    private function notice_from_query(): ?array
    {
        if (isset($_GET['created'])) {
            return [
                'type'    => 'success',
                'message' => __('Kategorie wurde angelegt.', 'ksv-vereine'),
            ];
        }

        if (isset($_GET['updated'])) {
            return [
                'type'    => 'success',
                'message' => __('Kategorie wurde gespeichert.', 'ksv-vereine'),
            ];
        }

        if (isset($_GET['deleted'])) {
            return [
                'type'    => 'success',
                'message' => __('Kategorie wurde gelöscht.', 'ksv-vereine'),
            ];
        }

        $error = isset($_GET['error']) ? sanitize_key((string) $_GET['error']) : '';
        if ($error === '') {
            return null;
        }

        return [
            'type'    => 'error',
            'message' => $this->error_message($error),
        ];
    }

    private function error_message(string $code): string
    {
        return match ($code) {
            'empty_name'    => __('Bitte geben Sie einen Namen ein.', 'ksv-vereine'),
            'invalid_term'  => __('Die Kategorie wurde nicht gefunden.', 'ksv-vereine'),
            'in_use'        => __('Die Kategorie kann nicht gelöscht werden, weil sie noch Vereinen zugeordnet ist.', 'ksv-vereine'),
            'delete_failed' => __('Die Kategorie konnte nicht gelöscht werden.', 'ksv-vereine'),
            'term_exists'   => __('Eine Kategorie mit diesem Namen oder Slug existiert bereits.', 'ksv-vereine'),
            default         => __('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'ksv-vereine'),
        };
    }

    private function redirect_with_success(string $action): void
    {
        wp_safe_redirect(
            admin_url(
                'edit.php?post_type=' . PostType::SLUG
                . '&page=' . self::PAGE_SLUG
                . '&' . $action . '=1'
            )
        );
        exit;
    }

    private function redirect_with_error(string $code, int $edit_id = 0): void
    {
        $url = admin_url(
            'edit.php?post_type=' . PostType::SLUG
            . '&page=' . self::PAGE_SLUG
            . '&error=' . rawurlencode($code)
        );

        if ($edit_id > 0) {
            $url .= '&edit=' . $edit_id;
        }

        wp_safe_redirect($url);
        exit;
    }
}
