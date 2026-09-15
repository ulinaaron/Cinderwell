<?php
namespace Cinderwell_Performance;

class Hydration {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_css']);
        add_filter('render_block', [$this, 'auto_instrument'], 10, 2);
    }

    public function enqueue_assets() {
        if (is_admin()) return;

        $settings = Settings::get_settings();
        if (empty($settings['hydration']['enabled'])) return;

        wp_enqueue_script(
            'cinderwell-hydration-controller',
            CINDERWELL_PERFORMANCE_URL . 'assets/js/hydration-controller.js',
            [],
            CINDERWELL_PERFORMANCE_VERSION,
            true
        );

        wp_localize_script('cinderwell-hydration-controller', 'cinderwellHydration', [
            'rootMargin' => $settings['hydration']['root_margin'] . 'px',
            'safeMode' => $settings['hydration']['safe_mode'],
            'seoBypass' => $settings['hydration']['seo_bypass'],
            'botUserAgents' => apply_filters(
                'cinderwell_performance_bot_user_agents',
                $settings['hydration']['bot_user_agents']
            ),
        ]);
    }

    public function enqueue_css() {
        if (is_admin()) return;
        $settings = Settings::get_settings();
        if (empty($settings['hydration']['enabled'])) return;
        wp_enqueue_style(
            'cinderwell-hydration',
            CINDERWELL_PERFORMANCE_URL . 'assets/css/hydration.css',
            [],
            CINDERWELL_PERFORMANCE_VERSION
        );
    }

    public function auto_instrument($block_content, $block) {
        $settings = Settings::get_settings();
        if (empty($settings['hydration']['enabled'])) return $block_content;

        $interactive_blocks = [
            'cinderwell/button',
            'cinderwell-popups/trigger',
            'cinderwell/faq',
            'cinderwell/gallery',
            'cinderwell/tabs',
            'cinderwell/accordion',
            'cinderwell/video',
            'cinderwell/gravity-form',
            'cinderwell/two-column',
        ];

        if (in_array($block['blockName'], $interactive_blocks)) {
            if (strpos($block_content, 'data-cw-hydrate=') !== false) {
                return $block_content;
            }

            $block_content = preg_replace(
                '/^(\s*<[a-z][a-z0-9]*)/i',
                '$1 data-cw-hydrate="true"',
                $block_content,
                1
            );
        }

        return $block_content;
    }
}
