<?php
/**
 * Versioned settings migrations for Site Utilities.
 *
 * @package Cinderwell_Utilities
 */

namespace Cinderwell_Utilities;

defined( 'ABSPATH' ) || exit;

class Schema_Migrator {
	const OPTION = 'cinderwell_utilities_schema_version';
	const CURRENT_VERSION = 2;

	public static function maybe_migrate() {
		$version = absint( get_option( self::OPTION, 0 ) );
		if ( $version >= self::CURRENT_VERSION ) {
			return;
		}

		$settings = get_option( Utilities::OPTION, [] );
		$settings = is_array( $settings ) ? $settings : [];

		if ( $version < 1 ) {
			$settings = self::migrate_plugin_locks( $settings );
		}

		update_option( Utilities::OPTION, Module_Registry::sanitize_all( $settings ), false );
		if ( $version < 2 ) {
			self::disable_settings_autoload();
		}
		update_option( self::OPTION, self::CURRENT_VERSION, false );
		do_action( 'cinderwell_utilities_migrated', $version, self::CURRENT_VERSION );
	}

	private static function migrate_plugin_locks( $settings ) {
		if ( ! isset( $settings['plugin_update_control'] ) || ! is_array( $settings['plugin_update_control'] ) ) {
			return $settings;
		}

		if ( ! array_key_exists( 'locked_plugins', $settings['plugin_update_control'] ) && isset( $settings['plugin_update_control']['frozen_plugins'] ) ) {
			$settings['plugin_update_control']['locked_plugins'] = $settings['plugin_update_control']['frozen_plugins'];
		}
		unset( $settings['plugin_update_control']['frozen_plugins'] );

		return $settings;
	}

	private static function disable_settings_autoload() {
		if ( function_exists( 'wp_set_option_autoload' ) ) {
			wp_set_option_autoload( Utilities::OPTION, false );
			return;
		}

		global $wpdb;
		$wpdb->update(
			$wpdb->options,
			[ 'autoload' => 'no' ],
			[ 'option_name' => Utilities::OPTION ],
			[ '%s' ],
			[ '%s' ]
		);
		wp_cache_delete( Utilities::OPTION, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
	}
}
