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

    <section class="ksv-vereine__suggestions" data-ksv-suggestions aria-labelledby="ksv-suggestions-heading">
        <h2 id="ksv-suggestions-heading" class="ksv-vereine__suggestions-title">
            <?php esc_html_e('Datenänderung vorschlagen', 'ksv-vereine'); ?>
        </h2>
        <p class="ksv-vereine__suggestions-intro">
            <?php esc_html_e(
                'Sind die Angaben zu Ihrem Verein veraltet oder fehlerhaft? Wählen Sie Ihren Verein und schlagen Sie Korrekturen vor. Der Verband prüft die Angaben und übernimmt sie nach Freigabe.',
                'ksv-vereine'
            ); ?>
        </p>

        <form class="ksv-suggestion-form" data-ksv-suggestion-form novalidate>
            <div class="ksv-suggestion-form__field">
                <label for="ksv-suggestion-verein"><?php esc_html_e('Verein', 'ksv-vereine'); ?> <span class="ksv-required">*</span></label>
                <select id="ksv-suggestion-verein" name="verein_id" required data-ksv-suggestion-verein>
                    <option value=""><?php esc_html_e('Bitte Verein wählen …', 'ksv-vereine'); ?></option>
                </select>
            </div>

            <fieldset class="ksv-suggestion-form__fieldset" data-ksv-suggestion-fields disabled>
                <legend><?php esc_html_e('Vorgeschlagene Daten', 'ksv-vereine'); ?></legend>

                <div class="ksv-suggestion-form__field">
                    <label for="ksv-suggestion-name"><?php esc_html_e('Vereinsname', 'ksv-vereine'); ?></label>
                    <input type="text" id="ksv-suggestion-name" name="name" autocomplete="organization" />
                </div>

                <div class="ksv-suggestion-form__field">
                    <label for="ksv-suggestion-street"><?php esc_html_e('Straße', 'ksv-vereine'); ?></label>
                    <input type="text" id="ksv-suggestion-street" name="street" autocomplete="street-address" />
                </div>

                <div class="ksv-suggestion-form__row">
                    <div class="ksv-suggestion-form__field">
                        <label for="ksv-suggestion-zip"><?php esc_html_e('PLZ', 'ksv-vereine'); ?></label>
                        <input type="text" id="ksv-suggestion-zip" name="zip" inputmode="numeric" autocomplete="postal-code" />
                    </div>
                    <div class="ksv-suggestion-form__field">
                        <label for="ksv-suggestion-city"><?php esc_html_e('Ort', 'ksv-vereine'); ?></label>
                        <input type="text" id="ksv-suggestion-city" name="city" autocomplete="address-level2" />
                    </div>
                </div>

                <div class="ksv-suggestion-form__field">
                    <label for="ksv-suggestion-website"><?php esc_html_e('Webseite', 'ksv-vereine'); ?></label>
                    <input type="url" id="ksv-suggestion-website" name="website" placeholder="https://" autocomplete="url" />
                </div>

                <div class="ksv-suggestion-form__field">
                    <span class="ksv-suggestion-form__label"><?php esc_html_e('Disziplinen', 'ksv-vereine'); ?></span>
                    <div class="ksv-suggestion-form__disciplines" data-ksv-suggestion-disciplines></div>
                </div>

                <div class="ksv-suggestion-form__field">
                    <label for="ksv-suggestion-comment"><?php esc_html_e('Anmerkung (z. B. Logo-Änderung)', 'ksv-vereine'); ?></label>
                    <textarea id="ksv-suggestion-comment" name="comment" rows="3"></textarea>
                </div>

                <div class="ksv-suggestion-form__field">
                    <label for="ksv-suggestion-contact"><?php esc_html_e('Ihre E-Mail (optional, für Rückfragen)', 'ksv-vereine'); ?></label>
                    <input type="email" id="ksv-suggestion-contact" name="contact_email" autocomplete="email" />
                </div>

                <div class="ksv-suggestion-form__honeypot" aria-hidden="true">
                    <label for="ksv-suggestion-honeypot"><?php esc_html_e('Website', 'ksv-vereine'); ?></label>
                    <input type="text" id="ksv-suggestion-honeypot" name="website_honeypot" tabindex="-1" autocomplete="off" />
                </div>
            </fieldset>

            <p class="ksv-suggestion-form__status" role="status" aria-live="polite" data-ksv-suggestion-status hidden></p>

            <button type="submit" class="ksv-suggestion-form__submit" data-ksv-suggestion-submit disabled>
                <?php esc_html_e('Änderungen einreichen', 'ksv-vereine'); ?>
            </button>
        </form>
    </section>
</div>
