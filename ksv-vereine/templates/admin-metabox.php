<?php
/**
 * @var string $street
 * @var string $zip
 * @var string $city
 * @var string $website
 * @var int    $logo_id
 * @var bool   $active
 * @var string $lat
 * @var string $lng
 * @var string $geo_err
 * @var string $logo_url
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<table class="form-table ksv-metabox-table" role="presentation">
    <tr>
        <th scope="row"><label for="ksv_street"><?php esc_html_e('Straße', 'ksv-vereine'); ?></label></th>
        <td><input type="text" class="large-text" id="ksv_street" name="ksv_street" value="<?php echo esc_attr($street); ?>" /></td>
    </tr>
    <tr>
        <th scope="row"><label for="ksv_zip"><?php esc_html_e('PLZ', 'ksv-vereine'); ?></label></th>
        <td><input type="text" class="small-text" id="ksv_zip" name="ksv_zip" value="<?php echo esc_attr($zip); ?>" /></td>
    </tr>
    <tr>
        <th scope="row"><label for="ksv_city"><?php esc_html_e('Ort', 'ksv-vereine'); ?></label></th>
        <td><input type="text" class="regular-text" id="ksv_city" name="ksv_city" value="<?php echo esc_attr($city); ?>" /></td>
    </tr>
    <tr>
        <th scope="row"><label for="ksv_website"><?php esc_html_e('Webseite', 'ksv-vereine'); ?></label></th>
        <td><input type="url" class="large-text" id="ksv_website" name="ksv_website" value="<?php echo esc_attr($website); ?>" placeholder="https://" /></td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e('Logo', 'ksv-vereine'); ?></th>
        <td>
            <div class="ksv-logo-picker">
                <input type="hidden" id="ksv_logo_id" name="ksv_logo_id" value="<?php echo esc_attr((string) $logo_id); ?>" />
                <div class="ksv-logo-preview">
                    <?php if ($logo_url) : ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="" width="100" height="100" />
                    <?php else : ?>
                        <span class="ksv-logo-placeholder"><?php esc_html_e('Kein Logo', 'ksv-vereine'); ?></span>
                    <?php endif; ?>
                </div>
                <p>
                    <button type="button" class="button" id="ksv_logo_select"><?php esc_html_e('Logo auswählen', 'ksv-vereine'); ?></button>
                    <button type="button" class="button" id="ksv_logo_remove"><?php esc_html_e('Entfernen', 'ksv-vereine'); ?></button>
                </p>
            </div>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e('Status', 'ksv-vereine'); ?></th>
        <td>
            <label>
                <input type="checkbox" name="ksv_active" value="1" <?php checked($active); ?> />
                <?php esc_html_e('Verein ist aktiv (im Frontend sichtbar)', 'ksv-vereine'); ?>
            </label>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e('Koordinaten', 'ksv-vereine'); ?></th>
        <td class="ksv-coords-cell">
            <input type="hidden" id="ksv_lat" name="ksv_lat" value="<?php echo esc_attr($lat); ?>" />
            <input type="hidden" id="ksv_lng" name="ksv_lng" value="<?php echo esc_attr($lng); ?>" />

            <label class="ksv-manual-coords-label">
                <input
                    type="checkbox"
                    id="ksv_manual_coords"
                    name="ksv_manual_coords"
                    value="1"
                    <?php checked($manual_coords); ?>
                />
                <?php esc_html_e('Position manuell auf der Karte setzen', 'ksv-vereine'); ?>
            </label>

            <div id="ksv-coords-picker" class="ksv-coords-picker" <?php echo $manual_coords ? '' : 'hidden'; ?>>
                <p class="description ksv-coords-map-hint">
                    <?php esc_html_e(
                        'Klicken Sie auf die Karte oder ziehen Sie den Marker, um die Position zu setzen.',
                        'ksv-vereine'
                    ); ?>
                </p>
                <div id="ksv-coords-map" class="ksv-coords-map" role="application" aria-label="<?php esc_attr_e('Karte zur Positionsauswahl', 'ksv-vereine'); ?>"></div>
                <p class="ksv-coords-display-wrap">
                    <strong><?php esc_html_e('Aktuelle Position:', 'ksv-vereine'); ?></strong>
                    <span id="ksv-coords-display" class="ksv-coords-display">
                        <?php
                        if ($lat !== '' && $lng !== '') {
                            echo esc_html($lat . ', ' . $lng);
                        } else {
                            esc_html_e('Noch nicht gesetzt', 'ksv-vereine');
                        }
                        ?>
                    </span>
                </p>
            </div>

            <div id="ksv-coords-auto" class="ksv-coords-auto" <?php echo $manual_coords ? 'hidden' : ''; ?>>
                <?php if ($lat !== '' && $lng !== '') : ?>
                    <p id="ksv-coords-auto-display"><?php echo esc_html($lat . ', ' . $lng); ?></p>
                    <p class="description">
                        <?php esc_html_e(
                            'Koordinaten werden beim Speichern aus der Adresse ermittelt, sofern sich Straße, PLZ oder Ort ändern.',
                            'ksv-vereine'
                        ); ?>
                    </p>
                <?php else : ?>
                    <p class="description" id="ksv-coords-auto-display">
                        <?php esc_html_e('Noch keine Koordinaten. Beim Speichern mit Adresse werden diese ermittelt.', 'ksv-vereine'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if ($geo_err !== '') : ?>
                <p class="ksv-geo-error"><?php echo esc_html($geo_err); ?></p>
            <?php endif; ?>
        </td>
    </tr>
</table>
