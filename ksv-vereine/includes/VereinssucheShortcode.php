<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class VereinssucheShortcode
{
    public function register(): void
    {
        add_shortcode('ksv_vereinssuche', [$this, 'render']);
    }

    /**
     * @param array<string, string>|string $atts
     */
    public function render(array|string $atts = []): string
    {
        if (! is_array($atts)) {
            $atts = [];
        }

        return Vereinssuche::render($atts);
    }
}
