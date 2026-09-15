<?php
/**
 * Connect published popups to the Cinderwell Button block.
 *
 * @package Cinderwell_Popups
 */

namespace Cinderwell_Popups;

defined( 'ABSPATH' ) || exit;

class Button_Integration {

	public function __construct() {
		add_filter( 'register_block_type_args', [ $this, 'register_button_attributes' ], 20, 2 );
		add_filter( 'render_block_cinderwell/button', [ $this, 'render_button' ], 30, 2 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ], 5 );
	}

	public function register_button_attributes( $args, $name ) {
		if ( 'cinderwell/button' !== $name ) {
			return $args;
		}

		$args['attributes']['action'] = [ 'type' => 'string', 'default' => 'link' ];
		$args['attributes']['popupId'] = [ 'type' => 'integer', 'default' => 0 ];
		return $args;
	}

	public function enqueue_editor_assets() {
		$script = CINDERWELL_POPUPS_PATH . 'build/button-actions.js';
		$asset  = CINDERWELL_POPUPS_PATH . 'build/button-actions.asset.php';
		if ( ! file_exists( $script ) || ! file_exists( $asset ) ) {
			return;
		}

		$metadata = include $asset;
		wp_enqueue_script(
			'cinderwell-popups-button-actions',
			CINDERWELL_POPUPS_URL . 'build/button-actions.js',
			$metadata['dependencies'],
			$metadata['version'],
			true
		);

		$popups = get_posts( [
			'post_type'      => 'cinderwell_popup',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );
		wp_localize_script( 'cinderwell-popups-button-actions', 'cinderwellPopupsForButtons', [
			'popups' => array_map( static function ( $popup ) {
				return [ 'id' => $popup->ID, 'title' => get_the_title( $popup ) ];
			}, $popups ),
			'addNewUrl' => admin_url( 'post-new.php?post_type=cinderwell_popup' ),
		] );
	}

	public function render_button( $content, $block ) {
		$attributes = $block['attrs'] ?? [];
		$popup_id  = absint( $attributes['popupId'] ?? 0 );
		if ( 'popup' !== ( $attributes['action'] ?? 'link' ) || ! $popup_id || 'publish' !== get_post_status( $popup_id ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $content;
		}

		$processor = new \WP_HTML_Tag_Processor( $content );
		if ( $processor->next_tag( 'a' ) ) {
			$processor->set_attribute( 'href', '#cinderwell-popup-' . $popup_id );
			$processor->set_attribute( 'data-cinderwell-popup', (string) $popup_id );
			$processor->set_attribute( 'aria-haspopup', 'dialog' );
			$processor->set_attribute( 'aria-controls', 'cinderwell-popup-' . $popup_id );
			return $processor->get_updated_html();
		}

		return $content;
	}
}
