<?php
namespace Cinderwell_Forms;

defined( 'ABSPATH' ) || exit;

/** Automatic submission retention. */
class Retention {
	const HOOK = 'cinderwell_forms_retention_cleanup';
	public function __construct() { add_action( self::HOOK, [ __CLASS__, 'cleanup' ] ); self::schedule(); }
	public static function schedule() { if ( ! wp_next_scheduled( self::HOOK ) ) wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK ); }
	public static function unschedule() { $timestamp = wp_next_scheduled( self::HOOK ); if ( $timestamp ) wp_unschedule_event( $timestamp, self::HOOK ); }
	public static function cleanup() { global $wpdb; $days = absint( get_option( 'cinderwell_forms_settings', [] )['retention_days'] ?? 0 ); if ( ! $days ) return 0; $cutoff = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ); $ids = array_map( 'absint', $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . Database::table( 'entries' ) . ' WHERE created_utc < %s ORDER BY id LIMIT 200', $cutoff ) ) ); foreach ( $ids as $id ) Entry_Repository::delete( $id ); return count( $ids ); }
}
