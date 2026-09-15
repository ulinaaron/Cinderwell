<?php
/**
 * Popup post type.
 *
 * @package Cinderwell_Popups
 */

namespace Cinderwell_Popups;

defined( 'ABSPATH' ) || exit;

class Popup_CPT {

	public function __construct() {
		add_action( 'init', [ __CLASS__, 'register' ] );
		add_filter( 'manage_cinderwell_popup_posts_columns', [ $this, 'set_columns' ] );
		add_action( 'manage_cinderwell_popup_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
		add_filter( 'manage_edit-cinderwell_popup_sortable_columns', [ $this, 'sortable_columns' ] );
		add_action( 'pre_get_posts', [ $this, 'apply_admin_sorting' ] );
	}

	public static function register() {
		register_post_type( 'cinderwell_popup', [
			'labels' => [
				'name'          => __( 'Popups', 'cinderwell-popups' ),
				'singular_name' => __( 'Popup', 'cinderwell-popups' ),
				'add_new'       => __( 'Add New Popup', 'cinderwell-popups' ),
				'add_new_item'  => __( 'Add New Popup', 'cinderwell-popups' ),
				'edit_item'     => __( 'Edit Popup', 'cinderwell-popups' ),
				'new_item'      => __( 'New Popup', 'cinderwell-popups' ),
				'view_item'     => __( 'Preview Popup', 'cinderwell-popups' ),
				'search_items'  => __( 'Search Popups', 'cinderwell-popups' ),
				'not_found'     => __( 'No popups found.', 'cinderwell-popups' ),
				'menu_name'     => __( 'Popups', 'cinderwell-popups' ),
			],
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'cinderwell',
			'show_in_rest'        => true,
			'rest_base'           => 'popups',
			'supports'            => [ 'title', 'editor', 'revisions' ],
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'rewrite'             => false,
			'template'            => [ [ 'cinderwell-popups/popup-content' ] ],
			'template_lock'       => 'all',
		] );
	}

	public function set_columns( $columns ) {
		unset( $columns['date'] );
		$columns['trigger']       = __( 'Trigger', 'cinderwell-popups' );
		$columns['display_rules'] = __( 'Display rules', 'cinderwell-popups' );
		$columns['frequency']     = __( 'Frequency', 'cinderwell-popups' );
		$columns['modified']      = __( 'Last modified', 'cinderwell-popups' );

		return $columns;
	}

	public function render_column( $column, $post_id ) {
		if ( 'trigger' === $column ) {
			$trigger = Popup_Meta::get_value( $post_id, 'trigger_type' );
			echo '<span class="dashicons ' . esc_attr( 'auto' === $trigger ? 'dashicons-clock' : 'dashicons-admin-links' ) . '" aria-hidden="true"></span> ';
			echo esc_html( 'auto' === $trigger ? __( 'Automatic', 'cinderwell-popups' ) : __( 'Manual', 'cinderwell-popups' ) );
		} elseif ( 'display_rules' === $column ) {
			echo esc_html( Display_Rules::get_summary( $post_id ) );
		} elseif ( 'frequency' === $column ) {
			$labels = [
				'once_session' => __( 'Once per session', 'cinderwell-popups' ),
				'once_ever'    => __( 'Once per year', 'cinderwell-popups' ),
				'always'       => __( 'Always', 'cinderwell-popups' ),
			];
			$value = Popup_Meta::get_value( $post_id, 'frequency' );
			echo esc_html( $labels[ $value ] ?? $value );
		} elseif ( 'modified' === $column ) {
			$post = get_post( $post_id );
			if ( $post ) {
				echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $post->post_modified ) );
			}
		}
	}

	public function sortable_columns( $columns ) {
		$columns['trigger']   = 'popup_trigger';
		$columns['frequency'] = 'popup_frequency';
		$columns['modified']  = 'modified';

		return $columns;
	}

	public function apply_admin_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || 'cinderwell_popup' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		if ( 'popup_trigger' === $orderby || 'popup_frequency' === $orderby ) {
			$query->set( 'meta_key', 'popup_trigger' === $orderby ? Popup_Meta::meta_key( 'trigger_type' ) : Popup_Meta::meta_key( 'frequency' ) );
			$query->set( 'orderby', 'meta_value' );
		}
	}
}
