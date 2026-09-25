<?php
namespace Cinderwell_Events;

defined( 'ABSPATH' ) || exit;

/** Plugin-owned block templates with normal theme/database precedence. */
class Templates {
	public function __construct() {
		$dir = CINDERWELL_EVENTS_PATH . 'templates/';
		new \Cinderwell\Module_Templates( 'cinderwell-events', [
			'single-' . Event_Post_Type::POST_TYPE => [
				'title'       => __( 'Event', 'cinderwell-events' ),
				'description' => __( 'Default single Event template.', 'cinderwell-events' ),
				'path'        => $dir . 'single-cw_event.html',
				'post_types'  => [ Event_Post_Type::POST_TYPE ],
			],
			'archive-' . Event_Post_Type::POST_TYPE => [
				'title'       => __( 'Events Archive', 'cinderwell-events' ),
				'description' => __( 'Upcoming Event dates ordered chronologically.', 'cinderwell-events' ),
				'path'        => $dir . 'archive-cw_event.html',
			],
			'taxonomy-' . Event_Post_Type::TAXONOMY => [
				'title'       => __( 'Event Category', 'cinderwell-events' ),
				'description' => __( 'Upcoming Event dates in an Event category.', 'cinderwell-events' ),
				'path'        => $dir . 'taxonomy-cw_event_category.html',
			],
		] );
	}
}
