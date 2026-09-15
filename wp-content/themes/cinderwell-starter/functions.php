<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CINDERWELL_STARTER_VERSION', '0.3.7' );

// The starter supplies lightweight account/cart links in its header. Disable
// WooCommerce's automatically injected drawer application and global legacy
// styles outside real commerce routes.
add_filter( 'cinderwell_use_lightweight_commerce_header', '__return_true' );
add_filter( 'cinderwell_optimize_woocommerce_assets', '__return_true' );

// Give high-density screens an appropriately sized logo candidate instead of
// forcing the original 880px PNG into a roughly 150–220px header slot.
add_action( 'after_setup_theme', function () {
    add_image_size( 'cinderwell-logo', 440, 100, false );
} );

add_filter( 'get_custom_logo_image_attributes', function ( $attributes ) {
    $attributes['sizes'] = '(max-width: 600px) 150px, 220px';
    return $attributes;
} );

/**
 * Remove optional accent presets when a client theme does not use them.
 *
 * A child theme can opt out with:
 * add_filter( 'cinderwell_enable_accent_colors', '__return_false' );
 */
add_filter( 'wp_theme_json_data_theme', function ( $theme_json ) {
    if ( apply_filters( 'cinderwell_enable_accent_colors', true ) ) {
        return $theme_json;
    }

    $data    = $theme_json->get_data();
    $palette = $data['settings']['color']['palette']['theme'] ?? [];

    if ( ! is_array( $palette ) ) {
        return $theme_json;
    }

    $accent_slugs = [ 'accent-1', 'accent-2', 'accent-3' ];
    $palette      = array_values(
        array_filter(
            $palette,
            static function ( $color ) use ( $accent_slugs ) {
                return ! in_array( $color['slug'] ?? '', $accent_slugs, true );
            }
        )
    );

    return $theme_json->update_with( [
        'version'  => 3,
        'settings' => [
            'color' => [
                'palette' => $palette,
            ],
        ],
    ] );
} );

// Theme setup.
add_action( 'after_setup_theme', function () {
    load_theme_textdomain( 'cinderwell-starter', get_template_directory() . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ] );
    add_theme_support( 'custom-logo', [
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ] );
    add_theme_support( 'editor-styles' );
    // Cinderwell owns its block-editor layout CSS. The legacy theme editor
    // stylesheet zeroes block padding and would hide responsive spacing tokens.
    add_editor_style( 'assets/css/main.css' );

    register_nav_menus( [
        'primary' => __( 'Primary Menu', 'cinderwell-starter' ),
        'footer'  => __( 'Footer Menu', 'cinderwell-starter' ),
    ] );
} );

// A link-focused mega-menu treatment for Navigation Submenu blocks. Editors can
// select a submenu and choose Styles > Mega menu in the Site Editor.
add_action( 'init', function () {
    register_block_style( 'core/navigation-submenu', [
        'name'  => 'mega-menu',
        'label' => __( 'Mega menu', 'cinderwell-starter' ),
    ] );
} );

// Enqueue styles and scripts.
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'cinderwell-starter-main', get_template_directory_uri() . '/assets/css/main.css', [], CINDERWELL_STARTER_VERSION );
} );

/**
 * Find a useful summary in block content when a manual excerpt is unavailable.
 */
function cinderwell_starter_get_content_description( $post_id ) {
    $content = get_post_field( 'post_content', $post_id );
    if ( ! $content ) {
        return '';
    }

    $find_description = function ( $blocks ) use ( &$find_description ) {
        foreach ( $blocks as $block ) {
            $attributes = $block['attrs'] ?? [];
            foreach ( [ 'subheading', 'bodyContent', 'description', 'excerpt' ] as $attribute ) {
                $candidate = trim( wp_strip_all_tags( (string) ( $attributes[ $attribute ] ?? '' ) ) );
                if ( '' !== $candidate ) {
                    return $candidate;
                }
            }

            if ( 'core/paragraph' === ( $block['blockName'] ?? '' ) ) {
                $candidate = trim( wp_strip_all_tags( $block['innerHTML'] ?? '' ) );
                if ( '' !== $candidate ) {
                    return $candidate;
                }
            }

            if ( ! empty( $block['innerBlocks'] ) ) {
                $candidate = $find_description( $block['innerBlocks'] );
                if ( '' !== $candidate ) {
                    return $candidate;
                }
            }
        }

        return '';
    };

    return $find_description( parse_blocks( $content ) );
}

// Provide a small metadata baseline until a dedicated SEO plugin takes over.
add_action( 'wp_head', function () {
    $seo_plugin_active = defined( 'WPSEO_VERSION' )
        || defined( 'RANK_MATH_VERSION' )
        || defined( 'AIOSEO_VERSION' )
        || defined( 'SEOPRESS_VERSION' );

    if ( $seo_plugin_active || ! apply_filters( 'cinderwell_starter_enable_fallback_meta', true ) ) {
        return;
    }

    $description = '';
    if ( is_front_page() || is_home() ) {
        $description = get_bloginfo( 'description' );
    } elseif ( is_singular() ) {
        $description = get_the_excerpt();
    } elseif ( is_category() || is_tag() || is_tax() ) {
        $description = term_description();
    }

    if ( '' === trim( wp_strip_all_tags( $description ) ) && is_singular() ) {
        $description = cinderwell_starter_get_content_description( get_queried_object_id() );
    }

    if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
        $description = sprintf(
            /* translators: %s: site title. */
            __( 'Explore %s for information, services, and the latest updates.', 'cinderwell-starter' ),
            get_bloginfo( 'name' )
        );
    }

    $description = wp_trim_words( wp_strip_all_tags( $description ), 32, '…' );
    $title       = wp_get_document_title();
    $url         = is_singular() ? get_permalink() : home_url( add_query_arg( [], $GLOBALS['wp']->request ?? '' ) );
    $image       = is_singular() && has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'large' ) : '';

    if ( $description ) {
        printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $description ) );
        printf( "<meta property=\"og:description\" content=\"%s\">\n", esc_attr( $description ) );
        printf( "<meta name=\"twitter:description\" content=\"%s\">\n", esc_attr( $description ) );
    }

    printf( "<meta property=\"og:title\" content=\"%s\">\n", esc_attr( $title ) );
    printf( "<meta property=\"og:type\" content=\"%s\">\n", esc_attr( is_singular( 'post' ) ? 'article' : 'website' ) );
    printf( "<meta property=\"og:url\" content=\"%s\">\n", esc_url( $url ) );
    echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";

    if ( $image ) {
        printf( "<meta property=\"og:image\" content=\"%s\">\n", esc_url( $image ) );
        printf( "<meta name=\"twitter:image\" content=\"%s\">\n", esc_url( $image ) );
    }
}, 5 );

// Widget areas.
add_action( 'widgets_init', function () {
    register_sidebar( [
        'name'          => __( 'Footer Widgets', 'cinderwell-starter' ),
        'id'            => 'footer-1',
        'description'   => __( 'Widgets in the footer area.', 'cinderwell-starter' ),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="footer-widget-title">',
        'after_title'   => '</h3>',
    ] );
} );
