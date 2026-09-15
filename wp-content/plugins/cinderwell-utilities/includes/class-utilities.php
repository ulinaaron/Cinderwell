<?php
namespace Cinderwell_Utilities;

class Utilities {
    private static $instance;

    const OPTION = 'cinderwell_utilities_settings';

    public function __construct() {
        $this->load_modules();
        if (is_admin()) {
            new Admin_Page();
        }
        new Rest_Api();
    }

    private function load_modules() {
        $settings = self::get_settings();
        $modules = self::get_module_classes();

        foreach ($modules as $key => $class) {
            if (!empty($settings[$key]['enabled'])) {
                new $class($settings[$key]);
            }
        }
    }

    public static function get_module_classes() {
        return [
            'content_duplication' => 'Cinderwell_Utilities\\Modules\\Content_Duplication',
            'content_order'       => 'Cinderwell_Utilities\\Modules\\Content_Order',
            'terms_order'         => 'Cinderwell_Utilities\\Modules\\Terms_Order',
            'media_replacement'   => 'Cinderwell_Utilities\\Modules\\Media_Replacement',
            'allow_svgs'          => 'Cinderwell_Utilities\\Modules\\Allow_SVGs',
            'disable_comments'    => 'Cinderwell_Utilities\\Modules\\Disable_Comments',
            'disable_feeds'       => 'Cinderwell_Utilities\\Modules\\Disable_Feeds',
            'disable_smaller'     => 'Cinderwell_Utilities\\Modules\\Disable_Smaller',
        ];
    }

    public static function get_module_labels() {
        return [
            'content_duplication' => ['label' => 'Content Duplication', 'group' => 'content', 'description' => 'One-click duplicate posts and pages from the admin.'],
            'content_order'       => ['label' => 'Content Order', 'group' => 'content', 'description' => 'Drag-and-drop ordering for hierarchical post types.'],
            'terms_order'         => ['label' => 'Taxonomy Terms Order', 'group' => 'content', 'description' => 'Drag-and-drop ordering for taxonomy terms.'],
            'media_replacement'   => ['label' => 'Media Replacement', 'group' => 'media', 'description' => 'Replace media files while keeping the same URL and ID.'],
            'allow_svgs'          => ['label' => 'Allow SVGs', 'group' => 'media', 'description' => 'Enable SVG uploads with automatic sanitization.'],
            'disable_comments'    => ['label' => 'Disable Comments', 'group' => 'disable', 'description' => 'Site-wide comment disabling. Hides forms, admin menus, and closes pings.'],
            'disable_feeds'       => ['label' => 'Disable Feeds', 'group' => 'disable', 'description' => 'Disable all RSS/Atom/RDF feeds and remove feed links from the head.'],
            'disable_smaller'     => ['label' => 'Disable Smaller Components', 'group' => 'disable', 'description' => 'Bundle of micro-disablers: emoji, embed, jQuery Migrate, generator tags, and more.'],
        ];
    }

    public static function get_defaults() {
        return [
            'content_duplication' => [
                'enabled' => false,
                'post_types' => ['page', 'post'],
                'roles' => ['administrator', 'editor'],
                'show_in' => ['list', 'edit', 'admin_bar'],
                'new_status' => 'draft',
                'title_suffix' => 'Copy of ',
            ],
            'content_order' => [
                'enabled' => false,
                'post_types' => ['page'],
                'apply_frontend' => true,
            ],
            'terms_order' => [
                'enabled' => false,
                'taxonomies' => ['category'],
                'apply_frontend' => true,
            ],
            'media_replacement' => [
                'enabled' => false,
                'roles' => ['administrator', 'editor'],
                'replace_from_grid' => true,
                'replace_from_edit' => true,
            ],
            'allow_svgs' => [
                'enabled' => false,
                'roles' => ['administrator'],
            ],
            'disable_comments' => [
                'enabled' => false,
                'hide_existing' => false,
                'closed_existing' => false,
            ],
            'disable_feeds' => [
                'enabled' => false,
                'redirect_to_home' => true,
            ],
            'disable_smaller' => [
                'enabled' => false,
                'remove_generator' => true,
                'remove_wp_version' => true,
                'remove_wlw' => true,
                'remove_rsd' => true,
                'remove_shortlink' => true,
                'remove_adjacent' => true,
                'disable_emoji' => true,
                'disable_wp_embed' => true,
                'disable_block_css' => false,
                'disable_jquery_migrate' => true,
                'disable_wc_assets' => false,
            ],
        ];
    }

    public static function get_settings() {
        $saved = get_option(self::OPTION, []);
        $saved = is_array($saved) ? $saved : [];
        $settings = [];

        foreach (self::get_defaults() as $module => $defaults) {
            $module_settings = isset($saved[$module]) && is_array($saved[$module]) ? $saved[$module] : [];
            $settings[$module] = wp_parse_args($module_settings, $defaults);
        }

        return $settings;
    }

    public static function update_settings($settings) {
        update_option(self::OPTION, $settings);
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
            'message' => sprintf('%d of %d modules active', $enabled, count(self::get_module_classes())),
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
        $settings = self::get_settings();
        return $settings[$module] ?? self::get_defaults()[$module] ?? [];
    }
}
