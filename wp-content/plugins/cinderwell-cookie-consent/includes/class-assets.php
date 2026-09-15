<?php
/**
 * Prior-blocking integration for categorized assets and embeds.
 *
 * @package Cinderwell_Cookie_Consent
 */

namespace Cinderwell_Cookie_Consent;

defined( 'ABSPATH' ) || exit;

class Assets {

    public function __construct() {
        add_action( 'init', [ $this, 'disable_remote_emoji_fallback' ] );
        add_filter( 'script_loader_tag', [ $this, 'block_script' ], 20, 3 );
        add_filter( 'style_loader_tag', [ $this, 'block_style' ], 20, 4 );
        add_filter( 'render_block', [ $this, 'block_embeds' ], 20, 2 );
    }

    public static function registry() {
        $registry = apply_filters( 'cinderwell_cookie_consent_assets', [
            'scripts' => [
                // WooCommerce source attribution is useful, but not necessary
                // to provide the cart or checkout requested by the visitor.
                'sourcebuster-js'     => 'analytics',
                'wc-order-attribution' => 'analytics',
            ],
            'styles' => [],
        ] );
        return wp_parse_args( is_array( $registry ) ? $registry : [], [ 'scripts' => [], 'styles' => [] ] );
    }

    /**
     * Avoid WordPress's optional sessionStorage emoji test and remote CDN
     * fallback. Modern browsers render native emoji without this enhancement.
     */
    public function disable_remote_emoji_fallback() {
        if ( ! Settings::get()['enabled'] ) {
            return;
        }
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    }

    public function block_script( $tag, $handle, $src ) {
        if ( ! Settings::get()['enabled'] || 'cinderwell-cookie-consent' === $handle ) {
            return $tag;
        }
        $category = $this->category_for_handle( 'scripts', $handle );
        if ( ! $category ) {
            return $tag;
        }
        $tag = preg_replace( '/\s+type=("|\')[^"\']*\1/i', '', $tag );
        return preg_replace( '/<script\b/i', '<script type="text/plain" data-cw-consent-script data-cw-consent-category="' . esc_attr( $category ) . '"', $tag, 1 );
    }

    public function block_style( $html, $handle, $href, $media ) {
        if ( ! Settings::get()['enabled'] ) {
            return $html;
        }
        $category = $this->category_for_handle( 'styles', $handle );
        if ( ! $category ) {
            return $html;
        }
        return sprintf(
            '<link data-cw-consent-style data-cw-consent-category="%1$s" data-cw-href="%2$s" data-cw-media="%3$s" id="%4$s">' . "\n",
            esc_attr( $category ),
            esc_url( $href ),
            esc_attr( $media ?: 'all' ),
            esc_attr( $handle . '-css' )
        );
    }

    public function block_embeds( $html, $block ) {
        if ( ! Settings::get()['enabled'] || false === stripos( $html, '<iframe' ) ) {
            return $html;
        }
        return preg_replace_callback( '/<iframe\b([^>]*)>/i', function ( $matches ) use ( $block ) {
            $attributes = $matches[1];
            preg_match( '/\ssrc=("|\')([^"\']+)\1/i', $attributes, $src_match );
            preg_match( '/\sdata-cw-consent=("|\')([^"\']+)\1/i', $attributes, $category_match );
            $src      = $src_match[2] ?? '';
            $category = sanitize_key( $category_match[2] ?? $this->embed_category( $src, $block ) );
            if ( ! $src || ! $this->is_optional_category( $category ) ) {
                return $matches[0];
            }
            $attributes = preg_replace( '/\ssrc=("|\')[^"\']+\1/i', '', $attributes, 1 );
            return '<iframe data-cw-consent-frame data-cw-consent-category="' . esc_attr( $category ) . '" data-cw-src="' . esc_url( $src ) . '"' . $attributes . '>';
        }, $html );
    }

    private function category_for_handle( $type, $handle ) {
        $registry = self::registry();
        $category = sanitize_key( $registry[ $type ][ $handle ] ?? '' );
        return $this->is_optional_category( $category ) ? $category : '';
    }

    private function embed_category( $src, $block ) {
        $host     = strtolower( (string) wp_parse_url( $src, PHP_URL_HOST ) );
        $category = '';
        if ( preg_match( '/(^|\.)(youtube\.com|youtube-nocookie\.com|youtu\.be|vimeo\.com)$/', $host ) ) {
            $category = 'marketing';
        } elseif ( preg_match( '/(^|\.)(google\.com|googleapis\.com)$/', $host ) && false !== strpos( $src, '/maps' ) ) {
            $category = 'preferences';
        }
        return apply_filters( 'cinderwell_cookie_consent_embed_category', $category, $src, $block );
    }

    private function is_optional_category( $category ) {
        $categories = Settings::categories();
        return isset( $categories[ $category ] ) && empty( $categories[ $category ]['required'] );
    }
}
