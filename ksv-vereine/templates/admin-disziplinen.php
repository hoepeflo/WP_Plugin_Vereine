<?php
/**
 * @var list<\WP_Term>              $terms
 * @var \WP_Term|null               $editing
 * @var array{type: string, message: string}|null $notice
 */

namespace KSV\Vereine;

use KSV\Vereine\DisziplinAdmin;
use KSV\Vereine\PostType;
use KSV\Vereine\Taxonomy;

if (! defined('ABSPATH')) {
    exit;
}

$base_url = admin_url('edit.php?post_type=' . PostType::SLUG . '&page=' . DisziplinAdmin::PAGE_SLUG);
?>
<div class="wrap ksv-disziplinen-admin">
    <h1><?php esc_html_e('Kategorien (Disziplinen)', 'ksv-vereine'); ?></h1>

    <p class="description">
        <?php esc_html_e('Kategorien werden Vereinen zugeordnet und im Frontend als Filter angezeigt. Slugs werden in URLs und API-Abfragen verwendet.', 'ksv-vereine'); ?>
    </p>

    <?php if ($notice !== null) : ?>
        <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
            <p><?php echo esc_html($notice['message']); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($editing instanceof \WP_Term) : ?>
        <h2><?php esc_html_e('Kategorie bearbeiten', 'ksv-vereine'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ksv-disziplin-form">
            <?php wp_nonce_field('ksv_disziplin_update'); ?>
            <input type="hidden" name="action" value="ksv_disziplin_update" />
            <input type="hidden" name="term_id" value="<?php echo esc_attr((string) $editing->term_id); ?>" />
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ksv_disziplin_name"><?php esc_html_e('Name', 'ksv-vereine'); ?></label></th>
                    <td>
                        <input type="text" class="regular-text" id="ksv_disziplin_name" name="name" value="<?php echo esc_attr($editing->name); ?>" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ksv_disziplin_slug"><?php esc_html_e('Slug', 'ksv-vereine'); ?></label></th>
                    <td>
                        <input type="text" class="regular-text" id="ksv_disziplin_slug" name="slug" value="<?php echo esc_attr($editing->slug); ?>" />
                        <p class="description"><?php esc_html_e('Kleinbuchstaben, Bindestriche. Wird für Filter und Import verwendet.', 'ksv-vereine'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Speichern', 'ksv-vereine')); ?>
            <a class="button" href="<?php echo esc_url($base_url); ?>"><?php esc_html_e('Abbrechen', 'ksv-vereine'); ?></a>
        </form>
    <?php else : ?>
        <h2><?php esc_html_e('Neue Kategorie anlegen', 'ksv-vereine'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ksv-disziplin-form">
            <?php wp_nonce_field('ksv_disziplin_create'); ?>
            <input type="hidden" name="action" value="ksv_disziplin_create" />
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ksv_disziplin_name_new"><?php esc_html_e('Name', 'ksv-vereine'); ?></label></th>
                    <td>
                        <input type="text" class="regular-text" id="ksv_disziplin_name_new" name="name" required />
                        <p class="description"><?php esc_html_e('Der Slug wird automatisch aus dem Namen erzeugt.', 'ksv-vereine'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Kategorie anlegen', 'ksv-vereine'), 'primary', 'submit', false); ?>
        </form>
    <?php endif; ?>

    <h2><?php esc_html_e('Vorhandene Kategorien', 'ksv-vereine'); ?></h2>

    <?php if ($terms === []) : ?>
        <p><?php esc_html_e('Noch keine Kategorien vorhanden.', 'ksv-vereine'); ?></p>
    <?php else : ?>
        <table class="widefat striped ksv-disziplinen-table">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Name', 'ksv-vereine'); ?></th>
                    <th scope="col"><?php esc_html_e('Slug', 'ksv-vereine'); ?></th>
                    <th scope="col"><?php esc_html_e('Vereine', 'ksv-vereine'); ?></th>
                    <th scope="col"><?php esc_html_e('Aktionen', 'ksv-vereine'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($terms as $term) : ?>
                    <?php $usage = Taxonomy::count_vereine_for_term((int) $term->term_id); ?>
                    <tr>
                        <td><strong><?php echo esc_html($term->name); ?></strong></td>
                        <td><code><?php echo esc_html($term->slug); ?></code></td>
                        <td><?php echo esc_html((string) $usage); ?></td>
                        <td class="ksv-disziplinen-actions">
                            <a href="<?php echo esc_url($base_url . '&edit=' . (int) $term->term_id); ?>">
                                <?php esc_html_e('Bearbeiten', 'ksv-vereine'); ?>
                            </a>
                            <?php if ($usage === 0) : ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ksv-inline-delete-form">
                                    <?php wp_nonce_field('ksv_disziplin_delete'); ?>
                                    <input type="hidden" name="action" value="ksv_disziplin_delete" />
                                    <input type="hidden" name="term_id" value="<?php echo esc_attr((string) $term->term_id); ?>" />
                                    <button
                                        type="submit"
                                        class="button-link-delete"
                                        onclick="return confirm('<?php echo esc_js(__('Diese Kategorie wirklich löschen?', 'ksv-vereine')); ?>');"
                                    >
                                        <?php esc_html_e('Löschen', 'ksv-vereine'); ?>
                                    </button>
                                </form>
                            <?php else : ?>
                                <span class="ksv-delete-disabled" title="<?php esc_attr_e('Zuerst von allen Vereinen entfernen', 'ksv-vereine'); ?>">
                                    <?php esc_html_e('Löschen', 'ksv-vereine'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
