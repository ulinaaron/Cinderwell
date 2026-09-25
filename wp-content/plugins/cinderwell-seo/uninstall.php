<?php
/** Remove Cinderwell SEO data only after explicit administrator opt-in. */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$settings = get_option( 'cinderwell_seo_settings', [] );
if ( empty( $settings['remove_data'] ) ) {
	return;
}

delete_option( 'cinderwell_seo_settings' );

global $wpdb;
$post_keys = $wpdb->esc_like( '_cw_seo_' ) . '%';
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", $post_keys ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->termmeta} WHERE meta_key LIKE %s", $post_keys ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
