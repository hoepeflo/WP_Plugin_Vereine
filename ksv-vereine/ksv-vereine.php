<?php
/**
 * Plugin Name:       KSV Vereine
 * Plugin URI:        https://github.com/example/ksv-vereine
 * Description:       Mitgliedsvereine eines Kreisschützenverbandes mit Karte, Suche und Disziplin-Filtern.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.4
 * Author:            Kreisschützenverband
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ksv-vereine
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('KSV_VEREINE_VERSION', '1.0.0');
define('KSV_VEREINE_FILE', __FILE__);
define('KSV_VEREINE_PATH', plugin_dir_path(__FILE__));
define('KSV_VEREINE_URL', plugin_dir_url(__FILE__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'KSV\\Vereine\\';
    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = KSV_VEREINE_PATH . 'includes/' . str_replace('\\', '/', $relative) . '.php';

    if (is_readable($file)) {
        require_once $file;
    }
});

register_activation_hook(__FILE__, [KSV\Vereine\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [KSV\Vereine\Activator::class, 'deactivate']);

add_action('plugins_loaded', static function (): void {
    KSV\Vereine\Plugin::instance()->init();
});
