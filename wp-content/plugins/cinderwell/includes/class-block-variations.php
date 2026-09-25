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
        'width',
        'background',
        'cardColor',
        'imageBoxAspect',
        'imageBoxOverlay',
        'imageBoxContentPosition',
        'imageBoxContentVisibility',
        'imageBoxLinkStyle',
    ];

    /** Presentation attributes a Hero variation may change. */
    private const HERO_PRESENTATION_ATTRIBUTES = [
        'layout',
        'alignment',
        'width',
        'imageSide',
        'splitGap',
        'background',
        'bgOverlay',
        'bgOverlayPreset',
        'textSize',
        'textColor',
        'textStyles',
        'spacingResponsive',
    ];

    /** Presentation attributes an Image + Text variation may change. */
    private const IMAGE_TEXT_PRESENTATION_ATTRIBUTES = [
        'layout',
        'alignment',
        'width',
        'imageAspect',
        'imageFit',
        'imagePosition',
    ];

    /** Presentation attributes a CTA variation may change. */
    private const CTA_PRESENTATION_ATTRIBUTES = [
        'layout',
        'alignment',
        'width',
    ];

    /** Presentation attributes a Body variation may change. */
    private const BODY_PRESENTATION_ATTRIBUTES = [
        'layout',
        'width',
        'constrainCopyWidth',
    ];

    /** Presentation attributes a Columns variation may change. */
    private const COLUMNS_PRESENTATION_ATTRIBUTES = [
        'layout',
        'gap',
        'stackAt',
        'reverseOnMobile',
        'verticalAlignment',
        'width',
        'childBackgroundMode',
    ];

    /** Presentation attributes a Section variation may change. */
    private const SECTION_PRESENTATION_ATTRIBUTES = [
        'layout',
        'width',
        'childBackgroundMode',
    ];

    /** Presentation attributes an Icon List variation may change. */
    private const ICON_LIST_PRESENTATION_ATTRIBUTES = [
        'layout',
        'width',
        'background',
        'gap',
        'defaultIcon',
        'iconSize',
        'iconColor',
        'iconTreatment',
        'align',
    ];

    public function __construct() {
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_styles' ], 20 );
    }

    /**
     * Get normalized variations for one block.
     *
     * Child themes and add-ons may add or replace entries by slug, or hide an
     * entry from the picker with `visible => false`. Client-owned presentation
     * recipes should declare `custom => true`; Cinderwell owns the author-facing
     * indicator and behavior for that provenance flag.
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
            $controlled = self::normalize_controlled_controls( $definition['controlled'] ?? [] );
            if ( 'cinderwell/loop' === $block_name ) {
                $attributes = array_intersect_key( $attributes, array_flip( self::LOOP_PRESENTATION_ATTRIBUTES ) );
                $attributes['layout'] = $slug;
            } elseif ( 'cinderwell/card-grid' === $block_name ) {
                $attributes = array_intersect_key( $attributes, array_flip( self::CARD_GRID_PRESENTATION_ATTRIBUTES ) );
                $attributes['layout'] = $slug;
            } elseif ( 'cinderwell/hero' === $block_name ) {
                $attributes = array_intersect_key( $attributes, array_flip( self::HERO_PRESENTATION_ATTRIBUTES ) );
                $attributes['layout'] = $slug;
            } elseif ( 'cinderwell/image-text' === $block_name ) {
                $attributes = array_intersect_key( $attributes, array_flip( self::IMAGE_TEXT_PRESENTATION_ATTRIBUTES ) );
                $attributes['layout'] = $slug;
            } elseif ( 'cinderwell/cta' === $block_name ) {
                $attributes = array_intersect_key( $attributes, array_flip( self::CTA_PRESENTATION_ATTRIBUTES ) );
                $attributes['layout'] = $slug;
            } elseif ( 'cinderwell/body' === $block_name ) {
                $attributes = array_intersect_key( $attributes, array_flip( self::BODY_PRESENTATION_ATTRIBUTES ) );
                $attributes['layout'] = $slug;
            } elseif ( 'cinderwell/columns' === $block_name ) {
                $attributes = array_intersect_key( $attributes, array_flip( self::COLUMNS_PRESENTATION_ATTRIBUTES ) );
                $attributes['layout'] = $slug;
            } elseif ( 'cinderwell/section' === $block_name ) {
                $attributes = array_intersect_key( $attributes, array_flip( self::SECTION_PRESENTATION_ATTRIBUTES ) );
                $attributes['layout'] = $slug;
            } elseif ( 'cinderwell/icon-list' === $block_name ) {
                $attributes = array_intersect_key( $attributes, array_flip( self::ICON_LIST_PRESENTATION_ATTRIBUTES ) );
                $attributes['layout'] = $slug;
            }

            $normalized[ $slug ] = [
                'slug'                 => $slug,
                'label'                => sanitize_text_field( $definition['label'] ?? ucwords( str_replace( '-', ' ', $slug ) ) ),
                'description'          => sanitize_text_field( $definition['description'] ?? '' ),
                'preview'              => sanitize_key( $definition['preview'] ?? $slug ),
                'attributes'           => $attributes,
                'visible'              => ! isset( $definition['visible'] ) || (bool) $definition['visible'],
                'custom'               => ! empty( $definition['custom'] ),
                'controlled'           => $controlled,
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
     * Normalize controls intentionally owned by a presentation variation.
     *
     * A controlled entry is keyed by the block attribute or shared control
     * group and contains the author-facing reason the control is unavailable.
     * Enabled controls must never be listed here merely to document defaults.
     *
     * @param mixed $controlled Raw variation registration value.
     * @return array<string,string>
     */
    private static function normalize_controlled_controls( $controlled ) {
        if ( ! is_array( $controlled ) ) {
            return [];
        }

        $normalized = [];
        foreach ( $controlled as $key => $reason ) {
            if ( is_int( $key ) ) {
                $key    = $reason;
                $reason = '';
            }

            $control = preg_replace( '/[^a-zA-Z0-9_.-]/', '', (string) $key );
            if ( '' === $control || false === $reason ) {
                continue;
            }

            $normalized[ $control ] = sanitize_text_field( is_string( $reason ) ? $reason : '' );
        }

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
        return [
            'cinderwell/loop',
            'cinderwell/card-grid',
            'cinderwell/hero',
            'cinderwell/image-text',
            'cinderwell/cta',
            'cinderwell/body',
            'cinderwell/columns',
            'cinderwell/section',
            'cinderwell/icon-list',
        ];
    }

    /**
     * Built-in definitions. These are presentation recipes, not content/query
     * transformations.
     */
    private static function get_core_variations( $block_name ) {
        if ( 'cinderwell/icon-list' === $block_name ) {
            return [
                'standard' => [
                    'label'       => self::translate( 'Standard' ),
                    'description' => self::translate( 'A compact semantic list with shared icon controls.' ),
                    'preview'     => 'icon-list-standard',
                    'order'       => 10,
                    'attributes'  => [
                        'width' => 'standard',
                        'gap'   => 'standard',
                    ],
                ],
                'split-introduction' => [
                    'label'       => self::translate( 'Split Introduction' ),
                    'description' => self::translate( 'An editorial introduction beside a structured feature list.' ),
                    'preview'     => 'icon-list-split-introduction',
                    'order'       => 20,
                    'attributes'  => [
                        'width'         => 'wide',
                        'background'    => 'light',
                        'gap'           => 'relaxed',
                        'defaultIcon'   => 'check-circle',
                        'iconSize'      => 'sm',
                        'iconTreatment' => 'plain',
                        'align'         => 'full',
                    ],
                ],
            ];
        }

        if ( 'cinderwell/body' === $block_name ) {
            return [
                'standard' => [
                    'label'       => self::translate( 'Standard' ),
                    'description' => self::translate( 'The standard editorial body composition.' ),
                    'preview'     => 'body-standard',
                    'order'       => 10,
                    'attributes'  => [
                        'width' => 'standard',
                    ],
                ],
            ];
        }

        if ( 'cinderwell/columns' === $block_name ) {
            return [
                'standard' => [
                    'label'       => self::translate( 'Standard' ),
                    'description' => self::translate( 'Responsive columns with the standard Cinderwell rhythm.' ),
                    'preview'     => 'columns-standard',
                    'order'       => 10,
                    'attributes'  => [
                        'gap'               => 'md',
                        'stackAt'           => 'mobile',
                        'verticalAlignment' => 'stretch',
                        'width'             => 'wide',
                    ],
                ],
            ];
        }

        if ( 'cinderwell/section' === $block_name ) {
            return [
                'standard' => [
                    'label'       => self::translate( 'Standard' ),
                    'description' => self::translate( 'A flexible section using the standard content axis.' ),
                    'preview'     => 'section-standard',
                    'order'       => 10,
                    'attributes'  => [
                        'width' => 'standard',
                    ],
                ],
            ];
        }

        if ( 'cinderwell/image-text' === $block_name ) {
            return [
                'standard' => [
                    'label'       => self::translate( 'Standard' ),
                    'description' => self::translate( 'A balanced image and content composition.' ),
                    'preview'     => 'image-text-standard',
                    'order'       => 10,
                    'attributes'  => [
                        'alignment'   => 'left',
                        'width'       => 'standard',
                        'imageAspect' => 'auto',
                        'imageFit'    => 'auto',
                    ],
                ],
            ];
        }

        if ( 'cinderwell/cta' === $block_name ) {
            return [
                'standard' => [
                    'label'       => self::translate( 'Standard' ),
                    'description' => self::translate( 'A centered call to action with focused content.' ),
                    'preview'     => 'cta-standard',
                    'order'       => 10,
                    'attributes'  => [
                        'alignment' => 'center',
                        'width'     => 'standard',
                    ],
                ],
            ];
        }

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
                'image-box' => [
                    'label'       => self::translate( 'Image Boxes' ),
                    'description' => self::translate( 'Image-led cards with concise content layered over a curated overlay.' ),
                    'preview'     => 'image-box',
                    'order'       => 55,
                    'attributes'  => [
                        'columns'                  => '3',
                        'columnsTablet'            => '2',
                        'columnsMobile'            => '1',
                        'imageBoxAspect'            => 'landscape',
                        'imageBoxOverlay'           => 'medium',
                        'imageBoxContentPosition'   => 'bottom',
                        'imageBoxContentVisibility' => 'always',
                        'imageBoxLinkStyle'         => 'card',
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
            'magazine' => [
                'label'       => self::translate( 'Magazine' ),
                'description' => self::translate( 'An oversized lead story with a dense supporting grid.' ),
                'preview'     => 'magazine',
                'order'       => 50,
                'attributes'  => [
                    'columns'       => '3',
                    'columnsTablet' => '2',
                    'columnsMobile' => '1',
                    'imageAspect'   => 'landscape',
                ],
            ],
            'overlay' => [
                'label'       => self::translate( 'Image Overlay' ),
                'description' => self::translate( 'Immersive image cards with content anchored over a deep gradient.' ),
                'preview'     => 'loop-overlay',
                'order'       => 60,
                'attributes'  => [
                    'columns'       => '3',
                    'columnsTablet' => '2',
                    'columnsMobile' => '1',
                    'imageAspect'   => 'portrait',
                ],
            ],
            'alternating' => [
                'label'       => self::translate( 'Alternating' ),
                'description' => self::translate( 'Full-width editorial rows that alternate image position.' ),
                'preview'     => 'alternating',
                'order'       => 70,
                'attributes'  => [
                    'columns'       => '1',
                    'columnsTablet' => '1',
                    'columnsMobile' => '1',
                    'imageAspect'   => 'landscape',
                ],
            ],
            'index' => [
                'label'       => self::translate( 'Index' ),
                'description' => self::translate( 'A numbered, typography-led article index without card imagery.' ),
                'preview'     => 'loop-index',
                'order'       => 80,
                'attributes'  => [
                    'columns'       => '1',
                    'columnsTablet' => '1',
                    'columnsMobile' => '1',
                    'imageAspect'   => 'landscape',
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
