<?php
defined( 'ABSPATH' ) || exit;

return [
	'version' => CINDERWELL_POPUPS_VERSION,
	'sections' => [
		'popups' => [
			'title' => __( 'Popups', 'cinderwell-popups' ), 'description' => __( 'Create accessible modal content and connect it to automatic or manual triggers.', 'cinderwell-popups' ), 'audience' => 'user', 'order' => 60,
		],
	],
	'topics' => [
		'popups-create' => [
			'section' => 'popups', 'title' => __( 'Create and publish a popup', 'cinderwell-popups' ), 'summary' => __( 'Build modal content with Cinderwell blocks and choose its behavior.', 'cinderwell-popups' ),
			'icon' => 'dashicons-format-chat', 'order' => 10, 'file' => 'topics/create.md', 'tags' => [ 'popups', 'publishing' ],
		],
		'popups-buttons' => [
			'section' => 'popups', 'title' => __( 'Open a popup from a button', 'cinderwell-popups' ), 'summary' => __( 'Change a Cinderwell Button action from a URL to a published popup.', 'cinderwell-popups' ),
			'icon' => 'dashicons-button', 'order' => 20, 'file' => 'topics/buttons.md', 'tags' => [ 'popups', 'buttons' ],
		],
	],
];
