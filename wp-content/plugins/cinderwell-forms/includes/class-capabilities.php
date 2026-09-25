<?php
namespace Cinderwell_Forms;

defined( 'ABSPATH' ) || exit;

/** Forms-specific capability installation. */
class Capabilities {
	const MANAGE_FORMS   = 'manage_cinderwell_forms';
	const VIEW_ENTRIES   = 'view_cinderwell_form_entries';
	const MANAGE_ENTRIES = 'manage_cinderwell_form_entries';
	const MANAGE_SETTINGS = 'manage_cinderwell_form_settings';

	public static function install() {
		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->add_cap( self::MANAGE_FORMS );
		}
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( [ self::MANAGE_FORMS, self::VIEW_ENTRIES, self::MANAGE_ENTRIES, self::MANAGE_SETTINGS ] as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}
}
