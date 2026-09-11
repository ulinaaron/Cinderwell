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
                'label'       => __( 'Muted Text', 'cinderwell' ),
                'type'        => 'color',
                'default'     => '#666666',
                'description' => __( 'Secondary text and subdued interface content', 'cinderwell' ),
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
                'default'     => 'clamp(1.5rem, 1.2rem + 1.2vw, 2rem)',
                'description' => __( 'Fluid small-heading type size', 'cinderwell' ),
            ],
            'cw_font_size_2xl'  => [
                'label'       => __( '2X Large Type', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(2rem, 1.45rem + 2.3vw, 3rem)',
                'description' => __( 'Fluid heading type size', 'cinderwell' ),
            ],
            'cw_font_size_3xl'  => [
                'label'       => __( '3X Large Type', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(2.5rem, 1.7rem + 3.4vw, 4rem)',
                'description' => __( 'Fluid display type size', 'cinderwell' ),
            ],
            'cw_font_size_4xl'  => [
                'label'       => __( '4X Large Type', 'cinderwell' ),
                'type'        => 'text',
                'default'     => 'clamp(3rem, 1.9rem + 4.5vw, 5rem)',
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
        ];

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
            ];
        }

        return $tokens;
    }

    /**
     * Assign manifest tokens to editor-facing categories.
     */
    private static function get_token_category( $key, $token ) {
        if ( 'color' === $token['type'] ) {
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

        if ( 0 === strpos( $key, 'cw_spacing_' ) ) {
            return 'spacing';
        }

        if ( 0 === strpos( $key, 'cw_width_' ) ) {
            return 'widths';
        }

        if ( 0 === strpos( $key, 'cw_gap_' ) || 0 === strpos( $key, 'cw_layout_' ) ) {
            return 'layout';
        }

        if ( 0 === strpos( $key, 'cw_radius_' ) ) {
            return 'shape';
        }

        if ( 0 === strpos( $key, 'cw_shadow_' ) ) {
            return 'elevation';
        }

        if ( 0 === strpos( $key, 'cw_duration_' ) || 0 === strpos( $key, 'cw_ease_' ) ) {
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
        $locale  = get_locale();
        $tokens  = self::get_resolved_tokens( $locale );
        $defaults = self::get_defaults();
        $tokens   = array_filter(
            $tokens,
            static function ( $value, $key ) use ( $defaults ) {
                return ! array_key_exists( $key, $defaults ) || $value !== $defaults[ $key ];
            },
            ARRAY_FILTER_USE_BOTH
        );

        if ( empty( $tokens ) ) {
            return;
        }

        echo '<style id="cinderwell-tokens">:root {';
        foreach ( $tokens as $key => $value ) {
            if ( ! preg_match( '/^cw_[a-z0-9_]+$/', $key ) ) {
                continue;
            }
            $css_key = str_replace( '_', '-', $key );
            echo '--' . esc_attr( $css_key ) . ': ' . esc_attr( $value ) . ';';
        }
        echo '}</style>' . "\n";
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
