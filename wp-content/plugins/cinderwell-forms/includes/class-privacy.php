<?php
namespace Cinderwell_Forms;

defined( 'ABSPATH' ) || exit;

/** WordPress personal-data integration. */
class Privacy {
	public function __construct() { add_filter( 'wp_privacy_personal_data_exporters', [ $this, 'exporters' ] ); add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'erasers' ] ); }
	public function exporters( $exporters ) { $exporters['cinderwell-forms'] = [ 'exporter_friendly_name' => __( 'Cinderwell Form Submissions', 'cinderwell-forms' ), 'callback' => [ $this, 'export' ] ]; return $exporters; }
	public function erasers( $erasers ) { $erasers['cinderwell-forms'] = [ 'eraser_friendly_name' => __( 'Cinderwell Form Submissions', 'cinderwell-forms' ), 'callback' => [ $this, 'erase' ] ]; return $erasers; }
	public function export( $email, $page = 1 ) { $ids = Entry_Repository::emails( sanitize_email( $email ) ); $data = []; foreach ( array_slice( $ids, ( max( 1, $page ) - 1 ) * 50, 50 ) as $id ) { $entry = Entry_Repository::get( $id ); if ( ! $entry ) continue; $items = [ [ 'name' => __( 'Form', 'cinderwell-forms' ), 'value' => $entry['form_title'] ], [ 'name' => __( 'Submitted', 'cinderwell-forms' ), 'value' => $entry['created_utc'] ] ]; foreach ( $entry['values'] as $value ) $items[] = [ 'name' => $value['label'], 'value' => is_array( $value['value'] ) ? implode( ', ', $value['value'] ) : $value['value'] ]; $data[] = [ 'group_id' => 'cinderwell-forms', 'group_label' => __( 'Form Submissions', 'cinderwell-forms' ), 'item_id' => 'entry-' . $id, 'data' => $items ]; } return [ 'data' => $data, 'done' => count( $ids ) <= $page * 50 ]; }
	public function erase( $email, $page = 1 ) { $ids = array_slice( Entry_Repository::emails( sanitize_email( $email ) ), 0, 50 ); foreach ( $ids as $id ) Entry_Repository::delete( $id ); return [ 'items_removed' => (bool) $ids, 'items_retained' => false, 'messages' => [], 'done' => count( $ids ) < 50 ]; }
}
