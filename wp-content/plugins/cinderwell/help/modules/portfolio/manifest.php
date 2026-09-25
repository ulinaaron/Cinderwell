<?php
defined( 'ABSPATH' ) || exit;

return [
	'version' => CINDERWELL_VERSION,
	'sections' => [
		'portfolio' => [ 'title' => __( 'Portfolio', 'cinderwell' ), 'description' => __( 'Maintain projects, categories, and portfolio listings.', 'cinderwell' ), 'audience' => 'user', 'order' => 80 ],
	],
	'topics' => [
		'portfolio-manage' => [
			'section' => 'portfolio', 'title' => __( 'Add and update portfolio items', 'cinderwell' ), 'summary' => __( 'Maintain project content, imagery, details, and categories.', 'cinderwell' ),
			'icon' => 'dashicons-portfolio', 'order' => 10, 'file' => 'topics/manage.md', 'tags' => [ 'portfolio', 'projects' ],
		],
		'portfolio-display' => [
			'section' => 'portfolio', 'title' => __( 'Display portfolio work', 'cinderwell' ), 'summary' => __( 'Create project listings with the Cinderwell Loop.', 'cinderwell' ),
			'icon' => 'dashicons-grid-view', 'order' => 20, 'file' => 'topics/display.md', 'tags' => [ 'portfolio', 'loop', 'listing' ],
		],
	],
];
