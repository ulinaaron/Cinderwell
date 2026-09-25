<?php
namespace Cinderwell_Events;

defined( 'ABSPATH' ) || exit;

/** Main add-on coordinator. */
class Plugin {
	private static $instance;
	private $sessions;
	private $modules;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		Database::maybe_install();
		$this->sessions = new Session_Repository();
		$this->modules  = new Module_Registry();

		new Event_Post_Type( $this->sessions );
		new Rest_Api( $this->sessions );
		new Admin( $this->sessions );
		new Blocks();
		new Templates();
		new Page_Header_Integration();

		$this->modules->boot( $this );
	}

	public function sessions() {
		return $this->sessions;
	}

	public function modules() {
		return $this->modules;
	}

	public static function get_health() {
		$events   = wp_count_posts( Event_Post_Type::POST_TYPE );
		$published = isset( $events->publish ) ? absint( $events->publish ) : 0;
		$upcoming = 0;
		if ( self::$instance ) {
			$upcoming = self::$instance->sessions()->query( [ 'per_page' => 1 ], true )['total'];
		}

		if ( ! $published ) {
			return [ 'status' => 'warning', 'message' => __( 'No published events yet.', 'cinderwell-events' ) ];
		}

		return [
			'status'  => 'good',
			'message' => sprintf(
				/* translators: 1: Event count, 2: upcoming Session count. */
				__( '%1$d published events and %2$d upcoming sessions.', 'cinderwell-events' ),
				$published,
				$upcoming
			),
		];
	}
}

