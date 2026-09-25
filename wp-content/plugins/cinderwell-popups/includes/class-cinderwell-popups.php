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
		add_filter( 'block_type_metadata_settings', [ $this, 'add_block_style_dependencies' ], 20, 2 );
		add_filter( 'cinderwell_admin_menu_capability', [ $this, 'allow_editor_menu' ] );
		add_action( 'admin_init', [ $this, 'redirect_editor_root' ] );
		add_action( 'cinderwell_register_documentation', [ $this, 'register_documentation' ] );
	}

	public function register_blocks() {
		foreach ( [ 'popup-content', 'popup-trigger' ] as $block ) {
			$path = CINDERWELL_POPUPS_PATH . 'build/blocks/' . $block;
			if ( file_exists( $path . '/block.json' ) ) {
				register_block_type_from_metadata( $path );
			}
		}
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

	public function register_documentation( $registry ) {
		$registry->register_directory( 'cinderwell-popups', CINDERWELL_POPUPS_PATH . 'help' );
	}
}
