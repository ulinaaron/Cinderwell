<?php
/**
 * Plugin bootstrap.
 *
 * @package Cinderwell_Snippets
 */

namespace Cinderwell_Snippets;

defined( 'ABSPATH' ) || exit;

class Plugin {
	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		new Post_Type();
		new Executor();
		new Transfer();
		if ( is_admin() ) {
			new Admin();
		}
	}

	public static function can_manage() {
		return current_user_can( 'manage_options' ) && current_user_can( 'unfiltered_html' );
	}

	public static function get_health() {
		$active = Repository::count_by_status( 'publish' );
		$failed = Repository::count_auto_disabled();

		if ( Repository::safe_mode_enabled() ) {
			return [
				'status'  => 'warning',
				'message' => __( 'Safe Mode is on; no snippets are running.', 'cinderwell-snippets' ),
			];
		}

		if ( $failed ) {
			return [
				'status'  => 'warning',
				'message' => sprintf(
					/* translators: 1: active snippets, 2: auto-disabled snippets. */
					__( '%1$d active; %2$d need attention.', 'cinderwell-snippets' ),
					$active,
					$failed
				),
			];
		}

		return [
			'status'  => 'good',
			'message' => sprintf(
				/* translators: %d: active snippets. */
				_n( '%d active snippet.', '%d active snippets.', $active, 'cinderwell-snippets' ),
				$active
			),
		];
	}
}

