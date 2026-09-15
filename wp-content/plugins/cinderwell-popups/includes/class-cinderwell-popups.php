<?php
/**
 * Main plugin coordinator.
 *
 * @package Cinderwell_Popups
 */

namespace Cinderwell_Popups;

defined( 'ABSPATH' ) || exit;

class Cinderwell_Popups {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		new Popup_CPT();
		new Popup_Meta();
		new Frontend();
		new Button_Integration();
		new Rest_Api();
		new Admin_Bar();

		add_action( 'init', [ $this, 'register_blocks' ], 20 );
		add_filter( 'block_categories_all', [ $this, 'register_block_category' ], 110 );
		add_filter( 'block_type_metadata_settings', [ $this, 'add_block_style_dependencies' ], 20, 2 );
		add_filter( 'cinderwell_admin_menu_capability', [ $this, 'allow_editor_menu' ] );
		add_action( 'admin_init', [ $this, 'redirect_editor_root' ] );
		add_filter( 'cinderwell_help_sections', [ $this, 'add_help_section' ] );
		add_filter( 'cinderwell_help_topics', [ $this, 'add_help_topics' ] );
	}

	public function register_blocks() {
		foreach ( [ 'popup-content', 'popup-trigger' ] as $block ) {
			$path = CINDERWELL_POPUPS_PATH . 'build/blocks/' . $block;
			if ( file_exists( $path . '/block.json' ) ) {
				register_block_type_from_metadata( $path );
			}
		}
	}

	public function register_block_category( $categories ) {
		foreach ( $categories as $category ) {
			if ( 'cinderwell-popups' === ( $category['slug'] ?? '' ) ) {
				return $categories;
			}
		}

		$categories[] = [
			'slug'  => 'cinderwell-popups',
			'title' => __( 'Cinderwell Popups', 'cinderwell-popups' ),
		];

		return $categories;
	}

	public function add_block_style_dependencies( $settings, $metadata ) {
		$name = $metadata['name'] ?? '';
		if ( 0 !== strpos( $name, 'cinderwell-popups/' ) ) {
			return $settings;
		}

		$handles = [ 'cinderwell-base' ];
		if ( 'cinderwell-popups/trigger' === $name ) {
			$handles[] = 'cinderwell-actions';
		}
		$settings['style_handles'] = array_values( array_unique( array_merge( $handles, $settings['style_handles'] ?? [] ) ) );
		$settings['editor_style_handles'] = array_values( array_unique( array_merge( $handles, $settings['editor_style_handles'] ?? [] ) ) );

		return $settings;
	}

	public function allow_editor_menu() {
		return 'edit_posts';
	}

	public function redirect_editor_root() {
		if ( current_user_can( 'manage_options' ) || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'cinderwell' === $page && ! class_exists( 'Cinderwell_Help\\Help' ) ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=cinderwell_popup' ) );
			exit;
		}
	}

	public function add_help_section( $sections ) {
		$sections['popups'] = [
			'title'       => __( 'Popups', 'cinderwell-popups' ),
			'description' => __( 'Create accessible modal content and connect it to automatic or manual triggers.', 'cinderwell-popups' ),
			'order'       => 60,
		];

		return $sections;
	}

	public function add_help_topics( $topics ) {
		$topics['popups-create'] = [
			'section' => 'popups',
			'title'   => __( 'Create and publish a popup', 'cinderwell-popups' ),
			'summary' => __( 'Build modal content with Cinderwell blocks and choose its behavior.', 'cinderwell-popups' ),
			'icon'    => 'dashicons-format-chat',
			'order'   => 10,
			'content' => sprintf(
				wp_kses_post( __( '<p>Open <a href="%s"><strong>Popups</strong></a>, add a popup, and build its content with Cinderwell blocks. Choose a manual or automatic trigger, configure its display rules and frequency, then publish it.</p>', 'cinderwell-popups' ) ),
				esc_url( admin_url( 'edit.php?post_type=cinderwell_popup' ) )
			),
		];
		$topics['popups-buttons'] = [
			'section' => 'popups',
			'title'   => __( 'Open a popup from a button', 'cinderwell-popups' ),
			'summary' => __( 'Change a Cinderwell Button action from a URL to a published popup.', 'cinderwell-popups' ),
			'icon'    => 'dashicons-button',
			'order'   => 20,
			'content' => __( '<p>Select a Cinderwell Button, open its <strong>Popup action</strong> panel, choose <strong>Open popup</strong>, and select a published popup. A standalone Popup Trigger block is also available.</p>', 'cinderwell-popups' ),
		];

		return $topics;
	}
}
