<?php

declare(strict_types=1);

namespace KSV\Vereine;

/**
 * App-Modul für die App „KSV Fallingbostel“.
 *
 * Verbindlicher Schnittstellen-Vertrag: Repo APP_KSV-Fallingbostel, docs/API-VERTRAG.md
 * (Abschnitte 1, 2 und 3.1). Änderungen an Routen, Antwortformaten oder Ereignissen
 * werden zuerst dort eingetragen.
 */
final class AppModul
{
    public function register(): void
    {
        (new AppRestApi())->register();
        (new AppEreignisse())->register();
        (new VereinscodeAdmin())->register();
    }
}
