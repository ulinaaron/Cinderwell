<?php
/**
 * Schema aggregator — collects data-cw-schema attributes and outputs JSON-LD.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Schema_Aggregator {

    private $schemas = [];

    public function __construct() {
        add_filter( 'render_block', [ $this, 'collect_schema' ], 99, 2 );
        add_action( 'wp_footer', [ $this, 'output_schema' ], 5 );
        add_filter( 'wp_kses_allowed_html', [ $this, 'allow_schema_attribute' ], 10, 2 );
    }

    /**
     * Allow data-cw-schema attribute on all HTML tags.
     */
    public function allow_schema_attribute( $allowed_tags, $context ) {
        foreach ( $allowed_tags as $tag => &$attributes ) {
            $attributes['data-cw-schema'] = true;
        }
        return $allowed_tags;
    }

    /**
     * Collect schema from block markup that actually survived frontend rendering.
     */
    public function collect_schema( $block_content, $block ) {
        if ( '' === $block_content || 0 !== strpos( $block['blockName'] ?? '', 'cinderwell/' ) ) {
            return $block_content;
        }

        if ( ! preg_match_all( '/data-cw-schema=(?:\'([^\']*)\'|"([^"]*)")/', $block_content, $matches, PREG_SET_ORDER ) ) {
            return $block_content;
        }

        foreach ( $matches as $match ) {
            $encoded = '' !== ( $match[1] ?? '' ) ? $match[1] : ( $match[2] ?? '' );
            $decoded = json_decode( html_entity_decode( $encoded, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), true );
            if ( $decoded ) {
                $this->schemas[ md5( wp_json_encode( $decoded ) ) ] = $decoded;
            }
        }

        return $block_content;
    }

    /**
     * Output collected JSON-LD after the page's blocks have rendered.
     */
    public function output_schema() {
        if ( empty( $this->schemas ) ) {
            return;
        }

        $schemas = array_values( $this->schemas );
        $output = count( $schemas ) === 1 ? $schemas[0] : $schemas;

        echo '<script type="application/ld+json">' . wp_json_encode( $output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }
}
