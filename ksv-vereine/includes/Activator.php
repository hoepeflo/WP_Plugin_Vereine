<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class Activator
{
    public static function activate(): void
    {
        // register() only hooks init; on activation init has already run.
        (new PostType())->register_post_type();
        (new Taxonomy())->register_taxonomy();

        (new PostType())->register();
        (new Taxonomy())->register();
        flush_rewrite_rules();
        Capabilities::assign_role_caps();
        Capabilities::sync_whitelist_on_load();
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}
