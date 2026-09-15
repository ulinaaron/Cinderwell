<?php
/**
 * Cinderwell admin-bar integration.
 *
 * @package Cinderwell_Popups
 */

namespace Cinderwell_Popups;

defined( 'ABSPATH' ) || exit;

class Admin_Bar {

	public function __construct() {
		add_action( 'cinderwell_admin_bar_menu', [ $this, 'add_menu' ], 20, 3 );
	}

	/**
	 * Add popup management and current-page edit shortcuts.
	 *
	 * @param \WP_Admin_Bar $admin_bar Admin bar instance.
	 * @param string        $root_id   Cinderwell root node ID.
	 * @param array         $context   Current request context.
	 */
	public function add_menu( $admin_bar, $root_id, $context ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$active = ! empty( $context['is_frontend'] ) ? $this->get_active_popups( $context ) : [];
		$active = array_values( array_filter( $active, static function ( $popup ) {
			return current_user_can( 'edit_post', $popup->ID );
		} ) );
		$count  = count( $active );
		$title  = $count
			? sprintf( '%s <span class="ab-label">%d</span>', esc_html__( 'Popups', 'cinderwell-popups' ), $count )
			: esc_html__( 'Popups', 'cinderwell-popups' );

		$admin_bar->add_node( [
			'id'     => 'cinderwell-popups',
			'parent' => $root_id,
			'title'  => $title,
			'href'   => admin_url( 'edit.php?post_type=cinderwell_popup' ),
			'meta'   => [ 'title' => esc_attr__( 'Manage popups', 'cinderwell-popups' ) ],
		] );

		if ( ! empty( $context['is_frontend'] ) ) {
			if ( $active ) {
				foreach ( $active as $popup ) {
					$trigger = Popup_Meta::get_value( $popup->ID, 'trigger_type' );
					$admin_bar->add_node( [
						'id'     => 'cinderwell-popup-' . $popup->ID,
						'parent' => 'cinderwell-popups',
						'title'  => sprintf(
							/* translators: 1: Popup title. 2: Trigger type. */
							__( 'Edit “%1$s” — %2$s', 'cinderwell-popups' ),
							esc_html( get_the_title( $popup ) ?: __( 'Untitled popup', 'cinderwell-popups' ) ),
							esc_html( 'auto' === $trigger ? __( 'Automatic', 'cinderwell-popups' ) : __( 'Manual trigger', 'cinderwell-popups' ) )
						),
						'href'   => get_edit_post_link( $popup->ID, 'raw' ),
					] );
				}
			} else {
				$admin_bar->add_node( [
					'id'     => 'cinderwell-popups-none',
					'parent' => 'cinderwell-popups',
					'title'  => esc_html__( 'No active popups on this page', 'cinderwell-popups' ),
					'href'   => false,
					'meta'   => [ 'class' => 'cinderwell-popups-none' ],
				] );
			}
		}

		$admin_bar->add_node( [
			'id'     => 'cinderwell-popups-new',
			'parent' => 'cinderwell-popups',
			'title'  => esc_html__( 'Add new popup', 'cinderwell-popups' ),
			'href'   => admin_url( 'post-new.php?post_type=cinderwell_popup' ),
		] );

		$admin_bar->add_node( [
			'id'     => 'cinderwell-popups-manage',
			'parent' => 'cinderwell-popups',
			'title'  => esc_html__( 'Manage all popups', 'cinderwell-popups' ),
			'href'   => admin_url( 'edit.php?post_type=cinderwell_popup' ),
		] );
	}

	/**
	 * Resolve automatic matches and manual triggers present in page content.
	 *
	 * @param array $context Cinderwell admin-bar context.
	 * @return \WP_Post[]
	 */
	private function get_active_popups( $context ) {
		$popups = get_posts( [
			'post_type'              => 'cinderwell_popup',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => [ 'menu_order' => 'ASC', 'date' => 'DESC' ],
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		] );
		if ( ! $popups ) {
			return [];
		}

		$post_id    = absint( $context['queried_object_id'] ?? 0 );
		$request_url = (string) ( $context['request_path'] ?? '/' );
		$request_url = $request_url ?: '/';
		$manual_ids = $post_id ? $this->get_manual_popup_ids( $post_id ) : [];
		$active     = [];

		foreach ( $popups as $popup ) {
			$trigger = Popup_Meta::get_value( $popup->ID, 'trigger_type' );
			if (
				( 'auto' === $trigger && Display_Rules::should_show( $popup->ID, [ 'url' => $request_url, 'post_id' => $post_id ] ) )
				|| in_array( $popup->ID, $manual_ids, true )
			) {
				$active[] = $popup;
			}
		}

		return $active;
	}

	/**
	 * Find popup IDs referenced by supported trigger blocks on a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int[]
	 */
	private function get_manual_popup_ids( $post_id ) {
		$content = get_post_field( 'post_content', $post_id );
		if ( ! $content ) {
			return [];
		}

		$ids  = [];
		$seen = [];
		$this->collect_popup_ids( parse_blocks( $content ), $ids, $seen );
		return array_values( array_unique( array_map( 'absint', $ids ) ) );
	}

	/**
	 * Recursively inspect blocks, including synced patterns.
	 *
	 * @param array $blocks Parsed blocks.
	 * @param array $ids    Collected IDs.
	 * @param array $seen   Visited synced-pattern IDs.
	 */
	private function collect_popup_ids( $blocks, &$ids, &$seen ) {
		foreach ( $blocks as $block ) {
			$name       = $block['blockName'] ?? '';
			$attributes = $block['attrs'] ?? [];
			$popup_id   = absint( $attributes['popupId'] ?? 0 );

			if (
				$popup_id &&
				( 'cinderwell-popups/trigger' === $name || ( 'cinderwell/button' === $name && 'popup' === ( $attributes['action'] ?? '' ) ) )
			) {
				$ids[] = $popup_id;
			}

			if ( 'core/block' === $name ) {
				$reference_id = absint( $attributes['ref'] ?? 0 );
				if ( $reference_id && empty( $seen[ $reference_id ] ) ) {
					$seen[ $reference_id ] = true;
					$reference_content     = get_post_field( 'post_content', $reference_id );
					if ( $reference_content ) {
						$this->collect_popup_ids( parse_blocks( $reference_content ), $ids, $seen );
					}
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$this->collect_popup_ids( $block['innerBlocks'], $ids, $seen );
			}
		}
	}
}
