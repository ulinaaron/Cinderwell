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
        // Markup filter for every render, override or not.
        add_filter( 'render_block', [ $this, 'filter_render' ], 10, 2 );
    }

    /**
     * Locate a block override template. The active (child) theme wins, then the parent theme.
     *
     * @param string $block_name Block name without the namespace.
     * @return string Path to index.html, or empty string when there is no override.
     */
    public function locate_override( $block_name ) {
        $dirs = array_unique( [ get_stylesheet_directory(), get_template_directory() ] );

        foreach ( $dirs as $dir ) {
            $file = $dir . '/cinderwell/' . $block_name . '/index.html';
            if ( file_exists( $file ) ) {
                return $file;
            }
        }

        return '';
    }

    /**
     * Fill an override template. `{{content}}` is replaced with the block's
     * inner content (trusted, already-rendered markup); `{{attr_name}}` is
     * replaced with the escaped scalar attribute value (empty when unset).
     *
     * @param string $html       Template HTML.
     * @param array  $attributes Block attributes.
     * @param string $content    Block inner content.
     * @return string
     */
    public function fill_template( $html, $attributes, $content ) {
        $html = preg_replace_callback(
            '/\{\{\s*([A-Za-z0-9_-]+)\s*\}\}/',
            function ( $m ) use ( $attributes, $content ) {
                if ( 'content' === $m[1] ) {
                    return (string) $content;
                }
                $value = $attributes[ $m[1] ] ?? '';
                return is_scalar( $value ) ? esc_html( (string) $value ) : '';
            },
            $html
        );

        return $html;
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
        $file       = $this->locate_override( $block_name );

        // Only overridden blocks get a render_callback; others keep their saved markup.
        if ( $file ) {
            $settings['render_callback'] = function ( $attributes, $content ) use ( $file ) {
                return $this->fill_template( file_get_contents( $file ), (array) $attributes, $content );
            };
        }

        return $settings;
    }

    /**
     * Apply `cinderwell_render_{block}` to every Cinderwell block render.
     *
     * @param string $html  Rendered block HTML.
     * @param array  $block Parsed block.
     * @return string
     */
    public function filter_render( $html, $block ) {
        $name = $block['blockName'] ?? '';
        if ( ! is_string( $name ) || ! str_starts_with( $name, 'cinderwell/' ) ) {
            return $html;
        }

        $block_name = substr( $name, strlen( 'cinderwell/' ) );

        /**
         * Filter block markup for {block_name}.
         *
         * @param string $html       Block HTML.
         * @param array  $attributes Block attributes.
         */
        return apply_filters( "cinderwell_render_{$block_name}", $html, $block['attrs'] ?? [] );
    }
}
