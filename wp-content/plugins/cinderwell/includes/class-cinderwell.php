<?php
/**
 * Main plugin class.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Cinderwell {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_textdomain();
        $this->register_components();
        $this->enqueue_shared_assets();
        $this->enqueue_editor_assets();
    }

    /**
     * Register public style capabilities and attach them to compatible blocks.
     */
    private function enqueue_shared_assets() {
        add_action( 'init', function () {
            $styles = [
                'base'       => [],
                'actions'    => [ 'cinderwell-base' ],
                'responsive' => [ 'cinderwell-base' ],
                'media'      => [ 'cinderwell-base' ],
            ];

            foreach ( $styles as $capability => $dependencies ) {
                wp_register_style(
                    "cinderwell-{$capability}",
                    CINDERWELL_BUILD_URL . "shared/{$capability}.css",
                    $dependencies,
                    CINDERWELL_VERSION
                );
            }
        }, 5 );

        add_filter( 'block_type_metadata_settings', function ( $settings, $metadata ) {
            $block_name = $metadata['name'] ?? '';
            if ( 0 !== strpos( $block_name, 'cinderwell/' ) ) {
                return $settings;
            }

            $responsive_blocks = [
                'cinderwell/accordion',
                'cinderwell/body',
                'cinderwell/card-grid',
                'cinderwell/cta',
                'cinderwell/faq',
                'cinderwell/gallery',
                'cinderwell/gravity-form',
                'cinderwell/hero',
                'cinderwell/image-text',
                'cinderwell/image-carousel',
                'cinderwell/loop',
                'cinderwell/quote',
                'cinderwell/section',
                'cinderwell/slot-layout',
                'cinderwell/tabs',
                'cinderwell/two-column',
            ];
            $action_blocks = [
                'cinderwell/button',
                'cinderwell/body',
                'cinderwell/card-grid',
                'cinderwell/cta',
                'cinderwell/hero',
                'cinderwell/image-text',
                'cinderwell/slot-layout',
                'cinderwell/two-column',
            ];
            $media_blocks = array_merge( $responsive_blocks, [ 'cinderwell/image' ] );

            $handles = [ 'cinderwell-base' ];
            if ( in_array( $block_name, $action_blocks, true ) ) {
                $handles[] = 'cinderwell-actions';
            }
            if ( in_array( $block_name, $responsive_blocks, true ) ) {
                $handles[] = 'cinderwell-responsive';
            }
            if ( in_array( $block_name, $media_blocks, true ) ) {
                $handles[] = 'cinderwell-media';
            }

            $settings['style_handles'] = array_values(
                array_unique( array_merge( $handles, $settings['style_handles'] ?? [] ) )
            );
            $settings['editor_style_handles'] = array_values(
                array_unique( array_merge( $handles, $settings['editor_style_handles'] ?? [] ) )
            );

            return $settings;
        }, 10, 2 );
    }

    /**
     * Enqueue editor-only CSS for full-width block layout.
     */
    private function enqueue_editor_assets() {
        // Enqueue editor controls CSS in the admin.
        add_action( 'admin_enqueue_scripts', function () {
            wp_enqueue_style(
                'cinderwell-editor-controls',
                CINDERWELL_BUILD_URL . 'shared/editor-controls.css',
                [],
                CINDERWELL_VERSION
            );
        } );

        // Also inject into the editor iframe via block_editor_settings_all.
        add_filter( 'block_editor_settings_all', function ( $settings ) {
            $style_path = CINDERWELL_BUILD_DIR . 'shared/editor-controls.css';
            $css = file_exists( $style_path ) ? file_get_contents( $style_path ) : '';
            if ( $css ) {
                $settings['styles'][] = [
                    'css' => $css,
                    'isGlobalStyles' => false,
                ];
            }
            return $settings;
        } );

        // Add the token management sidebar to Gutenberg's editor header.
        add_action( 'enqueue_block_editor_assets', function () {
            $asset_path = CINDERWELL_BUILD_DIR . 'editor/index.asset.php';
            $script_path = CINDERWELL_BUILD_DIR . 'editor/index.js';

            if ( ! file_exists( $asset_path ) || ! file_exists( $script_path ) ) {
                return;
            }

            $asset = include $asset_path;
            wp_enqueue_script(
                'cinderwell-editor-tools',
                CINDERWELL_BUILD_URL . 'editor/index.js',
                $asset['dependencies'],
                $asset['version'],
                true
            );

            $style_path = CINDERWELL_BUILD_DIR . 'editor/style-index.css';
            if ( file_exists( $style_path ) ) {
                wp_enqueue_style(
                    'cinderwell-editor-tools',
                    CINDERWELL_BUILD_URL . 'editor/style-index.css',
                    [ 'wp-components' ],
                    $asset['version']
                );
            }

			global $post;
			$post_id = $post instanceof \WP_Post ? $post->ID : get_the_ID();
			$user = wp_get_current_user();

            wp_localize_script(
                'cinderwell-editor-tools',
                'cinderwellEditorSettings',
                [
                    'tokens'       => Design_Tokens::get_editor_tokens(),
                    'canManage'    => current_user_can( 'manage_options' ),
                    'dataSources'  => Data_Sources::get_groups(),
                    'acfFields'    => Data_Sources::get_acf_fields( $post_id ),
                    'conditions'   => Conditions::get_client_visible(),
					'siteName'     => get_bloginfo( 'name' ),
					'siteTagline'  => get_bloginfo( 'description' ),
					'previewValues' => [
						'post_permalink'   => $post_id ? get_permalink( $post_id ) : '',
						'post_date'        => $post_id ? get_the_date( '', $post_id ) : '',
						'post_author'      => $post_id ? get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ) : '',
						'current_user_name' => $user->exists() ? $user->display_name : '',
					],
					'acfValues'    => Data_Sources::get_acf_preview_values( $post_id ),
                ]
            );
        } );
    }

    private function load_textdomain() {
        add_action( 'init', function () {
            load_plugin_textdomain( 'cinderwell', false, dirname( plugin_basename( CINDERWELL_FILE ) ) . '/languages' );
        } );
    }

    private function register_components() {
        new Design_Tokens();
        new Block_Loader();
        new Pattern_Loader();
        new Extension_API();
        new Safe_SVG_Uploads();
        new Renderer();
        new Responsive_Images();
        new Accessible_Links();
        new Commerce();
        new Schema_Aggregator();
        new Admin_Bar();
        new Admin_Page();
        new Update_Mechanism();
    }
}
