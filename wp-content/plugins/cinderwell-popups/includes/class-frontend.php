<?php
/**
 * Frontend assets and modal markup.
 *
 * @package Cinderwell_Popups
 */

namespace Cinderwell_Popups;

defined( 'ABSPATH' ) || exit;

class Frontend {

	/** @var array|null */
	private $popups = null;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_footer', [ $this, 'render_popups' ], 20 );
	}

	private function get_popups() {
		if ( null !== $this->popups ) {
			return $this->popups;
		}

		$this->popups = get_posts( [
			'post_type'              => 'cinderwell_popup',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => [ 'menu_order' => 'ASC', 'date' => 'DESC' ],
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		] );

		return $this->popups;
	}

	public function enqueue_assets() {
		$popups = $this->get_popups();
		if ( ! $popups ) {
			return;
		}

		wp_enqueue_style( 'cinderwell-popups-modal', CINDERWELL_POPUPS_URL . 'assets/css/modal.css', [], CINDERWELL_POPUPS_VERSION );
		$asset_path = CINDERWELL_POPUPS_PATH . 'build/popup-controller.asset.php';
		$asset      = file_exists( $asset_path ) ? include $asset_path : [ 'dependencies' => [], 'version' => CINDERWELL_POPUPS_VERSION ];
		wp_enqueue_script(
			'cinderwell-popups-controller',
			CINDERWELL_POPUPS_URL . 'build/popup-controller.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		$config = [];
		foreach ( $popups as $popup ) {
			$config[] = [
				'id'        => $popup->ID,
				'auto'      => Display_Rules::should_show( $popup->ID ),
				'frequency' => Popup_Meta::get_value( $popup->ID, 'frequency' ),
			];
		}

		wp_localize_script( 'cinderwell-popups-controller', 'cinderwellPopupsConfig', [
			'popups'  => $config,
			'restUrl' => esc_url_raw( rest_url( 'cinder-popups/v1/track' ) ),
			'i18n'    => [
				'close' => __( 'Close popup', 'cinderwell-popups' ),
			],
		] );
	}

	public function render_popups() {
		$popups = $this->get_popups();
		if ( ! $popups || ! wp_script_is( 'cinderwell-popups-controller', 'enqueued' ) ) {
			return;
		}

		echo '<div id="cinderwell-popups-container" class="cinderwell-popups-container">';
		foreach ( $popups as $popup ) {
			$popup_id = $popup->ID;
			$title    = get_the_title( $popup );
			$content  = apply_filters( 'the_content', $popup->post_content );
			$animation= Popup_Meta::get_value( $popup_id, 'animation' );
			$width    = Popup_Meta::get_value( $popup_id, 'width' );
			include CINDERWELL_POPUPS_PATH . 'templates/popup-render.php';
		}
		echo '</div>';
	}
}
