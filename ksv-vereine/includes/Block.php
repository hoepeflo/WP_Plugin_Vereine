<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class Block
{
    public function register(): void
    {
        add_action('init', [$this, 'register_block']);
    }

    public function register_block(): void
    {
        if (! function_exists('register_block_type')) {
            return;
        }

        register_block_type(KSV_VEREINE_PATH . 'blocks/vereine', [
            'render_callback' => static fn (): string => Frontend::render(),
        ]);
    }
}
