<?php
/**
 * Search-engine visibility status for administrators.
 *
 * @package Cinderwell_Utilities
 */

namespace Cinderwell_Utilities\Modules;

defined('ABSPATH') || exit;

class Search_Visibility_Status {
    public function __construct($settings = []) {
        add_action('admin_bar_menu', [$this, 'add_admin_bar_status'], 95);
        add_action('admin_head', [$this, 'print_admin_bar_styles']);
        add_action('wp_head', [$this, 'print_admin_bar_styles']);
    }

    public function add_admin_bar_status($admin_bar) {
        if (!$this->should_warn()) {
            return;
        }

        $admin_bar->add_node([
            'id'    => 'cinderwell-search-visibility',
            'title' => '<span class="ab-icon dashicons dashicons-hidden" aria-hidden="true"></span><span class="ab-label">' . esc_html__('Search engines discouraged', 'cinderwell-utilities') . '</span>',
            'href'  => admin_url('options-reading.php'),
            'meta'  => [
                'class' => 'cinderwell-search-visibility-status',
                'title' => __('Open Reading Settings', 'cinderwell-utilities'),
            ],
        ]);
    }

    public function print_admin_bar_styles() {
        if (!$this->should_warn() || !is_admin_bar_showing()) {
            return;
        }
        ?>
        <style id="cinderwell-search-visibility-status-css">
            #wpadminbar #wp-admin-bar-cinderwell-search-visibility > .ab-item {
                background: #dba617;
                color: #1d2327;
            }
            #wpadminbar #wp-admin-bar-cinderwell-search-visibility > .ab-item:hover,
            #wpadminbar #wp-admin-bar-cinderwell-search-visibility > .ab-item:focus {
                background: #f0c33c;
                color: #1d2327;
            }
            #wpadminbar #wp-admin-bar-cinderwell-search-visibility .ab-icon::before {
                color: currentcolor;
                top: 2px;
            }
        </style>
        <?php
    }

    private function should_warn() {
        return current_user_can('manage_options') && 0 === (int) get_option('blog_public', 1);
    }

}
