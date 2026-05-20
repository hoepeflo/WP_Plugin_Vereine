<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class Taxonomy
{
    public const SLUG = 'ksv_disziplin';

    /** @var array<string, string> slug => label */
    public const TERMS = [
        'luftdruckwaffen' => 'Luftdruckwaffen',
        'feuerwaffen'     => 'Feuerwaffen',
        'vorderlader'     => 'Vorderlader',
        'bogen'           => 'Bogen',
        'blasrohr'        => 'Blasrohr',
        'flinte'          => 'Flinte',
        'dart'            => 'Dart',
    ];

    public function register(): void
    {
        add_action('init', [$this, 'register_taxonomy']);
    }

    public function register_taxonomy(): void
    {
        register_taxonomy(self::SLUG, PostType::SLUG, [
            'labels'            => [
                'name'          => __('Disziplinen', 'ksv-vereine'),
                'singular_name' => __('Disziplin', 'ksv-vereine'),
            ],
            'public'            => false,
            'show_ui'           => false,
            'show_admin_column' => true,
            'hierarchical'      => false,
            'show_in_rest'      => false,
        ]);

        self::seed_terms();
    }

    public static function seed_terms(): void
    {
        if (! taxonomy_exists(self::SLUG)) {
            return;
        }

        foreach (self::TERMS as $slug => $name) {
            if (term_exists($slug, self::SLUG)) {
                continue;
            }

            $result = wp_insert_term($name, self::SLUG, ['slug' => $slug]);
            if (is_wp_error($result)) {
                continue;
            }
        }
    }

    /**
     * @return list<string> Display names from semicolon-separated import value.
     */
    public static function parse_import_disciplines(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        $parts = array_map('trim', explode(';', $value));

        return array_values(array_filter($parts, static fn (string $p): bool => $p !== ''));
    }

    /**
     * Resolve display names to term IDs.
     *
     * @param list<string> $names
     * @return list<int>
     */
    public static function term_ids_from_names(array $names): array
    {
        $ids = [];
        $flip = array_flip(self::TERMS);

        foreach ($names as $name) {
            $slug = array_search($name, $flip, true);
            if ($slug === false) {
                continue;
            }
            $term = get_term_by('slug', (string) $slug, self::SLUG);
            if ($term instanceof \WP_Term) {
                $ids[] = (int) $term->term_id;
            }
        }

        return $ids;
    }
}
