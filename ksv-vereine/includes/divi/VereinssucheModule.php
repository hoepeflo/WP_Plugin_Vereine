<?php

declare(strict_types=1);

namespace KSV\Vereine\Divi;

use KSV\Vereine\Vereinssuche;

if (! class_exists('ET_Builder_Module')) {
    return;
}

final class VereinssucheModule extends \ET_Builder_Module
{
    public $slug = 'ksv_vereinssuche';

    public $vb_support = 'on';

    /**
     * @var array<string, string>
     */
    protected $module_credits = [
        'module_uri' => '',
        'author'     => 'Florian Höper',
        'author_uri' => '',
    ];

    public function init(): void
    {
        $this->name       = esc_html__('KSV Vereinssuche', 'ksv-vereine');
        $this->icon_path  = '';
        $this->folder_name = 'ksv-vereine';
    }

    /**
     * @return array<string, mixed>
     */
    public function get_fields(): array
    {
        return [
            'heading' => [
                'label'           => esc_html__('Überschrift', 'ksv-vereine'),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'default'         => esc_html__('Schützenverein in Deiner Nähe finden', 'ksv-vereine'),
                'toggle_slug'     => 'main_content',
            ],
            'description' => [
                'label'           => esc_html__('Beschreibung', 'ksv-vereine'),
                'type'            => 'textarea',
                'option_category' => 'basic_option',
                'default'         => esc_html__(
                    'Nutze unsere dynamische Vereinssuche und finde Schützenvereine in Deiner Umgebung.',
                    'ksv-vereine'
                ),
                'toggle_slug'     => 'main_content',
            ],
            'image' => [
                'label'              => esc_html__('Kartengrafik', 'ksv-vereine'),
                'type'               => 'upload',
                'option_category'    => 'basic_option',
                'upload_button_text' => esc_html__('Bild hochladen', 'ksv-vereine'),
                'choose_text'        => esc_html__('Bild wählen', 'ksv-vereine'),
                'update_text'        => esc_html__('Bild ersetzen', 'ksv-vereine'),
                'toggle_slug'        => 'main_content',
            ],
            'vereine_page_url' => [
                'label'           => esc_html__('Vereinsseite (URL)', 'ksv-vereine'),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'description'     => esc_html__(
                    'Optional. Leer lassen, um die Einstellung aus Vereine → Einstellungen zu verwenden.',
                    'ksv-vereine'
                ),
                'toggle_slug'     => 'main_content',
            ],
            'show_link' => [
                'label'           => esc_html__('Link „Zur Vereinssuche“ anzeigen', 'ksv-vereine'),
                'type'            => 'yes_no_button',
                'option_category' => 'configuration',
                'options'         => [
                    'on'  => esc_html__('Ja', 'ksv-vereine'),
                    'off' => esc_html__('Nein', 'ksv-vereine'),
                ],
                'default'         => 'on',
                'toggle_slug'     => 'main_content',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $attrs
     * @param string|null $content
     * @param string $render_slug
     */
    public function render($attrs, $content, $render_slug): string
    {
        unset($content, $render_slug);

        $image_url = isset($attrs['image']) ? (string) $attrs['image'] : '';

        return Vereinssuche::render([
            'heading'          => isset($attrs['heading']) ? (string) $attrs['heading'] : '',
            'description'      => isset($attrs['description']) ? (string) $attrs['description'] : '',
            'image_url'        => $image_url,
            'vereine_page_url' => isset($attrs['vereine_page_url']) ? (string) $attrs['vereine_page_url'] : '',
            'show_link'        => ($attrs['show_link'] ?? 'on') === 'on',
        ]);
    }
}
