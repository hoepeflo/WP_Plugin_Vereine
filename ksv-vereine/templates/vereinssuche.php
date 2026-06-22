<?php

if (! defined('ABSPATH')) {
    exit;
}

/** @var string $heading */
/** @var string $description */
/** @var string $image_url */
/** @var string $vereine_page_url */
/** @var bool $show_link */
?>
<section class="ksv-vereinssuche" data-ksv-vereinssuche>
    <div class="ksv-vereinssuche__inner">
        <div class="ksv-vereinssuche__content">
            <h2 class="ksv-vereinssuche__heading"><?php echo esc_html($heading); ?></h2>
            <p class="ksv-vereinssuche__description"><?php echo esc_html($description); ?></p>

            <form class="ksv-vereinssuche__form" data-ksv-vereinssuche-form action="<?php echo esc_url($vereine_page_url); ?>" method="get" novalidate>
                <label class="screen-reader-text" for="ksv-vereinssuche-input">
                    <?php esc_html_e('Ort, PLZ oder Vereinsname', 'ksv-vereine'); ?>
                </label>
                <div class="ksv-vereinssuche__search-row">
                    <input
                        type="search"
                        id="ksv-vereinssuche-input"
                        class="ksv-vereinssuche__input"
                        name="ksv_search"
                        placeholder="<?php esc_attr_e('Ort, PLZ oder Vereinsname', 'ksv-vereine'); ?>"
                        autocomplete="address-level2"
                        <?php echo $vereine_page_url === '' ? 'disabled' : ''; ?>
                    />
                    <button type="submit" class="ksv-vereinssuche__button" <?php echo $vereine_page_url === '' ? 'disabled' : ''; ?>>
                        <?php esc_html_e('Suchen', 'ksv-vereine'); ?>
                    </button>
                </div>
            </form>

            <?php if ($show_link && $vereine_page_url !== '') : ?>
                <a class="ksv-vereinssuche__link" href="<?php echo esc_url($vereine_page_url); ?>">
                    <?php esc_html_e('Zur Vereinssuche', 'ksv-vereine'); ?>
                    <span class="ksv-vereinssuche__link-arrow" aria-hidden="true">→</span>
                </a>
            <?php endif; ?>

            <?php if ($vereine_page_url === '') : ?>
                <p class="ksv-vereinssuche__notice" role="status">
                    <?php esc_html_e('Bitte hinterlegen Sie unter Vereine → Einstellungen die Zielseite für die Vereinssuche.', 'ksv-vereine'); ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="ksv-vereinssuche__map" aria-hidden="true">
            <img
                class="ksv-vereinssuche__map-image"
                src="<?php echo esc_url($image_url); ?>"
                alt=""
                loading="lazy"
                decoding="async"
            />
        </div>
    </div>
</section>
