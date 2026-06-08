<?php
/**
 * Plugin Name: Pedraza Mahou Reservations
 * Plugin URI: https://grancastillodepedraza.com/
 * Description: Gestiona reservas de cestas picnic Mahou mediante shortcodes para WordPress y Elementor.
 * Version: 1.0.26
 * Author: Cloudari
 * Text Domain: pedraza-mahou-reservations
 * Requires PHP: 8.0
 */

if (! defined('ABSPATH')) {
    exit;
}

define('PMR_VERSION', '1.0.26');
define('PMR_DB_VERSION', '1.0.0');
define('PMR_PLUGIN_FILE', __FILE__);
define('PMR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PMR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PMR_PLUGIN_SLUG', 'pedraza-mahou-reservations');

require_once PMR_PLUGIN_DIR . 'includes/class-pmr-database.php';
require_once PMR_PLUGIN_DIR . 'includes/class-pmr-admin-settings.php';
require_once PMR_PLUGIN_DIR . 'includes/class-pmr-activator.php';
require_once PMR_PLUGIN_DIR . 'includes/class-pmr-auth.php';
require_once PMR_PLUGIN_DIR . 'includes/class-pmr-emails.php';
require_once PMR_PLUGIN_DIR . 'includes/class-pmr-reservations.php';
require_once PMR_PLUGIN_DIR . 'includes/class-pmr-update-checker.php';
require_once PMR_PLUGIN_DIR . 'includes/class-pmr-assets.php';
require_once PMR_PLUGIN_DIR . 'includes/class-pmr-shortcodes.php';

register_activation_hook(__FILE__, ['PMR_Activator', 'activate']);

final class Pedraza_Mahou_Reservations_Plugin
{
    public static function init(): void
    {
        PMR_Admin_Settings::init();
        PMR_Assets::init();
        PMR_Reservations::init();
        PMR_Update_Checker::init();
        PMR_Shortcodes::init();
    }
}

add_action('plugins_loaded', ['Pedraza_Mahou_Reservations_Plugin', 'init']);
