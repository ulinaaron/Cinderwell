<?php
namespace Cinderwell_Events;

defined( 'ABSPATH' ) || exit;

/** Event content type and taxonomy. */
class Event_Post_Type {
	const POST_TYPE = 'cw_event';
	const TAXONOMY  = 'cw_event_category';
	const OPTION    = 'cinderwell_events_settings';
	const MODE_META = '_cinderwell_event_schedule_mode';

	private $sessions;

	public function __construct( Session_Repository $sessions ) {
		$this->sessions = $sessions;
		add_action( 'init', [ __CLASS__, 'register' ] );
		add_action( 'before_delete_post', [ $this, 'delete_sessions' ], 10, 2 );
		add_filter( 'enter_title_here', [ $this, 'title_placeholder' ], 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'column_content' ], 10, 2 );
	}

	public static function defaults() {
		return [ 'slug' => 'events' ];
	}

	public static function settings() {
		$saved = wp_parse_args( (array) get_option( self::OPTION, [] ), self::defaults() );
		return [ 'slug' => self::sanitize_rewrite_base( $saved['slug'] ?? '' ) ];
	}

	public static function sanitize_rewrite_base( $value ) {
		$segments = array_filter( array_map( 'sanitize_title', explode( '/', trim( (string) $value, '/' ) ) ) );
		return $segments ? implode( '/', $segments ) : 'events';
	}

	public static function register() {
		$slug = self::settings()['slug'];
		register_post_type( self::POST_TYPE, [
			'labels' => [
				'name'               => __( 'Events', 'cinderwell-events' ),
				'singular_name'      => __( 'Event', 'cinderwell-events' ),
				'add_new_item'       => __( 'Add New Event', 'cinderwell-events' ),
				'edit_item'          => __( 'Edit Event', 'cinderwell-events' ),
				'new_item'           => __( 'New Event', 'cinderwell-events' ),
				'view_item'          => __( 'View Event', 'cinderwell-events' ),
				'search_items'       => __( 'Search Events', 'cinderwell-events' ),
				'not_found'          => __( 'No events found.', 'cinderwell-events' ),
				'featured_image'     => __( 'Event image', 'cinderwell-events' ),
				'set_featured_image' => __( 'Set event image', 'cinderwell-events' ),
			],
			'public'             => true,
			'show_in_rest'       => true,
			'rest_base'          => 'events',
			'show_in_menu'       => true,
			'menu_position'      => 21,
			'menu_icon'          => 'dashicons-calendar-alt',
			'has_archive'        => $slug,
			'rewrite'            => [ 'slug' => $slug, 'with_front' => false ],
			'supports'           => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ],
			'taxonomies'         => [ self::TAXONOMY ],
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		] );

		register_taxonomy( self::TAXONOMY, [ self::POST_TYPE ], [
			'labels' => [
				'name'          => __( 'Event Categories', 'cinderwell-events' ),
				'singular_name' => __( 'Event Category', 'cinderwell-events' ),
				'menu_name'     => __( 'Categories', 'cinderwell-events' ),
			],
			'public'            => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rest_base'         => 'event-categories',
			'rewrite'           => [ 'slug' => $slug . '/category', 'with_front' => false ],
		] );

		// A nested Event base can otherwise let the post type's attachment
		// rewrite consume `/category/{term}` before WordPress reaches the
		// taxonomy rule. Keep the taxonomy route explicitly ahead of it.
		add_rewrite_rule(
			'^' . preg_quote( $slug, '#' ) . '/category/([^/]+)/?$',
			'index.php?' . self::TAXONOMY . '=$matches[1]',
			'top'
		);

		register_post_meta( self::POST_TYPE, self::MODE_META, [
			'type'              => 'string',
			'single'            => true,
			'default'           => 'single',
			'show_in_rest'      => true,
			'sanitize_callback' => [ __CLASS__, 'sanitize_schedule_mode' ],
			'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			},
		] );
	}

	public static function sanitize_schedule_mode( $mode ) {
		return 'sessions' === $mode ? 'sessions' : 'single';
	}

	public static function schedule_mode( $event_id ) {
		return self::sanitize_schedule_mode( get_post_meta( absint( $event_id ), self::MODE_META, true ) );
	}

	public function delete_sessions( $post_id, $post ) {
		if ( $post instanceof \WP_Post && self::POST_TYPE === $post->post_type ) {
			$this->sessions->delete_for_event( $post_id );
		}
	}

	public function title_placeholder( $title, $post ) {
		return $post instanceof \WP_Post && self::POST_TYPE === $post->post_type
			? __( 'Event name', 'cinderwell-events' )
			: $title;
	}

	public function columns( $columns ) {
		unset( $columns['date'] );
		$columns['event_next_session'] = __( 'Date / Next Session', 'cinderwell-events' );
		$columns['event_sessions']     = __( 'Schedule', 'cinderwell-events' );
		$columns['date']               = __( 'Published', 'cinderwell-events' );
		return $columns;
	}

	public function column_content( $column, $post_id ) {
		if ( 'event_next_session' === $column ) {
			$result = $this->sessions->query( [ 'event_id' => $post_id, 'per_page' => 1 ], false );
			if ( $result['items'] ) {
				echo esc_html( $result['items'][0]['display']['date_time'] );
			} else {
				echo '<span aria-hidden="true">—</span><span class="screen-reader-text">' . esc_html__( 'No upcoming sessions', 'cinderwell-events' ) . '</span>';
			}
		} elseif ( 'event_sessions' === $column ) {
			$mode   = self::schedule_mode( $post_id );
			$result = $this->sessions->query( [ 'event_id' => $post_id, 'include_past' => true, 'per_page' => 1 ], false );
			if ( 'sessions' === $mode ) {
				echo esc_html( sprintf( _n( '%s session', '%s sessions', $result['total'], 'cinderwell-events' ), number_format_i18n( $result['total'] ) ) );
			} else {
				echo esc_html__( 'One-time event', 'cinderwell-events' );
			}
		}
	}
}
