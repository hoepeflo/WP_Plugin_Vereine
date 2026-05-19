# Spezifikation: WordPress-Plugin „KSV Vereine“

Plugin für die Webseite eines Kreisschützenverbandes zur Darstellung der Mitgliedsvereine mit OSM-Karte, Suche, Disziplin-Filtern und Backend-Pflege.

**Version der Spezifikation:** 1.0  
**Zielplattform:** WordPress (aktuelle stabile Version), PHP ≥ 8.4

---

## 1. Ziele

- Mitgliedsvereine übersichtlich im Card-Design listen
- Vereine auf einer OpenStreetMap-Karte (Leaflet) anzeigen
- Suche nach beliebigem Ort/PLZ/Text; Sortierung nach Entfernung (Luftlinie), ohne Entfernungsanzeige
- Disziplinen als Tags und filterbar
- Pflege im WordPress-Backend inkl. CSV-Import
- Barrierefreie Bedienung soweit technisch sinnvoll

---

## 2. Vereinsdaten

### 2.1 Felder

| Feld | Pflicht | Beschreibung |
|------|---------|--------------|
| Vereinsname | ja | Titel des Beitrags (Custom Post Type) |
| Straße | nein | Schützenhaus, Straße |
| PLZ | nein | Postleitzahl |
| Ort | nein | Ortsname |
| Webseite | nein | URL; wenn leer, Card im Frontend nicht klickbar |
| Logo | nein | 100×100 px über Mediathek; sonst Platzhalter aus Einstellungen |
| Breitengrad / Längengrad | auto | Beim Speichern per Geocoding (OpenRouteService) |
| Disziplinen | nein | 0–8 Kategorien, siehe Abschnitt 3 |
| Status aktiv | ja | Inaktive Vereine nur im Backend sichtbar |

**Geocoding:** Beim Speichern wird aus Straße, PLZ und Ort eine Adresszeichenkette gebildet und über die OpenRouteService Geocoding-API aufgelöst. Mindestens PLZ oder Ort sollten für ein sinnvolles Ergebnis vorhanden sein. Fehler werden im Backend angezeigt; der Beitrag kann trotzdem gespeichert werden (Koordinaten bleiben leer oder unverändert).

### 2.2 Custom Post Type

- **Slug:** `ksv_verein`
- **Öffentlich:** nein (kein eigenes Frontend-Archiv)
- **Capabilities:** eigener Typ `ksv_verein` / `ksv_vereine` mit `map_meta_cap`

### 2.3 Löschen

- Nur Benutzer mit Rolle **Administrator** dürfen Vereine endgültig löschen
- Redakteure und Whitelist-Benutzer: bearbeiten, nicht löschen

---

## 3. Disziplinen

Feste Taxonomie `ksv_disziplin` (keine freien Tags). Beim Plugin-Aktivieren werden folgende Begriffe angelegt:

| Slug | Anzeigename |
|------|-------------|
| `luftdruckwaffen` | Luftdruckwaffen |
| `feuerwaffen` | Feuerwaffen |
| `vorderlader` | Vorderlader |
| `bogen` | Bogen |
| `blasrohr` | Blasrohr |
| `flinte` | Flinte |
| `dart` | Dart |

- **Backend:** Checkboxen (Mehrfachauswahl)
- **Frontend:** Tags auf der Card
- **Filter:** **ODER-Logik** — ein Verein wird angezeigt, wenn er **mindestens eine** der gewählten Disziplinen anbietet. Keine Filterauswahl = alle Disziplinen.

---

## 4. Berechtigungen

### 4.1 Rollen

| Aktion | Administrator | Redakteur | Whitelist-Benutzer |
|--------|---------------|-----------|---------------------|
| Vereine erstellen/bearbeiten | ja | ja | ja |
| Vereine löschen | ja | nein | nein |
| Plugin-Einstellungen | ja | nein | nein |
| CSV-Import | ja | nein | nein |

### 4.2 Whitelist

- Einstellungsseite: Mehrfachauswahl von WordPress-Benutzern
- Ausgewählte Benutzer erhalten die Vereins-Capabilities (ohne `delete_ksv_vereine`)
- Entfernung aus der Whitelist entzieht die zusätzlichen Rechte (Rollenrechte des Redakteurs bleiben unberührt)

### 4.3 Empfehlung für Einzelbenutzer ohne Redakteur-Rolle

Benutzer mit minimaler Rolle (z. B. „Mitglied“) können über die Whitelist gezielt berechtigt werden, ohne globale Redakteur-Rechte zu vergeben.

---

## 5. Backend

### 5.1 Menü

- Oberes Admin-Menü **„Vereine“** (Icon: Standort/Gruppe)
- Untermenü **„Einstellungen“** (nur Administratoren)
- Untermenü **„Import“** (nur Administratoren)

### 5.2 Einstellungen

| Option | Beschreibung |
|--------|--------------|
| OpenRouteService API-Key | Pflicht für Geocoding |
| Standard-Breitengrad / -längengrad / Zoom | Kartenansicht ohne Vereins-Pins |
| Platzhalter-Logo | Medien-ID für Vereine ohne Logo |
| Benutzer-Whitelist | IDs der berechtigten Benutzer |

### 5.3 Metaboxen / Felder

- Adresse: Straße, PLZ, Ort
- Webseite (URL)
- Logo (Medienauswahl)
- Aktiv-Checkbox
- Hinweis zu Geocoding-Status nach Speichern

### 5.4 CSV-Import

- Upload mit Vorschau und Import-Bericht
- Download einer **Muster-CSV**
- Spalten:

```csv
name,street,zip,city,website,disziplinen,active
```

- `disziplinen`: Semikolon-getrennte Anzeigenamen, z. B. `Luftdruckwaffen;Bogen;Dart`
- `active`: `1` oder `0`
- Nach Import: Geocoding pro importiertem Verein (mit Rücksicht auf API-Limits)

Beispieldatei: `ksv-vereine/samples/vereine-beispiel.csv`

---

## 6. Frontend

### 6.1 Einbindung

- **Shortcode:** `[ksv_vereine]`
- **Gutenberg-Block:** `ksv/vereine` (gleiche Funktionalität)

### 6.2 Layout

**Desktop:** Liste links, Karte rechts — beide Bereiche immer sichtbar.

**Mobil:** Karte oben, Liste darunter.

### 6.3 Card-Design

```
┌──────────────────────────────────────────┐
│ [Logo      ]  Vereinsname                │
│  100×100     Straße, PLZ Ort             │
│              [Tag] [Tag] …               │
│              https://verein.example      │
└──────────────────────────────────────────┘
```

- Logo links, 100×100 px, `object-fit: contain`
- Rechts: Name (Überschrift), formatierte Adresse, Disziplin-Tags, Webseiten-URL als Text
- **Ganze Card:** nur klickbar, wenn Webseite gesetzt → Link in neuem Tab (`rel="noopener noreferrer"`)
- Jede Card: `id="ksv-verein-{post_id}"` für Scroll-Ziel

### 6.4 Suche

- Ein Suchfeld (freie Eingabe: PLZ, Ort, Straße, etc.)
- Eingabe wird per OpenRouteService geocodiert
- Alle **aktiven** Vereine mit Koordinaten werden nach **Luftlinie** (Haversine) sortiert
- **Keine** Anzeige der Entfernung auf den Cards
- Vereine ohne Koordinaten: am Ende der Liste oder nach Name (konfigurierbar im Code, Standard: Ende)
- Geocoding der Suche schlägt fehl: Hinweis für Nutzer, alphabetische Sortierung optional

### 6.5 Disziplin-Filter

- Checkboxen oder Chips für alle acht Disziplinen
- ODER-Logik (siehe Abschnitt 3)
- Filter und Suche kombinierbar

### 6.6 Karte

- **Leaflet** mit **OpenStreetMap**-Kacheln
- Kein Marker-Clustering
- Ein Pin pro aktivem Verein mit Koordinaten
- Klick auf Pin → Popup (Name, Adresse) + Link **„Zur Vereinsinfo“** → scrollt zur zugehörigen Card
- Karte aktualisiert sich bei Suche/Filter synchron zur Liste

### 6.7 Styling

- Anpassung ans Theme über **CSS Custom Properties**, z. B.:
  - `--ksv-card-bg`, `--ksv-card-border`, `--ksv-tag-bg`, `--ksv-tag-color`, `--ksv-link-color`, `--ksv-gap`, `--ksv-radius`
- Sinnvolle Fallback-Werte im Plugin-CSS

### 6.8 REST-API

- Namespace: `ksv/v1`
- Endpoint z. B. `GET /wp-json/ksv/v1/vereine`
- Query-Parameter: `search`, `disziplinen[]` (Slugs)
- Antwort: JSON mit Vereinsdaten inkl. Koordinaten und Disziplinen für das Frontend-Skript

---

## 7. Externe Dienste

### 7.1 OpenRouteService

- **Geocoding** beim Speichern von Vereinen (Backend)
- **Geocoding** der Suchanfrage (Frontend)
- API-Key in den Plugin-Einstellungen (nicht im Frontend-JavaScript exponieren — Geocoding der Suche über WordPress-REST-Proxy oder serverseitigen Endpoint)

### 7.2 OpenStreetMap

- Kartenkacheln über Leaflet; Attribution gemäß OSM-Nutzungsbedingungen im Plugin ausgeben

**Distanzberechnung:** lokal per Haversine im Plugin (kein ORS Matrix/Directions nötig).

---

## 8. Barrierefreiheit

- Beschriftete Formularfelder (`label` / `aria-label`)
- Disziplin-Filter als `fieldset` mit `legend`
- Cards als semantisches `article` mit Überschrift
- Klickbare Cards als echte Links mit verständlichem Linktext (z. B. „Webseite von {Name} öffnen“)
- Hinweis, dass die Karte die Liste ergänzt; alle Informationen in der Liste verfügbar
- Sichtbarer Fokus, Tastaturbedienung für Suche und Filter
- Popups schließbar (Escape)

Keine besonderen DSGVO-Funktionen (kein Cookie-Banner speziell fürs Plugin); Standort des Browsers wird nicht abgefragt.

---

## 9. Technische Architektur

### 9.1 Verzeichnisstruktur

```
ksv-vereine/
├── ksv-vereine.php              # Bootstrap
├── includes/
│   ├── Plugin.php
│   ├── Activator.php
│   ├── PostType.php
│   ├── Taxonomy.php
│   ├── MetaBoxes.php
│   ├── Capabilities.php
│   ├── Settings.php
│   ├── Geocoding.php
│   ├── RestApi.php
│   ├── Import.php
│   ├── Shortcode.php
│   └── Block.php
├── blocks/vereine/
├── assets/
│   ├── css/frontend.css
│   ├── css/admin.css
│   └── js/frontend.js
├── templates/frontend.php
└── samples/vereine-beispiel.csv
```

### 9.2 PHP

- Namespace: `KSV\Vereine`
- Strict types, PHP 8.4+
- WordPress Coding Standards wo praktikabel

### 9.3 Abhängigkeiten Frontend

- Leaflet (CSS/JS, eingebunden über Plugin)
- Kein Build-Step zwingend für v1 (Block: `block.json` + einfaches `view.js`)

---

## 10. Implementierungsphasen

| Phase | Inhalt |
|-------|--------|
| 1 | Plugin-Skeleton, CPT, Taxonomie, Metaboxen, Caps, Whitelist, Löschschutz |
| 2 | Einstellungen, OpenRouteService Geocoding beim Speichern |
| 3 | REST-API, Shortcode, Block, Frontend CSS/JS, Karte, Suche, Filter |
| 4 | CSV-Import, Muster-CSV |
| 5 | A11y-Feinschliff, Responsive, Dokumentation README |

---

## 11. Nicht im Scope (v1)

- Mehrsprachigkeit (WPML/Polylang)
- Marker-Clustering
- Anzeige der Entfernung in km
- Straßenrouting / ORS Matrix
- DSGVO-spezifische Consent-Dialoge
- Öffentliche Einzelansichten pro Verein (Archiv-Single)

---

## 12. Abnahmekriterien (Kurz)

- [ ] Verein im Backend anlegen mit Disziplinen und Adresse → Koordinaten werden gesetzt
- [ ] Inaktiver Verein erscheint nicht im Frontend
- [ ] Shortcode/Block zeigt Liste links, Karte rechts (Desktop)
- [ ] Suche sortiert Vereine nach Nähe zur gesuchten Stelle
- [ ] Disziplinfilter (ODER) schränkt Liste und Karte ein
- [ ] Pin-Popup scrollt zur Card
- [ ] Card ohne Webseite ist nicht klickbar
- [ ] CSV-Import mit Musterdatei funktioniert
- [ ] Nur Admin kann löschen; Whitelist-Benutzer kann bearbeiten
- [ ] Redakteur kann Vereine pflegen
