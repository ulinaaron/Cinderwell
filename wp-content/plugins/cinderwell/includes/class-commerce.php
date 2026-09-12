<?php
/**
 * WooCommerce integration.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Commerce {

    const STYLE_HANDLE = 'cinderwell-commerce';

    /**
     * Public WooCommerce blocks that can establish a commerce surface.
     * Inner cart and checkout blocks inherit the stylesheet from their root.
     */
    const ROOT_BLOCKS = [
        'woocommerce/add-to-cart-form',
        'woocommerce/add-to-cart-with-options',
        'woocommerce/all-products',
        'woocommerce/all-reviews',
        'woocommerce/cart',
        'woocommerce/cart-link',
        'woocommerce/catalog-sorting',
        'woocommerce/checkout',
        'woocommerce/customer-account',
        'woocommerce/featured-category',
        'woocommerce/featured-product',
        'woocommerce/handpicked-products',
        'woocommerce/legacy-template',
        'woocommerce/mini-cart',
        'woocommerce/product-best-sellers',
        'woocommerce/product-categories',
        'woocommerce/product-category',
        'woocommerce/product-collection',
        'woocommerce/product-filters',
        'woocommerce/product-new',
        'woocommerce/product-on-sale',
        'woocommerce/product-search',
        'woocommerce/product-template',
        'woocommerce/product-top-rated',
        'woocommerce/products-by-attribute',
        'woocommerce/related-products',
        'woocommerce/reviews-by-category',
        'woocommerce/reviews-by-product',
        'woocommerce/single-product',
        'woocommerce/store-notices',
    ];

    public function __construct() {
        add_action( 'init', [ $this, 'register_style' ], 20 );
        add_action( 'init', [ $this, 'attach_block_styles' ], 100 );
        add_action( 'enqueue_block_assets', [ $this, 'enqueue_editor_style' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_legacy_style' ], 20 );
    }

    /**
     * Whether WooCommerce is available for this request.
     */
    private function is_available() {
        return defined( 'WC_VERSION' ) || class_exists( '\\WooCommerce' );
    }

    /**
     * Register the shared commerce capability.
     */
    public function register_style() {
        if ( ! $this->is_available() ) {
            return;
        }

        wp_register_style(
            self::STYLE_HANDLE,
            CINDERWELL_BUILD_URL . 'shared/commerce.css',
            [ 'cinderwell-base', 'cinderwell-actions' ],
            CINDERWELL_VERSION
        );
    }

    /**
     * Attach the adapter to WooCommerce root blocks through WordPress's
     * on-demand block-style API.
     */
    public function attach_block_styles() {
        if ( ! $this->is_available() ) {
            return;
        }

        $blocks = apply_filters( 'cinderwell_woocommerce_blocks', self::ROOT_BLOCKS );

        foreach ( array_unique( $blocks ) as $block_name ) {
            if ( ! is_string( $block_name ) || 0 !== strpos( $block_name, 'woocommerce/' ) ) {
                continue;
            }

            wp_enqueue_block_style(
                $block_name,
                [
                    'handle' => self::STYLE_HANDLE,
                    'src'    => CINDERWELL_BUILD_URL . 'shared/commerce.css',
                    'path'   => CINDERWELL_BUILD_DIR . 'shared/commerce.css',
                    'deps'   => [ 'cinderwell-base', 'cinderwell-actions' ],
                    'ver'    => CINDERWELL_VERSION,
                ]
            );
        }
    }

    /**
     * Block styles need to be present in the iframed editor canvas even before
     * a dynamic WooCommerce block has rendered on the server.
     */
    public function enqueue_editor_style() {
        if ( is_admin() && $this->is_available() ) {
            wp_enqueue_style( self::STYLE_HANDLE );
        }
    }

    /**
     * Cover WooCommerce's PHP templates and shortcodes. Block pages are handled
     * by attach_block_styles() and remain conditional.
     */
    public function enqueue_legacy_style() {
        if ( ! $this->is_available() ) {
            return;
        }

        $is_store_request = ( function_exists( 'is_woocommerce' ) && is_woocommerce() )
            || ( function_exists( 'is_cart' ) && is_cart() )
            || ( function_exists( 'is_checkout' ) && is_checkout() )
            || ( function_exists( 'is_account_page' ) && is_account_page() );

        if ( $is_store_request ) {
            wp_enqueue_style( self::STYLE_HANDLE );
        }
    }
}
