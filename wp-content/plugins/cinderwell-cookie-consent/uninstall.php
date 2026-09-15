<?php
/**
 * Remove plugin-owned settings when explicitly uninstalled.
 *
 * @package Cinderwell_Cookie_Consent
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'cinderwell_cookie_consent_settings' );
