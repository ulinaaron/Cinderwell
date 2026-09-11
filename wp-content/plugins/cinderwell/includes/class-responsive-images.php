<?php
/**
 * Upgrade saved Cinderwell image markup with WordPress responsive attributes.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Responsive_Images {

    public function __construct() {
        add_filter( 'render_block', [ $this, 'enhance_block_images' ], 30, 2 );
    }

    public function enhance_block_images( $block_content, $block ) {
        $block_name = $block['blockName'] ?? '';
        if ( '' === $block_content || 0 !== strpos( $block_name, 'cinderwell/' ) || ! class_exists( '\\WP_HTML_Tag_Processor' ) ) {
            return $block_content;
        }

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
