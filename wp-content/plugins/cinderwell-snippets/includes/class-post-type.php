<?php
/**
 * Private snippet storage.
 *
 * @package Cinderwell_Snippets
 */

namespace Cinderwell_Snippets;

defined( 'ABSPATH' ) || exit;

class Post_Type {
	const POST_TYPE = 'cinderwell_snippet';

	public function __construct() {
		add_action( 'init', [ __CLASS__, 'register' ] );
	}

	public static function register() {
		register_post_type( self::POST_TYPE, [
			'labels'              => [
				'name'          => __( 'Snippets', 'cinderwell-snippets' ),
				'singular_name' => __( 'Snippet', 'cinderwell-snippets' ),
			],
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => [ 'title', 'editor', 'author', 'revisions' ],
			'map_meta_cap'        => true,
			'capability_type'     => 'post',
		] );
	}
}

