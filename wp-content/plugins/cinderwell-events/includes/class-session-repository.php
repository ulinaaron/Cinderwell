<?php
namespace Cinderwell_Events;

defined( 'ABSPATH' ) || exit;

/** Persistence and chronological query service for authoritative Sessions. */
class Session_Repository {
	public function get( $session_id ) {
		global $wpdb;
		$table = Database::table_name();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $session_id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Internal table name.
		return $row ? $this->prepare_item( $row ) : null;
	}

	/**
	 * Query Sessions. Public queries only include published parent Events.
	 */
	public function query( array $args = [], $public = true ) {
		global $wpdb;

		$defaults = [
			'event_id'     => 0,
			'category_id'  => 0,
			'category_ids' => [],
			'search'       => '',
			'after'        => '',
			'before'       => '',
			'include_past' => false,
			'page'         => 1,
			'per_page'     => 10,
			'order'        => 'ASC',
		];
		$args = wp_parse_args( apply_filters( 'cinderwell_events_session_query_args', $args, $public ), $defaults );

		$page      = max( 1, absint( $args['page'] ) );
		$per_page  = min( 100, max( 1, absint( $args['per_page'] ) ) );
		$order     = 'DESC' === strtoupper( (string) $args['order'] ) ? 'DESC' : 'ASC';
		$table     = Database::table_name();
		$posts     = $wpdb->posts;
		$joins     = " INNER JOIN {$posts} p ON p.ID = s.event_id";
		$where     = [ "p.post_type = '" . esc_sql( Event_Post_Type::POST_TYPE ) . "'" ];
		$values    = [];

		if ( $public ) {
			$where[] = "p.post_status = 'publish'";
		}
		if ( ! empty( $args['event_id'] ) ) {
			$where[]  = 's.event_id = %d';
			$values[] = absint( $args['event_id'] );
		}
		$category_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $args['category_ids'] ) ) ) );
		if ( ! $category_ids && ! empty( $args['category_id'] ) ) {
			$category_ids = [ absint( $args['category_id'] ) ];
		}
		if ( $category_ids ) {
			$joins   .= " INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = s.event_id INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id";
			$placeholders = implode( ', ', array_fill( 0, count( $category_ids ), '%d' ) );
			$where[]  = "tt.taxonomy = %s AND tt.term_id IN ({$placeholders})";
			$values[] = Event_Post_Type::TAXONOMY;
			$values   = array_merge( $values, $category_ids );
		}
		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]  = '(p.post_title LIKE %s OR p.post_excerpt LIKE %s OR p.post_content LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		if ( empty( $args['include_past'] ) ) {
			$after = $args['after'] ? $this->to_utc_mysql( $args['after'] ) : gmdate( 'Y-m-d H:i:s' );
			if ( ! is_wp_error( $after ) ) {
				$where[]  = 's.end_utc >= %s';
				$values[] = $after;
			}
		} elseif ( ! empty( $args['after'] ) ) {
			$after = $this->to_utc_mysql( $args['after'] );
			if ( ! is_wp_error( $after ) ) {
				$where[]  = 's.end_utc >= %s';
				$values[] = $after;
			}
		}
		if ( ! empty( $args['before'] ) ) {
			$before = $this->to_utc_mysql( $args['before'] );
			if ( ! is_wp_error( $before ) ) {
				$where[]  = 's.start_utc <= %s';
				$values[] = $before;
			}
		}

		$where_sql = ' WHERE ' . implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(DISTINCT s.id) FROM {$table} s{$joins}{$where_sql}";
		if ( $values ) {
			$count_sql = $wpdb->prepare( $count_sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholders are assembled internally.
		}
		$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$offset     = ( $page - 1 ) * $per_page;
		$select_sql = "SELECT DISTINCT s.* FROM {$table} s{$joins}{$where_sql} ORDER BY s.start_utc {$order}, s.id {$order} LIMIT %d OFFSET %d";
		$select_values   = array_merge( $values, [ $per_page, $offset ] );
		$select_sql      = $wpdb->prepare( $select_sql, $select_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholders are assembled internally.
		$rows            = $wpdb->get_results( $select_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$items           = array_map( [ $this, 'prepare_item' ], $rows );
		$total_pages     = $total ? (int) ceil( $total / $per_page ) : 0;

		return compact( 'items', 'total', 'page', 'per_page', 'total_pages' );
	}

	public function create( $event_id, array $data ) {
		global $wpdb;
		$event_id = absint( $event_id );
		if ( Event_Post_Type::POST_TYPE !== get_post_type( $event_id ) ) {
			return new \WP_Error( 'invalid_event', __( 'A valid Event is required.', 'cinderwell-events' ), [ 'status' => 400 ] );
		}
		$normalized = $this->normalize_input( $data );
		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}
		$now = gmdate( 'Y-m-d H:i:s' );
		$inserted = $wpdb->insert(
			Database::table_name(),
			array_merge( [ 'event_id' => $event_id ], $normalized, [ 'created_utc' => $now, 'updated_utc' => $now ] ),
			[ '%d', '%s', '%s', '%s', '%d', '%s', '%s' ]
		);
		if ( false === $inserted ) {
			return new \WP_Error( 'session_create_failed', __( 'The Session could not be created.', 'cinderwell-events' ), [ 'status' => 500 ] );
		}
		$item = $this->get( (int) $wpdb->insert_id );
		do_action( 'cinderwell_events_session_created', $item, $data );
		return $item;
	}

	public function update( $session_id, array $data ) {
		global $wpdb;
		$current = $this->get( $session_id );
		if ( ! $current ) {
			return new \WP_Error( 'session_not_found', __( 'Session not found.', 'cinderwell-events' ), [ 'status' => 404 ] );
		}
		$normalized = $this->normalize_input( $data );
		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}
		$normalized['updated_utc'] = gmdate( 'Y-m-d H:i:s' );
		$updated = $wpdb->update(
			Database::table_name(),
			$normalized,
			[ 'id' => absint( $session_id ) ],
			[ '%s', '%s', '%s', '%d', '%s' ],
			[ '%d' ]
		);
		if ( false === $updated ) {
			return new \WP_Error( 'session_update_failed', __( 'The Session could not be updated.', 'cinderwell-events' ), [ 'status' => 500 ] );
		}
		$item = $this->get( $session_id );
		do_action( 'cinderwell_events_session_updated', $item, $current, $data );
		return $item;
	}

	public function delete( $session_id ) {
		global $wpdb;
		$current = $this->get( $session_id );
		if ( ! $current ) {
			return false;
		}
		$deleted = (bool) $wpdb->delete( Database::table_name(), [ 'id' => absint( $session_id ) ], [ '%d' ] );
		if ( $deleted ) {
			do_action( 'cinderwell_events_session_deleted', $current );
		}
		return $deleted;
	}

	public function delete_for_event( $event_id ) {
		global $wpdb;
		return false !== $wpdb->delete( Database::table_name(), [ 'event_id' => absint( $event_id ) ], [ '%d' ] );
	}

	private function normalize_input( array $data ) {
		$all_day = ! empty( $data['all_day'] );
		$timezone = wp_timezone();
		$start_raw = sanitize_text_field( (string) ( $data['start'] ?? '' ) );
		$end_raw   = sanitize_text_field( (string) ( $data['end'] ?? '' ) );
		if ( '' === $start_raw || '' === $end_raw ) {
			return new \WP_Error( 'invalid_session_dates', __( 'Start and end are required.', 'cinderwell-events' ), [ 'status' => 400 ] );
		}

		try {
			if ( $all_day ) {
				$start = \DateTimeImmutable::createFromFormat( '!Y-m-d', substr( $start_raw, 0, 10 ), $timezone );
				$end   = \DateTimeImmutable::createFromFormat( '!Y-m-d', substr( $end_raw, 0, 10 ), $timezone );
				if ( ! $start || ! $end ) {
					throw new \Exception( 'Invalid date.' );
				}
				$end = $end->modify( '+1 day' );
			} else {
				$start = new \DateTimeImmutable( $start_raw, $timezone );
				$end   = new \DateTimeImmutable( $end_raw, $timezone );
			}
		} catch ( \Exception $exception ) {
			return new \WP_Error( 'invalid_session_dates', __( 'Enter valid start and end dates.', 'cinderwell-events' ), [ 'status' => 400 ] );
		}

		if ( $end <= $start ) {
			return new \WP_Error( 'invalid_session_range', __( 'The Session end must be after its start.', 'cinderwell-events' ), [ 'status' => 400 ] );
		}

		$utc = new \DateTimeZone( 'UTC' );
		return [
			'start_utc' => $start->setTimezone( $utc )->format( 'Y-m-d H:i:s' ),
			'end_utc'   => $end->setTimezone( $utc )->format( 'Y-m-d H:i:s' ),
			'timezone'  => $timezone->getName(),
			'all_day'   => $all_day ? 1 : 0,
		];
	}

	private function to_utc_mysql( $value ) {
		try {
			$date = new \DateTimeImmutable( (string) $value, wp_timezone() );
			return $date->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		} catch ( \Exception $exception ) {
			return new \WP_Error( 'invalid_date', __( 'Invalid date filter.', 'cinderwell-events' ) );
		}
	}

	private function prepare_item( array $row ) {
		try {
			$timezone = new \DateTimeZone( $row['timezone'] ?: wp_timezone()->getName() );
		} catch ( \Exception $exception ) {
			$timezone = wp_timezone();
		}
		$utc       = new \DateTimeZone( 'UTC' );
		$start_utc = new \DateTimeImmutable( $row['start_utc'], $utc );
		$end_utc   = new \DateTimeImmutable( $row['end_utc'], $utc );
		$start     = $start_utc->setTimezone( $timezone );
		$end       = $end_utc->setTimezone( $timezone );
		$all_day   = ! empty( $row['all_day'] );
		$display_end = $all_day ? $end->modify( '-1 day' ) : $end;
		$is_past   = $end_utc->getTimestamp() < time();
		$is_ongoing = ! $is_past && $start_utc->getTimestamp() <= time();
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );
		$date_label = wp_date( $date_format, $start->getTimestamp(), $timezone );
		if ( $all_day && $display_end->format( 'Y-m-d' ) !== $start->format( 'Y-m-d' ) ) {
			$date_label .= ' – ' . wp_date( $date_format, $display_end->getTimestamp(), $timezone );
		}
		$time_label = $all_day
			? __( 'All day', 'cinderwell-events' )
			: sprintf(
				/* translators: 1: start time, 2: end time. */
				__( '%1$s–%2$s', 'cinderwell-events' ),
				wp_date( $time_format, $start->getTimestamp(), $timezone ),
				wp_date( $time_format, $end->getTimestamp(), $timezone )
			);
		$event_id = absint( $row['event_id'] );
		$item = [
			'id'         => absint( $row['id'] ),
			'event_id'   => $event_id,
			'start'      => $all_day ? $start->format( 'Y-m-d' ) : $start->format( DATE_RFC3339 ),
			'end'        => $all_day ? $display_end->format( 'Y-m-d' ) : $end->format( DATE_RFC3339 ),
			'start_gmt'  => $start_utc->format( DATE_RFC3339 ),
			'end_gmt'    => $end_utc->format( DATE_RFC3339 ),
			'timezone'   => $timezone->getName(),
			'all_day'    => $all_day,
			'state'      => $is_past ? 'past' : ( $is_ongoing ? 'ongoing' : 'upcoming' ),
			'display'    => [
				'date'      => $date_label,
				'time'      => $time_label,
				'date_time' => $date_label . ', ' . $time_label,
			],
			'event'      => [
				'id'        => $event_id,
				'title'     => get_the_title( $event_id ),
				'permalink' => get_permalink( $event_id ),
			],
			'created_gmt' => mysql_to_rfc3339( $row['created_utc'] ),
			'updated_gmt' => mysql_to_rfc3339( $row['updated_utc'] ),
		];

		return (array) apply_filters( 'cinderwell_events_session', $item, $row );
	}
}
