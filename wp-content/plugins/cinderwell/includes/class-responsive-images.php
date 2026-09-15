<?php
/**
 * Upgrade saved Cinderwell image markup with WordPress responsive attributes.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Responsive_Images {

    /** @var array<int, bool> Background rules already emitted this request. */
    private $rendered_backgrounds = [];

    public function __construct() {
        add_filter( 'render_block', [ $this, 'enhance_block_images' ], 30, 2 );
    }

    public function enhance_block_images( $block_content, $block ) {
        $block_name = $block['blockName'] ?? '';
        if ( '' === $block_content || 0 !== strpos( $block_name, 'cinderwell/' ) || ! class_exists( '\\WP_HTML_Tag_Processor' ) ) {
            return $block_content;
        }

        $block_content = $this->enhance_background_image( $block_content, $block_name, $block['attrs'] ?? [] );

        $images = $this->get_block_images( $block_name, $block['attrs'] ?? [] );
        if ( empty( $images ) ) {
            return $block_content;
        }

        $processor = new \WP_HTML_Tag_Processor( $block_content );
        foreach ( $images as $image ) {
            if ( ! $processor->next_tag( 'img' ) ) {
                break;
            }

            $attachment_id = absint( $image['id'] ?? 0 );
            if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
                continue;
            }

            $class = (string) $processor->get_attribute( 'class' );
            $alt   = (string) $processor->get_attribute( 'alt' );
            $core_markup = wp_get_attachment_image(
                $attachment_id,
                $image['size'] ?? 'large',
                false,
                [
                    'class' => $class,
                    'alt'   => $alt,
                ]
            );

            if ( ! $core_markup ) {
                continue;
            }

            $core_image = new \WP_HTML_Tag_Processor( $core_markup );
            if ( ! $core_image->next_tag( 'img' ) ) {
                continue;
            }

            foreach ( [ 'src', 'srcset', 'sizes', 'width', 'height', 'loading', 'decoding', 'fetchpriority' ] as $attribute ) {
                $value = $core_image->get_attribute( $attribute );
                if ( null === $value || false === $value || '' === $value ) {
                    $processor->remove_attribute( $attribute );
                } else {
                    $processor->set_attribute( $attribute, $value );
                }
            }

            if ( ! empty( $image['loading'] ) ) {
                $processor->set_attribute( 'loading', $image['loading'] );
            }
        }

        return $processor->get_updated_html();
    }

    /**
     * Serve token-controlled block backgrounds at viewport-appropriate sizes.
     * CSS backgrounds cannot use srcset, so saved full-size URLs otherwise make
     * every phone download the original upload.
     *
     * @param string $block_content Rendered block markup.
     * @param string $block_name    Block name.
     * @param array  $attributes    Block attributes.
     * @return string
     */
    private function enhance_background_image( $block_content, $block_name, $attributes ) {
        $attachment_id = 'cinderwell/hero' === $block_name
            ? absint( $attributes['bgImage'] ?? 0 )
            : absint( $attributes['backgroundImage'] ?? 0 );

        if (
            ! $attachment_id
            || ! wp_attachment_is_image( $attachment_id )
            || ( 'cinderwell/hero' === $block_name && false === ( $attributes['showBgImage'] ?? true ) )
        ) {
            return $block_content;
        }

        $mobile_url  = wp_get_attachment_image_url( $attachment_id, 'large' );
        $desktop_url = wp_get_attachment_image_url( $attachment_id, '1536x1536' ) ?: $mobile_url;
        $wide_url    = wp_get_attachment_image_url( $attachment_id, '2048x2048' ) ?: $desktop_url;

        if ( ! $mobile_url ) {
            return $block_content;
        }

        $class_name = 'cw-responsive-bg-' . $attachment_id;
        $processor  = new \WP_HTML_Tag_Processor( $block_content );
        if ( ! $processor->next_tag() ) {
            return $block_content;
        }

        $processor->add_class( $class_name );
        $style = (string) $processor->get_attribute( 'style' );
        $style = preg_replace( '/(^|;)\\s*background-image\\s*:[^;]+;?/i', '$1', $style );
        $style = trim( preg_replace( '/;{2,}/', ';', (string) $style ), " ;\t\n\r\0\x0B" );
        if ( '' === $style ) {
            $processor->remove_attribute( 'style' );
        } else {
            $processor->set_attribute( 'style', $style );
        }

        if ( empty( $this->rendered_backgrounds[ $attachment_id ] ) ) {
            $css = sprintf(
                '.%1$s{background-image:url(%2$s)!important}@media(min-width:900px){.%1$s{background-image:url(%3$s)!important}}@media(min-width:1600px){.%1$s{background-image:url(%4$s)!important}}',
                $class_name,
                wp_json_encode( esc_url_raw( $mobile_url ) ),
                wp_json_encode( esc_url_raw( $desktop_url ) ),
                wp_json_encode( esc_url_raw( $wide_url ) )
            );
            wp_add_inline_style( 'cinderwell-base', $css );
            $this->rendered_backgrounds[ $attachment_id ] = true;
        }

        return $processor->get_updated_html();
    }

    private function get_block_images( $block_name, $attributes ) {
        switch ( $block_name ) {
            case 'cinderwell/hero':
                $images = [];
                if ( false !== ( $attributes['showBgImage'] ?? false ) && ! empty( $attributes['bgImage'] ) ) {
                    $images[] = [ 'id' => $attributes['bgImage'], 'size' => 'full' ];
                }
                if ( false !== ( $attributes['showImage'] ?? true ) && ! empty( $attributes['image'] ) ) {
                    $images[] = [ 'id' => $attributes['image'], 'size' => 'large' ];
                }
                return $images;

            case 'cinderwell/image-text':
                return false !== ( $attributes['showImage'] ?? true ) && ! empty( $attributes['image'] ) ? [ [ 'id' => $attributes['image'], 'size' => 'large' ] ] : [];

            case 'cinderwell/card-grid':
                return array_values( array_map(
                    static function ( $card ) {
                        return [ 'id' => $card['image'], 'size' => 'medium_large' ];
                    },
                    array_filter( $attributes['cards'] ?? [], static function ( $card ) {
                        $visual_type = $card['visualType'] ?? ( ! empty( $card['showImage'] ) ? 'image' : 'none' );
                        return 'image' === $visual_type && ! empty( $card['image'] );
                    } )
                ) );

            case 'cinderwell/gallery':
                return array_values( array_map(
                    static function ( $image ) {
                        return [ 'id' => $image['id'], 'size' => 'large' ];
                    },
                    array_filter( $attributes['images'] ?? [], static function ( $image ) {
                        return ! empty( $image['id'] );
                    } )
                ) );

            case 'cinderwell/image-carousel':
                $carousel_images = [];
                foreach ( array_values( array_filter( $attributes['images'] ?? [], static function ( $image ) {
                    return ! empty( $image['id'] );
                } ) ) as $index => $image ) {
                    $carousel_images[] = [
                        'id'      => $image['id'],
                        'size'    => 'large',
                        'loading' => $index > 0 ? 'lazy' : '',
                    ];
                }
                return $carousel_images;

            case 'cinderwell/slot-layout':
                return array_values( array_map(
                    static function ( $slot ) {
                        return [ 'id' => $slot['imageId'], 'size' => 'large' ];
                    },
                    array_filter( $attributes['slots'] ?? [], static function ( $slot ) {
                        return 'image' === ( $slot['type'] ?? '' ) && ! empty( $slot['imageId'] );
                    } )
                ) );

            case 'cinderwell/image':
                return ! empty( $attributes['imageId'] ) ? [ [ 'id' => $attributes['imageId'], 'size' => 'large' ] ] : [];

            default:
                return [];
        }
    }
}
