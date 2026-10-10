<?php

declare(strict_types=1);

namespace KSV\Vereine;

/**
 * Prüfung signierter Server-zu-Server-Aufrufe (API-Vertrag, Abschnitt 1.2).
 *
 * Eigene Implementierung ohne Abhängigkeit vom KSV App Connector.
 */
final class AppSignatur
{
    /** Maximale Zeitabweichung in Sekunden. */
    public const MAX_ABWEICHUNG = 300;

    /** Mindestlänge des gemeinsamen Geheimnisses. */
    public const MIN_LAENGE = 32;

    public static function geheimnis(): string
    {
        $secret = defined('KSV_APP_SECRET') ? constant('KSV_APP_SECRET') : get_option('ksv_app_secret', '');

        return is_string($secret) ? $secret : '';
    }

    /**
     * Als permission_callback für signierte Routen verwendbar.
     */
    public static function pruefe(\WP_REST_Request $request): bool|\WP_Error
    {
        $secret = self::geheimnis();
        if (strlen($secret) < self::MIN_LAENGE) {
            return new \WP_Error(
                'ksv_app_nicht_konfiguriert',
                __('App-Schnittstelle nicht eingerichtet.', 'ksv-vereine'),
                ['status' => 503]
            );
        }

        $ts  = trim((string) $request->get_header('x_ksv_timestamp'));
        $sig = trim((string) $request->get_header('x_ksv_signature'));

        if ($ts === '' || $sig === '' || ! ctype_digit($ts) || abs(time() - (int) $ts) > self::MAX_ABWEICHUNG) {
            return self::fehler();
        }

        $path = (string) wp_parse_url(rest_url(ltrim($request->get_route(), '/')), PHP_URL_PATH);
        $data = $ts . "\n" . strtoupper($request->get_method()) . "\n" . $path . "\n" . $request->get_body();

        if (! hash_equals(hash_hmac('sha256', $data, $secret), strtolower($sig))) {
            return self::fehler();
        }

        return true;
    }

    private static function fehler(): \WP_Error
    {
        return new \WP_Error(
            'ksv_app_signatur',
            __('Ungültige Signatur.', 'ksv-vereine'),
            ['status' => 401]
        );
    }
}
