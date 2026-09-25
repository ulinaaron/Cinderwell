<?php
namespace Cinderwell_Forms;

defined( 'ABSPATH' ) || exit;

/** Submission, values, and delivery persistence. */
class Entry_Repository {
	public static function create( array $form, array $values, $source_url ) {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$ok = $wpdb->insert( Database::table( 'entries' ), [
			'entry_key' => wp_generate_uuid4(), 'form_id' => $form['id'], 'form_title' => $form['title'],
			'status' => 'unread', 'starred' => 0, 'source_url' => esc_url_raw( $source_url ),
			'user_id' => get_current_user_id(), 'created_utc' => $now, 'updated_utc' => $now,
		], [ '%s', '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s' ] );
		if ( ! $ok ) return new \WP_Error( 'entry_insert_failed', __( 'The submission could not be stored.', 'cinderwell-forms' ) );
		$entry_id = (int) $wpdb->insert_id;
		foreach ( $form['definition']['fields'] as $field ) {
			if ( ! array_key_exists( $field['key'], $values ) || in_array( $field['type'], [ 'content', 'divider' ], true ) ) continue;
			$value = is_array( $values[ $field['key'] ] ) ? wp_json_encode( array_values( $values[ $field['key'] ] ) ) : (string) $values[ $field['key'] ];
			$stored = $wpdb->insert( Database::table( 'values' ), [
				'entry_id' => $entry_id, 'field_id' => $field['id'], 'field_key' => $field['key'],
				'field_label' => $field['label'], 'field_type' => $field['type'], 'field_value' => $value,
			], [ '%d', '%s', '%s', '%s', '%s', '%s' ] );
			if ( false === $stored ) {
				self::delete( $entry_id );
				return new \WP_Error( 'entry_value_insert_failed', __( 'The submission could not be stored completely.', 'cinderwell-forms' ) );
			}
		}
		return $entry_id;
	}

	public static function get( $entry_id ) {
		global $wpdb;
		$entry = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . Database::table( 'entries' ) . ' WHERE id = %d', $entry_id ), ARRAY_A );
		if ( ! $entry ) return null;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . Database::table( 'values' ) . ' WHERE entry_id = %d ORDER BY id ASC', $entry_id ), ARRAY_A );
		$entry['values'] = [];
		foreach ( $rows as $row ) {
			$value = in_array( $row['field_type'], [ 'checkboxes' ], true ) ? json_decode( $row['field_value'], true ) : $row['field_value'];
			$entry['values'][ $row['field_key'] ] = [ 'label' => $row['field_label'], 'type' => $row['field_type'], 'value' => is_array( $value ) ? $value : (string) $value ];
		}
		return $entry;
	}

	public static function query( array $args = [] ) {
		global $wpdb;
		$args = wp_parse_args( $args, [ 'form_id' => 0, 'status' => '', 'starred' => null, 'search' => '', 'date_from' => '', 'date_to' => '', 'page' => 1, 'per_page' => 20 ] );
		$where = [ '1=1' ]; $params = [];
		if ( $args['form_id'] ) { $where[] = 'e.form_id = %d'; $params[] = absint( $args['form_id'] ); }
		if ( in_array( $args['status'], [ 'unread', 'read' ], true ) ) { $where[] = 'e.status = %s'; $params[] = $args['status']; }
		if ( null !== $args['starred'] ) { $where[] = 'e.starred = %d'; $params[] = $args['starred'] ? 1 : 0; }
		if ( $args['date_from'] ) { $where[] = 'e.created_utc >= %s'; $params[] = sanitize_text_field( $args['date_from'] ) . ' 00:00:00'; }
		if ( $args['date_to'] ) { $where[] = 'e.created_utc <= %s'; $params[] = sanitize_text_field( $args['date_to'] ) . ' 23:59:59'; }
		if ( $args['search'] ) { $where[] = '(e.form_title LIKE %s OR EXISTS (SELECT 1 FROM ' . Database::table( 'values' ) . ' v WHERE v.entry_id=e.id AND v.field_value LIKE %s))'; $like = '%' . $wpdb->esc_like( $args['search'] ) . '%'; $params[] = $like; $params[] = $like; }
		$where_sql = implode( ' AND ', $where );
		$count_sql = 'SELECT COUNT(*) FROM ' . Database::table( 'entries' ) . ' e WHERE ' . $where_sql;
		$total = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );
		$limit = max( 1, min( 200, absint( $args['per_page'] ) ) ); $offset = ( max( 1, absint( $args['page'] ) ) - 1 ) * $limit;
		$sql = 'SELECT e.* FROM ' . Database::table( 'entries' ) . ' e WHERE ' . $where_sql . ' ORDER BY e.created_utc DESC LIMIT %d OFFSET %d';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $params, [ $limit, $offset ] ) ), ARRAY_A );
		return [ 'items' => $rows, 'total' => $total, 'pages' => (int) ceil( $total / $limit ) ];
	}

	public static function count( $form_id = 0, $status = '' ) {
		return self::query( [ 'form_id' => $form_id, 'status' => $status, 'per_page' => 1 ] )['total'];
	}

	public static function update_state( array $ids, $action ) {
		global $wpdb;
		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( ! $ids ) return;
		$in = implode( ',', $ids );
		if ( 'read' === $action || 'unread' === $action ) $wpdb->query( $wpdb->prepare( 'UPDATE ' . Database::table( 'entries' ) . " SET status=%s, updated_utc=%s WHERE id IN ({$in})", $action, current_time( 'mysql', true ) ) );
		if ( 'star' === $action || 'unstar' === $action ) $wpdb->query( 'UPDATE ' . Database::table( 'entries' ) . ' SET starred=' . ( 'star' === $action ? '1' : '0' ) . " WHERE id IN ({$in})" );
		if ( 'delete' === $action ) foreach ( $ids as $id ) self::delete( $id );
	}

	public static function delete( $entry_id ) {
		global $wpdb;
		$wpdb->delete( Database::table( 'deliveries' ), [ 'entry_id' => $entry_id ], [ '%d' ] );
		$wpdb->delete( Database::table( 'values' ), [ 'entry_id' => $entry_id ], [ '%d' ] );
		return (bool) $wpdb->delete( Database::table( 'entries' ), [ 'id' => $entry_id ], [ '%d' ] );
	}

	public static function emails( $email ) {
		global $wpdb;
		return array_map( 'absint', $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT entry_id FROM ' . Database::table( 'values' ) . ' WHERE field_type=%s AND LOWER(field_value)=LOWER(%s)', 'email', $email ) ) );
	}
}
