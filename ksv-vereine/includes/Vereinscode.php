<?php

declare(strict_types=1);

namespace KSV\Vereine;

/**
 * Vereinscodes für die App (API-Vertrag, Abschnitt 3.1).
 *
 * Je Verein genau ein gültiger Code im Format KSV-XXXX-XXXX. Gespeichert wird nur ein
 * HMAC-SHA256-Hash in Post-Meta, dazu die Code-Version (beginnt bei 1).
 */
final class Vereinscode
{
    public const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    public const PRAEFIX  = 'KSV';
    public const LAENGE   = 8;

    public const META_HASH     = '_ksv_app_code_hash';
    public const META_VERSION  = '_ksv_app_code_version';
    public const META_ERSTELLT = '_ksv_app_code_erstellt';

    /**
     * Zufälligen Klartext-Code erzeugen, z. B. „KSV-7Q4M-X2PA“.
     */
    public static function zufallscode(): string
    {
        $max   = strlen(self::ALPHABET) - 1;
        $kern  = '';
        for ($i = 0; $i < self::LAENGE; ++$i) {
            $kern .= self::ALPHABET[random_int(0, $max)];
        }

        return self::formatieren($kern);
    }

    public static function formatieren(string $kern): string
    {
        return self::PRAEFIX . '-' . substr($kern, 0, 4) . '-' . substr($kern, 4, 4);
    }

    /**
     * Eingabe normalisieren: Groß-/Kleinschreibung, Bindestriche und Leerzeichen spielen keine Rolle.
     * Das Präfix „KSV“ ist optional. Liefert die 8 Zeichen des Codes oder null bei ungültigem Format.
     */
    public static function normalisieren(string $eingabe): ?string
    {
        if (strlen($eingabe) > 64) {
            return null;
        }

        $wert = preg_replace('/[\s\-_\x{2010}-\x{2015}]+/u', '', $eingabe);
        if (! is_string($wert)) {
            return null;
        }

        $wert = strtoupper($wert);
        if (strlen($wert) === strlen(self::PRAEFIX) + self::LAENGE && str_starts_with($wert, self::PRAEFIX)) {
            $wert = substr($wert, strlen(self::PRAEFIX));
        }

        if (strlen($wert) !== self::LAENGE || strspn($wert, self::ALPHABET) !== self::LAENGE) {
            return null;
        }

        return $wert;
    }

    /** Mindestlänge des Schlüssels KSV_VEREINE_CODE_KEY. */
    public const SCHLUESSEL_MINDESTLAENGE = 32;

    /**
     * HMAC über den normalisierten Code. Schlüssel: Konstante KSV_VEREINE_CODE_KEY aus der
     * wp-config.php (mind. 32 Zeichen). Ohne gültigen Schlüssel wird kein Hash gebildet.
     *
     * @throws \RuntimeException Wenn KSV_VEREINE_CODE_KEY fehlt oder zu kurz ist.
     */
    public static function hash(string $normalisiert): string
    {
        $schluessel = self::schluessel();
        if ($schluessel === null) {
            throw new \RuntimeException('KSV_VEREINE_CODE_KEY fehlt oder ist kürzer als 32 Zeichen.');
        }

        return hash_hmac('sha256', 'ksv-vereinscode|' . $normalisiert, $schluessel);
    }

    /**
     * Ist ein gültiger Schlüssel (KSV_VEREINE_CODE_KEY, mind. 32 Zeichen) konfiguriert?
     * Ohne Schlüssel werden Codes weder erzeugt noch geprüft – kein stiller Rückfall auf wp_salt().
     */
    public static function schluessel_vorhanden(): bool
    {
        return self::schluessel() !== null;
    }

    private static function schluessel(): ?string
    {
        if (! defined('KSV_VEREINE_CODE_KEY')) {
            return null;
        }

        $key = constant('KSV_VEREINE_CODE_KEY');

        return is_string($key) && strlen($key) >= self::SCHLUESSEL_MINDESTLAENGE ? $key : null;
    }

    public static function version(int $post_id): int
    {
        return max(0, (int) get_post_meta($post_id, self::META_VERSION, true));
    }

    public static function hat_code(int $post_id): bool
    {
        return (string) get_post_meta($post_id, self::META_HASH, true) !== '';
    }

    public static function erstellt_am(int $post_id): int
    {
        return (int) get_post_meta($post_id, self::META_ERSTELLT, true);
    }

    /**
     * Neuen Code erzeugen bzw. zurücksetzen. Der bisherige Code ist danach sofort ungültig.
     * Beim Zurücksetzen (es gab bereits einen Code) wird `vereine.code_zurueckgesetzt.<id>.<version>` ausgelöst.
     *
     * @return array{code: string, version: int}
     * @throws \RuntimeException Ohne gültigen Schlüssel (siehe schluessel_vorhanden()).
     */
    public static function neu_erzeugen(int $post_id): array
    {
        if (! self::schluessel_vorhanden()) {
            throw new \RuntimeException('KSV_VEREINE_CODE_KEY fehlt oder ist kürzer als 32 Zeichen.');
        }

        $vorher_version = self::version($post_id);
        $hatte_code     = $vorher_version > 0 || self::hat_code($post_id);

        $code = '';
        $hash = '';
        for ($versuch = 0; $versuch < 10; ++$versuch) {
            $code = self::zufallscode();
            $hash = self::hash((string) self::normalisieren($code));
            if (self::posts_mit_hash($hash, 1) === []) {
                break;
            }
        }

        $version = $vorher_version + 1;

        update_post_meta($post_id, self::META_HASH, $hash);
        update_post_meta($post_id, self::META_VERSION, $version);
        update_post_meta($post_id, self::META_ERSTELLT, time());

        if ($hatte_code) {
            AppEreignisse::code_zurueckgesetzt($post_id, $version);
        }

        return [
            'code'    => $code,
            'version' => $version,
        ];
    }

    /**
     * Verein zu einem eingegebenen Code finden. Nur veröffentlichte, aktive Vereine.
     */
    public static function finde_verein(string $eingabe): ?\WP_Post
    {
        $normalisiert = self::normalisieren($eingabe);
        if ($normalisiert === null || ! self::schluessel_vorhanden()) {
            return null;
        }

        $hash  = self::hash($normalisiert);
        $posts = self::posts_mit_hash($hash, 2, 'publish');

        // Mehrdeutige Treffer (z. B. durch kopierte Beiträge) werden abgelehnt.
        if (count($posts) !== 1) {
            return null;
        }

        $post = $posts[0];
        if (! hash_equals((string) get_post_meta($post->ID, self::META_HASH, true), $hash)) {
            return null;
        }

        if ((string) get_post_meta($post->ID, '_ksv_active', true) !== '1') {
            return null;
        }

        return $post;
    }

    /**
     * Alle Code-Metadaten eines Beitrags entfernen (z. B. nach dem Kopieren eines Vereins).
     */
    public static function entfernen(int $post_id): void
    {
        delete_post_meta($post_id, self::META_HASH);
        delete_post_meta($post_id, self::META_VERSION);
        delete_post_meta($post_id, self::META_ERSTELLT);
    }

    /**
     * Trägt ein anderer Beitrag denselben Code-Hash (z. B. durch ein Kopier-Plugin dupliziert)?
     * Solche Codes können nicht eingelöst werden (finde_verein() lehnt mehrdeutige Treffer ab).
     */
    public static function hash_doppelt(int $post_id): bool
    {
        $hash = (string) get_post_meta($post_id, self::META_HASH, true);
        if ($hash === '') {
            return false;
        }

        foreach (self::posts_mit_hash($hash, 2) as $post) {
            if ($post->ID !== $post_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<\WP_Post>
     */
    private static function posts_mit_hash(string $hash, int $limit, string $status = 'any'): array
    {
        $query = new \WP_Query([
            'post_type'              => PostType::SLUG,
            'post_status'            => $status === 'any' ? ['publish', 'draft', 'pending', 'private', 'future', 'trash'] : $status,
            'posts_per_page'         => $limit,
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'meta_query'             => [
                [
                    'key'     => self::META_HASH,
                    'value'   => $hash,
                    'compare' => '=',
                ],
            ],
        ]);

        return array_values(array_filter($query->posts, static fn ($p): bool => $p instanceof \WP_Post));
    }
}
