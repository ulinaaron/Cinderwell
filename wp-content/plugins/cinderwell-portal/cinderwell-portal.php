<?php
/**
 * Plugin Name: Cinderwell Members Portal
 * Plugin URI: https://github.com/ulinaaron/Cinderwell/tree/main/wp-content/plugins/cinderwell-portal
 * Update URI: https://cinderwell-updates.surge.sh/cinderwell-portal/
 * Description: Private member area add-on for Cinderwell. Two registration modes: open with moderation, or admin-issued.
 * Version: 0.1.0
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Requires Plugins: cinderwell
 * Author: Stevens Inc.
 * License: GPL-2.0-or-later
 * Text Domain: cinderwell-portal
 *
 * @package Cinderwell_Portal
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CINDERWELL_PORTAL_VERSION', '0.1.0');
define('CINDERWELL_PORTAL_PATH', plugin_dir_path(__FILE__));
define('CINDERWELL_PORTAL_URL', plugin_dir_url(__FILE__));
define('CINDERWELL_PORTAL_OPTION', 'cinderwell_portal_settings');
define('CINDERWELL_PORTAL_CAP', 'access_portal_content');

spl_autoload_register(function ($class) {
    if (strpos($class, 'Cinderwell_Portal\\') !== 0) {
        return;
    }
    $class_name = str_replace('Cinderwell_Portal\\', '', $class);
    $file = CINDERWELL_PORTAL_PATH . 'includes/class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Return the search policy owned by Members Portal for a post.
 *
 * Consumers can integrate without taking a hard dependency on this add-on.
 */
function cinderwell_portal_get_search_policy($post_id) {
    if (!defined('CINDERWELL_PORTAL_ACTIVE') || !CINDERWELL_PORTAL_ACTIVE) {
        return [];
    }
    return \Cinderwell_Portal\Access_Control::get_search_policy($post_id);
}

register_activation_hook(__FILE__, function () {
    add_role('cinderwell_pending_member', 'Pending Member', ['read' => true]);
    add_role('cinderwell_member', 'Member', ['read' => true, CINDERWELL_PORTAL_CAP => true]);
    // In case the role already existed without the capability.
    $role = get_role('cinderwell_member');
    if ($role && !$role->has_cap(CINDERWELL_PORTAL_CAP)) {
        $role->add_cap(CINDERWELL_PORTAL_CAP);
    }
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

// Enrich the add-on catalog entry with portal-specific metadata.
add_filter('cinderwell_addon_catalog', function ($catalog) {
    if (isset($catalog['cinderwell-portal'])) {
        $catalog['cinderwell-portal'] = array_merge($catalog['cinderwell-portal'], [
            'settings_tab'      => 'portal',
            'icon'              => 'dashicons-lock',
            'option'            => CINDERWELL_PORTAL_OPTION,
            'defaults_callback' => ['Cinderwell_Portal\Portal', 'get_defaults'],
            'health_callback'   => ['Cinderwell_Portal\Portal', 'get_health'],
        ]);
    }
    return $catalog;
});

add_action('plugins_loaded', function () {
    if (!class_exists('Cinderwell\\Cinderwell')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>Cinderwell Members Portal requires the Cinderwell plugin.</p></div>';
        });
        return;
    }

    load_plugin_textdomain('cinderwell-portal', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Ensure the capability exists on the member role (covers upgrades).
    $role = get_role('cinderwell_member');
    if ($role && !$role->has_cap(CINDERWELL_PORTAL_CAP)) {
        $role->add_cap(CINDERWELL_PORTAL_CAP);
    }

    // Only load if enabled via Add-Ons tab, or if Addons class doesn't exist (standalone fallback).
    if (class_exists('Cinderwell\\Addons') && !\Cinderwell\Addons::is_enabled('cinderwell-portal')) {
        return;
    }

    define('CINDERWELL_PORTAL_ACTIVE', true);
    new \Cinderwell_Portal\Portal();
});
