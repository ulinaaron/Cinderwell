<?php
namespace Cinderwell_Forms;

defined( 'ABSPATH' ) || exit;

/** Main add-on coordinator. */
class Plugin {
	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		Database::maybe_install();
		new Post_Type();
		new Blocks();
		new Submission_Handler();
		new Privacy();
		new Retention();
		if ( is_admin() ) {
			new Admin();
		}
	}

	public static function get_health() {
		$counts = wp_count_posts( Post_Type::POST_TYPE );
		$forms  = isset( $counts->publish ) ? absint( $counts->publish ) : 0;
		$entries = Entry_Repository::count();
		return [
			'status'  => $forms ? 'good' : 'warning',
			'message' => $forms
				? sprintf( __( '%1$d published forms and %2$d stored submissions.', 'cinderwell-forms' ), $forms, $entries )
				: __( 'No published forms yet.', 'cinderwell-forms' ),
		];
	}
}
