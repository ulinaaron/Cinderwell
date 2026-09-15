<?php
namespace Cinderwell_Performance;

class Resource_Hints {
    public function __construct() {
        add_action('wp_head', [$this, 'output_hints'], 2);
    }

    public function output_hints() {
        if (is_admin()) return;

        $settings = Settings::get_settings();
        if (empty($settings['performance']['resource_hints_enabled'])) return;
        if (empty($settings['performance']['enabled'])) return;

        $perf = $settings['performance'];

        if (!empty($perf['resource_hints_preconnect'])) {
            foreach ($perf['resource_hints_preconnect'] as $domain) {
                $domain = esc_url(trim($domain));
                if (!empty($domain)) {
                    echo '<link rel="preconnect" href="' . esc_attr($domain) . '" crossorigin>' . "\n";
                }
            }
        }

        if (!empty($perf['resource_hints_dns_prefetch'])) {
            foreach ($perf['resource_hints_dns_prefetch'] as $domain) {
                $domain = esc_url(trim($domain));
                if (!empty($domain)) {
                    echo '<link rel="dns-prefetch" href="' . esc_attr($domain) . '">' . "\n";
                }
            }
        }
    }
}
