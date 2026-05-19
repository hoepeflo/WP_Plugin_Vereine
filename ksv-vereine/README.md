# KSV Vereine – WordPress-Plugin

WordPress-Plugin zur Darstellung der Mitgliedsvereine eines Kreisschützenverbandes mit OSM-Karte, Suche und Disziplin-Filtern.

Vollständige Spezifikation: [docs/SPEZIFIKATION.md](../docs/SPEZIFIKATION.md)

## Installation

1. Ordner `ksv-vereine` nach `wp-content/plugins/` kopieren
2. Plugin im WordPress-Backend aktivieren
3. Unter **Vereine → Einstellungen** den OpenRouteService API-Key hinterlegen
4. Vereine anlegen oder per CSV importieren

## Einbindung

**Shortcode:**

```
[ksv_vereine]
```

**Gutenberg:** Block „KSV Vereine“ einfügen

## Anforderungen

- WordPress 6.4+
- PHP 8.4+
- OpenRouteService API-Key (Geocoding)

## Berechtigungen

- **Administrator:** voller Zugriff inkl. Löschen, Einstellungen, Import
- **Redakteur:** Vereine pflegen
- **Whitelist:** zusätzliche Benutzer unter Einstellungen → Benutzer-Whitelist

## CSV-Import

Musterdatei: `samples/vereine-beispiel.csv`  
Spalten: `name`, `street`, `zip`, `city`, `website`, `disziplinen`, `active`
