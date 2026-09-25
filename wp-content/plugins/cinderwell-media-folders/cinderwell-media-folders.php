<?php
/**
 * Plugin Name: Cinderwell Media Folders
 * Plugin URI: https://github.com/ulinaaron/Cinderwell/tree/main/wp-content/plugins/cinderwell-media-folders
 * Update URI: https://cinderwell-updates.surge.sh/cinderwell-media-folders/
 * Description: Hierarchical media folders with bulk drag-and-drop, modal filtering, and safe migration tools.
 * Version: 0.2.6
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Requires Plugins: cinderwell
 * Author: Stevens Inc.
 * License: GPL-2.0-or-later
 * Text Domain: cinderwell-media-folders
 *
 * @package Cinderwell_Media_Folders
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CINDERWELL_MEDIA_FOLDERS_VERSION', '0.2.6');
define('CINDERWELL_MEDIA_FOLDERS_PATH', plugin_dir_path(__FILE__));
define('CINDERWELL_MEDIA_FOLDERS_URL', plugin_dir_url(__FILE__));
define('CINDERWELL_MEDIA_FOLDERS_OPTION', 'cinderwell_media_folders_settings');

spl_autoload_register(function ($class) {
    if (strpos($class, 'Cinderwell_Media_Folders\\') !== 0) {
        return;
    }
    $class_name = str_replace('Cinderwell_Media_Folders\\', '', $class);
    $file = CINDERWELL_MEDIA_FOLDERS_PATH . 'includes/class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

add_filter('cinderwell_addon_catalog', function ($catalog) {
    if (isset($catalog['cinderwell-media-folders'])) {
        $catalog['cinderwell-media-folders'] = array_merge($catalog['cinderwell-media-folders'], [
            'settings_tab'      => 'media-folders',
            'icon'              => 'dashicons-portfolio',
            'option'            => CINDERWELL_MEDIA_FOLDERS_OPTION,
            'defaults_callback' => ['Cinderwell_Media_Folders\Admin_Page', 'get_defaults'],
            'health_callback'   => ['Cinderwell_Media_Folders\Folder_Taxonomy', 'get_health'],
        ]);
    }
    return $catalog;
});

add_action('plugins_loaded', function () {
    if (!class_exists('Cinderwell\\Cinderwell')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>Cinderwell Media Folders requires the Cinderwell plugin.</p></div>';
        });
        return;
    }

    load_plugin_textdomain('cinderwell-media-folders', false, dirname(plugin_basename(__FILE__)) . '/languages');

    if (class_exists('Cinderwell\\Addons') && !\Cinderwell\Addons::is_enabled('cinderwell-media-folders')) {
        return;
    }

    new \Cinderwell_Media_Folders\Folder_Taxonomy();
    new \Cinderwell_Media_Folders\Media_Library_UI();
    new \Cinderwell_Media_Folders\Import_HappyFiles();
    new \Cinderwell_Media_Folders\Import_WPMF();

    if (is_admin()) {
        new \Cinderwell_Media_Folders\Admin_Page();
    }

    new \Cinderwell_Media_Folders\Rest_Api();
});
