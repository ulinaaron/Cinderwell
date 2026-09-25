<?php
defined( 'ABSPATH' ) || exit;

return [
	'version' => CINDERWELL_EVENTS_VERSION,
	'sections' => [
		'events' => [
			'title' => __( 'Events', 'cinderwell-events' ), 'description' => __( 'Publish one-time Events or manage Events with multiple Sessions.', 'cinderwell-events' ), 'audience' => 'user', 'order' => 70,
		],
	],
	'topics' => [
		'events-manage' => [
			'section' => 'events', 'title' => __( 'Schedule an Event', 'cinderwell-events' ), 'summary' => __( 'Use a simple date for one-time Events or enable Sessions when an Event happens more than once.', 'cinderwell-events' ),
			'icon' => 'dashicons-calendar-alt', 'order' => 10, 'file' => 'topics/schedule.md', 'tags' => [ 'events', 'sessions', 'scheduling' ],
		],
	],
];
