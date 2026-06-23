# AGENTS.md

## Cursor Cloud specific instructions

### Was ist das

`ksv-vereine/` ist ein **WordPress-Plugin** (PHP 8.4+), kein eigenständiges Programm. Es
gibt **keinen Build-Step** und **keine Composer-/npm-Abhängigkeiten** im Repo – Leaflet
und OpenStreetMap werden im Frontend per CDN geladen. Zum Ausführen wird eine WordPress-
Installation benötigt, in die das Plugin geladen wird.

### Dev-WordPress-Umgebung (bereits im VM-Snapshot eingerichtet)

- WordPress-Core liegt unter `~/wp` und nutzt **SQLite** statt MySQL (Drop-in
  `~/wp/wp-content/db.php` via Plugin `sqlite-database-integration`), da im VM kein
  MySQL läuft.
- Das Repo-Plugin ist als Symlink eingebunden:
  `~/wp/wp-content/plugins/ksv-vereine -> /workspace/ksv-vereine`. Änderungen am Code
  wirken dadurch sofort, ohne Kopieren.
- Admin-Login: `http://localhost:8088/wp-admin` mit `admin` / `admin123`.
- Demo-Seite mit Shortcode `[ksv_vereine]`: `http://localhost:8088/?page_id=7`.

### Dev-Server starten

```bash
cd ~/wp && PHP_CLI_SERVER_WORKERS=6 php -S 0.0.0.0:8088 -t ~/wp
```

Wichtig: **`PHP_CLI_SERVER_WORKERS` (>1) ist nötig.** Der PHP-Built-in-Server ist sonst
single-threaded und blockiert bei gleichzeitigen Asset-/REST-Requests (Seite hängt).

### Verein anlegen / WP-CLI

WP-CLI ist installiert; im VM immer mit `--allow-root` aufrufen, z. B.
`wp post list --post_type=ksv_verein --allow-root` (cwd `~/wp`). Relevante Meta-Keys:
`_ksv_active`, `_ksv_street`, `_ksv_zip`, `_ksv_city`, `_ksv_website`, `_ksv_lat`,
`_ksv_lng`, `_ksv_logo_id`; Disziplin-Taxonomie: `ksv_disziplin`; CPT-Slug: `ksv_verein`.

### Lint / Test / Build

- **Lint:** Es gibt keine phpcs-Konfiguration. Syntaxprüfung über alle Plugin-Dateien:
  `find ksv-vereine -name '*.php' -print0 | xargs -0 -n1 php -l`
- **Test:** Es existiert keine automatisierte Testsuite (kein PHPUnit/phpunit.xml).
- **Build:** Kein Build-Step (Blocks sind plain JS via `block.json`).

### Bekannte Stolperfallen (kein Bug)

- Die **Suche/Geocoding** und das Geocoding beim Speichern eines Vereins benötigen einen
  **OpenRouteService-API-Key** (Backend: **Vereine → Einstellungen**). Ohne Key liefert
  die Suche bewusst einen Fallback auf **alphabetische Sortierung** mit Hinweistext –
  das ist erwartetes Verhalten, kein Fehler.
