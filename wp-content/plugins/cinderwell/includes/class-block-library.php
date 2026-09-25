<?php
/**
 * Sitewide block-library curation and functional grouping.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Block_Library {

    const OPTION = 'cinderwell_block_library';

    public function __construct() {
        add_filter( 'allowed_block_types_all', [ $this, 'filter_allowed_blocks' ], 18, 2 );
    }

    public static function get_profiles() {
        return (array) apply_filters( 'cinderwell_block_library_profiles', [
            'curated' => [
                'label'       => __( 'Curated', 'cinderwell' ),
                'description' => __( 'Cinderwell and active Cinderwell add-ons only.', 'cinderwell' ),
            ],
            'essentials' => [
                'label'       => __( 'Essentials', 'cinderwell' ),
                'description' => __( 'Cinderwell plus a small set of familiar WordPress content blocks.', 'cinderwell' ),
            ],
            'full' => [
                'label'       => __( 'Full', 'cinderwell' ),
                'description' => __( 'Every block registered by WordPress, plugins, and the active theme.', 'cinderwell' ),
            ],
        ] );
    }

    public static function get_groups() {
        return (array) apply_filters( 'cinderwell_block_library_groups', [
            'cinderwell-layout'      => [ 'title' => __( 'Cinderwell Layout', 'cinderwell' ), 'icon' => null ],
            'cinderwell-content'     => [ 'title' => __( 'Cinderwell Content', 'cinderwell' ), 'icon' => null ],
            'cinderwell-media'       => [ 'title' => __( 'Cinderwell Media', 'cinderwell' ), 'icon' => null ],
            'cinderwell-interactive' => [ 'title' => __( 'Cinderwell Interactive', 'cinderwell' ), 'icon' => null ],
            'cinderwell-site'        => [ 'title' => __( 'Cinderwell Site', 'cinderwell' ), 'icon' => null ],
        ] );
    }

    public static function get_catalog() {
        $groups = [
            'cinderwell/hero'             => 'cinderwell-layout',
            'cinderwell/section'          => 'cinderwell-layout',
            'cinderwell/columns'          => 'cinderwell-layout',
            'cinderwell/column'           => 'cinderwell-layout',
            'cinderwell/card-grid'        => 'cinderwell-layout',
            'cinderwell/image-text'       => 'cinderwell-layout',
            'cinderwell/cta'              => 'cinderwell-layout',
            'cinderwell/body'             => 'cinderwell-content',
            'cinderwell/heading'          => 'cinderwell-content',
            'cinderwell/number'           => 'cinderwell-content',
            'cinderwell/quote'            => 'cinderwell-content',
            'cinderwell/testimonials'     => 'cinderwell-content',
			'cinderwell/content-slider'    => 'cinderwell-content',
            'cinderwell/note'             => 'cinderwell-content',
            'cinderwell/accordion'        => 'cinderwell-content',
            'cinderwell/faq'              => 'cinderwell-content',
            'cinderwell/tabs'             => 'cinderwell-content',
            'cinderwell/tab-item'         => 'cinderwell-content',
            'cinderwell/icon-list'        => 'cinderwell-content',
            'cinderwell/icon-list-item'   => 'cinderwell-content',
            'cinderwell/divider'          => 'cinderwell-content',
            'cinderwell/loop'             => 'cinderwell-content',
            'cinderwell/company-details'  => 'cinderwell-content',
            'cinderwell/image'            => 'cinderwell-media',
            'cinderwell/gallery'          => 'cinderwell-media',
            'cinderwell/image-carousel'   => 'cinderwell-media',
            'cinderwell/map'              => 'cinderwell-media',
            'cinderwell/video'            => 'cinderwell-media',
            'cinderwell/icon'             => 'cinderwell-media',
            'cinderwell/button'           => 'cinderwell-interactive',
            'cinderwell/link'             => 'cinderwell-interactive',
            'cinderwell/gravity-form'     => 'cinderwell-interactive',
            'cinderwell/page-header'      => 'cinderwell-site',
            'cinderwell/header-search'    => 'cinderwell-site',
            'cinderwell/utility-bar'      => 'cinderwell-site',
            'cinderwell/mega-menu'        => 'cinderwell-site',
            'cinderwell/mega-menu-column' => 'cinderwell-site',
            'cinderwell/commerce-links'   => 'cinderwell-site',
            'cinderwell-popups/trigger'   => 'cinderwell-interactive',
            'cinderwell-popups/popup-content' => 'cinderwell-interactive',
            'cinderwell-portal/login'          => 'cinderwell-interactive',
            'cinderwell-portal/register'       => 'cinderwell-interactive',
            'cinderwell-portal/member-profile' => 'cinderwell-interactive',
            'cinderwell-portal/member-only'    => 'cinderwell-interactive',
        ];

        return (array) apply_filters( 'cinderwell_block_library_catalog', $groups );
    }

    public static function group_for_block( $name ) {
        $catalog = self::get_catalog();
        if ( isset( $catalog[ $name ] ) ) {
            return $catalog[ $name ];
        }

        return self::is_cinderwell_block( $name ) ? 'cinderwell-content' : null;
    }

    public static function is_cinderwell_block( $name ) {
        return 0 === strpos( $name, 'cinderwell/' ) || 0 === strpos( $name, 'cinderwell-' );
    }

    public static function get_settings() {
        $stored   = get_option( self::OPTION, [] );
        $stored   = is_array( $stored ) ? $stored : [];
        $profiles = self::get_profiles();
        $profile  = sanitize_key( $stored['profile'] ?? 'curated' );

        return [
            'version'   => 1,
            'profile'   => isset( $profiles[ $profile ] ) ? $profile : 'curated',
            'overrides' => self::sanitize_overrides( $stored['overrides'] ?? [] ),
        ];
    }

    public static function save_from_request( $submitted ) {
        $submitted = is_array( $submitted ) ? $submitted : [];
        $profiles  = self::get_profiles();
        $profile   = sanitize_key( $submitted['profile'] ?? 'curated' );
        $profile   = isset( $profiles[ $profile ] ) ? $profile : 'curated';
        $enabled   = array_map( 'sanitize_text_field', (array) ( $submitted['blocks'] ?? [] ) );
        $enabled   = array_fill_keys( $enabled, true );
        $overrides = [];

        foreach ( self::get_external_blocks() as $name => $block ) {
            $default = self::profile_allows_external( $name, $profile );
            $checked = isset( $enabled[ $name ] );
            if ( $checked !== $default ) {
                $overrides[ $name ] = $checked;
            }
        }

        update_option( self::OPTION, [
            'version'   => 1,
            'profile'   => $profile,
            'overrides' => $overrides,
        ] );
    }

    public static function get_external_blocks() {
        $blocks = [];
        foreach ( \WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $block ) {
            if ( self::is_cinderwell_block( $name ) || false === ( $block->supports['inserter'] ?? true ) || ! empty( $block->parent ) || ! empty( $block->ancestor ) ) {
                continue;
            }
            $namespace = strtok( $name, '/' ) ?: __( 'Other', 'cinderwell' );
            $blocks[ $name ] = [
                'label'    => $block->title ?: $name,
                'provider' => self::provider_label( $namespace ),
            ];
        }
        uasort( $blocks, static function ( $a, $b ) {
            return strcasecmp( $a['provider'] . $a['label'], $b['provider'] . $b['label'] );
        } );

        return (array) apply_filters( 'cinderwell_block_library_external_blocks', $blocks );
    }

    public static function is_external_enabled( $name, $settings = null ) {
        $settings = is_array( $settings ) ? $settings : self::get_settings();
        if ( array_key_exists( $name, $settings['overrides'] ) ) {
            return (bool) $settings['overrides'][ $name ];
        }
        return self::profile_allows_external( $name, $settings['profile'] );
    }

    public function filter_allowed_blocks( $allowed, $context ) {
        if ( ! self::should_curate_context( $context ) ) {
            return $allowed;
        }

        $settings = self::get_settings();
        if ( 'full' === $settings['profile'] && ! $settings['overrides'] ) {
            return $allowed;
        }

        $registered = array_keys( \WP_Block_Type_Registry::get_instance()->get_all_registered() );
        $permitted  = [];
        foreach ( $registered as $name ) {
            if ( self::is_cinderwell_block( $name ) || self::is_external_enabled( $name, $settings ) ) {
                $permitted[] = $name;
            }
        }

        $permitted = (array) apply_filters( 'cinderwell_block_library_allowed_blocks', $permitted, $settings, $context );
        if ( is_array( $allowed ) ) {
            return array_values( array_intersect( $allowed, $permitted ) );
        }
        return false === $allowed ? false : array_values( array_unique( $permitted ) );
    }

    private static function should_curate_context( $context ) {
        $name      = $context->name ?? '';
        $post_type = $context->post->post_type ?? '';
        $enabled   = 'core/edit-post' === $name && ! in_array( $post_type, [ 'product', 'product_variation', 'shop_order' ], true );

        return (bool) apply_filters( 'cinderwell_block_library_curate_context', $enabled, $context );
    }

    private static function profile_allows_external( $name, $profile ) {
        if ( 'full' === $profile ) {
            return true;
        }
        if ( 'essentials' !== $profile ) {
            return false;
        }

        return in_array( $name, self::essential_blocks(), true );
    }

    private static function essential_blocks() {
        return (array) apply_filters( 'cinderwell_block_library_essential_blocks', [
            'core/paragraph',
            'core/heading',
            'core/list',
            'core/table',
            'core/image',
            'core/gallery',
            'core/file',
            'core/video',
            'core/embed',
            'core/shortcode',
            'core/buttons',
            'core/button',
            'core/separator',
            'core/spacer',
        ] );
    }

    private static function sanitize_overrides( $overrides ) {
        $clean = [];
        foreach ( (array) $overrides as $name => $enabled ) {
            $name = sanitize_text_field( $name );
            if ( false !== strpos( $name, '/' ) ) {
                $clean[ $name ] = (bool) $enabled;
            }
        }
        return $clean;
    }

    private static function provider_label( $namespace ) {
        $labels = [
            'core'        => __( 'WordPress', 'cinderwell' ),
            'woocommerce' => __( 'WooCommerce', 'cinderwell' ),
            'gravityforms'=> __( 'Gravity Forms', 'cinderwell' ),
        ];
        return $labels[ $namespace ] ?? ucwords( str_replace( [ '-', '_' ], ' ', $namespace ) );
    }
}
