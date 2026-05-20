<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class Activator
{
    public static function activate(): void
    {
        (new PostType())->register();
        (new Taxonomy())->register();
        flush_rewrite_rules();

        Taxonomy::seed_terms();
        Capabilities::assign_role_caps();
        Capabilities::sync_whitelist_on_load();
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}
