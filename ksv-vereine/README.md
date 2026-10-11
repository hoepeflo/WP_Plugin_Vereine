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

Das Plugin liefert Vereine, Disziplinen und Vereinscodes an die App „KSV Fallingbostel“ (Flutter + Firebase).

> **Verbindlicher Schnittstellen-Vertrag:** Repo `APP_KSV-Fallingbostel`, Datei `docs/API-VERTRAG.md` (Abschnitte 1, 2 und 3.1).
> Routen, Antwortformate und Ereignisse dürfen nur geändert werden, nachdem der Vertrag dort angepasst wurde.

**REST-Routen** (Namespace `ksv-app/v1`, unabhängig von `ksv/v1` für das Web-Frontend):

| Methode & Pfad | Zugriff | Antwort |
|---|---|---|
| `GET /wp-json/ksv-app/v1/vereine` | öffentlich | `{ items: [Verein], generated_at }` – nur veröffentlichte, aktive Vereine |
| `GET /wp-json/ksv-app/v1/vereine/{id}` | öffentlich | `Verein` bzw. `404 ksv_app_nicht_gefunden` |
| `GET /wp-json/ksv-app/v1/disziplinen` | öffentlich | `{ items: [{ slug, name }], generated_at }` |
| `POST /wp-json/ksv-app/v1/vereine/code-pruefen` | signiert | `{ verein_id, name, code_version }` bzw. `404 ksv_app_code_ungueltig`; ohne `KSV_VEREINE_CODE_KEY` → `503 ksv_app_code_schluessel_fehlt` |

- GET-Antworten setzen `ETag` und `Cache-Control: public, max-age=300`; bei passendem `If-None-Match` folgt `304`. Das ETag wird über die Daten **ohne** `generated_at` gebildet.
- `logo` enthält `thumbnail`, `medium`, `full` und `platzhalter`; SVG-Bilder werden nicht ausgeliefert (dann Platzhalter-Bild bzw. `null`). Leere Webseite → `website: null`.
- Signierte Aufrufe: gemeinsames Geheimnis `KSV_APP_SECRET` in der `wp-config.php` (oder Option `ksv_app_secret`, gepflegt vom KSV App Connector), mind. 32 Zeichen. Ohne Geheimnis → `503 ksv_app_nicht_konfiguriert`, falsche Signatur → `401 ksv_app_signatur`. Die Prüfung (`AppSignatur`) ist im Plugin selbst implementiert.

**Vereinscode:** Metabox *App-Vereinscode* im Bearbeiten-Bildschirm eines Vereins (für alle, die den Verein bearbeiten dürfen).

- *Vereinscode erzeugen* bzw. *Code zurücksetzen* (mit Rückfrage) erzeugt einen Code `KSV-XXXX-XXXX`. Der Klartext wird nur direkt danach angezeigt – mit QR-Code und Druckansicht für den Aushang.
- Gespeichert werden nur ein HMAC-Hash (`_ksv_app_code_hash`, Schlüssel: Konstante `KSV_VEREINE_CODE_KEY` in der `wp-config.php`, mind. 32 Zeichen), die Version (`_ksv_app_code_version`, beginnt bei 1) und der Zeitpunkt (`_ksv_app_code_erstellt`). **Ohne gültigen Schlüssel** zeigt das Backend Administratoren einen dauerhaften Hinweis, es werden keine Codes erzeugt und `POST /vereine/code-pruefen` antwortet mit `503 ksv_app_code_schluessel_fehlt`. **Wird der Schlüssel geändert, sind alle Codes ungültig** und müssen zurückgesetzt werden.
- Beim Kopieren eines Vereins (z. B. Yoast Duplicate Post) werden die Code-Metadaten nicht übernommen; trägt ein neuer Beitrag dennoch einen bereits vergebenen Hash, wird er dort entfernt. Ein doppelt vergebener Code wird im Bearbeiten-Bildschirm als Warnung angezeigt und kann nicht eingelöst werden.
- Beim Zurücksetzen ist der alte Code sofort ungültig; alle verknüpften App-Geräte des Vereins werden abgemeldet.
- Eingelöst werden kann ein Code nur für veröffentlichte, aktive Vereine. Groß-/Kleinschreibung, Bindestriche und Leerzeichen spielen keine Rolle.
- Der QR-Code enthält den Code im Klartext (`KSV-XXXX-XXXX`) und wird im Browser erzeugt (gebündelte Bibliothek `assets/vendor/qrcode-generator`, MIT-Lizenz).

**Ereignisse** über `do_action('ksv_app_ereignis', …)` (ohne KSV App Connector wirkungslos, keine Personendaten):

- `vereine.geaendert.<post_id>.<zeitstempel>` – Speichern, Papierkorb, Wiederherstellen, Löschen eines Vereins; nach einem CSV-Import einmal als Sammelereignis mit `post_id` 0
- `disziplinen.geaendert.<zeitstempel>` – Anlegen, Ändern, Löschen einer Disziplin (Abgleich `disziplinen`, `vereine`)
- `vereine.code_zurueckgesetzt.<verein_id>.<version>` – Vereinscode zurückgesetzt (`daten`: `verein_id`, `code_version`)
