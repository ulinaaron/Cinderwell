<?php
namespace Cinderwell_Events;

defined( 'ABSPATH' ) || exit;

/** Sessions table lifecycle. */
class Database {
	const VERSION        = '1';
	const VERSION_OPTION = 'cinderwell_events_schema_version';

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'cinderwell_event_sessions';
	}

	public static function maybe_install() {
		if ( self::VERSION !== get_option( self::VERSION_OPTION ) ) {
			self::install();
		}
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id bigint(20) unsigned NOT NULL,
			start_utc datetime NOT NULL,
			end_utc datetime NOT NULL,
			timezone varchar(64) NOT NULL,
			all_day tinyint(1) unsigned NOT NULL DEFAULT 0,
			created_utc datetime NOT NULL,
			updated_utc datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY event_start (event_id,start_utc),
			KEY start_utc (start_utc),
			KEY end_utc (end_utc)
		) {$charset_collate};";

		dbDelta( $sql );
		update_option( self::VERSION_OPTION, self::VERSION, false );
	}
}

