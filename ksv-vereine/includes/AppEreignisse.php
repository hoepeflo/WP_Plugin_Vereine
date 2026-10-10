<?php

declare(strict_types=1);

namespace KSV\Vereine;

/**
 * Löst App-Ereignisse über den Hook `ksv_app_ereignis` aus (API-Vertrag, Abschnitte 2 und 3.1).
 *
 * Ohne KSV App Connector hat der Hook keine Wirkung. Ereignisse enthalten keine Personendaten.
 */
final class AppEreignisse
{
    public const QUELLE = 'ksv-vereine';

    private static bool $import_laeuft = false;

    /** @var array<string, true> Bereits in dieser Anfrage ausgelöste Ereignisse (Entprellung). */
    private static array $ausgeloest = [];

    public function register(): void
    {
        add_action('wp_after_insert_post', [$this, 'nach_speichern'], 20, 2);
        add_action('trashed_post', [$this, 'nach_statuswechsel']);
        add_action('untrashed_post', [$this, 'nach_statuswechsel']);
        add_action('deleted_post', [$this, 'nach_loeschen'], 10, 2);

        add_action('ksv_vereine_import_beginn', [$this, 'import_beginn']);
        add_action('ksv_vereine_import_ende', [$this, 'import_ende']);

        add_action('created_' . Taxonomy::SLUG, [$this, 'disziplinen_geaendert']);
        add_action('edited_' . Taxonomy::SLUG, [$this, 'disziplinen_geaendert']);
        add_action('delete_' . Taxonomy::SLUG, [$this, 'disziplinen_geaendert']);
    }

    public function nach_speichern(int $post_id, \WP_Post $post): void
    {
        if ($post->post_type !== PostType::SLUG || $post->post_status === 'auto-draft') {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        self::verein_geaendert($post_id);
    }

    public function nach_statuswechsel(int $post_id): void
    {
        if (get_post_type($post_id) !== PostType::SLUG) {
            return;
        }

        self::verein_geaendert($post_id);
    }

    public function nach_loeschen(int $post_id, mixed $post = null): void
    {
        if (! $post instanceof \WP_Post || $post->post_type !== PostType::SLUG) {
            return;
        }

        self::verein_geaendert($post_id);
    }

    public function import_beginn(): void
    {
        self::$import_laeuft = true;
    }

    public function import_ende(int $erfolgreich = 0): void
    {
        self::$import_laeuft = false;

        if ($erfolgreich > 0) {
            // Sammelereignis für den gesamten Import (post_id 0).
            self::verein_geaendert(0, true);
        }
    }

    public function disziplinen_geaendert(): void
    {
        if (isset(self::$ausgeloest['disziplinen'])) {
            return;
        }
        self::$ausgeloest['disziplinen'] = true;

        self::ausloesen(
            'disziplinen.geaendert.' . time(),
            ['disziplinen', 'vereine']
        );
    }

    public static function verein_geaendert(int $post_id, bool $erzwingen = false): void
    {
        if (self::$import_laeuft && ! $erzwingen) {
            return;
        }

        $merker = 'verein.' . $post_id;
        if (isset(self::$ausgeloest[$merker]) && ! $erzwingen) {
            return;
        }
        self::$ausgeloest[$merker] = true;

        self::ausloesen(
            'vereine.geaendert.' . $post_id . '.' . time(),
            ['vereine'],
            $post_id > 0 ? ['verein_id' => $post_id] : []
        );
    }

    public static function code_zurueckgesetzt(int $verein_id, int $code_version): void
    {
        self::ausloesen(
            'vereine.code_zurueckgesetzt.' . $verein_id . '.' . $code_version,
            ['vereine'],
            [
                'verein_id'    => $verein_id,
                'code_version' => $code_version,
            ]
        );
    }

    /**
     * @param list<string>         $abgleich
     * @param array<string, mixed> $daten
     */
    private static function ausloesen(string $schluessel, array $abgleich, array $daten = []): void
    {
        do_action('ksv_app_ereignis', [
            'schluessel' => substr($schluessel, 0, 190),
            'quelle'     => self::QUELLE,
            'push'       => false,
            'thema'      => null,
            'titel'      => '',
            'text'       => '',
            'ziel'       => 'vereine',
            'abgleich'   => $abgleich,
            'daten'      => $daten,
        ]);
    }
}
