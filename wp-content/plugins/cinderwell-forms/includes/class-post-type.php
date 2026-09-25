<?php
namespace Cinderwell_Forms;

defined( 'ABSPATH' ) || exit;

/** Private form-definition post type. */
class Post_Type {
	const POST_TYPE = 'cw_form';
	const META_DEFINITION = '_cinderwell_form_definition';

	public function __construct() {
		add_action( 'init', [ __CLASS__, 'register' ] );
	}

	public static function register() {
		register_post_type( self::POST_TYPE, [
			'labels' => [ 'name' => __( 'Forms', 'cinderwell-forms' ), 'singular_name' => __( 'Form', 'cinderwell-forms' ) ],
			'public' => false,
			'show_ui' => false,
			'show_in_rest' => false,
			'supports' => [ 'title', 'revisions' ],
			'capability_type' => 'post',
			'map_meta_cap' => true,
		] );

		register_post_meta( self::POST_TYPE, self::META_DEFINITION, [
			'type' => 'object',
			'single' => true,
			'show_in_rest' => false,
			'revisions_enabled' => true,
			'auth_callback' => static function () { return current_user_can( Capabilities::MANAGE_FORMS ); },
			'sanitize_callback' => [ Form_Repository::class, 'sanitize_definition' ],
		] );
	}
}
