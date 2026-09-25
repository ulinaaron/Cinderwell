<?php
defined( 'ABSPATH' ) || exit;

return [
	'version' => CINDERWELL_FORMS_VERSION,
	'sections' => [
		'forms' => [ 'title' => __( 'Forms', 'cinderwell-forms' ), 'description' => __( 'Build forms and review submitted responses.', 'cinderwell-forms' ), 'audience' => 'user', 'order' => 75 ],
	],
	'topics' => [
		'forms-build' => [ 'section' => 'forms', 'title' => __( 'Build and publish a form', 'cinderwell-forms' ), 'summary' => __( 'Add fields, notifications, and a confirmation, then place the Form block on a page.', 'cinderwell-forms' ), 'icon' => 'dashicons-feedback', 'order' => 10, 'file' => 'topics/build-form.md', 'tags' => [ 'forms', 'submissions', 'notifications' ] ],
	],
];
