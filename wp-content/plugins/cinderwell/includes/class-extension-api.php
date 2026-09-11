<?php
/**
 * Extension API — filter and action hooks for theme/plugin integration.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Extension_API {

    public function __construct() {
        add_action( 'init', [ $this, 'register_hooks' ] );
    }

    public function register_hooks() {
        // Theme override directory support.
        add_filter( 'block_type_metadata_settings', [ $this, 'maybe_override_render' ], 10, 2 );
    }

    /**
     * Check for theme override templates and apply render_callback if found.
     *
     * @param array  $settings Block type settings.
     * @param array  $metadata Block type metadata.
     * @return array Modified settings.
     */
    public function maybe_override_render( $settings, $metadata ) {
        if ( empty( $metadata['name'] ) || ! str_starts_with( $metadata['name'], 'cinderwell/' ) ) {
            return $settings;
        }

        $block_name = str_replace( 'cinderwell/', '', $metadata['name'] );
        $theme_dir  = get_template_directory() . '/cinderwell/' . $block_name;
        $plugin_dir = CINDERWELL_DIR . 'src/blocks/' . $block_name;

        // Check theme override directory.
        if ( file_exists( $theme_dir . '/index.html' ) ) {
            $settings['render_callback'] = function ( $attributes, $content ) use ( $block_name, $theme_dir ) {
                $html = file_get_contents( $theme_dir . '/index.html' );

                /**
                 * Filter block markup for {block_name}.
                 *
                 * @param string $html       Block HTML.
                 * @param array  $attributes Block attributes.
                 */
                return apply_filters( "cinderwell_render_{$block_name}", $html, $attributes );
            };
        }

        return $settings;
    }
}
