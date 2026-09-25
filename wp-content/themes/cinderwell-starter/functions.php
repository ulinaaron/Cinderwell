<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CINDERWELL_STARTER_VERSION', '0.4.3' );

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

// Theme setup.
add_action( 'after_setup_theme', function () {
    load_theme_textdomain( 'cinderwell-starter', get_template_directory() . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'cinderwell-design-tokens' );
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
