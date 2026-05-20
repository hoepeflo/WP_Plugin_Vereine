<?php

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="ksv-vereine" data-ksv-vereine>
    <p class="ksv-vereine__map-hint screen-reader-text"><?php esc_html_e('Die Karte ergänzt die Liste. Alle Vereinsinformationen finden Sie in der Liste.', 'ksv-vereine'); ?></p>

    <div class="ksv-vereine__toolbar">
        <div class="ksv-vereine__search">
            <label class="ksv-vereine__search-label" for="ksv-search-input"><?php esc_html_e('Vereine in Ihrer Nähe finden', 'ksv-vereine'); ?></label>
            <div class="ksv-vereine__search-row">
                <input
                    type="search"
                    id="ksv-search-input"
                    class="ksv-vereine__search-input"
                    placeholder="<?php esc_attr_e('Ort, PLZ oder Adresse eingeben …', 'ksv-vereine'); ?>"
                    autocomplete="address-level2"
                />
                <button type="button" class="ksv-vereine__search-btn"><?php esc_html_e('Suchen', 'ksv-vereine'); ?></button>
            </div>
            <p class="ksv-vereine__status" role="status" aria-live="polite" hidden></p>
        </div>

        <fieldset class="ksv-vereine__filters">
            <legend><?php esc_html_e('Nach Disziplin filtern', 'ksv-vereine'); ?></legend>
            <div class="ksv-vereine__filter-list" data-ksv-filters></div>
        </fieldset>
    </div>

    <div class="ksv-vereine__layout">
        <div class="ksv-vereine__list-wrap">
            <div class="ksv-vereine__list" data-ksv-list role="list"></div>
            <p class="ksv-vereine__empty" data-ksv-empty hidden><?php esc_html_e('Keine Vereine gefunden.', 'ksv-vereine'); ?></p>
        </div>
        <div class="ksv-vereine__map-wrap">
            <p class="screen-reader-text"><?php esc_html_e('Interaktive Karte der Vereinsstandorte', 'ksv-vereine'); ?></p>
            <div class="ksv-vereine__map" data-ksv-map></div>
        </div>
    </div>
</div>
