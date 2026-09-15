<?php
namespace Cinderwell_Utilities\Modules;

class Disable_Smaller {
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;

        if (!empty($settings['remove_generator'])) {
            add_filter('the_generator', '__return_empty_string');
            remove_action('wp_head', 'wp_generator');
        }

        if (!empty($settings['remove_wp_version'])) {
            add_filter('style_loader_src', [$this, 'remove_version'], 999);
            add_filter('script_loader_src', [$this, 'remove_version'], 999);
        }

        if (!empty($settings['remove_wlw'])) {
            remove_action('wp_head', 'wlwmanifest_link');
        }

        if (!empty($settings['remove_rsd'])) {
            remove_action('wp_head', 'rsd_link');
        }

        if (!empty($settings['remove_shortlink'])) {
            remove_action('wp_head', 'wp_shortlink_wp_head');
            remove_action('template_redirect', 'wp_shortlink_header', 11);
        }

        if (!empty($settings['remove_adjacent'])) {
            remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
        }

        if (!empty($settings['disable_emoji'])) {
            add_action('init', [$this, 'disable_emoji']);
        }

        if (!empty($settings['disable_wp_embed'])) {
            add_action('init', [$this, 'disable_wp_embed']);
        }

        if (!empty($settings['disable_block_css'])) {
            add_action('wp_enqueue_scripts', [$this, 'disable_block_css'], 100);
        }

        if (!empty($settings['disable_jquery_migrate'])) {
            add_action('wp_default_scripts', [$this, 'disable_jquery_migrate']);
        }

        if (!empty($settings['disable_wc_assets']) && class_exists('WooCommerce')) {
            add_action('wp_enqueue_scripts', [$this, 'disable_wc_assets'], 999);
        }
    }

    public function remove_version($src) {
        if (strpos($src, 'ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }

    public function disable_emoji() {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('tiny_mce_plugins', function ($plugins) {
            return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : [];
        });
        add_filter('wp_resource_hints', function ($urls, $relation_type) {
            if ('dns-prefetch' === $relation_type) {
                $urls = array_filter($urls, function ($url) {
                    return !preg_match('#//s\.w\.org/images/core/emoji#', $url['href'] ?? '');
                });
            }
            return $urls;
        }, 10, 2);
    }

    public function disable_wp_embed() {
        wp_deregister_script('wp-embed');
    }

    public function disable_block_css() {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('global-styles');
        wp_dequeue_style('classic-theme-styles');
    }

    public function disable_jquery_migrate($scripts) {
        if (!is_admin() && isset($scripts->registered['jquery'])) {
            $script = $scripts->registered['jquery'];
            if (!empty($script->deps)) {
                $script->deps = array_diff($script->deps, ['jquery-migrate']);
            }
        }
    }

    public function disable_wc_assets() {
        if (is_woocommerce() || is_cart() || is_checkout() || is_account_page()) {
            return;
        }
        wp_dequeue_style('woocommerce-general');
        wp_dequeue_style('woocommerce-layout');
        wp_dequeue_style('woocommerce-smallscreen');
        wp_dequeue_style('wc-blocks-style');
        wp_dequeue_script('wc-cart-fragments');
        wp_dequeue_script('woocommerce');
        wp_dequeue_script('wc-add-to-cart');
    }
}
