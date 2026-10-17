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

## App-Modul (App „KSV Fallingbostel“)

Das Plugin liefert Vereine und Disziplinen an die App „KSV Fallingbostel“ (Flutter + Firebase). Mitglieder wählen ihren Verein in der App frei aus der Vereinsliste – es gibt keine Vereinscodes und keine Zugangsschlüssel.

> **Verbindlicher Schnittstellen-Vertrag:** Repo `APP_KSV-Fallingbostel`, Datei `docs/API-VERTRAG.md` (Abschnitte 1, 2 und 3.1).
> Routen, Antwortformate und Ereignisse dürfen nur geändert werden, nachdem der Vertrag dort angepasst wurde.

**REST-Routen** (Namespace `ksv-app/v1`, unabhängig von `ksv/v1` für das Web-Frontend; alle öffentlich, nur GET):

| Methode & Pfad | Zugriff | Antwort |
|---|---|---|
| `GET /wp-json/ksv-app/v1/vereine` | öffentlich | `{ items: [Verein], generated_at }` – nur veröffentlichte, aktive Vereine |
| `GET /wp-json/ksv-app/v1/vereine/{id}` | öffentlich | `Verein` bzw. `404 ksv_app_nicht_gefunden` |
| `GET /wp-json/ksv-app/v1/disziplinen` | öffentlich | `{ items: [{ slug, name }], generated_at }` |

- GET-Antworten setzen `ETag` und `Cache-Control: public, max-age=300`; bei passendem `If-None-Match` folgt `304`. Das ETag wird über die Daten **ohne** `generated_at` gebildet.
- `logo` enthält `thumbnail`, `medium`, `full` und `platzhalter`; SVG-Bilder werden nicht ausgeliefert (dann Platzhalter-Bild bzw. `null`). Leere Webseite → `website: null`.
- Es gibt keine signierten Aufrufe und keine Konfiguration in der `wp-config.php`.

**Hinweis zur Aktualisierung auf 1.3.0:** Die früheren App-Vereinscodes (Metabox „App-Vereinscode“, `POST /vereine/code-pruefen`, Konstante `KSV_VEREINE_CODE_KEY`) wurden ersatzlos entfernt. Beim ersten Laden nach der Aktualisierung löscht das Plugin die alten Metadaten `_ksv_app_code_hash`, `_ksv_app_code_version` und `_ksv_app_code_erstellt` einmalig aus allen Beiträgen. Eine vorhandene Konstante `KSV_VEREINE_CODE_KEY` kann aus der `wp-config.php` entfernt werden.

**Ereignisse** über `do_action('ksv_app_ereignis', …)` (ohne KSV App Connector wirkungslos, keine Personendaten):

- `vereine.geaendert.<post_id>.<zeitstempel>` – Speichern, Papierkorb, Wiederherstellen, Löschen eines Vereins; nach einem CSV-Import einmal als Sammelereignis mit `post_id` 0
- `disziplinen.geaendert.<zeitstempel>` – Anlegen, Ändern, Löschen einer Disziplin (Abgleich `disziplinen`, `vereine`)
