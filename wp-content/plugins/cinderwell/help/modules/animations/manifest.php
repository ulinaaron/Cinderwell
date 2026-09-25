<?php
defined( 'ABSPATH' ) || exit;

return [
	'version' => CINDERWELL_VERSION,
	'sections' => [
		'animations' => [ 'title' => __( 'Animations', 'cinderwell' ), 'description' => __( 'Add restrained entrance motion to blocks and content sections.', 'cinderwell' ), 'audience' => 'user', 'order' => 110 ],
	],
	'topics' => [
		'animations-use' => [
			'section' => 'animations', 'title' => __( 'Animate a block', 'cinderwell' ), 'summary' => __( 'Choose a preset, target, duration, and delay.', 'cinderwell' ),
			'icon' => 'dashicons-controls-play', 'order' => 10, 'file' => 'topics/use.md', 'tags' => [ 'animations', 'blocks' ],
		],
		'animations-accessibility' => [
			'section' => 'animations', 'title' => __( 'Use motion responsibly', 'cinderwell' ), 'summary' => __( 'Keep movement purposeful and verify reduced-motion behavior.', 'cinderwell' ),
			'icon' => 'dashicons-universal-access-alt', 'order' => 20, 'file' => 'topics/accessibility.md', 'tags' => [ 'animations', 'accessibility', 'reduced-motion' ],
		],
	],
];
