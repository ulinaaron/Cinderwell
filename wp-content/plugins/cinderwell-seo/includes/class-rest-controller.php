<?php
namespace Cinderwell_SEO;

defined( 'ABSPATH' ) || exit;

/** Authenticated draft analysis endpoint. */
class Rest_Controller extends \WP_REST_Controller {
	private $compatibility;

	public function __construct( Compatibility $compatibility ) {
		$this->namespace     = 'cinderwell-seo/v1';
		$this->rest_base     = 'analyze';
		$this->compatibility = $compatibility;
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'analyze' ],
			'permission_callback' => [ $this, 'permissions_check' ],
			'args'                => [
				'post_id' => [ 'type' => 'integer', 'required' => true, 'minimum' => 1 ],
				'post_type' => [ 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_key' ],
				'title' => [ 'type' => 'string', 'default' => '' ],
				'excerpt' => [ 'type' => 'string', 'default' => '' ],
				'content' => [ 'type' => 'string', 'default' => '' ],
				'url' => [ 'type' => 'string', 'format' => 'uri', 'default' => '' ],
				'meta' => [
					'type'       => 'object',
					'default'    => new \stdClass(),
					'properties' => [
						'title' => [ 'type' => 'string' ],
						'description' => [ 'type' => 'string' ],
						'focus_phrase' => [ 'type' => 'string' ],
						'canonical' => [ 'type' => 'string' ],
						'robots_index' => [ 'type' => 'string' ],
						'robots_follow' => [ 'type' => 'string' ],
						'social_title' => [ 'type' => 'string' ],
						'social_description' => [ 'type' => 'string' ],
						'social_image_id' => [ 'type' => 'integer' ],
					],
				],
			],
			'schema'              => [ $this, 'get_public_item_schema' ],
		] );
	}

	public function permissions_check( $request ) {
		return current_user_can( 'edit_post', absint( $request['post_id'] ) );
	}

	public function analyze( $request ) {
		if ( $this->compatibility->has_conflict() ) {
			return new \WP_Error(
				'cinderwell_seo_standby',
				__( 'Cinderwell SEO analysis is unavailable while another SEO provider is active.', 'cinderwell-seo' ),
				[ 'status' => 409 ]
			);
		}

		$post = get_post( absint( $request['post_id'] ) );
		if ( ! $post || $post->post_type !== $request['post_type'] || ! in_array( $post->post_type, Settings::enabled_post_types(), true ) ) {
			return new \WP_Error( 'cinderwell_seo_invalid_post', __( 'This content type is not enabled for SEO analysis.', 'cinderwell-seo' ), [ 'status' => 400 ] );
		}

		$meta = [];
		foreach ( Meta::fields() as $key => $field ) {
			if ( in_array( $key, [ 'score', 'analysis_version' ], true ) ) {
				continue;
			}
			$value        = isset( $request['meta'][ $key ] ) ? $request['meta'][ $key ] : $field['default'];
			$meta[ $key ] = is_callable( $field['sanitize_callback'] ) ? call_user_func( $field['sanitize_callback'], $value ) : $value;
		}

		$description = $meta['description'];
		if ( '' === trim( $description ) ) {
			$description = trim( wp_strip_all_tags( $request['excerpt'] ) );
		}
		if ( '' === $description ) {
			$description = Document::content_summary( $request['content'] );
		}

		$result = Analyzer::analyze( [
			'post_id'   => $post->ID,
			'post_type' => $post->post_type,
			'title'     => sanitize_text_field( $request['title'] ),
			'excerpt'   => sanitize_textarea_field( $request['excerpt'] ),
			'content'   => wp_kses_post( $request['content'] ),
			'url'       => esc_url_raw( $request['url'] ),
			'meta'      => $meta,
			'document'  => [
				'title'       => $meta['title'] ?: sanitize_text_field( $request['title'] ),
				'description' => $description,
			],
		] );

		return rest_ensure_response( $result );
	}

	public function get_item_schema() {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'cinderwell-seo-analysis',
			'type'       => 'object',
			'properties' => [
				'score' => [ 'type' => 'integer', 'minimum' => 0, 'maximum' => 100 ],
				'band' => [ 'type' => 'string' ],
				'band_label' => [ 'type' => 'string' ],
				'checks' => [ 'type' => 'array', 'items' => [ 'type' => 'object' ] ],
				'analysis_version' => [ 'type' => 'string' ],
			],
		];
	}
}
