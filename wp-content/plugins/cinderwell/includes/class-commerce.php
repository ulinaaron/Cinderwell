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
        add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_non_commerce_assets' ], 999 );
        add_action( 'wp_footer', [ $this, 'dequeue_non_commerce_assets' ], 1 );
        add_filter( 'hooked_block_types', [ $this, 'filter_header_block_hooks' ], 20, 4 );
        add_filter( 'woocommerce_enqueue_styles', [ $this, 'filter_legacy_woocommerce_styles' ] );
    }

    /**
     * Replace WooCommerce's automatically hooked account and drawer mini-cart
     * when the active theme provides Cinderwell's lightweight commerce links.
     *
     * @param array       $hooked_blocks Hooked block names.
     * @param string      $position      Relative hook position.
     * @param string      $anchor_block  Anchor block name.
     * @param object|null $context       Block template context.
     * @return array
     */
    public function filter_header_block_hooks( $hooked_blocks, $position, $anchor_block, $context ) {
        if ( ! apply_filters( 'cinderwell_use_lightweight_commerce_header', false ) ) {
            return $hooked_blocks;
        }

        if ( 'core/navigation' !== $anchor_block || 'after' !== $position ) {
            return $hooked_blocks;
        }

        return array_values(
            array_diff(
                $hooked_blocks,
                [ 'woocommerce/customer-account', 'woocommerce/mini-cart' ]
            )
        );
    }

    /**
     * WooCommerce's legacy stylesheet bundle is unnecessary on ordinary block
     * pages. Keep it on every actual commerce route and let individual Woo
     * blocks enqueue their own styles elsewhere.
     *
     * @param array $styles WooCommerce stylesheet definitions.
     * @return array
     */
    public function filter_legacy_woocommerce_styles( $styles ) {
        if ( is_admin() || ! apply_filters( 'cinderwell_optimize_woocommerce_assets', false ) ) {
            return $styles;
        }

        return $this->is_commerce_request() ? $styles : [];
    }

    /**
     * Remove WooCommerce's global frontend bundle from pages that contain no
     * commerce UI. Scripts and styles remain untouched on store routes, pages
     * containing Woo blocks, and pages using common Woo shortcodes.
     */
    public function dequeue_non_commerce_assets() {
        if (
            is_admin()
            || ! apply_filters( 'cinderwell_optimize_woocommerce_assets', false )
            || $this->is_commerce_request()
        ) {
            return;
        }

        foreach ( [
            'woocommerce-blocktheme',
            'woocommerce-inline',
            'wc-blocks-style',
            'wc-blocks-packages-style',
        ] as $handle ) {
            wp_dequeue_style( $handle );
        }

        foreach ( [
            'wc-add-to-cart',
            'woocommerce',
            'wc-jquery-blockui',
            'wc-js-cookie',
        ] as $handle ) {
            wp_dequeue_script( $handle );
        }
    }

    /**
     * Determine whether the current response needs WooCommerce's full assets.
     */
    private function is_commerce_request() {
        $is_store_request = ( function_exists( 'is_woocommerce' ) && is_woocommerce() )
            || ( function_exists( 'is_cart' ) && is_cart() )
            || ( function_exists( 'is_checkout' ) && is_checkout() )
            || ( function_exists( 'is_account_page' ) && is_account_page() );

        if ( $is_store_request ) {
            return true;
        }

        $post = get_queried_object();
        if ( ! $post instanceof \WP_Post || '' === trim( (string) $post->post_content ) ) {
            return (bool) apply_filters( 'cinderwell_is_commerce_request', false, $post );
        }

        foreach ( parse_blocks( $post->post_content ) as $block ) {
            if ( $this->contains_woocommerce_block( $block ) ) {
                return true;
            }
        }

        foreach ( [
            'add_to_cart',
            'add_to_cart_url',
            'best_selling_products',
            'product',
            'product_attribute',
            'product_category',
            'product_page',
            'products',
            'recent_products',
            'sale_products',
            'top_rated_products',
            'woocommerce_cart',
            'woocommerce_checkout',
            'woocommerce_my_account',
        ] as $shortcode ) {
            if ( has_shortcode( $post->post_content, $shortcode ) ) {
                return true;
            }
        }

        return (bool) apply_filters( 'cinderwell_is_commerce_request', false, $post );
    }

    /**
     * Recursively inspect parsed content for WooCommerce blocks.
     *
     * @param array $block Parsed block.
     */
    private function contains_woocommerce_block( $block ) {
        if ( 0 === strpos( (string) ( $block['blockName'] ?? '' ), 'woocommerce/' ) ) {
            return true;
        }

        foreach ( $block['innerBlocks'] ?? [] as $inner_block ) {
            if ( $this->contains_woocommerce_block( $inner_block ) ) {
                return true;
            }
        }

        return false;
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
