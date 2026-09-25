<?php
namespace Cinderwell_Events;

defined( 'ABSPATH' ) || exit;

/** Register Events blocks and their Cinderwell asset dependencies. */
class Blocks {
	public function __construct() {
		add_action( 'init', [ $this, 'register' ], 20 );
		add_filter( 'block_type_metadata_settings', [ $this, 'dependencies' ], 20, 2 );
		add_filter( 'cinderwell_block_library_catalog', [ $this, 'catalog' ] );
	}

	public function register() {
		$path = CINDERWELL_EVENTS_PATH . 'build/blocks/upcoming-sessions';
		if ( is_readable( $path . '/block.json' ) ) {
			register_block_type_from_metadata( $path );
		}
	}

	public function dependencies( $settings, $metadata ) {
		if ( 'cinderwell-events/upcoming-sessions' !== ( $metadata['name'] ?? '' ) ) {
			return $settings;
		}
		$handles = [ 'cinderwell-base', 'cinderwell-responsive' ];
		$settings['style_handles'] = array_values( array_unique( array_merge( $handles, $settings['style_handles'] ?? [] ) ) );
		$settings['editor_style_handles'] = array_values( array_unique( array_merge( $handles, $settings['editor_style_handles'] ?? [] ) ) );
		return $settings;
	}

	public function catalog( $catalog ) {
		$catalog['cinderwell-events/upcoming-sessions'] = 'cinderwell-content';
		return $catalog;
	}
}

