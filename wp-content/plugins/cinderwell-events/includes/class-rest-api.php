<?php
namespace Cinderwell_Events;

defined( 'ABSPATH' ) || exit;

/** Schema-backed REST controller for Session queries and editor CRUD. */
class Rest_Api extends \WP_REST_Controller {
	private $sessions;

	public function __construct( Session_Repository $sessions ) {
		$this->namespace = 'cinderwell-events/v1';
		$this->rest_base = 'sessions';
		$this->sessions  = $sessions;
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => '__return_true',
				'args'                => $this->get_collection_params(),
			],
			'schema' => [ $this, 'get_public_item_schema' ],
		] );

		register_rest_route( $this->namespace, '/events/(?P<event_id>\d+)/sessions', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_event_items' ],
				'permission_callback' => [ $this, 'can_read_event_items' ],
				'args'                => array_merge( $this->get_collection_params(), [
					'event_id' => [ 'type' => 'integer', 'required' => true, 'minimum' => 1 ],
				] ),
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'can_edit_event' ],
				'args'                => $this->get_endpoint_args_for_item_schema( true ),
			],
			'schema' => [ $this, 'get_public_item_schema' ],
		] );

		register_rest_route( $this->namespace, '/sessions/(?P<id>\d+)', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'can_read_item' ],
				'args'                => [ 'id' => [ 'type' => 'integer', 'required' => true, 'minimum' => 1 ] ],
			],
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'can_edit_item' ],
				'args'                => $this->get_endpoint_args_for_item_schema( false ),
			],
			[
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_item' ],
				'permission_callback' => [ $this, 'can_edit_item' ],
				'args'                => [ 'id' => [ 'type' => 'integer', 'required' => true, 'minimum' => 1 ] ],
			],
			'schema' => [ $this, 'get_public_item_schema' ],
		] );
	}

	public function get_items( $request ) {
		return $this->collection_response( $request, true );
	}

	public function get_event_items( $request ) {
		$event_id = absint( $request['event_id'] );
		$public   = ! ( 'edit' === $request->get_param( 'context' ) && current_user_can( 'edit_post', $event_id ) );
		return $this->collection_response( $request, $public, $event_id );
	}

	private function collection_response( $request, $public, $event_id = 0 ) {
		$args = [
			'event_id'     => $event_id ?: absint( $request->get_param( 'event' ) ),
			'category_id'  => absint( $request->get_param( 'category' ) ),
			'after'        => (string) $request->get_param( 'after' ),
			'before'       => (string) $request->get_param( 'before' ),
			'include_past' => rest_sanitize_boolean( $request->get_param( 'include_past' ) ),
			'page'         => absint( $request->get_param( 'page' ) ?: 1 ),
			'per_page'     => absint( $request->get_param( 'per_page' ) ?: 10 ),
			'order'        => (string) ( $request->get_param( 'order' ) ?: 'asc' ),
		];
		$result   = $this->sessions->query( $args, $public );
		$response = rest_ensure_response( $result['items'] );
		$response->header( 'X-WP-Total', $result['total'] );
		$response->header( 'X-WP-TotalPages', $result['total_pages'] );
		return $response;
	}

	public function get_item( $request ) {
		$item = $this->sessions->get( absint( $request['id'] ) );
		return $item ? rest_ensure_response( $item ) : new \WP_Error( 'session_not_found', __( 'Session not found.', 'cinderwell-events' ), [ 'status' => 404 ] );
	}

	public function create_item( $request ) {
		$item = $this->sessions->create( absint( $request['event_id'] ), $this->request_data( $request ) );
		if ( is_wp_error( $item ) ) {
			return $item;
		}
		$response = rest_ensure_response( $item );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( $this->namespace . '/sessions/' . $item['id'] ) );
		return $response;
	}

	public function update_item( $request ) {
		$item = $this->sessions->update( absint( $request['id'] ), $this->request_data( $request ) );
		return is_wp_error( $item ) ? $item : rest_ensure_response( $item );
	}

	public function delete_item( $request ) {
		$item = $this->sessions->get( absint( $request['id'] ) );
		if ( ! $item ) {
			return new \WP_Error( 'session_not_found', __( 'Session not found.', 'cinderwell-events' ), [ 'status' => 404 ] );
		}
		if ( ! $this->sessions->delete( $item['id'] ) ) {
			return new \WP_Error( 'session_delete_failed', __( 'The Session could not be deleted.', 'cinderwell-events' ), [ 'status' => 500 ] );
		}
		return rest_ensure_response( [ 'deleted' => true, 'previous' => $item ] );
	}

	public function can_read_event_items( $request ) {
		$event_id = absint( $request['event_id'] );
		if ( Event_Post_Type::POST_TYPE !== get_post_type( $event_id ) ) {
			return new \WP_Error( 'event_not_found', __( 'Event not found.', 'cinderwell-events' ), [ 'status' => 404 ] );
		}
		return 'publish' === get_post_status( $event_id ) || current_user_can( 'edit_post', $event_id );
	}

	public function can_edit_event( $request ) {
		$event_id = absint( $request['event_id'] );
		return Event_Post_Type::POST_TYPE === get_post_type( $event_id ) && current_user_can( 'edit_post', $event_id );
	}

	public function can_read_item( $request ) {
		$item = $this->sessions->get( absint( $request['id'] ) );
		if ( ! $item ) {
			return new \WP_Error( 'session_not_found', __( 'Session not found.', 'cinderwell-events' ), [ 'status' => 404 ] );
		}
		return 'publish' === get_post_status( $item['event_id'] ) || current_user_can( 'edit_post', $item['event_id'] );
	}

	public function can_edit_item( $request ) {
		$item = $this->sessions->get( absint( $request['id'] ) );
		return $item && current_user_can( 'edit_post', $item['event_id'] );
	}

	private function request_data( $request ) {
		return [
			'start'   => (string) $request->get_param( 'start' ),
			'end'     => (string) $request->get_param( 'end' ),
			'all_day' => rest_sanitize_boolean( $request->get_param( 'all_day' ) ),
		];
	}

	public function get_collection_params() {
		return [
			'context'      => [ 'type' => 'string', 'enum' => [ 'view', 'edit' ], 'default' => 'view' ],
			'event'        => [ 'type' => 'integer', 'minimum' => 1 ],
			'category'     => [ 'type' => 'integer', 'minimum' => 1 ],
			'after'        => [ 'type' => 'string', 'format' => 'date-time' ],
			'before'       => [ 'type' => 'string', 'format' => 'date-time' ],
			'include_past' => [ 'type' => 'boolean', 'default' => false ],
			'page'         => [ 'type' => 'integer', 'minimum' => 1, 'default' => 1 ],
			'per_page'     => [ 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10 ],
			'order'        => [ 'type' => 'string', 'enum' => [ 'asc', 'desc' ], 'default' => 'asc' ],
		];
	}

	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->schema;
		}
		$this->schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'cinderwell-event-session',
			'type'       => 'object',
			'required'   => [ 'start', 'end' ],
			'properties' => [
				'id'       => [ 'type' => 'integer', 'readonly' => true ],
				'event_id' => [ 'type' => 'integer', 'readonly' => true ],
				'start'    => [ 'type' => 'string', 'required' => true ],
				'end'      => [ 'type' => 'string', 'required' => true ],
				'all_day'  => [ 'type' => 'boolean', 'default' => false ],
				'timezone' => [ 'type' => 'string', 'readonly' => true ],
				'state'    => [ 'type' => 'string', 'enum' => [ 'upcoming', 'ongoing', 'past' ], 'readonly' => true ],
				'display'  => [ 'type' => 'object', 'readonly' => true ],
				'event'    => [ 'type' => 'object', 'readonly' => true ],
			],
		];
		return $this->schema;
	}
}
