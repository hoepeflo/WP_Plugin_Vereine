<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class Shortcode
{
    public function register(): void
    {
        add_shortcode('ksv_vereine', [$this, 'render']);
    }

    /**
     * @param array<string, string>|string $atts
     */
    public function render(array|string $atts = []): string
    {
        return Frontend::render();
    }
}
