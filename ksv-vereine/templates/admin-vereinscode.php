<?php
/**
 * Metabox „App-Vereinscode“.
 *
 * @var int    $post_id
 * @var bool   $ist_neu
 * @var int    $version
 * @var bool   $hat_code
 * @var string $erstellt_text
 * @var bool   $einloesbar
 * @var string $verein_name
 * @var string $nonce
 * @var bool   $schluessel_fehlt
 * @var bool   $hash_doppelt
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div
    class="ksv-vereinscode"
    data-post-id="<?php echo esc_attr((string) $post_id); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    data-version="<?php echo esc_attr((string) $version); ?>"
    data-verein="<?php echo esc_attr($verein_name); ?>"
>
    <?php if ($schluessel_fehlt) : ?>
        <p class="ksv-vereinscode__hinweis">
            <?php esc_html_e('Vereinscodes sind nicht konfiguriert: Die Konstante KSV_VEREINE_CODE_KEY (mind. 32 Zeichen) muss in der wp-config.php gesetzt werden.', 'ksv-vereine'); ?>
        </p>
    <?php elseif ($ist_neu) : ?>
        <p><?php esc_html_e('Bitte speichern Sie den Verein zuerst. Danach kann ein Vereinscode für die App erzeugt werden.', 'ksv-vereine'); ?></p>
    <?php else : ?>
        <p class="ksv-vereinscode__status">
            <?php
            if ($hat_code) {
                echo esc_html(sprintf(
                    /* translators: 1: Code-Version, 2: Datum */
                    __('Aktiver Code: Version %1$s, erzeugt am %2$s.', 'ksv-vereine'),
                    (string) $version,
                    $erstellt_text !== '' ? $erstellt_text : '—'
                ));
            } else {
                esc_html_e('Für diesen Verein wurde noch kein Vereinscode erzeugt.', 'ksv-vereine');
            }
            ?>
        </p>

        <p class="description">
            <?php esc_html_e('Mit dem Vereinscode verknüpfen Mitglieder die App „KSV Fallingbostel“ mit dem Verein und erhalten interne Vereinsnachrichten. Gespeichert wird nur ein Hash – der Code ist ausschließlich direkt nach dem Erzeugen sichtbar.', 'ksv-vereine'); ?>
        </p>

        <?php if ($hash_doppelt) : ?>
            <p class="ksv-vereinscode__hinweis">
                <?php esc_html_e('Achtung: Ein weiterer Verein trägt denselben Vereinscode (kopierter Beitrag). Der Code kann nicht eingelöst werden – bitte zurücksetzen.', 'ksv-vereine'); ?>
            </p>
        <?php endif; ?>

        <?php if (! $einloesbar) : ?>
            <p class="ksv-vereinscode__hinweis">
                <?php esc_html_e('Hinweis: Der Code kann erst eingelöst werden, wenn der Verein veröffentlicht und als aktiv markiert ist.', 'ksv-vereine'); ?>
            </p>
        <?php endif; ?>

        <p>
            <button type="button" class="button <?php echo $hat_code ? '' : 'button-primary'; ?> ksv-vereinscode__button">
                <?php
                echo $hat_code
                    ? esc_html__('Code zurücksetzen', 'ksv-vereine')
                    : esc_html__('Vereinscode erzeugen', 'ksv-vereine');
                ?>
            </button>
        </p>

        <p class="ksv-vereinscode__meldung" role="status" aria-live="polite"></p>

        <div class="ksv-vereinscode__ergebnis" hidden>
            <p><strong><?php esc_html_e('Neuer Vereinscode:', 'ksv-vereine'); ?></strong></p>
            <p class="ksv-vereinscode__code"></p>
            <div class="ksv-vereinscode__qr"></div>
            <p class="ksv-vereinscode__warnung">
                <?php esc_html_e('Bitte jetzt notieren oder drucken – der Code wird nach dem Verlassen dieser Seite nicht erneut angezeigt.', 'ksv-vereine'); ?>
            </p>
            <p>
                <button type="button" class="button ksv-vereinscode__drucken">
                    <?php esc_html_e('Druckansicht öffnen', 'ksv-vereine'); ?>
                </button>
            </p>
        </div>
    <?php endif; ?>
</div>
