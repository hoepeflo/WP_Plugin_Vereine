<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class DiviModule
{
    public function register(): void
    {
        add_action('divi_extensions_init', [$this, 'load_extension']);
        add_action('et_builder_ready', [$this, 'register_module']);
    }

    public function load_extension(): void
    {
        // Divi-Extension-Hook für zukünftige Erweiterungen.
    }

    public function register_module(): void
    {
        if (! class_exists('ET_Builder_Module')) {
            return;
        }

        require_once KSV_VEREINE_PATH . 'includes/divi/VereinssucheModule.php';

        new Divi\VereinssucheModule();
    }
}
