<?php
/**
 * Design tokens — manifest, CSS output, and filter hooks.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Design_Tokens {

    public function __construct() {
        add_action( 'wp_head', [ $this, 'output_tokens_css' ] );
        add_action( 'admin_head', [ $this, 'output_tokens_css' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        add_filter( 'wp_theme_json_data_theme', [ $this, 'sync_theme_json' ], 20 );
    }

    /**
     * Get the built-in token manifest.
     */
    public static function get_manifest() {
        $manifest = [
            'cw_color_white'    => [
                'label'       => __( 'White', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#ffffff',
                'description' => __( 'White color token', 'cinderwell' ),
            ],
            'cw_color_light'    => [
                'label'       => __( 'Light', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#f8f5ef',
                'description' => __( 'Light background color', 'cinderwell' ),
            ],
            'cw_color_dark'     => [
                'label'       => __( 'Dark', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#1a1a1a',
                'description' => __( 'Dark background color', 'cinderwell' ),
            ],
            'cw_color_brand'    => [
                'label'       => __( 'Brand', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#b84c00',
                'description' => __( 'Primary brand color', 'cinderwell' ),
            ],
            'cw_color_brand_light' => [
                'label'       => __( 'Brand Light', 'cinderwell' ),
                'type'        => 'derived-color',
                'default'     => 'color-mix(in srgb, var(--cw-color-brand) 20%, white)',
                'description' => __( 'Automatically derived from the Brand color for subtle backgrounds and accents', 'cinderwell' ),
                'editable'    => false,
            ],
            'cw_color_brand_dark' => [
                'label'       => __( 'Brand Dark', 'cinderwell' ),
                'type'        => 'derived-color',
                'default'     => 'color-mix(in srgb, var(--cw-color-brand) 75%, black)',
                'description' => __( 'Automatically derived from the Brand color for stronger emphasis and hover states', 'cinderwell' ),
                'editable'    => false,
            ],
            'cw_color_text'     => [
                'label'       => __( 'Text', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#1a1a1a',
                'description' => __( 'Default text color', 'cinderwell' ),
            ],
            'cw_color_bg'       => [
                'label'       => __( 'Background', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#ffffff',
                'description' => __( 'Default background color', 'cinderwell' ),
            ],
            'cw_color_brand_contrast' => [
                'label'       => __( 'Brand Contrast', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#ffffff',
                'description' => __( 'Text and icons displayed on the brand color', 'cinderwell' ),
            ],
            'cw_color_surface' => [
                'label'       => __( 'Surface', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#ffffff',
                'description' => __( 'Cards, controls, and raised content surfaces', 'cinderwell' ),
            ],
            'cw_color_muted' => [
                'label'       => __( 'Muted', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#666666',
                'description' => __( 'Secondary text, metadata, and subdued interface content', 'cinderwell' ),
            ],
            'cw_color_info' => [
                'label'       => __( 'Info', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#005ea8',
                'description' => __( 'Informational notices, messages, and interface states', 'cinderwell' ),
            ],
            'cw_color_success' => [
                'label'       => __( 'Success', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#287d3c',
                'description' => __( 'Successful actions, confirmations, and positive states', 'cinderwell' ),
            ],
            'cw_color_danger' => [
                'label'       => __( 'Danger', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#b42318',
                'description' => __( 'Errors, destructive actions, and urgent states', 'cinderwell' ),
            ],
            'cw_color_accent_1' => [
                'label'       => __( 'Accent 1', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#6f42c1',
                'description' => __( 'Optional supporting accent color', 'cinderwell' ),
            ],
            'cw_color_accent_2' => [
                'label'       => __( 'Accent 2', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#007c83',
                'description' => __( 'Optional supporting accent color', 'cinderwell' ),
            ],
            'cw_color_accent_3' => [
                'label'       => __( 'Accent 3', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#9a6700',
                'description' => __( 'Optional supporting accent color', 'cinderwell' ),
            ],
            'cw_color_border' => [
                'label'       => __( 'Border', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#d9d6d0',
                'description' => __( 'Default border and divider color', 'cinderwell' ),
            ],
            'cw_color_link' => [
                'label'       => __( 'Link', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#b84c00',
                'description' => __( 'Default inline link color', 'cinderwell' ),
            ],
            'cw_color_link_hover' => [
                'label'       => __( 'Link Hover', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#1a1a1a',
                'description' => __( 'Inline link hover and focus color', 'cinderwell' ),
            ],
            'cw_color_focus' => [
                'label'       => __( 'Focus Ring', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#b84c00',
                'description' => __( 'Keyboard focus indicator color', 'cinderwell' ),
            ],
            'cw_button_primary_background' => [
                'label'       => __( 'Primary Background', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'var(--cw-color-brand)',
                'description' => __( 'Background and border for primary buttons', 'cinderwell' ),
            ],
            'cw_button_primary_foreground' => [
                'label'       => __( 'Primary Text', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'var(--cw-color-brand-contrast)',
                'description' => __( 'Text and icon color for primary buttons', 'cinderwell' ),
            ],
            'cw_button_primary_hover_background' => [
                'label'       => __( 'Primary Hover Background', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'var(--cw-color-dark)',
                'description' => __( 'Background and border for primary buttons on hover', 'cinderwell' ),
            ],
            'cw_button_primary_hover_foreground' => [
                'label'       => __( 'Primary Hover Text', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'var(--cw-color-white)',
                'description' => __( 'Text and icon color for primary buttons on hover', 'cinderwell' ),
            ],
            'cw_button_secondary_foreground' => [
                'label'       => __( 'Secondary Text and Border', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'var(--cw-color-brand)',
                'description' => __( 'Text, icon, and border color for secondary buttons', 'cinderwell' ),
            ],
            'cw_button_secondary_hover_background' => [
                'label'       => __( 'Secondary Hover Background', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'var(--cw-color-brand)',
                'description' => __( 'Background and border for secondary buttons on hover', 'cinderwell' ),
            ],
            'cw_button_secondary_hover_foreground' => [
                'label'       => __( 'Secondary Hover Text', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'var(--cw-color-brand-contrast)',
                'description' => __( 'Text and icon color for secondary buttons on hover', 'cinderwell' ),
            ],
            'cw_button_ghost_foreground' => [
                'label'       => __( 'Ghost Text', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'var(--cw-color-text)',
                'description' => __( 'Text and icon color for ghost buttons', 'cinderwell' ),
            ],
            'cw_button_ghost_background' => [
                'label'       => __( 'Ghost Background', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'color-mix(in srgb, var(--cw-button-ghost-foreground) 7%, transparent)',
                'description' => __( 'Subtle background for ghost buttons', 'cinderwell' ),
            ],
            'cw_button_ghost_hover_background' => [
                'label'       => __( 'Ghost Hover Background', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'color-mix(in srgb, var(--cw-button-ghost-foreground) 13%, transparent)',
                'description' => __( 'Background for ghost buttons on hover', 'cinderwell' ),
            ],
            'cw_button_link_foreground' => [
                'label'       => __( 'Link Button Text', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'var(--cw-color-link)',
                'description' => __( 'Text and icon color for link-style buttons', 'cinderwell' ),
            ],
            'cw_button_link_hover_foreground' => [
                'label'       => __( 'Link Button Hover Text', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'var(--cw-color-link-hover)',
                'description' => __( 'Text and icon color for link-style buttons on hover', 'cinderwell' ),
            ],
            'cw_font_heading'   => [
                'label'       => __( 'Heading Font', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'inherit',
                'description' => __( 'Font family for headings', 'cinderwell' ),
            ],
            'cw_font_body'      => [
                'label'       => __( 'Body Font', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'inherit',
                'description' => __( 'Font family for body text', 'cinderwell' ),
            ],
            'cw_line_height_tight' => [
                'label'       => __( 'Tight Line Height', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '1.2',
                'description' => __( 'Compact line height for headings', 'cinderwell' ),
            ],
            'cw_line_height_body' => [
                'label'       => __( 'Body Line Height', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '1.6',
                'description' => __( 'Default line height for body copy', 'cinderwell' ),
            ],
            'cw_line_height_relaxed' => [
                'label'       => __( 'Relaxed Line Height', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '1.7',
                'description' => __( 'Open line height for long-form content', 'cinderwell' ),
            ],
            'cw_font_size_xs'   => [
                'label'       => __( 'Extra Small Type', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(0.75rem, 0.72rem + 0.12vw, 0.8125rem)',
                'description' => __( 'Fluid extra-small type size', 'cinderwell' ),
            ],
            'cw_font_size_sm'   => [
                'label'       => __( 'Small Type', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(0.875rem, 0.82rem + 0.22vw, 1rem)',
                'description' => __( 'Fluid small type size', 'cinderwell' ),
            ],
            'cw_font_size_md'   => [
                'label'       => __( 'Medium Type', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(1rem, 0.95rem + 0.25vw, 1.125rem)',
                'description' => __( 'Fluid body type size', 'cinderwell' ),
            ],
            'cw_font_size_lg'   => [
                'label'       => __( 'Large Type', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(1.125rem, 1rem + 0.55vw, 1.375rem)',
                'description' => __( 'Fluid lead type size', 'cinderwell' ),
            ],
            'cw_font_size_xl'   => [
                'label'       => __( 'Extra Large Type', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(1.375rem, 1.2rem + 0.7vw, 1.75rem)',
                'description' => __( 'Fluid small-heading type size', 'cinderwell' ),
            ],
            'cw_font_size_2xl'  => [
                'label'       => __( '2X Large Type', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(1.75rem, 1.45rem + 1.35vw, 2.5rem)',
                'description' => __( 'Fluid heading type size', 'cinderwell' ),
            ],
            'cw_font_size_3xl'  => [
                'label'       => __( '3X Large Type', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(2.25rem, 1.75rem + 2.1vw, 3.25rem)',
                'description' => __( 'Fluid display type size', 'cinderwell' ),
            ],
            'cw_font_size_4xl'  => [
                'label'       => __( '4X Large Type', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(2.75rem, 2rem + 3vw, 4rem)',
                'description' => __( 'Fluid large-display type size', 'cinderwell' ),
            ],
            'cw_spacing_none'    => [
                'label'       => __( 'No Spacing', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '0rem',
                'description' => __( 'Removes spacing', 'cinderwell' ),
            ],
            'cw_spacing_xs'      => [
                'label'       => __( 'Extra Small Spacing', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(0.5rem, 0.4rem + 0.4vw, 0.75rem)',
                'description' => __( 'Fluid extra-small spacing value', 'cinderwell' ),
            ],
            'cw_spacing_sm'      => [
                'label'       => __( 'Small Spacing', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(1.5rem, 1.25rem + 1vw, 2rem)',
                'description' => __( 'Fluid small spacing value', 'cinderwell' ),
            ],
            'cw_spacing_md'      => [
                'label'       => __( 'Medium Spacing', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(3rem, 2.5rem + 2vw, 4rem)',
                'description' => __( 'Fluid medium spacing value', 'cinderwell' ),
            ],
            'cw_spacing_lg'      => [
                'label'       => __( 'Large Spacing', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(4rem, 3rem + 4vw, 6rem)',
                'description' => __( 'Fluid large spacing value', 'cinderwell' ),
            ],
            'cw_spacing_xl'      => [
                'label'       => __( 'Extra Large Spacing', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(6rem, 5rem + 4vw, 8rem)',
                'description' => __( 'Fluid extra-large spacing value', 'cinderwell' ),
            ],
            'cw_gap_sm' => [
                'label'       => __( 'Small Gap', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '1rem',
                'description' => __( 'Compact gap between related elements', 'cinderwell' ),
            ],
            'cw_gap_md' => [
                'label'       => __( 'Medium Gap', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '1.5rem',
                'description' => __( 'Default grid and component gap', 'cinderwell' ),
            ],
            'cw_gap_lg' => [
                'label'       => __( 'Large Gap', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '2rem',
                'description' => __( 'Spacious gap between layout regions', 'cinderwell' ),
            ],
            'cw_layout_gutter' => [
                'label'       => __( 'Page Gutter', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '1.5rem',
                'description' => __( 'Horizontal breathing room at content edges', 'cinderwell' ),
            ],
            'cw_page_padding_inline' => [
                'label'       => __( 'Page Inline Padding', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'var(--cw-layout-gutter)',
                'description' => __( 'Horizontal padding for standard page and application shells', 'cinderwell' ),
            ],
            'cw_page_padding_block' => [
                'label'       => __( 'Page Block Padding', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'var(--cw-spacing-md)',
                'description' => __( 'Vertical padding between standard page content and the site chrome', 'cinderwell' ),
            ],
            'cw_width_narrow'   => [
                'label'       => __( 'Narrow Width', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '600px',
                'description' => __( 'Narrow content width', 'cinderwell' ),
            ],
            'cw_width_standard' => [
                'label'       => __( 'Standard Width', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '900px',
                'description' => __( 'Standard content width', 'cinderwell' ),
            ],
            'cw_width_wide'     => [
                'label'       => __( 'Wide Width', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '1200px',
                'description' => __( 'Wide content width', 'cinderwell' ),
            ],
            'cw_width_full'     => [
                'label'       => __( 'Full Width', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '100%',
                'description' => __( 'Full content width', 'cinderwell' ),
            ],
            'cw_image_radius'   => [
                'label'       => __( 'Block Image Corners', 'cinderwell' ),
                'type'        => 'choice',
                'default'     => '0px',
                'description' => __( 'Use square corners or a small radius on content images.', 'cinderwell' ),
                'options'     => [
                    [ 'value' => '0px', 'label' => __( 'Square', 'cinderwell' ) ],
                    [ 'value' => '4px', 'label' => __( 'Small radius', 'cinderwell' ) ],
                ],
            ],
            'cw_radius_sm' => [
                'label'       => __( 'Small Radius', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '4px',
                'description' => __( 'Controls, buttons, and compact surfaces', 'cinderwell' ),
            ],
            'cw_radius_md' => [
                'label'       => __( 'Medium Radius', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '8px',
                'description' => __( 'Cards and larger content surfaces', 'cinderwell' ),
            ],
            'cw_radius_pill' => [
                'label'       => __( 'Pill Radius', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '999px',
                'description' => __( 'Pills and circular icon treatments', 'cinderwell' ),
            ],
            'cw_shadow_sm' => [
                'label'       => __( 'Small Shadow', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '0 2px 8px rgb(0 0 0 / 8%)',
                'description' => __( 'Subtle elevation for controls and overlays', 'cinderwell' ),
            ],
            'cw_shadow_md' => [
                'label'       => __( 'Medium Shadow', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '0 8px 24px rgb(0 0 0 / 12%)',
                'description' => __( 'Raised card and interactive surface elevation', 'cinderwell' ),
            ],
            'cw_duration_fast' => [
                'label'       => __( 'Fast Motion', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '100ms',
                'description' => __( 'Immediate interaction feedback duration', 'cinderwell' ),
            ],
            'cw_duration_normal' => [
                'label'       => __( 'Normal Motion', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '200ms',
                'description' => __( 'Default interface transition duration', 'cinderwell' ),
            ],
            'cw_duration_slow' => [
                'label'       => __( 'Slow Motion', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '300ms',
                'description' => __( 'Image and larger movement transition duration', 'cinderwell' ),
            ],
            'cw_ease_standard' => [
                'label'       => __( 'Standard Easing', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'ease',
                'description' => __( 'Default motion timing function', 'cinderwell' ),
            ],
            'cw_motion_distance' => [
                'label'       => __( 'Motion Distance', 'cinderwell' ),
                'type'        => 'text',
                'default'     => '24px',
                'description' => __( 'Entrance animation travel distance', 'cinderwell' ),
            ],
        ];

        $palette_tokens = [
            'cw_color_white'       => [ 'slug' => 'white', 'contexts' => [ 'background', 'text' ], 'automatic_foreground' => true ],
            'cw_color_light'       => [ 'slug' => 'light', 'contexts' => [ 'background', 'text' ], 'automatic_foreground' => true ],
            'cw_color_dark'        => [ 'slug' => 'dark', 'contexts' => [ 'background', 'text' ], 'automatic_foreground' => true ],
            'cw_color_brand'       => [ 'slug' => 'brand', 'contexts' => [ 'background', 'text' ] ],
            'cw_color_brand_light' => [ 'slug' => 'brand-light', 'contexts' => [ 'background', 'text' ] ],
            'cw_color_brand_dark'  => [ 'slug' => 'brand-dark', 'contexts' => [ 'background', 'text' ] ],
            'cw_color_text'        => [ 'slug' => 'text', 'contexts' => [ 'text' ], 'automatic_foreground' => true ],
            'cw_color_muted'       => [ 'slug' => 'muted', 'contexts' => [ 'background', 'text' ] ],
            'cw_color_info'        => [ 'slug' => 'info', 'contexts' => [ 'background', 'text' ] ],
            'cw_color_success'     => [ 'slug' => 'success', 'contexts' => [ 'background', 'text' ] ],
            'cw_color_danger'      => [ 'slug' => 'danger', 'contexts' => [ 'background', 'text' ] ],
            'cw_color_accent_1'    => [ 'slug' => 'accent-1', 'contexts' => [ 'background', 'text' ] ],
            'cw_color_accent_2'    => [ 'slug' => 'accent-2', 'contexts' => [ 'background', 'text' ] ],
            'cw_color_accent_3'    => [ 'slug' => 'accent-3', 'contexts' => [ 'background', 'text' ] ],
        ];

        foreach ( $palette_tokens as $key => $palette ) {
            $manifest[ $key ]['palette'] = $palette;
        }

        if ( ! apply_filters( 'cinderwell_enable_accent_colors', true ) ) {
            unset(
                $manifest['cw_color_accent_1'],
                $manifest['cw_color_accent_2'],
                $manifest['cw_color_accent_3']
            );
        }

        /**
         * Filter the token manifest.
         *
         * @param array $manifest Token manifest.
         */
        return apply_filters( 'cinderwell_token_manifest', $manifest );
    }

    /**
     * Get default token values from manifest.
     */
    public static function get_defaults() {
        $manifest  = self::get_manifest();
        $defaults  = [];
        foreach ( $manifest as $key => $token ) {
            $defaults[ $key ] = $token['default'];
        }
        return $defaults;
    }

    /**
     * Return the resolved manifest in the shape consumed by editor tools.
     */
    public static function get_editor_tokens() {
        $manifest = self::get_manifest();
        $resolved = self::get_resolved_tokens();
        $tokens   = [];

        foreach ( $manifest as $key => $token ) {
            $tokens[] = [
                'key'         => $key,
                'cssVariable' => '--' . str_replace( '_', '-', $key ),
                'label'       => $token['label'],
                'type'        => $token['type'],
                'description' => $token['description'],
                'default'     => $token['default'],
                'value'       => $resolved[ $key ] ?? $token['default'],
                'category'    => self::get_token_category( $key, $token ),
                'options'     => $token['options'] ?? [],
                'editable'    => $token['editable'] ?? true,
                'palette'     => $token['palette'] ?? null,
            ];
        }

        return $tokens;
    }

    /**
     * Return registered editor colors with resolved values and safe slugs.
     *
     * Client tokens opt in by adding a `palette` definition to their token
     * manifest entry. This keeps utility colors such as borders and focus
     * rings out of content controls unless a theme intentionally exposes them.
     */
    public static function get_color_registry( $locale = null ) {
        $manifest = self::get_manifest();
        $resolved = self::get_resolved_tokens( $locale );
        $colors   = [];

        foreach ( $manifest as $key => $token ) {
            $palette = $token['palette'] ?? null;
            if ( ! preg_match( '/^cw_[a-z0-9_]+$/', $key ) || ! is_array( $palette ) || ! in_array( $token['type'] ?? '', [ 'color', 'derived-color' ], true ) ) {
                continue;
            }

            $slug = sanitize_title( $palette['slug'] ?? preg_replace( '/^cw_color_/', '', $key ) );
            if ( '' === $slug || isset( $colors[ $slug ] ) ) {
                continue;
            }

            $contexts = array_values( array_intersect( (array) ( $palette['contexts'] ?? [] ), [ 'background', 'text' ] ) );
            if ( empty( $contexts ) ) {
                continue;
            }

            $value = $resolved[ $key ] ?? ( $token['default'] ?? '' );
            $colors[ $slug ] = [
                'key'                 => $key,
                'slug'                => $slug,
                'label'               => $token['label'] ?? $slug,
                'cssVariable'         => '--' . str_replace( '_', '-', $key ),
                'value'               => $value,
                'resolvedColor'       => self::resolve_color_value( $value, $resolved ),
                'contexts'            => $contexts,
                'automaticForeground' => ! empty( $palette['automatic_foreground'] ),
            ];
        }

        return array_values( $colors );
    }

    /**
     * Build the core WordPress palette from Cinderwell's registered colors.
     *
     * Palette values intentionally reference the public CSS custom properties
     * instead of copying resolved hex values. Saved token changes can therefore
     * update core and Cinderwell controls without regenerating theme.json data.
     *
     * @return array<int,array{name:string,slug:string,color:string}>
     */
    public static function get_theme_json_palette() {
        $palette = [];

        foreach ( self::get_color_registry() as $color ) {
            $palette[] = [
                'name'  => $color['label'],
                'slug'  => $color['slug'],
                'color' => 'var(' . $color['cssVariable'] . ')',
            ];
        }

        return $palette;
    }

    /**
     * Keep WordPress global styles on the same token source as Cinderwell.
     *
     * The starter theme opts into this bridge with theme support. Child themes
     * then customize values, labels, and palette exposure exclusively through
     * `cinderwell_token_manifest` rather than maintaining duplicate JSON.
     *
     * @param \WP_Theme_JSON_Data $theme_json Theme-origin global styles data.
     * @return \WP_Theme_JSON_Data
     */
    public function sync_theme_json( $theme_json ) {
        $enabled = current_theme_supports( 'cinderwell-design-tokens' );

        /**
         * Filter whether Cinderwell should synchronize its tokens into theme.json.
         *
         * @param bool                $enabled    Whether synchronization is enabled.
         * @param \WP_Theme_JSON_Data $theme_json Theme-origin global styles data.
         */
        if ( ! apply_filters( 'cinderwell_sync_theme_json_tokens', $enabled, $theme_json ) ) {
            return $theme_json;
        }

        return $theme_json->update_with( [
            'version'  => 3,
            'settings' => [
                'layout' => [
                    'contentSize' => 'var(--cw-width-standard)',
                    'wideSize'    => 'var(--cw-width-wide)',
                ],
                'color'  => [
                    'palette' => self::get_theme_json_palette(),
                ],
            ],
            'styles'   => [
                'color'    => [
                    'background' => 'var(--cw-color-bg)',
                    'text'       => 'var(--cw-color-text)',
                ],
                'elements' => [
                    'link' => [
                        'color' => [
                            'text' => 'var(--cw-color-link)',
                        ],
                    ],
                ],
            ],
        ] );
    }

    /**
     * Return valid palette slugs for a block control context.
     */
    public static function get_color_slugs( $context ) {
        $slugs = [];
        foreach ( self::get_color_registry() as $color ) {
            if ( in_array( $context, $color['contexts'], true ) ) {
                $slugs[] = $color['slug'];
            }
        }
        return $slugs;
    }

    /**
     * Resolve supported token color values to six-digit hex for contrast math.
     */
    private static function resolve_color_value( $value, $tokens ) {
        $value = strtolower( trim( (string) $value ) );
        if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $value, $match ) ) {
            $hex = $match[1];
            if ( 3 === strlen( $hex ) ) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            return '#' . $hex;
        }

        if ( preg_match( '/^var\(\s*--([a-z0-9-]+)\s*\)$/', $value, $match ) ) {
            $source_key = str_replace( '-', '_', $match[1] );
            return isset( $tokens[ $source_key ] ) ? self::resolve_color_value( $tokens[ $source_key ], $tokens ) : '';
        }

        if ( preg_match( '/^color-mix\(\s*in\s+srgb\s*,\s*var\(\s*--([a-z0-9-]+)\s*\)\s+([0-9]+(?:\.[0-9]+)?)%\s*,\s*(white|black)(?:\s+[0-9]+(?:\.[0-9]+)?%)?\s*\)$/', $value, $match ) ) {
            $source_key = str_replace( '-', '_', $match[1] );
            $source     = isset( $tokens[ $source_key ] ) ? self::resolve_color_value( $tokens[ $source_key ], $tokens ) : '';
            if ( '' === $source ) {
                return '';
            }

            $weight = min( 1, max( 0, (float) $match[2] / 100 ) );
            $mix    = 'white' === $match[3] ? [ 255, 255, 255 ] : [ 0, 0, 0 ];
            $rgb    = [
                hexdec( substr( $source, 1, 2 ) ),
                hexdec( substr( $source, 3, 2 ) ),
                hexdec( substr( $source, 5, 2 ) ),
            ];
            $result = array_map(
                static function ( $channel, $index ) use ( $weight, $mix ) {
                    return (int) round( ( $channel * $weight ) + ( $mix[ $index ] * ( 1 - $weight ) ) );
                },
                $rgb,
                array_keys( $rgb )
            );

            return sprintf( '#%02x%02x%02x', $result[0], $result[1], $result[2] );
        }

        return '';
    }

    /**
     * Calculate WCAG relative luminance for a resolved hex color.
     */
    private static function get_color_luminance( $hex ) {
        $channels = [
            hexdec( substr( $hex, 1, 2 ) ) / 255,
            hexdec( substr( $hex, 3, 2 ) ) / 255,
            hexdec( substr( $hex, 5, 2 ) ) / 255,
        ];
        $channels = array_map(
            static function ( $channel ) {
                return $channel <= 0.04045 ? $channel / 12.92 : pow( ( $channel + 0.055 ) / 1.055, 2.4 );
            },
            $channels
        );
        return ( 0.2126 * $channels[0] ) + ( 0.7152 * $channels[1] ) + ( 0.0722 * $channels[2] );
    }

    /**
     * Calculate the WCAG contrast ratio between two resolved hex colors.
     */
    private static function get_contrast_ratio( $first, $second ) {
        $first_luminance  = self::get_color_luminance( $first );
        $second_luminance = self::get_color_luminance( $second );
        return ( max( $first_luminance, $second_luminance ) + 0.05 ) / ( min( $first_luminance, $second_luminance ) + 0.05 );
    }

    /**
     * Choose the strongest registered automatic foreground for a background.
     */
    private static function get_automatic_foreground( $background, $registry ) {
        $best       = null;
        $best_ratio = 0;

        foreach ( $registry as $color ) {
            if ( empty( $color['automaticForeground'] ) || ! in_array( 'text', $color['contexts'], true ) || empty( $color['resolvedColor'] ) ) {
                continue;
            }

            $ratio = self::get_contrast_ratio( $background['resolvedColor'], $color['resolvedColor'] );
            if ( $ratio > $best_ratio ) {
                $best       = $color;
                $best_ratio = $ratio;
            }
        }

        return $best;
    }

    /**
     * Generate palette classes and enforce AA fallbacks for unsafe pairings.
     */
    public static function get_palette_css() {
        $registry             = self::get_color_registry();
        $resolved             = self::get_resolved_tokens();
        $secondary_foreground = self::resolve_color_value( $resolved['cw_button_secondary_foreground'] ?? '', $resolved );
        $css                  = '';

        foreach ( $registry as $text_color ) {
            if ( in_array( 'text', $text_color['contexts'], true ) && ! empty( $text_color['resolvedColor'] ) ) {
                $css .= sprintf(
                    '.cinderwell-text-color-%1$s{color:var(%2$s,%3$s)!important;}',
                    $text_color['slug'],
                    $text_color['cssVariable'],
                    $text_color['resolvedColor']
                );
            }
        }

        foreach ( $registry as $background ) {
            if ( ! in_array( 'background', $background['contexts'], true ) || empty( $background['resolvedColor'] ) ) {
                continue;
            }

            $foreground = self::get_automatic_foreground( $background, $registry );
            if ( ! $foreground ) {
                continue;
            }

            $selector = sprintf( '[class*="cinderwell-"][class*="--bg-%s"]', $background['slug'] );
            $css     .= sprintf(
                '%1$s{--cw-surface-background:var(%2$s,%4$s);--cw-surface-foreground:var(%3$s,%5$s);--cw-tabs-accent:var(%3$s,%5$s);--cw-tabs-accent-contrast:var(%2$s,%4$s);background-color:var(%2$s,%4$s);color:var(%3$s,%5$s);}',
                $selector,
                $background['cssVariable'],
                $foreground['cssVariable'],
                $background['resolvedColor'],
                $foreground['resolvedColor']
            );

            // Secondary buttons are transparent, so their text and border must
            // contrast with the authored surface behind them. Preserve the
            // configured button token when it passes AA; otherwise inherit the
            // same automatically selected foreground as the surface content.
            if ( $secondary_foreground && self::get_contrast_ratio( $background['resolvedColor'], $secondary_foreground ) < 4.5 ) {
                $css .= sprintf(
                    '%1$s{--cw-button-secondary-effective-foreground:var(%2$s,%3$s);}',
                    $selector,
                    $foreground['cssVariable'],
                    $foreground['resolvedColor']
                );
            }

            // A Primary button using the Brand fill disappears on the Brand
            // surface. Invert only that exact surface through the computed AA
            // foreground/background pair; derived brand surfaces keep the
            // normal Primary recipe.
            if ( 'brand' === $background['slug'] ) {
                $css .= sprintf(
                    '%1$s:not([class*="--bg-brand-"]){--cw-button-primary-effective-background:var(%2$s,%3$s);--cw-button-primary-effective-foreground:var(%4$s,%5$s);}',
                    $selector,
                    $foreground['cssVariable'],
                    $foreground['resolvedColor'],
                    $background['cssVariable'],
                    $background['resolvedColor']
                );
            }

            $unsafe_text_slugs = [];
            foreach ( $registry as $text_color ) {
                if ( ! in_array( 'text', $text_color['contexts'], true ) || empty( $text_color['resolvedColor'] ) ) {
                    continue;
                }
                if ( self::get_contrast_ratio( $background['resolvedColor'], $text_color['resolvedColor'] ) >= 4.5 ) {
                    continue;
                }

                $unsafe_text_slugs[] = '.cinderwell-text-color-' . $text_color['slug'];
            }
            if ( $unsafe_text_slugs ) {
                $css .= sprintf(
                    '%1$s:is(%2$s),%1$s :is(%2$s){color:var(%3$s,%4$s)!important;}',
                    $selector,
                    implode( ',', $unsafe_text_slugs ),
                    $foreground['cssVariable'],
                    $foreground['resolvedColor']
                );
            }
        }

        return $css;
    }

    /**
     * Assign manifest tokens to editor-facing categories.
     */
    private static function get_token_category( $key, $token ) {
        if ( in_array( $token['type'], [ 'color', 'derived-color' ], true ) ) {
            return 'colors';
        }

        if ( in_array( $key, [ 'cw_font_heading', 'cw_font_body' ], true ) ) {
            return 'fonts';
        }

        if ( 0 === strpos( $key, 'cw_font_size_' ) ) {
            return 'type-scale';
        }

        if ( 0 === strpos( $key, 'cw_line_height_' ) ) {
            return 'type-scale';
        }

        if ( 0 === strpos( $key, 'cw_button_' ) ) {
            return 'buttons';
        }

        if ( 0 === strpos( $key, 'cw_spacing_' ) ) {
            return 'spacing';
        }

        if ( 0 === strpos( $key, 'cw_width_' ) ) {
            return 'widths';
        }

        if ( 0 === strpos( $key, 'cw_gap_' ) || 0 === strpos( $key, 'cw_layout_' ) || 0 === strpos( $key, 'cw_page_' ) ) {
            return 'layout';
        }

        if ( 0 === strpos( $key, 'cw_radius_' ) ) {
            return 'shape';
        }

        if ( 0 === strpos( $key, 'cw_shadow_' ) ) {
            return 'elevation';
        }

        if ( 0 === strpos( $key, 'cw_duration_' ) || 0 === strpos( $key, 'cw_ease_' ) || 0 === strpos( $key, 'cw_motion_' ) ) {
            return 'motion';
        }

        if ( 0 === strpos( $key, 'cw_image_' ) ) {
            return 'images';
        }

        return 'other';
    }

    /**
     * Sanitize one token override according to its manifest definition.
     */
    public static function sanitize_token_value( $key, $value, $token ) {
        $value = sanitize_text_field( (string) $value );

        if ( '' === $value ) {
            return '';
        }

        if ( 'color' === $token['type'] ) {
            return sanitize_hex_color( $value ) ?: '';
        }

        if ( 'choice' === $token['type'] ) {
            $allowed = wp_list_pluck( $token['options'] ?? [], 'value' );
            return in_array( $value, $allowed, true ) ? $value : '';
        }

        // Token values are inserted into CSS custom-property declarations.
        if ( preg_match( '/[;{}<>]/', $value ) ) {
            return '';
        }

        // Fluid size tokens intentionally accept only clamp-based values.
        if ( 0 === strpos( $key, 'cw_font_size_' ) && ! preg_match( '/^clamp\([^{};]+\)$/', $value ) ) {
            return '';
        }

        if ( 0 === strpos( $key, 'cw_line_height_' ) && ! preg_match( '/^(?:\d+(?:\.\d+)?|\.\d+)(?:px|rem|em|%)?$/', $value ) ) {
            return '';
        }

        if ( 0 === strpos( $key, 'cw_duration_' ) && ! preg_match( '/^(?:\d+(?:\.\d+)?|\.\d+)(?:ms|s)$/', $value ) ) {
            return '';
        }

        if ( 'cw_ease_standard' === $key && ! preg_match( '/^(?:linear|ease|ease-in|ease-out|ease-in-out|cubic-bezier\(\s*-?\d*\.?\d+\s*,\s*-?\d*\.?\d+\s*,\s*-?\d*\.?\d+\s*,\s*-?\d*\.?\d+\s*\))$/', $value ) ) {
            return '';
        }

        return $value;
    }

    /**
     * Register the capability-protected Gutenberg token update endpoint.
     */
    public function register_rest_routes() {
        register_rest_route(
            'cinderwell/v1',
            '/tokens',
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'update_tokens' ],
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
                'args'                => [
                    'tokens' => [
                        'required'          => true,
                        'type'              => 'object',
                        'validate_callback' => [ $this, 'validate_tokens_payload' ],
                    ],
                ],
            ]
        );
    }

    /**
     * Validate that the payload contains only known scalar token values.
     */
    public function validate_tokens_payload( $value ) {
        if ( ! is_array( $value ) ) {
            return new \WP_Error( 'cinderwell_invalid_tokens', __( 'Tokens must be an object.', 'cinderwell' ) );
        }

        $manifest = self::get_manifest();
        foreach ( $value as $key => $token_value ) {
            if ( ! isset( $manifest[ $key ] ) || ! is_scalar( $token_value ) ) {
                return new \WP_Error( 'cinderwell_invalid_token', __( 'The token payload contains an invalid value.', 'cinderwell' ) );
            }

            if ( '' !== (string) $token_value && '' === self::sanitize_token_value( $key, $token_value, $manifest[ $key ] ) ) {
                return new \WP_Error( 'cinderwell_invalid_token_value', __( 'A token value has an invalid format.', 'cinderwell' ) );
            }
        }

        return true;
    }

    /**
     * Save design-token overrides from Gutenberg.
     */
    public function update_tokens( \WP_REST_Request $request ) {
        $manifest  = self::get_manifest();
        $submitted = $request->get_param( 'tokens' );
        $saved     = [];

        foreach ( $manifest as $key => $token ) {
            $saved[ $key ] = self::sanitize_token_value( $key, $submitted[ $key ] ?? '', $token );
        }

        update_option( 'cinderwell_design_tokens', $saved );

        return rest_ensure_response(
            [
                'tokens' => self::get_editor_tokens(),
            ]
        );
    }

    /**
     * Output CSS custom properties in wp_head.
     */
    public function output_tokens_css() {
        $tokens_css  = self::get_custom_properties_css();
        $palette_css = self::get_palette_css();
        if ( '' === $tokens_css && '' === $palette_css ) {
            return;
        }

        echo '<style id="cinderwell-tokens">';
        echo $tokens_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated exclusively from sanitized token keys and values.
        echo $palette_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated exclusively from validated token keys and sanitized slugs.
        echo '</style>' . "\n";
    }

    /**
     * Generate the resolved custom-property overrides for both frontend and editor canvases.
     *
     * Client themes register their defaults through `cinderwell_token_manifest`.
     * Saved token values and locale-aware overrides are then resolved through the
     * same pipeline before this CSS is emitted.
     */
    public static function get_custom_properties_css() {
        $tokens = self::get_resolved_tokens();

        if ( empty( $tokens ) ) {
            return '';
        }

        $css = ':root{';
        foreach ( $tokens as $key => $value ) {
            if ( ! preg_match( '/^cw_[a-z0-9_]+$/', $key ) ) {
                continue;
            }
            $css .= '--' . esc_attr( str_replace( '_', '-', $key ) ) . ':' . esc_attr( $value ) . ';';
        }

        return $css . '}';
    }

    /**
     * Resolve tokens: defaults → saved options → per-language filter.
     */
    public static function get_resolved_tokens( $locale = null ) {
        $locale   = $locale ?: get_locale();
        $defaults = self::get_defaults();
        $saved    = get_option( 'cinderwell_design_tokens', [] );

        // Ignore retired or otherwise unknown saved keys. Compatibility aliases
        // are defined in shared/base.css instead of remaining editable design tokens.
        $saved  = array_intersect_key( $saved, $defaults );
        $tokens = array_merge( $defaults, array_filter( $saved, function ( $v ) { return '' !== $v; } ) );

        /**
         * Filter design tokens (with locale context).
         *
         * @param array  $tokens Resolved tokens.
         * @param string $locale Current locale.
         */
        return apply_filters( 'cinderwell_design_tokens', $tokens, $locale );
    }
}
