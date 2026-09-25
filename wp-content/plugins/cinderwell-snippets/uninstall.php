<?php
/**
 * Remove Snippet Manager data after explicit plugin deletion.
 *
 * @package Cinderwell_Snippets
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$snippet_ids = get_posts( [
	'post_type'      => 'cinderwell_snippet',
	'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'trash', 'auto-draft', 'inherit' ],
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'no_found_rows'  => true,
] );

foreach ( $snippet_ids as $snippet_id ) {
	wp_delete_post( $snippet_id, true );
}

delete_option( 'cinderwell_snippets_manifest' );
delete_option( 'cinderwell_snippets_safe_mode' );

