<?php
/**
 * @var array<string, mixed> $settings
 * @var list<\WP_User|object> $users
 */

namespace KSV\Vereine;

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e('KSV Vereine – Einstellungen', 'ksv-vereine'); ?></h1>
    <form method="post" action="options.php">
        <?php settings_fields('ksv_vereine_settings_group'); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="ors_api_key"><?php esc_html_e('OpenRouteService API-Key', 'ksv-vereine'); ?></label></th>
                <td>
                    <input type="password" class="large-text" id="ors_api_key" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[ors_api_key]" value="<?php echo esc_attr((string) $settings['ors_api_key']); ?>" autocomplete="off" />
                    <p class="description"><?php esc_html_e('Wird für Geocoding beim Speichern und für die Frontend-Suche verwendet.', 'ksv-vereine'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Karten-Standardansicht', 'ksv-vereine'); ?></th>
                <td>
                    <label for="map_lat"><?php esc_html_e('Breitengrad', 'ksv-vereine'); ?></label>
                    <input type="text" id="map_lat" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[map_lat]" value="<?php echo esc_attr((string) $settings['map_lat']); ?>" class="small-text" />
                    <label for="map_lng"><?php esc_html_e('Längengrad', 'ksv-vereine'); ?></label>
                    <input type="text" id="map_lng" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[map_lng]" value="<?php echo esc_attr((string) $settings['map_lng']); ?>" class="small-text" />
                    <label for="map_zoom"><?php esc_html_e('Zoom', 'ksv-vereine'); ?></label>
                    <input type="number" id="map_zoom" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[map_zoom]" value="<?php echo esc_attr((string) $settings['map_zoom']); ?>" min="1" max="18" class="small-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="placeholder_logo_id"><?php esc_html_e('Platzhalter-Logo (Medien-ID)', 'ksv-vereine'); ?></label></th>
                <td>
                    <input type="number" id="placeholder_logo_id" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[placeholder_logo_id]" value="<?php echo esc_attr((string) $settings['placeholder_logo_id']); ?>" class="small-text" />
                    <p class="description"><?php esc_html_e('Optional. Wenn leer, wird ein Standard-Platzhalter verwendet.', 'ksv-vereine'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="suggestion_notify_email"><?php esc_html_e('E-Mail für Änderungsvorschläge', 'ksv-vereine'); ?></label></th>
                <td>
                    <input
                        type="email"
                        class="large-text"
                        id="suggestion_notify_email"
                        name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[suggestion_notify_email]"
                        value="<?php echo esc_attr((string) ($settings['suggestion_notify_email'] ?? '')); ?>"
                        placeholder="<?php echo esc_attr((string) get_option('admin_email')); ?>"
                    />
                    <p class="description">
                        <?php esc_html_e(
                            'Empfänger für Änderungsvorschläge aus dem Frontend. Wenn leer, wird die WordPress-Admin-E-Mail verwendet (WP Mail SMTP).',
                            'ksv-vereine'
                        ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Benutzer-Whitelist', 'ksv-vereine'); ?></th>
                <td>
                    <p class="description"><?php esc_html_e('Diese Benutzer dürfen Vereine pflegen (zusätzlich zu Administratoren und Redakteuren).', 'ksv-vereine'); ?></p>
                    <select name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[whitelist_users][]" multiple size="8" style="min-width: 280px;">
                        <?php
                        $selected = array_map('intval', (array) ($settings['whitelist_users'] ?? []));
                        foreach ($users as $user) :
                            if (! isset($user->ID)) {
                                continue;
                            }
                            ?>
                            <option value="<?php echo esc_attr((string) $user->ID); ?>" <?php selected(in_array((int) $user->ID, $selected, true)); ?>>
                                <?php echo esc_html($user->display_name . ' (' . $user->user_login . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
