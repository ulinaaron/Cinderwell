<?php
/**
 * Switchable presentation variations for Cinderwell blocks.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Block_Variations {

    /**
     * Presentation attributes a Loop variation may change.
     *
     * Query, content, visibility, and link attributes are intentionally absent.
     */
    private const LOOP_PRESENTATION_ATTRIBUTES = [
        'layout',
        'columns',
        'columnsTablet',
        'columnsMobile',
        'imageAspect',
    ];

    /** Presentation attributes a Card Grid variation may change. */
    private const CARD_GRID_PRESENTATION_ATTRIBUTES = [
        'layout',
        'columns',
        'columnsTablet',
        'columnsMobile',
    ];

    /** Presentation attributes a Hero variation may change. */
    private const HERO_PRESENTATION_ATTRIBUTES = [
        'layout',
        'alignment',
        'width',
        'imageSide',
        'splitGap',
    ];

    public function __construct() {
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_styles' ], 20 );
    }

    /**
     * Get normalized variations for one block.
     *
     * Child themes and add-ons may add or replace entries by slug, or hide an
     * entry from the picker with `visible => false`.
     *
     * @param string $block_name Full block name.
     * @return array<string,array<string,mixed>>
     */
    public static function get( $block_name ) {
        $variations = self::get_core_variations( $block_name );

        /**
         * Filters switchable Cinderwell block variations.
         *
         * @param array  $variations Variation definitions keyed by slug.
         * @param string $block_name Full block name, for example cinderwell/loop.
         */
        $variations = apply_filters( 'cinderwell_block_variations', $variations, $block_name );

        if ( ! is_array( $variations ) ) {
            return [];
        }

        $normalized = [];
        foreach ( $variations as $key => $definition ) {
            if ( ! is_array( $definition ) ) {
                continue;
            }

            $slug = sanitize_key( $definition['slug'] ?? $key );
            if ( ! $slug ) {
                continue;
            }

            $attributes = is_array( $definition['attributes'] ?? null ) ? $definition['attributes'] : [];
            if ( 'cinderwell/loop' === $block_name ) {
                $attributes = array_intersect_key( $attributes, array_flip( self::LOOP_PRESENTATION_ATTRIBUTES ) );
                $attributes['layout'] = $slug;
            } elseif ( 'cinderwell/card-grid' === $block_name ) {
                $attributes = array_intersect_key( $attributes, array_flip( self::CARD_GRID_PRESENTATION_ATTRIBUTES ) );
                $attributes['layout'] = $slug;
            } elseif ( 'cinderwell/hero' === $block_name ) {
                $attributes = array_intersect_key( $attributes, array_flip( self::HERO_PRESENTATION_ATTRIBUTES ) );
                $attributes['layout'] = $slug;
            }

            $normalized[ $slug ] = [
                'slug'                 => $slug,
                'label'                => sanitize_text_field( $definition['label'] ?? ucwords( str_replace( '-', ' ', $slug ) ) ),
                'description'          => sanitize_text_field( $definition['description'] ?? '' ),
                'preview'              => sanitize_key( $definition['preview'] ?? $slug ),
                'attributes'           => $attributes,
                'visible'              => ! isset( $definition['visible'] ) || (bool) $definition['visible'],
                'order'                => (int) ( $definition['order'] ?? 100 ),
                'style_handle'         => sanitize_key( $definition['style_handle'] ?? '' ),
                'editor_style_handle'  => sanitize_key( $definition['editor_style_handle'] ?? '' ),
                'render_callback'      => $definition['render_callback'] ?? null,
                'render_item_callback' => $definition['render_item_callback'] ?? null,
            ];
        }

        uasort( $normalized, static function ( $left, $right ) {
            return $left['order'] <=> $right['order'];
        } );

        return $normalized;
    }

    /**
     * Get the editor-safe variation catalog for every supported block.
     */
    public static function get_editor_catalog() {
        $catalog = [];

        foreach ( self::get_supported_blocks() as $block_name ) {
            foreach ( self::get( $block_name ) as $slug => $definition ) {
                unset( $definition['render_callback'], $definition['render_item_callback'], $definition['style_handle'], $definition['editor_style_handle'] );
                $catalog[ $block_name ][ $slug ] = $definition;
            }
        }

        return $catalog;
    }

    /**
     * Enqueue styles registered by visible client-defined editor variations.
     */
    public function enqueue_editor_styles() {
        foreach ( self::get_supported_blocks() as $block_name ) {
            foreach ( self::get( $block_name ) as $definition ) {
                $handle = $definition['editor_style_handle'];
                if ( $handle && wp_style_is( $handle, 'registered' ) ) {
                    wp_enqueue_style( $handle );
                }
            }
        }
    }

    /** Get block names currently backed by this registry. */
    public static function get_supported_blocks() {
        return [ 'cinderwell/loop', 'cinderwell/card-grid', 'cinderwell/hero' ];
    }

    /**
     * Built-in definitions. These are presentation recipes, not content/query
     * transformations.
     */
    private static function get_core_variations( $block_name ) {
        if ( 'cinderwell/hero' === $block_name ) {
            return [
                'split' => [
                    'label'       => self::translate( 'Split' ),
                    'description' => self::translate( 'The dependable 50/50 content and image composition.' ),
                    'preview'     => 'hero-split',
                    'order'       => 10,
                    'attributes'  => [
                        'alignment' => 'left',
                        'width'     => 'full',
                        'imageSide' => 'right',
                        'splitGap'  => 'md',
                    ],
                ],
                'statement' => [
                    'label'       => self::translate( 'Statement' ),
                    'description' => self::translate( 'Centered, type-led content with an optional panoramic image.' ),
                    'preview'     => 'hero-statement',
                    'order'       => 20,
                    'attributes'  => [
                        'alignment' => 'center',
                        'width'     => 'narrow',
                        'imageSide' => 'bottom',
                        'splitGap'  => 'lg',
                    ],
                ],
                'editorial' => [
                    'label'       => self::translate( 'Editorial' ),
                    'description' => self::translate( 'An overlapping content panel against an oversized image.' ),
                    'preview'     => 'hero-editorial',
                    'order'       => 30,
                    'attributes'  => [
                        'alignment' => 'left',
                        'width'     => 'wide',
                        'imageSide' => 'right',
                        'splitGap'  => 'none',
                    ],
                ],
                'immersive' => [
                    'label'       => self::translate( 'Background Image' ),
                    'description' => self::translate( 'Full-bleed imagery with centered content over a deep gradient.' ),
                    'preview'     => 'hero-immersive',
                    'order'       => 40,
                    'attributes'  => [
                        'alignment' => 'center',
                        'width'     => 'full',
                        'imageSide' => 'right',
                        'splitGap'  => 'none',
                    ],
                ],
                'framed' => [
                    'label'       => self::translate( 'Framed' ),
                    'description' => self::translate( 'A contained two-column composition with breathing room around the image.' ),
                    'preview'     => 'hero-framed',
                    'order'       => 50,
                    'attributes'  => [
                        'alignment' => 'left',
                        'width'     => 'wide',
                        'imageSide' => 'right',
                        'splitGap'  => 'lg',
                    ],
                ],
            ];
        }

        if ( 'cinderwell/card-grid' === $block_name ) {
            return [
                'raised' => [
                    'label'       => self::translate( 'Raised' ),
                    'description' => self::translate( 'Elevated cards with a restrained hover lift.' ),
                    'preview'     => 'raised',
                    'order'       => 10,
                    'attributes'  => [
                        'columns'       => '3',
                        'columnsTablet' => '',
                        'columnsMobile' => '',
                    ],
                ],
                'bordered' => [
                    'label'       => self::translate( 'Bordered' ),
                    'description' => self::translate( 'Flat cards separated with a crisp border.' ),
                    'preview'     => 'bordered',
                    'order'       => 20,
                    'attributes'  => [
                        'columns'       => '3',
                        'columnsTablet' => '',
                        'columnsMobile' => '',
                    ],
                ],
                'horizontal' => [
                    'label'       => self::translate( 'Split Rows' ),
                    'description' => self::translate( 'Alternating editorial rows with generous image and content areas.' ),
                    'preview'     => 'horizontal',
                    'order'       => 30,
                    'attributes'  => [
                        'columns'       => '1',
                        'columnsTablet' => '1',
                        'columnsMobile' => '1',
                    ],
                ],
                'featured' => [
                    'label'       => self::translate( 'Mosaic' ),
                    'description' => self::translate( 'An asymmetric lead card surrounded by smaller supporting cards.' ),
                    'preview'     => 'featured',
                    'order'       => 40,
                    'attributes'  => [
                        'columns'       => '3',
                        'columnsTablet' => '2',
                        'columnsMobile' => '1',
                    ],
                ],
                'poster' => [
                    'label'       => self::translate( 'Posters' ),
                    'description' => self::translate( 'Full-bleed images with content layered over a strong gradient.' ),
                    'preview'     => 'poster',
                    'order'       => 50,
                    'attributes'  => [
                        'columns'       => '3',
                        'columnsTablet' => '2',
                        'columnsMobile' => '1',
                    ],
                ],
                'directory' => [
                    'label'       => self::translate( 'Directory' ),
                    'description' => self::translate( 'Numbered, compact rows for services, steps, or resources.' ),
                    'preview'     => 'directory',
                    'order'       => 60,
                    'attributes'  => [
                        'columns'       => '1',
                        'columnsTablet' => '1',
                        'columnsMobile' => '1',
                    ],
                ],
            ];
        }

        if ( 'cinderwell/loop' !== $block_name ) {
            return [];
        }

        return [
            'cards' => [
                'label'       => self::translate( 'Cards' ),
                'description' => self::translate( 'A balanced grid of image-led cards.' ),
                'preview'     => 'cards',
                'order'       => 10,
                'attributes'  => [
                    'columns'        => '3',
                    'columnsTablet'  => '2',
                    'columnsMobile'  => '1',
                    'imageAspect'    => 'landscape',
                ],
            ],
            'media-list' => [
                'label'       => self::translate( 'Media List' ),
                'description' => self::translate( 'Wide thumbnails paired with compact content.' ),
                'preview'     => 'media-list',
                'order'       => 20,
                'attributes'  => [
                    'columns'        => '1',
                    'columnsTablet'  => '1',
                    'columnsMobile'  => '1',
                    'imageAspect'    => 'landscape',
                ],
            ],
            'minimal-list' => [
                'label'       => self::translate( 'Minimal List' ),
                'description' => self::translate( 'A restrained, divided editorial list.' ),
                'preview'     => 'minimal-list',
                'order'       => 30,
                'attributes'  => [
                    'columns'        => '1',
                    'columnsTablet'  => '1',
                    'columnsMobile'  => '1',
                    'imageAspect'    => 'landscape',
                ],
            ],
            'featured-lead' => [
                'label'       => self::translate( 'Featured Lead' ),
                'description' => self::translate( 'A prominent first item followed by cards.' ),
                'preview'     => 'featured-lead',
                'order'       => 40,
                'attributes'  => [
                    'columns'        => '3',
                    'columnsTablet'  => '2',
                    'columnsMobile'  => '1',
                    'imageAspect'    => 'landscape',
                ],
            ],
        ];
    }

    /**
     * Avoid triggering just-in-time translation loading when an integration
     * inspects the registry before WordPress reaches init.
     */
    private static function translate( $text ) {
        return did_action( 'init' ) ? __( $text, 'cinderwell' ) : $text;
    }
}
