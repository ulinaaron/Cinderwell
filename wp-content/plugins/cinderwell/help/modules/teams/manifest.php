<?php
defined( 'ABSPATH' ) || exit;
$settings = \Cinderwell\Teams::get_settings();
$label    = 'people' === $settings['terminology'] ? __( 'People', 'cinderwell' ) : __( 'Team', 'cinderwell' );

return [
	'version' => CINDERWELL_VERSION,
	'sections' => [
		'teams' => [ 'title' => $label, 'description' => __( 'Maintain profiles, categories, and people listings.', 'cinderwell' ), 'audience' => 'user', 'order' => 70 ],
	],
	'topics' => [
		'teams-manage' => [
			'section' => 'teams', 'title' => __( 'Add and update people', 'cinderwell' ), 'summary' => __( 'Maintain names, positions, photos, biographies, and contact details.', 'cinderwell' ),
			'icon' => 'dashicons-groups', 'order' => 10, 'file' => 'topics/manage.md', 'tags' => [ 'people', 'team', 'profiles' ],
		],
		'teams-display' => [
			'section' => 'teams', 'title' => __( 'Display a people listing', 'cinderwell' ), 'summary' => __( 'Use a focused Loop and optionally filter it by category.', 'cinderwell' ),
			'icon' => 'dashicons-grid-view', 'order' => 20, 'file' => 'topics/display.md', 'tags' => [ 'people', 'loop', 'listing' ],
		],
	],
];
