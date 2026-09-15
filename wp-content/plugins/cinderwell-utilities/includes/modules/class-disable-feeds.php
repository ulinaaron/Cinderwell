<?php
namespace Cinderwell_Utilities\Modules;

class Disable_Feeds {
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;
        add_action('template_redirect', [$this, 'disable_feeds']);
        add_action('wp_head', [$this, 'remove_feed_links'], 1);
    }

    public function disable_feeds() {
        if (!is_feed()) return;

        if (!empty($this->settings['redirect_to_home'])) {
            wp_safe_redirect(home_url(), 301);
        } else {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
        }
        exit;
    }

    public function remove_feed_links() {
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
    }
}
