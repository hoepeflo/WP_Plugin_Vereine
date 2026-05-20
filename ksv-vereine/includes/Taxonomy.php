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

        foreach ($names as $name) {
            $term = get_term_by('name', $name, self::SLUG);
            if (! $term instanceof \WP_Term) {
                $flip = array_flip(self::TERMS);
                $slug = array_search($name, $flip, true);
                if ($slug !== false) {
                    $term = get_term_by('slug', (string) $slug, self::SLUG);
                }
            }
            if ($term instanceof \WP_Term) {
                $ids[] = (int) $term->term_id;
            }
        }

        return $ids;
    }

    /**
     * @return list<\WP_Term>
     */
    public static function get_all_terms(): array
    {
        $terms = get_terms([
            'taxonomy'   => self::SLUG,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if (is_wp_error($terms) || $terms === []) {
            self::seed_terms();
            $terms = get_terms([
                'taxonomy'   => self::SLUG,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);
        }

        if (is_wp_error($terms)) {
            return [];
        }

        return array_values(array_filter($terms, static fn ($t): bool => $t instanceof \WP_Term));
    }

    /**
     * @return list<array{slug: string, name: string}>
     */
    public static function get_all_for_frontend(): array
    {
        $terms = self::get_all_terms();
        if ($terms === []) {
            return array_map(
                static fn (string $slug, string $name): array => ['slug' => $slug, 'name' => $name],
                array_keys(self::TERMS),
                array_values(self::TERMS)
            );
        }

        return array_map(
            static fn (\WP_Term $term): array => [
                'slug' => $term->slug,
                'name' => $term->name,
            ],
            $terms
        );
    }

    public static function count_vereine_for_term(int $term_id): int
    {
        $query = new \WP_Query([
            'post_type'      => PostType::SLUG,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'tax_query'      => [
                [
                    'taxonomy' => self::SLUG,
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ],
            ],
        ]);

        return (int) $query->found_posts;
    }
}
