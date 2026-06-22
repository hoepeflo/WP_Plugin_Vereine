# KSV Vereine – WordPress-Plugin

WordPress-Plugin zur Darstellung der Mitgliedsvereine eines Kreisschützenverbandes mit OSM-Karte, Suche und Disziplin-Filtern.

Vollständige Spezifikation: [docs/SPEZIFIKATION.md](../docs/SPEZIFIKATION.md)

## Installation

1. Ordner `ksv-vereine` nach `wp-content/plugins/` kopieren
2. Plugin im WordPress-Backend aktivieren
3. Unter **Vereine → Einstellungen** den OpenRouteService API-Key hinterlegen
4. Vereine anlegen oder per CSV importieren

## Einbindung

### Vereinsübersicht (Liste + Karte)

**Shortcode:**

```
[ksv_vereine]
```

**Gutenberg:** Block „KSV Vereine“ einfügen

### Vereinssuche für die Startseite

Suchbanner mit Kartengrafik. Die Suche leitet zur konfigurierten Vereinsseite weiter; dort werden die Ergebnisse im Block `[ksv_vereine]` angezeigt.

**Voraussetzung:** Unter **Vereine → Einstellungen** die **Vereinsseite** auswählen (Seite mit `[ksv_vereine]`).

**Shortcode:**

```
[ksv_vereinssuche]
```

**Gutenberg:** Block „KSV Vereinssuche“ einfügen

**Divi:** Modul „KSV Vereinssuche“ im Bereich *KSV Vereine* verwenden (alternativ Shortcode in einem Textmodul)

Optionale Shortcode-Attribute: `heading`, `description`, `image_url`, `vereine_page_url`, `show_link="0"`

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
