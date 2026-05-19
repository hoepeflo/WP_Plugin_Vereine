<?php
/**
 * @var array{success?: int, failed?: int} $report
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e('Vereine – CSV-Import', 'ksv-vereine'); ?></h1>

    <?php if (isset($_GET['error'])) : ?>
        <div class="notice notice-error"><p><?php esc_html_e('Import fehlgeschlagen. Bitte Datei prüfen.', 'ksv-vereine'); ?></p></div>
    <?php endif; ?>

    <?php if (isset($report['success'])) : ?>
        <div class="notice notice-success">
            <p>
                <?php
                printf(
                    /* translators: 1: success count, 2: failed count */
                    esc_html__('Import abgeschlossen: %1$d erfolgreich, %2$d fehlgeschlagen.', 'ksv-vereine'),
                    (int) $report['success'],
                    (int) ($report['failed'] ?? 0)
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <p>
        <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=ksv_download_sample_csv'), 'ksv_download_sample')); ?>">
            <?php esc_html_e('Muster-CSV herunterladen', 'ksv-vereine'); ?>
        </a>
    </p>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field('ksv_import_csv'); ?>
        <input type="hidden" name="action" value="ksv_import_csv" />
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="ksv_csv"><?php esc_html_e('CSV-Datei', 'ksv-vereine'); ?></label></th>
                <td><input type="file" id="ksv_csv" name="ksv_csv" accept=".csv,text/csv" required /></td>
            </tr>
        </table>
        <?php submit_button(__('Import starten', 'ksv-vereine')); ?>
    </form>

    <h2><?php esc_html_e('Spalten der CSV', 'ksv-vereine'); ?></h2>
    <p><code>name,street,zip,city,website,disziplinen,active</code></p>
    <p><?php esc_html_e('Disziplinen semikolongetrennt, z. B. Luftdruckwaffen;Bogen;Dart', 'ksv-vereine'); ?></p>
</div>
