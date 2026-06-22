<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class VereinssucheBlock
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

        register_block_type(KSV_VEREINE_PATH . 'blocks/vereinssuche', [
            'render_callback' => [$this, 'render'],
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function render(array $attributes = []): string
    {
        $atts = [
            'heading'           => isset($attributes['heading']) ? (string) $attributes['heading'] : '',
            'description'       => isset($attributes['description']) ? (string) $attributes['description'] : '',
            'image_id'          => isset($attributes['imageId']) ? (int) $attributes['imageId'] : 0,
            'image_url'         => isset($attributes['imageUrl']) ? (string) $attributes['imageUrl'] : '',
            'vereine_page_url'  => isset($attributes['vereinePageUrl']) ? (string) $attributes['vereinePageUrl'] : '',
            'show_link'         => ! array_key_exists('showLink', $attributes) || (bool) $attributes['showLink'],
        ];

        return Vereinssuche::render($atts);
    }
}
