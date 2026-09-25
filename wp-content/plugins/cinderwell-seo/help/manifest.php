<?php
defined( 'ABSPATH' ) || exit;

return [
	'sections' => [
		'seo' => [
			'title'       => __( 'SEO', 'cinderwell-seo' ),
			'description' => __( 'Prepare public content for search results and social sharing.', 'cinderwell-seo' ),
			'audience'    => 'user',
			'order'       => 80,
		],
	],
	'topics' => [
		'search-appearance' => [
			'section'  => 'seo',
			'title'    => __( 'Search appearance', 'cinderwell-seo' ),
			'summary'  => __( 'Edit metadata, review SEO readiness, and understand search visibility.', 'cinderwell-seo' ),
			'file'     => 'topics/search-appearance.md',
			'audience' => 'user',
			'order'    => 10,
		],
	],
];
