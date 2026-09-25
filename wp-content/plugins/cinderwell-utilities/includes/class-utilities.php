<?php
namespace Cinderwell_Utilities;

class Utilities {
    private static $instance;

    const OPTION = 'cinderwell_utilities_settings';

    public function __construct() {
        Schema_Migrator::maybe_migrate();
        Mail_Log::maybe_install();
        $this->load_modules();
        add_filter('cinderwell_settings_export_options', [$this, 'register_export_option']);
        add_filter('cinderwell_import_setting', [$this, 'sanitize_imported_settings'], 10, 3);
        if (is_admin()) {
            new Admin_Page();
        }
        new Rest_Api();
    }

    private function load_modules() {
        $settings = self::get_settings();
        foreach (Module_Registry::get_modules() as $key => $module) {
            $class = $module['class'];
            if (empty($settings[$key]['enabled'])) {
                continue;
            }
            if (!empty($module['boot_callback']) && is_callable($module['boot_callback'])) {
                call_user_func($module['boot_callback'], $settings[$key], $module);
            } elseif (class_exists($class)) {
                new $class($settings[$key]);
            }
        }
    }

    /**
     * Compatibility view derived from the module registry.
     */
    public static function get_module_classes() {
        return wp_list_pluck(Module_Registry::get_modules(), 'class');
    }

    /**
     * Compatibility view derived from the module registry.
     */
    public static function get_module_labels() {
        $labels = [];
        foreach (Module_Registry::get_modules() as $key => $module) {
            $labels[$key] = array_intersect_key($module, array_flip(['label', 'group', 'description', 'icon']));
        }
        return $labels;
    }

    public static function get_defaults() {
        return Module_Registry::get_defaults();
    }

    public static function get_settings() {
        $saved = get_option(self::OPTION, []);
        return Module_Registry::sanitize_all(is_array($saved) ? $saved : []);
    }

    public static function update_settings($settings) {
        update_option(self::OPTION, Module_Registry::sanitize_all($settings), false);
    }

    public function register_export_option($options) {
        $options[] = self::OPTION;
        return array_values(array_unique($options));
    }

    public function sanitize_imported_settings($value, $option_name, $raw_value) {
        if (self::OPTION !== $option_name) {
            return $value;
        }
        return Module_Registry::sanitize_all(is_array($raw_value) ? $raw_value : []);
    }

    /**
     * Normalize and validate plugin basenames before storing them.
     */
    public static function sanitize_plugin_basenames($plugins, $installed_only = true) {
        $sanitized = [];
        $installed = null;

        if ($installed_only) {
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $installed = array_keys(get_plugins());
        }

        foreach ((array) $plugins as $plugin) {
            $plugin = plugin_basename(wp_normalize_path(sanitize_text_field((string) $plugin)));
            if (
                '' === $plugin ||
                0 !== validate_file($plugin) ||
                '.php' !== strtolower(substr($plugin, -4)) ||
                (is_array($installed) && !in_array($plugin, $installed, true))
            ) {
                continue;
            }
            $sanitized[] = $plugin;
        }

        return array_values(array_unique($sanitized));
    }

    public static function get_health() {
        $settings = self::get_settings();
        $enabled = 0;
        foreach ($settings as $key => $module) {
            if (!empty($module['enabled'])) {
                $enabled++;
            }
        }
        return [
            'status' => 'good',
            'message' => sprintf('%d of %d modules active', $enabled, count(Module_Registry::get_modules())),
        ];
    }

    /**
     * Check if a module is enabled.
     */
    public static function module_enabled($module) {
        $settings = self::get_settings();
        return !empty($settings[$module]['enabled']);
    }

    /**
     * Get settings for a specific module.
     */
    public static function module_settings($module) {
        return Module_Registry::sanitize_module($module, self::get_settings()[$module] ?? []);
    }
}
