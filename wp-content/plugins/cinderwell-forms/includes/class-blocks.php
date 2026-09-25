<?php
namespace Cinderwell_Forms;

defined( 'ABSPATH' ) || exit;

/** Dynamic Form block and conditional assets. */
class Blocks {
	public function __construct() {
		add_action( 'init', [ $this, 'register' ], 20 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'editor_data' ] );
		add_filter( 'block_type_metadata_settings', [ $this, 'style_dependencies' ], 20, 2 );
		add_filter( 'cinderwell_block_library_catalog', [ $this, 'catalog' ] );
		add_filter( 'cinderwell_animation_supported_blocks', [ $this, 'animation_supported_blocks' ] );
		add_filter( 'cinderwell_animation_section_selectors', [ $this, 'animation_section_selectors' ] );
	}

	public function register() {
		wp_register_style( 'cinderwell-forms-front', CINDERWELL_FORMS_URL . 'assets/forms.css', [ 'cinderwell-base', 'cinderwell-actions', 'cinderwell-responsive', 'cinderwell-media', 'cinderwell-forms' ], CINDERWELL_FORMS_VERSION );
		wp_register_script( 'cinderwell-forms-view', CINDERWELL_FORMS_URL . 'assets/view.js', [], CINDERWELL_FORMS_VERSION, true );
		wp_register_script( 'cinderwell-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], null, true );
		wp_register_script( 'cinderwell-forms-block-editor', CINDERWELL_FORMS_URL . 'assets/block-editor.js', [ 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render', 'cinderwell-editor-tools' ], CINDERWELL_FORMS_VERSION, true );
		register_block_type( CINDERWELL_FORMS_PATH . 'block', [
			'editor_script' => 'cinderwell-forms-block-editor',
			'editor_style' => 'cinderwell-forms-front',
			'render_callback' => static function ( $attributes ) {
				$attributes['_blockRender'] = true;
				return Renderer::render( absint( $attributes['formId'] ?? 0 ), $attributes );
			},
		] );
	}

	public function editor_data() {
		$forms = get_posts( [ 'post_type' => Post_Type::POST_TYPE, 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
		$can_manage = current_user_can( Capabilities::MANAGE_FORMS );
		wp_localize_script( 'cinderwell-forms-block-editor', 'cinderwellFormsBlock', [
			'forms' => array_map( static function ( $form ) use ( $can_manage ) {
				return [
					'value'   => (int) $form->ID,
					'label'   => $form->post_title,
					'editUrl' => $can_manage ? admin_url( 'admin.php?page=' . Admin::PAGE . '&action=edit&form=' . $form->ID ) : '',
				];
			}, $forms ),
			'newFormUrl'           => $can_manage ? admin_url( 'admin.php?page=' . Admin::PAGE . '-new' ) : '',
			'newNewsletterFormUrl' => $can_manage ? admin_url( 'admin.php?page=' . Admin::PAGE . '-new&preset=newsletter' ) : '',
		] );
	}

	public function style_dependencies( $settings, $metadata ) {
		if ( 'cinderwell/form' !== ( $metadata['name'] ?? '' ) ) {
			return $settings;
		}
		$handles = [ 'cinderwell-base', 'cinderwell-actions', 'cinderwell-responsive', 'cinderwell-media', 'cinderwell-forms', 'cinderwell-forms-front' ];
		$settings['style_handles'] = array_values( array_unique( array_merge( $handles, $settings['style_handles'] ?? [] ) ) );
		$settings['editor_style_handles'] = array_values( array_unique( array_merge( $handles, $settings['editor_style_handles'] ?? [] ) ) );
		return $settings;
	}

	public function catalog( $catalog ) { $catalog['cinderwell/form'] = 'cinderwell-content'; return $catalog; }

	public function animation_supported_blocks( $blocks ) {
		$blocks[] = 'cinderwell/form';
		return array_values( array_unique( $blocks ) );
	}

	public function animation_section_selectors( $selectors ) {
		$selectors['cinderwell/form'] = '.cinderwell-form__field';
		return $selectors;
	}
}
