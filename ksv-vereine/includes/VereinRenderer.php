<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class VereinRenderer
{
    /**
     * @param list<array<string, mixed>> $vereine
     */
    public static function render_list(array $vereine): string
    {
        if ($vereine === []) {
            return '';
        }

        $html = '';
        foreach ($vereine as $verein) {
            if (is_array($verein)) {
                $html .= self::render_card($verein);
            }
        }

        return $html;
    }

    /**
     * @param array<string, mixed> $verein
     */
    public static function render_card(array $verein): string
    {
        $id       = (int) ($verein['id'] ?? 0);
        $name     = (string) ($verein['name'] ?? '');
        $address  = (string) ($verein['address'] ?? '');
        $website  = (string) ($verein['website'] ?? '');
        $logo_url = (string) ($verein['logo_url'] ?? '');
        $tags     = is_array($verein['disziplinen'] ?? null) ? $verein['disziplinen'] : [];

        $has_website = $website !== '';
        $tag         = $has_website ? 'a' : 'article';
        $classes     = 'ksv-card' . ($has_website ? ' ksv-card--linked' : '');

        $attrs = sprintf(
            'class="%s" id="ksv-verein-%d" role="listitem"',
            esc_attr($classes),
            $id
        );

        if ($has_website) {
            $attrs .= sprintf(
                ' href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s"',
                esc_url($website),
                esc_attr(sprintf(__('Webseite von %s öffnen', 'ksv-vereine'), $name))
            );
        } else {
            $attrs .= ' tabindex="-1"';
        }

        $logo_html = sprintf(
            '<img class="ksv-card__logo" src="%s" alt="" width="100" height="100" loading="lazy" />',
            esc_url($logo_url)
        );

        $body_parts = [
            sprintf('<h3 class="ksv-card__title">%s</h3>', esc_html($name)),
        ];

        if ($address !== '') {
            $body_parts[] = sprintf('<p class="ksv-card__address">%s</p>', esc_html($address));
        }

        if ($tags !== []) {
            $tag_items = '';
            foreach ($tags as $tag_data) {
                if (! is_array($tag_data)) {
                    continue;
                }
                $tag_items .= sprintf(
                    '<span class="ksv-card__tag">%s</span>',
                    esc_html((string) ($tag_data['name'] ?? ''))
                );
            }
            if ($tag_items !== '') {
                $body_parts[] = sprintf('<div class="ksv-card__tags">%s</div>', $tag_items);
            }
        }

        if ($has_website) {
            $body_parts[] = sprintf('<p class="ksv-card__url">%s</p>', esc_html($website));
        }

        $body_html = sprintf(
            '<div class="ksv-card__body">%s</div>',
            implode('', $body_parts)
        );

        return sprintf('<%1$s %2$s>%3$s%4$s</%1$s>', $tag, $attrs, $logo_html, $body_html);
    }

    /**
     * @param list<array{slug: string, name: string}> $disciplines
     */
    public static function render_filter_chips(array $disciplines): string
    {
        if ($disciplines === []) {
            return '';
        }

        $html = '';
        foreach ($disciplines as $discipline) {
            $slug = (string) ($discipline['slug'] ?? '');
            $name = (string) ($discipline['name'] ?? '');
            if ($slug === '') {
                continue;
            }

            $html .= sprintf(
                '<label class="ksv-vereine__filter-chip"><input type="checkbox" id="ksv-filter-%1$s" value="%1$s" /> %2$s</label>',
                esc_attr($slug),
                esc_html($name)
            );
        }

        return $html;
    }

    /**
     * @param list<array<string, mixed>> $vereine
     */
    public static function render_suggestion_options(array $vereine): string
    {
        $html = sprintf(
            '<option value="">%s</option>',
            esc_html__('Bitte Verein wählen …', 'ksv-vereine')
        );

        foreach ($vereine as $verein) {
            if (! is_array($verein)) {
                continue;
            }
            $html .= sprintf(
                '<option value="%d">%s</option>',
                (int) ($verein['id'] ?? 0),
                esc_html((string) ($verein['name'] ?? ''))
            );
        }

        return $html;
    }

    /**
     * @param list<array<string, mixed>> $vereine
     */
    public static function render_json_ld(array $vereine): string
    {
        if ($vereine === []) {
            return '';
        }

        $items = [];
        foreach ($vereine as $index => $verein) {
            if (! is_array($verein)) {
                continue;
            }

            $organization = [
                '@type' => 'SportsOrganization',
                'name'  => (string) ($verein['name'] ?? ''),
            ];

            $address = (string) ($verein['address'] ?? '');
            if ($address !== '') {
                $organization['address'] = [
                    '@type'           => 'PostalAddress',
                    'streetAddress'   => (string) ($verein['street'] ?? ''),
                    'postalCode'      => (string) ($verein['zip'] ?? ''),
                    'addressLocality' => (string) ($verein['city'] ?? ''),
                ];
            }

            $website = (string) ($verein['website'] ?? '');
            if ($website !== '') {
                $organization['url'] = $website;
            }

            $items[] = [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'item'     => $organization,
            ];
        }

        if ($items === []) {
            return '';
        }

        $schema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => __('Mitgliedsvereine', 'ksv-vereine'),
            'numberOfItems'   => count($items),
            'itemListElement' => $items,
        ];

        $json = wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (! is_string($json)) {
            return '';
        }

        return sprintf(
            '<script type="application/ld+json">%s</script>',
            $json
        );
    }
}
