<?php
namespace Cinderwell_Performance;

class Settings {
    const OPTION = 'cinderwell_performance_settings';

    public static function get_defaults() {
        return [
            'performance' => [
                'enabled' => false,
                'html_cleanup' => true,
                'image_async_decoding' => true,
                'image_lazy_loading' => true,
                'image_fetchpriority' => false,
                'image_width_height' => false,
                'resource_hints_enabled' => false,
                'resource_hints_preconnect' => [],
                'resource_hints_dns_prefetch' => [],
            ],
            'hydration' => [
                'enabled' => false,
                'root_margin' => 200,
                'safe_mode' => true,
                'seo_bypass' => true,
                'bot_user_agents' => [
                    'Googlebot',
                    'Bingbot',
                    'Slurp',
                    'DuckDuckBot',
                    'Baiduspider',
                    'YandexBot',
                    'facebookexternalhit',
                    'Twitterbot',
                ],
            ],
        ];
    }

    public static function get_settings() {
        $defaults = self::get_defaults();
        $saved = (array) get_option(self::OPTION, []);

        return [
            'performance' => wp_parse_args((array) ($saved['performance'] ?? []), $defaults['performance']),
            'hydration' => wp_parse_args((array) ($saved['hydration'] ?? []), $defaults['hydration']),
        ];
    }

    public static function update_settings($settings) {
        update_option(self::OPTION, self::sanitize($settings));
    }

    public static function migrate_deprecated_settings() {
        $saved = (array) get_option(self::OPTION, []);
        $performance = (array) ($saved['performance'] ?? []);

        if (!array_key_exists('css_combining', $performance) && !array_key_exists('css_combining_threshold', $performance)) {
            return;
        }

        unset($performance['css_combining'], $performance['css_combining_threshold']);
        $saved['performance'] = $performance;
        self::update_settings($saved);
    }

    public static function sanitize($input) {
        $defaults = self::get_defaults();
        $settings = [];

        // Performance section
        $p = $input['performance'] ?? [];
        $settings['performance'] = [
            'enabled'                    => !empty($p['enabled']),
            'html_cleanup'               => !empty($p['html_cleanup']),
            'image_async_decoding'       => !empty($p['image_async_decoding']),
            'image_lazy_loading'         => !empty($p['image_lazy_loading']),
            'image_fetchpriority'        => !empty($p['image_fetchpriority']),
            'image_width_height'         => !empty($p['image_width_height']),
            'resource_hints_enabled'     => !empty($p['resource_hints_enabled']),
            'resource_hints_preconnect'  => self::sanitize_urls($p['resource_hints_preconnect'] ?? []),
            'resource_hints_dns_prefetch'=> self::sanitize_urls($p['resource_hints_dns_prefetch'] ?? []),
        ];

        // Hydration section
        $h = $input['hydration'] ?? [];
        $settings['hydration'] = [
            'enabled'         => !empty($h['enabled']),
            'root_margin'     => max(0, min(500, intval($h['root_margin'] ?? 200))),
            'safe_mode'       => !empty($h['safe_mode']),
            'seo_bypass'      => !empty($h['seo_bypass']),
            'bot_user_agents' => $defaults['hydration']['bot_user_agents'],
        ];

        return $settings;
    }

    public static function get_health() {
        $settings = self::get_settings();
        $active = 0;
        $perf = $settings['performance'];
        $hyd = $settings['hydration'];

        if (!empty($perf['enabled'])) {
            $features = ['html_cleanup', 'image_async_decoding', 'image_lazy_loading', 'image_fetchpriority', 'image_width_height', 'resource_hints_enabled'];
            foreach ($features as $f) {
                if (!empty($perf[$f])) $active++;
            }
        }
        if (!empty($hyd['enabled'])) $active++;

        return [
            'status' => 'good',
            'message' => $active > 0 ? "$active feature(s) active" : 'All features off',
        ];
    }

    public static function enabled() {
        $settings = self::get_settings();
        return !empty($settings['performance']['enabled']) || !empty($settings['hydration']['enabled']);
    }

    private static function sanitize_urls($value) {
        $urls = is_array($value) ? $value : explode("\n", (string) $value);
        return array_values(array_filter(array_map('esc_url_raw', array_map('trim', $urls))));
    }
}
