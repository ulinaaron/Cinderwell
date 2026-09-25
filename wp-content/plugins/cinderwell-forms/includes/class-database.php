<?php
namespace Cinderwell_Forms;

defined( 'ABSPATH' ) || exit;

/** Submission table lifecycle. */
class Database {
	const VERSION = '1';
	const VERSION_OPTION = 'cinderwell_forms_schema_version';

	public static function table( $name ) {
		global $wpdb;
		$tables = [
			'entries'    => $wpdb->prefix . 'cinderwell_form_entries',
			'values'     => $wpdb->prefix . 'cinderwell_form_entry_values',
			'deliveries' => $wpdb->prefix . 'cinderwell_form_deliveries',
		];
		return $tables[ $name ] ?? '';
	}

	public static function maybe_install() {
		if ( self::VERSION !== get_option( self::VERSION_OPTION ) ) {
			self::install();
		}
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$entries = self::table( 'entries' );
		$values = self::table( 'values' );
		$deliveries = self::table( 'deliveries' );

		dbDelta( "CREATE TABLE {$entries} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entry_key char(36) NOT NULL,
			form_id bigint(20) unsigned NOT NULL DEFAULT 0,
			form_title varchar(191) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'unread',
			starred tinyint(1) unsigned NOT NULL DEFAULT 0,
			source_url text NOT NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_utc datetime NOT NULL,
			updated_utc datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY entry_key (entry_key),
			KEY form_created (form_id,created_utc),
			KEY status_created (status,created_utc)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$values} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entry_id bigint(20) unsigned NOT NULL,
			field_id varchar(64) NOT NULL,
			field_key varchar(64) NOT NULL,
			field_label varchar(191) NOT NULL,
			field_type varchar(32) NOT NULL,
			field_value longtext NOT NULL,
			PRIMARY KEY  (id),
			KEY entry_id (entry_id),
			KEY field_key (field_key)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$deliveries} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entry_id bigint(20) unsigned NOT NULL,
			rule_id varchar(64) NOT NULL,
			rule_name varchar(191) NOT NULL,
			recipient varchar(191) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			attempts smallint(5) unsigned NOT NULL DEFAULT 0,
			last_error text NOT NULL,
			payload longtext NOT NULL,
			created_utc datetime NOT NULL,
			updated_utc datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY entry_status (entry_id,status)
		) {$charset};" );

		update_option( self::VERSION_OPTION, self::VERSION, false );
	}
}
