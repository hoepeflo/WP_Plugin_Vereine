<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class Import
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_ksv_import_csv', [$this, 'handle_import']);
        add_action('admin_post_ksv_download_sample_csv', [$this, 'download_sample']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . PostType::SLUG,
            __('CSV-Import', 'ksv-vereine'),
            __('Import', 'ksv-vereine'),
            'manage_options',
            'ksv-vereine-import',
            [$this, 'render_page']
        );
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $report = [];
        if (isset($_GET['imported']) && is_numeric($_GET['imported'])) {
            $report['success'] = (int) $_GET['imported'];
            $report['failed']  = isset($_GET['failed']) ? (int) $_GET['failed'] : 0;
        }

        include KSV_VEREINE_PATH . 'templates/admin-import.php';
    }

    public function download_sample(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'ksv-vereine'));
        }

        check_admin_referer('ksv_download_sample');

        $file = KSV_VEREINE_PATH . 'samples/vereine-beispiel.csv';
        if (! is_readable($file)) {
            wp_die(esc_html__('Musterdatei nicht gefunden.', 'ksv-vereine'));
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="vereine-beispiel.csv"');
        readfile($file);
        exit;
    }

    public function handle_import(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'ksv-vereine'));
        }

        check_admin_referer('ksv_import_csv');

        if (
            ! isset($_FILES['ksv_csv'])
            || ! is_array($_FILES['ksv_csv'])
            || (int) ($_FILES['ksv_csv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        ) {
            wp_safe_redirect(admin_url('edit.php?post_type=' . PostType::SLUG . '&page=ksv-vereine-import&error=upload'));
            exit;
        }

        $tmp  = (string) $_FILES['ksv_csv']['tmp_name'];
        $rows = $this->parse_csv($tmp);
        if ($rows === null) {
            wp_safe_redirect(admin_url('edit.php?post_type=' . PostType::SLUG . '&page=ksv-vereine-import&error=parse'));
            exit;
        }

        $success = 0;
        $failed  = 0;

        foreach ($rows as $row) {
            if ($this->import_row($row)) {
                ++$success;
            } else {
                ++$failed;
            }
            usleep(200000);
        }

        wp_safe_redirect(
            admin_url(
                'edit.php?post_type=' . PostType::SLUG
                . '&page=ksv-vereine-import&imported=' . $success . '&failed=' . $failed
            )
        );
        exit;
    }

    /**
     * @return list<array<string, string>>|null
     */
    private function parse_csv(string $path): ?array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return null;
        }

        $header = array_map(static fn (string $h): string => strtolower(trim($h)), $header);
        $rows   = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === 1 && ($data[0] === null || $data[0] === '')) {
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = isset($data[$i]) ? trim((string) $data[$i]) : '';
            }
            if (($row['name'] ?? '') !== '') {
                $rows[] = $row;
            }
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param array<string, string> $row
     */
    private function import_row(array $row): bool
    {
        $post_id = wp_insert_post([
            'post_type'   => PostType::SLUG,
            'post_title'  => $row['name'],
            'post_status' => 'publish',
        ], true);

        if (is_wp_error($post_id) || ! is_int($post_id)) {
            return false;
        }

        update_post_meta($post_id, '_ksv_street', $row['street'] ?? '');
        update_post_meta($post_id, '_ksv_zip', $row['zip'] ?? '');
        update_post_meta($post_id, '_ksv_city', $row['city'] ?? '');
        update_post_meta($post_id, '_ksv_website', esc_url_raw($row['website'] ?? ''));
        update_post_meta($post_id, '_ksv_active', ($row['active'] ?? '1') === '1' ? '1' : '0');

        $discipline_names = Taxonomy::parse_import_disciplines($row['disziplinen'] ?? '');
        $term_ids         = Taxonomy::term_ids_from_names($discipline_names);
        wp_set_object_terms($post_id, $term_ids, Taxonomy::SLUG);

        Geocoding::geocode_post($post_id);

        return true;
    }
}
