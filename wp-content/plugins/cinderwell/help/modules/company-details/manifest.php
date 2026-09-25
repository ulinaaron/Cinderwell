<?php
defined( 'ABSPATH' ) || exit;

return [
	'version' => CINDERWELL_VERSION,
	'sections' => [
		'company-details' => [ 'title' => __( 'Company details', 'cinderwell' ), 'description' => __( 'Maintain shared organization and contact information.', 'cinderwell' ), 'audience' => 'development', 'order' => 215 ],
	],
	'topics' => [
		'company-details-manage' => [
			'section' => 'company-details', 'title' => __( 'Update shared company information', 'cinderwell' ), 'summary' => __( 'Change contact details once and reuse them throughout the site.', 'cinderwell' ),
			'icon' => 'dashicons-building', 'order' => 10, 'capability' => 'manage_options', 'file' => 'topics/manage.md', 'tags' => [ 'company-details', 'settings' ],
		],
		'company-details-schema' => [
			'section' => 'company-details', 'title' => __( 'Manage organization schema', 'cinderwell' ), 'summary' => __( 'Avoid duplicate structured organization data.', 'cinderwell' ),
			'icon' => 'dashicons-media-code', 'order' => 20, 'capability' => 'manage_options', 'file' => 'topics/schema.md', 'tags' => [ 'company-details', 'schema' ],
		],
	],
];
