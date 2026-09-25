<?php
defined( 'ABSPATH' ) || exit;

return [
	'version' => CINDERWELL_VERSION,
	'sections' => [
		'locations' => [ 'title' => __( 'Locations', 'cinderwell' ), 'description' => __( 'Maintain offices, branches, campuses, and service areas.', 'cinderwell' ), 'audience' => 'user', 'order' => 100 ],
	],
	'topics' => [
		'locations-manage' => [
			'section' => 'locations', 'title' => __( 'Add and update locations', 'cinderwell' ), 'summary' => __( 'Maintain each location’s content and structured details.', 'cinderwell' ),
			'icon' => 'dashicons-location-alt', 'order' => 10, 'file' => 'topics/manage.md', 'tags' => [ 'locations', 'content' ],
		],
		'locations-display' => [
			'section' => 'locations', 'title' => __( 'Display locations', 'cinderwell' ), 'summary' => __( 'Build a location listing from the shared source entries.', 'cinderwell' ),
			'icon' => 'dashicons-grid-view', 'order' => 20, 'file' => 'topics/display.md', 'tags' => [ 'locations', 'loop', 'listing' ],
		],
	],
];
