<?php
/** Cinderwell core documentation manifest. */

defined( 'ABSPATH' ) || exit;

return [
	'version'  => CINDERWELL_VERSION,
	'sections' => [
		'getting-started' => [
			'title'       => __( 'Getting started', 'cinderwell' ),
			'description' => __( 'Find your way around WordPress and the editor.', 'cinderwell' ),
			'audience'    => 'user',
			'order'       => 10,
		],
		'editing-content' => [
			'title'       => __( 'Editing content', 'cinderwell' ),
			'description' => __( 'Update words, links, images, and page content.', 'cinderwell' ),
			'audience'    => 'user',
			'order'       => 20,
		],
		'cinderwell-blocks' => [
			'title'       => __( 'Cinderwell blocks', 'cinderwell' ),
			'description' => __( 'Use the site’s purpose-built content blocks.', 'cinderwell' ),
			'audience'    => 'user',
			'order'       => 30,
		],
		'site-management' => [
			'title'       => __( 'Site management', 'cinderwell' ),
			'description' => __( 'Maintain navigation, reusable content, and publishing quality.', 'cinderwell' ),
			'audience'    => 'user',
			'order'       => 40,
		],
		'development-theme' => [
			'title'       => __( 'Client theme', 'cinderwell' ),
			'description' => __( 'Own the client presentation layer.', 'cinderwell' ),
			'audience'    => 'development',
			'order'       => 200,
		],
		'development-system' => [
			'title'       => __( 'Design system', 'cinderwell' ),
			'description' => __( 'Extend tokens and block presentations.', 'cinderwell' ),
			'audience'    => 'development',
			'order'       => 210,
		],
		'development-extensions' => [
			'title'       => __( 'Extensions', 'cinderwell' ),
			'description' => __( 'Add blocks, data sources, conditions, and content models.', 'cinderwell' ),
			'audience'    => 'development',
			'order'       => 220,
		],
		'development-delivery' => [
			'title'       => __( 'Delivery', 'cinderwell' ),
			'description' => __( 'Document, test, and release the client layer.', 'cinderwell' ),
			'audience'    => 'development',
			'order'       => 230,
		],
	],
	'topics' => [
		'editor-overview' => [
			'section' => 'getting-started', 'title' => __( 'Find your way around the editor', 'cinderwell' ),
			'summary' => __( 'Understand pages, blocks, the toolbar, and saving.', 'cinderwell' ), 'icon' => 'dashicons-welcome-learn-more', 'order' => 10,
			'file' => 'topics/user/editor-overview.md', 'tags' => [ 'editor', 'blocks', 'saving' ],
		],
		'safe-publishing' => [
			'section' => 'getting-started', 'title' => __( 'Preview and publish safely', 'cinderwell' ),
			'summary' => __( 'Check desktop and mobile layouts before saving.', 'cinderwell' ), 'icon' => 'dashicons-visibility', 'order' => 20,
			'file' => 'topics/user/safe-publishing.md', 'tags' => [ 'preview', 'publishing', 'revisions' ],
		],
		'edit-text' => [
			'section' => 'editing-content', 'title' => __( 'Edit text and headings', 'cinderwell' ),
			'summary' => __( 'Make content changes without changing the page structure.', 'cinderwell' ), 'icon' => 'dashicons-editor-textcolor', 'order' => 30,
			'file' => 'topics/user/edit-text.md', 'tags' => [ 'text', 'headings', 'accessibility' ],
		],
		'edit-links' => [
			'section' => 'editing-content', 'title' => __( 'Edit links and buttons', 'cinderwell' ),
			'summary' => __( 'Update destinations and write meaningful link text.', 'cinderwell' ), 'icon' => 'dashicons-admin-links', 'order' => 40,
			'file' => 'topics/user/edit-links.md', 'tags' => [ 'links', 'buttons', 'accessibility' ],
		],
		'images' => [
			'section' => 'editing-content', 'title' => __( 'Replace images and write alt text', 'cinderwell' ),
			'summary' => __( 'Choose appropriate images while preserving accessibility.', 'cinderwell' ), 'icon' => 'dashicons-format-image', 'order' => 50,
			'file' => 'topics/user/images.md', 'tags' => [ 'images', 'alt-text', 'accessibility' ],
		],
		'videos' => [
			'section' => 'editing-content', 'title' => __( 'Add and manage video', 'cinderwell' ),
			'summary' => __( 'Use uploaded video, provider embeds, captions, and controlled background motion.', 'cinderwell' ), 'icon' => 'dashicons-video-alt3', 'order' => 55,
			'file' => 'topics/user/videos.md', 'tags' => [ 'video', 'captions', 'background' ],
		],
		'add-blocks' => [
			'section' => 'cinderwell-blocks', 'title' => __( 'Add a Cinderwell block', 'cinderwell' ),
			'summary' => __( 'Insert components that already match the site system.', 'cinderwell' ), 'icon' => 'dashicons-screenoptions', 'order' => 60,
			'file' => 'topics/user/add-blocks.md', 'tags' => [ 'blocks', 'inserter' ],
		],
		'move-blocks' => [
			'section' => 'cinderwell-blocks', 'title' => __( 'Move, duplicate, or remove content', 'cinderwell' ),
			'summary' => __( 'Use List View for predictable structural edits.', 'cinderwell' ), 'icon' => 'dashicons-move', 'order' => 70,
			'file' => 'topics/user/move-blocks.md', 'tags' => [ 'blocks', 'list-view', 'reorder' ],
		],
		'responsive-visibility' => [
			'section' => 'cinderwell-blocks', 'title' => __( 'Control responsive visibility', 'cinderwell' ),
			'summary' => __( 'Hide supported content at selected screen sizes.', 'cinderwell' ), 'icon' => 'dashicons-smartphone', 'order' => 80,
			'file' => 'topics/user/responsive-visibility.md', 'tags' => [ 'responsive', 'visibility' ],
		],
		'navigation' => [
			'section' => 'site-management', 'title' => __( 'Edit site navigation', 'cinderwell' ),
			'summary' => __( 'Maintain header links and menus in the Site Editor.', 'cinderwell' ), 'icon' => 'dashicons-menu-alt3', 'order' => 90,
			'capability' => 'edit_theme_options', 'file' => 'topics/user/navigation.md', 'tags' => [ 'navigation', 'site-editor' ],
		],
		'content-quality' => [
			'section' => 'site-management', 'title' => __( 'Keep content accessible and useful', 'cinderwell' ),
			'summary' => __( 'A short checklist for every update.', 'cinderwell' ), 'icon' => 'dashicons-universal-access-alt', 'order' => 100,
			'file' => 'topics/user/content-quality.md', 'tags' => [ 'accessibility', 'quality' ],
		],
		'development-client-theme' => [
			'section' => 'development-theme', 'title' => __( 'Create a client child theme', 'cinderwell' ),
			'summary' => __( 'Keep client identity separate from the reusable Cinderwell foundation.', 'cinderwell' ), 'icon' => 'dashicons-admin-appearance', 'order' => 200,
			'capability' => 'manage_options', 'file' => 'topics/development/client-theme.md', 'tags' => [ 'theme', 'architecture' ],
		],
		'development-tokens' => [
			'section' => 'development-system', 'title' => __( 'Extend design tokens', 'cinderwell' ),
			'summary' => __( 'Change brand values without rebuilding component CSS.', 'cinderwell' ), 'icon' => 'dashicons-art', 'order' => 210,
			'capability' => 'manage_options', 'file' => 'topics/development/tokens.md', 'tags' => [ 'tokens', 'design-system' ],
		],
		'development-patterns-templates' => [
			'section' => 'development-theme', 'title' => __( 'Own patterns and templates', 'cinderwell' ),
			'summary' => __( 'Compose branded pages while preserving the shared block system.', 'cinderwell' ), 'icon' => 'dashicons-layout', 'order' => 220,
			'capability' => 'manage_options', 'file' => 'topics/development/patterns-templates.md', 'tags' => [ 'patterns', 'templates', 'theme' ],
		],
		'development-variations' => [
			'section' => 'development-system', 'title' => __( 'Add block variations', 'cinderwell' ),
			'summary' => __( 'Create reusable presentations from supported Cinderwell blocks.', 'cinderwell' ), 'icon' => 'dashicons-images-alt2', 'order' => 230,
			'capability' => 'manage_options', 'file' => 'topics/development/variations.md', 'tags' => [ 'blocks', 'variations' ],
		],
		'development-blocks' => [
			'section' => 'development-extensions', 'title' => __( 'Register a client block', 'cinderwell' ),
			'summary' => __( 'Add project-specific blocks at the supported registration point.', 'cinderwell' ), 'icon' => 'dashicons-block-default', 'order' => 240,
			'capability' => 'manage_options', 'file' => 'topics/development/blocks.md', 'tags' => [ 'blocks', 'development' ],
		],
		'development-dynamic-content' => [
			'section' => 'development-extensions', 'title' => __( 'Add dynamic data and conditions', 'cinderwell' ),
			'summary' => __( 'Connect client data without hard-coding it into block markup.', 'cinderwell' ), 'icon' => 'dashicons-database', 'order' => 250,
			'capability' => 'manage_options', 'file' => 'topics/development/dynamic-content.md', 'tags' => [ 'dynamic-data', 'conditions' ],
		],
		'development-content-fields' => [
			'section' => 'development-extensions', 'title' => __( 'Register native content fields', 'cinderwell' ),
			'summary' => __( 'Define structured client data without requiring a visual field-builder plugin.', 'cinderwell' ), 'icon' => 'dashicons-feedback', 'order' => 255,
			'capability' => 'manage_options', 'file' => 'topics/development/content-fields.md', 'tags' => [ 'fields', 'metadata', 'development' ],
		],
		'development-content-models' => [
			'section' => 'development-extensions', 'title' => __( 'Customize content models and loops', 'cinderwell' ),
			'summary' => __( 'Adapt shared fields and listings to the client.', 'cinderwell' ), 'icon' => 'dashicons-filter', 'order' => 260,
			'capability' => 'manage_options', 'file' => 'topics/development/content-models.md', 'tags' => [ 'content-models', 'loops' ],
		],
		'development-help-release' => [
			'section' => 'development-delivery', 'title' => __( 'Document and release the client layer', 'cinderwell' ),
			'summary' => __( 'Keep client guidance and deployable changes alongside the theme.', 'cinderwell' ), 'icon' => 'dashicons-book-alt', 'order' => 270,
			'capability' => 'manage_options', 'file' => 'topics/development/help-release.md', 'tags' => [ 'documentation', 'release' ],
		],
	],
];
