<?php
defined( 'ABSPATH' ) || exit;

return [
	'version' => CINDERWELL_COOKIE_CONSENT_VERSION,
	'sections' => [
		'cookie-consent' => [
			'title' => __( 'Cookie consent', 'cinderwell-cookie-consent' ), 'description' => __( 'Configure visitor choices and maintain accurate disclosures.', 'cinderwell-cookie-consent' ), 'audience' => 'development', 'order' => 225,
		],
	],
	'topics' => [
		'cookie-consent-setup' => [
			'section' => 'cookie-consent', 'title' => __( 'Configure cookie consent', 'cinderwell-cookie-consent' ), 'summary' => __( 'Set the consent message, expiry, categories, and privacy page.', 'cinderwell-cookie-consent' ),
			'icon' => 'dashicons-privacy', 'order' => 10, 'capability' => 'manage_options', 'file' => 'topics/configure.md', 'tags' => [ 'privacy', 'consent', 'settings' ],
		],
		'cookie-consent-audit' => [
			'section' => 'cookie-consent', 'title' => __( 'Audit consent before launch', 'cinderwell-cookie-consent' ), 'summary' => __( 'Verify optional technologies are blocked and accurately disclosed.', 'cinderwell-cookie-consent' ),
			'icon' => 'dashicons-search', 'order' => 20, 'capability' => 'manage_options', 'file' => 'topics/audit.md', 'tags' => [ 'privacy', 'consent', 'audit' ],
		],
	],
];
