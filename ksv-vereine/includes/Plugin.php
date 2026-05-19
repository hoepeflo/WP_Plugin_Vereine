<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class Plugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init(): void
    {
        load_plugin_textdomain('ksv-vereine', false, dirname(plugin_basename(KSV_VEREINE_FILE)) . '/languages');

        (new PostType())->register();
        (new Taxonomy())->register();
        (new MetaBoxes())->register();
        (new Capabilities())->register();
        (new Settings())->register();
        (new Geocoding())->register();
        (new RestApi())->register();
        (new Import())->register();
        (new Shortcode())->register();
        (new Block())->register();
    }
}
