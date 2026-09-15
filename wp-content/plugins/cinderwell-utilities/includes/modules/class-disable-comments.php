<?php
namespace Cinderwell_Utilities\Modules;

class Disable_Comments {
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;
        add_action('init', [$this, 'disable_comments_support'], 100);
        add_action('init', [$this, 'close_comments']);
        add_filter('comments_open', '__return_false', 20);
        add_filter('pings_open', '__return_false', 20);
        add_action('admin_menu', [$this, 'remove_admin_menu']);
        add_action('admin_init', [$this, 'disable_comments_page']);
        add_action('widgets_init', [$this, 'remove_comments_widget'], 99);

        if (!empty($settings['hide_existing'])) {
            add_filter('the_comments_array', '__return_empty_array', 20);
        }
    }

    public function disable_comments_support() {
        $post_types = get_post_types(['public' => true]);
        foreach ($post_types as $pt) {
            remove_post_type_support($pt, 'comments');
            remove_post_type_support($pt, 'trackbacks');
        }
    }

    public function close_comments() {
        if (!empty($this->settings['closed_existing']) && !get_option('cinderwell_utilities_comments_closed')) {
            global $wpdb;
            $wpdb->query("UPDATE {$wpdb->posts} SET comment_status = 'closed', ping_status = 'closed' WHERE comment_status = 'open' OR ping_status = 'open'");
            update_option('cinderwell_utilities_comments_closed', true);
        }
    }

    public function remove_admin_menu() {
        remove_menu_page('edit-comments.php');
    }

    public function disable_comments_page() {
        global $pagenow;
        if ($pagenow === 'edit-comments.php') {
            wp_safe_redirect(admin_url());
            exit;
        }
    }

    public function remove_comments_widget() {
        unregister_widget('WP_Widget_Recent_Comments');
    }
}
