<?php
/**
 * Popup configuration fields and persistence.
 *
 * @package Cinderwell_Popups
 */

namespace Cinderwell_Popups;

defined( 'ABSPATH' ) || exit;

class Popup_Meta {

	const PREFIX = '_cinderwell_popup_';

	public function __construct() {
		add_action( 'init', [ $this, 'register_meta' ] );
		add_action( 'add_meta_boxes_cinderwell_popup', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_cinderwell_popup', [ $this, 'save_meta' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public static function definitions() {
		return [
			'trigger_type'       => [ 'type' => 'string', 'default' => 'manual', 'enum' => [ 'manual', 'auto' ] ],
			'display_location'   => [ 'type' => 'string', 'default' => 'all', 'enum' => [ 'all', 'specific_urls', 'specific_post_types', 'specific_posts' ] ],
			'display_urls'       => [ 'type' => 'array', 'default' => [], 'items' => 'string', 'sanitize_items' => 'text' ],
			'display_post_types' => [ 'type' => 'array', 'default' => [], 'items' => 'string' ],
			'display_posts'      => [ 'type' => 'array', 'default' => [], 'items' => 'integer' ],
			'exclude_urls'       => [ 'type' => 'array', 'default' => [], 'items' => 'string', 'sanitize_items' => 'text' ],
			'exclude_posts'      => [ 'type' => 'array', 'default' => [], 'items' => 'integer' ],
			'frequency'          => [ 'type' => 'string', 'default' => 'once_session', 'enum' => [ 'once_session', 'once_ever', 'always' ] ],
			'animation'          => [ 'type' => 'string', 'default' => 'fade', 'enum' => [ 'fade', 'slide', 'scale' ] ],
			'width'              => [ 'type' => 'string', 'default' => 'medium', 'enum' => [ 'small', 'medium', 'large', 'full' ] ],
		];
	}

	public static function meta_key( $name ) {
		return self::PREFIX . sanitize_key( $name );
	}

	public static function get_value( $post_id, $name ) {
		$definitions = self::definitions();
		$definition  = $definitions[ $name ] ?? [ 'default' => '' ];
		$value       = get_post_meta( $post_id, self::meta_key( $name ), true );

		return '' === $value ? $definition['default'] : $value;
	}

	public function register_meta() {
		foreach ( self::definitions() as $name => $definition ) {
			$schema = [
				'type'    => $definition['type'],
				'default' => $definition['default'],
			];
			if ( 'array' === $definition['type'] ) {
				$schema['show_in_rest'] = [
					'schema' => [
						'type'  => 'array',
						'items' => [ 'type' => $definition['items'] ],
					],
				];
			} else {
				$schema['show_in_rest'] = true;
			}
			$schema['single']        = true;
			$schema['auth_callback'] = static function ( $allowed, $meta_key, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			};
			$schema['sanitize_callback'] = function ( $value ) use ( $definition ) {
				return $this->sanitize_value( $value, $definition );
			};

			register_post_meta( 'cinderwell_popup', self::meta_key( $name ), $schema );
		}
	}

	public function add_meta_boxes() {
		add_meta_box(
			'cinderwell-popup-trigger',
			__( 'Trigger and display', 'cinderwell-popups' ),
			[ $this, 'render_trigger_box' ],
			'cinderwell_popup',
			'side',
			'high'
		);
		add_meta_box(
			'cinderwell-popup-settings',
			__( 'Popup settings', 'cinderwell-popups' ),
			[ $this, 'render_settings_box' ],
			'cinderwell_popup',
			'side',
			'default'
		);
	}

	public function render_trigger_box( $post ) {
		$context = 'trigger';
		include CINDERWELL_POPUPS_PATH . 'templates/admin-meta-boxes.php';
	}

	public function render_settings_box( $post ) {
		$context = 'settings';
		include CINDERWELL_POPUPS_PATH . 'templates/admin-meta-boxes.php';
	}

	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'cinderwell_popup' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'cinderwell-popups-admin',
			CINDERWELL_POPUPS_URL . 'assets/css/admin.css',
			wp_style_is( 'cinderwell-editor-controls', 'registered' ) ? [ 'cinderwell-editor-controls' ] : [],
			CINDERWELL_POPUPS_VERSION
		);
		wp_enqueue_script( 'cinderwell-popups-admin', CINDERWELL_POPUPS_URL . 'assets/js/admin.js', [], CINDERWELL_POPUPS_VERSION, true );
	}

	public function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['cinderwell_popup_meta_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cinderwell_popup_meta_nonce'] ) ), 'cinderwell_popup_meta' ) ||
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			wp_is_post_revision( $post_id ) ||
			! current_user_can( 'edit_post', $post_id ) ||
			'cinderwell_popup' !== $post->post_type ) {
			return;
		}

		$incoming = [
			'trigger_type'       => $_POST['cinderwell_popup_trigger_type'] ?? 'manual',
			'display_location'   => $_POST['cinderwell_popup_display_location'] ?? 'all',
			'display_urls'       => $this->lines_to_patterns( $_POST['cinderwell_popup_display_urls'] ?? '' ),
			'display_post_types' => $_POST['cinderwell_popup_display_post_types'] ?? [],
			'display_posts'      => $_POST['cinderwell_popup_display_posts'] ?? [],
			'exclude_urls'       => $this->lines_to_patterns( $_POST['cinderwell_popup_exclude_urls'] ?? '' ),
			'exclude_posts'      => $_POST['cinderwell_popup_exclude_posts'] ?? [],
			'frequency'          => $_POST['cinderwell_popup_frequency'] ?? 'once_session',
			'animation'          => $_POST['cinderwell_popup_animation'] ?? 'fade',
			'width'              => $_POST['cinderwell_popup_width'] ?? 'medium',
		];

		foreach ( self::definitions() as $name => $definition ) {
			$value = $this->sanitize_value( wp_unslash( $incoming[ $name ] ), $definition );
			if ( 'display_post_types' === $name ) {
				$value = array_values( array_intersect( $value, get_post_types( [ 'public' => true ], 'names' ) ) );
			} elseif ( in_array( $name, [ 'display_posts', 'exclude_posts' ], true ) ) {
				$value = array_values( array_filter( $value, 'get_post' ) );
			}
			update_post_meta( $post_id, self::meta_key( $name ), $value );
		}
	}

	private function lines_to_patterns( $value ) {
		$value = sanitize_textarea_field( wp_unslash( $value ) );
		return array_values( array_unique( array_filter( array_map( 'trim', preg_split( '/\R/', $value ) ) ) ) );
	}

	private function sanitize_value( $value, $definition ) {
		if ( 'array' === $definition['type'] ) {
			$callback = 'integer' === $definition['items']
				? 'absint'
				: ( 'text' === ( $definition['sanitize_items'] ?? '' ) ? 'sanitize_text_field' : 'sanitize_key' );
			return array_values( array_unique( array_filter( array_map( $callback, (array) $value ) ) ) );
		}

		$value = sanitize_key( (string) $value );
		if ( isset( $definition['enum'] ) && ! in_array( $value, $definition['enum'], true ) ) {
			return $definition['default'];
		}

		return $value;
	}
}
