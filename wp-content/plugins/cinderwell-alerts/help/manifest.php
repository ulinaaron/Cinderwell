<?php
defined( 'ABSPATH' ) || exit;

return [
	'version' => CINDERWELL_ALERTS_VERSION,
	'sections' => [
		'alerts' => [
			'title' => __( 'Alerts', 'cinderwell-alerts' ), 'description' => __( 'Create, target, schedule, and review site alerts.', 'cinderwell-alerts' ), 'audience' => 'user', 'order' => 50,
		],
	],
	'topics' => [
		'alerts-create' => [
			'section' => 'alerts', 'title' => __( 'Create and publish an alert', 'cinderwell-alerts' ), 'summary' => __( 'Build an accessible alert with the focused block editor.', 'cinderwell-alerts' ),
			'icon' => 'dashicons-megaphone', 'order' => 10, 'file' => 'topics/create.md', 'tags' => [ 'alerts', 'publishing' ],
		],
		'alerts-targeting' => [
			'section' => 'alerts', 'title' => __( 'Choose where and when an alert appears', 'cinderwell-alerts' ), 'summary' => __( 'Configure placement, scheduling, priority, and page conditions.', 'cinderwell-alerts' ),
			'icon' => 'dashicons-calendar-alt', 'order' => 20, 'file' => 'topics/targeting.md', 'tags' => [ 'alerts', 'targeting', 'scheduling' ],
		],
		'alerts-dismissal' => [
			'section' => 'alerts', 'title' => __( 'Control alert dismissal', 'cinderwell-alerts' ), 'summary' => __( 'Decide whether visitors can close an alert and when it returns.', 'cinderwell-alerts' ),
			'icon' => 'dashicons-dismiss', 'order' => 30, 'file' => 'topics/dismissal.md', 'tags' => [ 'alerts', 'dismissal' ],
		],
	],
];
