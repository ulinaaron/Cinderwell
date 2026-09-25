<?php
/**
 * Plugin Name: Cinderwell Site Utilities
 * Plugin URI: https://github.com/ulinaaron/Cinderwell/tree/main/wp-content/plugins/cinderwell-utilities
 * Update URI: https://cinderwell-updates.surge.sh/cinderwell-utilities/
 * Description: Admin and site quality-of-life modules for Cinderwell. Each module is independently toggleable.
 * Version: 0.4.0
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Requires Plugins: cinderwell
 * Author: Stevens Inc.
 * License: GPL-2.0-or-later
 * Text Domain: cinderwell-utilities
 *
 * @package Cinderwell_Utilities
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CINDERWELL_UTILITIES_VERSION', '0.4.0');
define('CINDERWELL_UTILITIES_PATH', plugin_dir_path(__FILE__));
define('CINDERWELL_UTILITIES_URL', plugin_dir_url(__FILE__));
define('CINDERWELL_UTILITIES_OPTION', 'cinderwell_utilities_settings');

spl_autoload_register(function ($class) {
    if (strpos($class, 'Cinderwell_Utilities\\') !== 0) {
        return;
    }
    $class_name = str_replace('Cinderwell_Utilities\\', '', $class);
    $parts = explode('\\', $class_name);
    if (count($parts) > 1 && $parts[0] === 'Modules') {
        $file = CINDERWELL_UTILITIES_PATH . 'includes/modules/class-' . strtolower(str_replace('_', '-', $parts[1])) . '.php';
    } else {
        $file = CINDERWELL_UTILITIES_PATH . 'includes/class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
    }
    if (file_exists($file)) {
        require_once $file;
    }
});

register_activation_hook(__FILE__, function () {
    \Cinderwell_Utilities\Schema_Migrator::maybe_migrate();
    \Cinderwell_Utilities\Mail_Log::install();
    \Cinderwell_Utilities\Mail_Log::sync_schedule(\Cinderwell_Utilities\Utilities::module_enabled('mail_delivery'));
});

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook(\Cinderwell_Utilities\Mail_Log::CLEANUP_HOOK);
});

// Enrich the add-on catalog entry.
add_filter('cinderwell_addon_catalog', function ($catalog) {
    if (isset($catalog['cinderwell-utilities'])) {
        $catalog['cinderwell-utilities'] = array_merge($catalog['cinderwell-utilities'], [
            'settings_tab'      => 'utilities',
            'icon'              => 'dashicons-admin-tools',
            'option'            => CINDERWELL_UTILITIES_OPTION,
            'defaults_callback' => ['Cinderwell_Utilities\Utilities', 'get_defaults'],
            'health_callback'   => ['Cinderwell_Utilities\Utilities', 'get_health'],
        ]);
    }
    return $catalog;
});

add_action('plugins_loaded', function () {
    if (!class_exists('Cinderwell\\Cinderwell')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>Cinderwell Site Utilities requires the Cinderwell plugin.</p></div>';
        });
        return;
    }

    load_plugin_textdomain('cinderwell-utilities', false, dirname(plugin_basename(__FILE__)) . '/languages');

    new \Cinderwell_Utilities\Utilities();
});
