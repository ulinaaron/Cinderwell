<?php
namespace Cinderwell_SEO;

defined( 'ABSPATH' ) || exit;

/** Register and sanitize post/term metadata. */
class Meta {
	const PREFIX = '_cw_seo_';
	const ANALYSIS_VERSION = '1';

	public function __construct() {
		add_action( 'init', [ $this, 'register' ], 30 );
		add_action( 'save_post', [ $this, 'refresh_score' ], 40, 2 );
	}

	public static function fields() {
		return [
			'title'             => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
			'description'       => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field', 'default' => '' ],
			'focus_phrase'      => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
			'canonical'         => [ 'type' => 'string', 'sanitize_callback' => [ __CLASS__, 'sanitize_canonical' ], 'default' => '' ],
			'robots_index'      => [ 'type' => 'string', 'sanitize_callback' => [ __CLASS__, 'sanitize_index' ], 'default' => '' ],
			'robots_follow'     => [ 'type' => 'string', 'sanitize_callback' => [ __CLASS__, 'sanitize_follow' ], 'default' => '' ],
			'social_title'      => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
			'social_description'=> [ 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field', 'default' => '' ],
			'social_image_id'   => [ 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 0 ],
			'score'             => [ 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 0 ],
			'analysis_version'  => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
		];
	}

	public function register() {
		foreach ( Settings::supported_post_types() as $post_type => $object ) {
			add_post_type_support( $post_type, 'custom-fields' );
			add_action( "rest_after_insert_{$post_type}", [ $this, 'refresh_rest_score' ], 40, 3 );
			foreach ( self::fields() as $key => $field ) {
				$is_system    = in_array( $key, [ 'score', 'analysis_version' ], true );
				$show_in_rest = $is_system ? false : [ 'schema' => [ 'type' => $field['type'] ] ];
				register_post_meta( $post_type, self::key( $key ), [
					'single'            => true,
					'type'              => $field['type'],
					'default'           => $field['default'],
					'show_in_rest'      => $show_in_rest,
					'sanitize_callback' => $field['sanitize_callback'],
					'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) use ( $is_system ) {
						return $is_system ? current_user_can( 'manage_options' ) : current_user_can( 'edit_post', $post_id );
					},
				] );
			}
		}

		foreach ( Settings::supported_taxonomies() as $taxonomy => $object ) {
			foreach ( self::fields() as $key => $field ) {
				if ( in_array( $key, [ 'score', 'analysis_version' ], true ) ) {
					continue;
				}
				register_term_meta( $taxonomy, self::key( $key ), [
					'single'            => true,
					'type'              => $field['type'],
					'default'           => $field['default'],
					'show_in_rest'      => [ 'schema' => [ 'type' => $field['type'] ] ],
					'sanitize_callback' => $field['sanitize_callback'],
					'auth_callback'     => static function () use ( $taxonomy ) {
						$tax = get_taxonomy( $taxonomy );
						return $tax && current_user_can( $tax->cap->manage_terms );
					},
				] );
			}
		}
	}

	public static function key( $name ) {
		return self::PREFIX . sanitize_key( $name );
	}

	public static function values( $object_id, $object_type = 'post' ) {
		$values = [];
		foreach ( self::fields() as $key => $field ) {
			$values[ $key ] = 'term' === $object_type
				? get_term_meta( $object_id, self::key( $key ), true )
				: get_post_meta( $object_id, self::key( $key ), true );
		}
		return $values;
	}

	public static function sanitize_canonical( $value ) {
		$value = trim( (string) $value );
		return '' === $value ? '' : esc_url_raw( $value, [ 'http', 'https' ] );
	}

	public static function sanitize_index( $value ) {
		return in_array( $value, [ '', 'index', 'noindex' ], true ) ? $value : '';
	}

	public static function sanitize_follow( $value ) {
		return in_array( $value, [ '', 'follow', 'nofollow' ], true ) ? $value : '';
	}

	public function refresh_score( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! in_array( $post->post_type, Settings::enabled_post_types(), true ) ) {
			return;
		}

		$document = Document::resolve( [ 'post_id' => $post_id ] );
		$result   = Analyzer::analyze( [
			'post_id'      => $post_id,
			'post_type'    => $post->post_type,
			'title'        => $post->post_title,
			'excerpt'      => $post->post_excerpt,
			'content'      => $post->post_content,
			'url'          => get_permalink( $post_id ),
			'meta'         => Meta::values( $post_id ),
			'document'     => $document,
		] );

		update_post_meta( $post_id, self::key( 'score' ), $result['score'] );
		update_post_meta( $post_id, self::key( 'analysis_version' ), self::ANALYSIS_VERSION );
	}

	public function refresh_rest_score( $post, $request, $creating ) {
		$this->refresh_score( $post->ID, $post );
	}
}
