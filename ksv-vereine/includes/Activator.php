<?php

declare(strict_types=1);

namespace KSV\Vereine;

final class Activator
{
    /** Option mit der zuletzt installierten Plugin-Version (für einmalige Aktualisierungsschritte). */
    public const OPTION_VERSION = 'ksv_vereine_version';

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
        self::upgrade();
    }

    /**
     * Führt einmalige Aktualisierungsschritte aus, wenn sich die Plugin-Version geändert hat.
     * Wird bei der Aktivierung und bei jedem Laden des Plugins geprüft.
     */
    public static function upgrade(): void
    {
        $installiert = (string) get_option(self::OPTION_VERSION, '');

        if ($installiert === KSV_VEREINE_VERSION) {
            return;
        }

        if ($installiert === '' || version_compare($installiert, '1.3.0', '<')) {
            self::entferne_vereinscode_metadaten();
        }

        update_option(self::OPTION_VERSION, KSV_VEREINE_VERSION, false);
    }

    /**
     * Die App-Vereinscodes wurden mit Version 1.3.0 entfernt. Alte Hashes, Versionen und
     * Zeitstempel werden einmalig aus den Beitrags-Metadaten gelöscht.
     */
    private static function entferne_vereinscode_metadaten(): void
    {
        foreach (['_ksv_app_code_hash', '_ksv_app_code_version', '_ksv_app_code_erstellt'] as $meta_key) {
            delete_post_meta_by_key($meta_key);
        }
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}
