<?php
/**
 * Plugin Name: Cinderwell Performance
 * Plugin URI: https://github.com/ulinaaron/Cinderwell/tree/main/wp-content/plugins/cinderwell-performance
 * Update URI: https://cinderwell-updates.surge.sh/cinderwell-performance/
 * Description: Block-aware performance optimization. HTML cleanup, image hints, resource hints, and down-the-page hydration.
 * Version: 0.1.1
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Requires Plugins: cinderwell
 * Author: Stevens Inc.
 * License: GPL-2.0-or-later
 * Text Domain: cinderwell-performance
 *
 * @package Cinderwell_Performance
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CINDERWELL_PERFORMANCE_VERSION', '0.1.1');
define('CINDERWELL_PERFORMANCE_PATH', plugin_dir_path(__FILE__));
define('CINDERWELL_PERFORMANCE_URL', plugin_dir_url(__FILE__));
define('CINDERWELL_PERFORMANCE_OPTION', 'cinderwell_performance_settings');

spl_autoload_register(function ($class) {
    if (strpos($class, 'Cinderwell_Performance\\') !== 0) {
        return;
    }
    $class_name = str_replace('Cinderwell_Performance\\', '', $class);
    $parts = explode('_', $class_name);
    $file_name = strtolower(implode('-', $parts));

    $file = CINDERWELL_PERFORMANCE_PATH . 'includes/class-' . $file_name . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }

    $file = CINDERWELL_PERFORMANCE_PATH . 'includes/modules/class-' . $file_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

register_activation_hook(__FILE__, function () {
    if (!get_option(CINDERWELL_PERFORMANCE_OPTION)) {
        update_option(CINDERWELL_PERFORMANCE_OPTION, \Cinderwell_Performance\Settings::get_defaults());
    }
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

add_filter('cinderwell_addon_catalog', function ($catalog) {
    if (isset($catalog['cinderwell-performance'])) {
        $catalog['cinderwell-performance'] = array_merge($catalog['cinderwell-performance'], [
            'settings_tab'      => 'performance',
            'icon'              => 'dashicons-performance',
            'option'            => CINDERWELL_PERFORMANCE_OPTION,
            'defaults_callback' => ['Cinderwell_Performance\Settings', 'get_defaults'],
            'health_callback'   => ['Cinderwell_Performance\Settings', 'get_health'],
        ]);
    }
    return $catalog;
});

add_action('plugins_loaded', function () {
    if (!class_exists('Cinderwell\\Cinderwell')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>Cinderwell Performance requires the Cinderwell plugin.</p></div>';
        });
        return;
    }

    load_plugin_textdomain('cinderwell-performance', false, dirname(plugin_basename(__FILE__)) . '/languages');

    if (class_exists('Cinderwell\\Addons') && !\Cinderwell\Addons::is_enabled('cinderwell-performance')) {
        return;
    }

    \Cinderwell_Performance\Settings::migrate_deprecated_settings();

    new \Cinderwell_Performance\Performance();
    new \Cinderwell_Performance\Resource_Hints();
    new \Cinderwell_Performance\Hydration();

    if (is_admin()) {
        new \Cinderwell_Performance\Admin_Page();
    }

    new \Cinderwell_Performance\Rest_Api();

    // Register the settings group so Settings API forms work.
    register_setting('cinderwell_performance_settings_group', CINDERWELL_PERFORMANCE_OPTION, [
        'type' => 'object',
        'sanitize_callback' => function ($settings) {
            return \Cinderwell_Performance\Settings::sanitize($settings);
        },
    ]);
});
