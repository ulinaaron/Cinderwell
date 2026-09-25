<?php
namespace Cinderwell_Forms;

defined( 'ABSPATH' ) || exit;

/** Accessible form HTML renderer. */
class Renderer {
	private static $instance = 0;

	public static function render( $form_id, array $args = [] ) {
		$form = Form_Repository::get( $form_id, true );
		if ( ! $form ) return current_user_can( Capabilities::MANAGE_FORMS ) ? '<p class="cinderwell-form__message cinderwell-form__message--info">' . esc_html__( 'Select a published form.', 'cinderwell-forms' ) . '</p>' : '';
		$editor_preview = ! empty( $args['editorPreview'] );
		self::$instance++;
		$instance = 'cw-form-' . $form_id . '-' . self::$instance;
		$result = $editor_preview ? [] : self::consume_result( $form_id );
		$values = is_array( $result['values'] ?? null ) ? $result['values'] : self::defaults( $form );
		$errors = is_array( $result['errors'] ?? null ) ? $result['errors'] : [];
		$settings = get_option( 'cinderwell_forms_settings', [] );
		$turnstile = ! empty( $form['definition']['settings']['turnstile'] ) && ! empty( $settings['turnstile_site_key'] ) && self::turnstile_secret();
		wp_enqueue_style( 'cinderwell-forms-front' );
		if ( ! $editor_preview ) {
			wp_enqueue_script( 'cinderwell-forms-view' );
			if ( $turnstile ) wp_enqueue_script( 'cinderwell-turnstile' );
		}

		$width       = in_array( $args['width'] ?? '', [ 'narrow', 'standard', 'wide', 'full' ], true ) ? $args['width'] : 'standard';
		$backgrounds = class_exists( '\\Cinderwell\\Design_Tokens' ) ? \Cinderwell\Design_Tokens::get_color_slugs( 'background' ) : [ 'white', 'light', 'dark', 'brand' ];
		$text_colors = class_exists( '\\Cinderwell\\Design_Tokens' ) ? \Cinderwell\Design_Tokens::get_color_slugs( 'text' ) : [ 'text', 'muted', 'brand', 'dark', 'light', 'white' ];
		$background  = in_array( $args['background'] ?? '', $backgrounds, true ) ? $args['background'] : 'white';
		$classes     = [ 'cinderwell-form-section', 'cinderwell-form-section--bg-' . $background ];
		$presentation = in_array( $args['presentation'] ?? '', [ 'standard', 'newsletter' ], true ) ? $args['presentation'] : ( $form['definition']['settings']['type'] ?? 'standard' );
		$form_classes = [ 'cinderwell-form', 'cinderwell-form-builder', 'cw-width-' . $width, 'cinderwell-form--' . $presentation ];
		if ( $editor_preview ) $form_classes[] = 'is-editor-preview';

		$spacing_values = [ 'none', 'xs', 'sm', 'md', 'lg', 'xl' ];
		foreach ( [ 'desktop', 'tablet', 'mobile' ] as $breakpoint ) {
			foreach ( [ 'top', 'bottom' ] as $edge ) {
				$value = $args['spacingResponsive'][ $breakpoint ][ $edge ] ?? '';
				if ( in_array( $value, $spacing_values, true ) ) {
					$classes[] = 'cw-spacing-' . $breakpoint . '-' . $edge . '-' . $value;
				}
			}
		}
		if ( in_array( $args['textSize'] ?? '', [ 'sm', 'md', 'lg' ], true ) ) {
			$classes[] = 'cinderwell-text-size-' . $args['textSize'];
		}
		if ( in_array( $args['textColor'] ?? '', $text_colors, true ) ) {
			$classes[] = 'cinderwell-text-color-' . $args['textColor'];
		}

		$wrapper_style = '';
		if ( ! empty( $args['backgroundImage'] ) && ! empty( $args['backgroundImageUrl'] ) ) {
			$fit_map      = [ 'cover' => 'cover', 'contain' => 'contain' ];
			$position_map = [
				'center'       => 'center center',
				'top'          => 'center top',
				'bottom'       => 'center bottom',
				'left'         => 'left center',
				'right'        => 'right center',
				'top-left'     => 'left top',
				'top-right'    => 'right top',
				'bottom-left'  => 'left bottom',
				'bottom-right' => 'right bottom',
			];
			$overlay       = in_array( $args['backgroundOverlay'] ?? '', [ 'none', 'soft', 'medium', 'strong' ], true ) ? $args['backgroundOverlay'] : 'none';
			$classes[]     = 'cw-has-background-image';
			$classes[]     = 'cw-background-overlay-' . $overlay;
			$wrapper_style = sprintf(
				'background-image:url("%s");background-size:%s;background-position:%s;background-repeat:no-repeat;',
				esc_url_raw( $args['backgroundImageUrl'] ),
				$fit_map[ $args['backgroundImageFit'] ?? '' ] ?? 'cover',
				$position_map[ $args['backgroundImagePosition'] ?? '' ] ?? 'center center'
			);
		}

		$wrapper_attributes = ! empty( $args['_blockRender'] )
			? get_block_wrapper_attributes( [ 'class' => implode( ' ', $classes ), 'style' => $wrapper_style ?: null ] )
			: 'class="' . esc_attr( implode( ' ', $classes ) ) . '"' . ( $wrapper_style ? ' style="' . esc_attr( $wrapper_style ) . '"' : '' );
		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="<?php echo esc_attr( implode( ' ', $form_classes ) ); ?>" id="<?php echo esc_attr( $instance ); ?>"<?php echo $editor_preview ? ' data-cw-form-preview' : ' data-cw-form'; ?>>
			<?php if ( $editor_preview ) : ?>
				<p class="screen-reader-text"><?php echo esc_html( sprintf( __( 'Preview of %s. Form submission is disabled in the editor.', 'cinderwell-forms' ), $form['title'] ) ); ?></p>
				<fieldset class="cinderwell-form__editor-fieldset" disabled>
					<div class="cinderwell-form__grid">
					<?php foreach ( $form['definition']['fields'] as $field ) self::field( $field, $values[ $field['key'] ] ?? '', '', $instance ); ?>
					</div>
					<button class="btn btn--primary btn--md cinderwell-form__submit" type="button" disabled><?php echo esc_html( $form['definition']['settings']['submit_label'] ?: __( 'Submit', 'cinderwell-forms' ) ); ?></button>
				</fieldset>
			<?php else : ?>
			<?php if ( 'success' === ( $result['status'] ?? '' ) ) : ?>
				<div class="cinderwell-form__message cinderwell-form__message--success" role="status" tabindex="-1" data-cw-form-result><?php echo esc_html( $result['message'] ); ?></div>
			<?php else : ?>
			<?php if ( $errors ) : ?>
				<div class="cinderwell-form__message cinderwell-form__message--danger cinderwell-form__error-summary" role="alert" tabindex="-1" data-cw-form-result>
					<h2><?php esc_html_e( 'Please correct the following:', 'cinderwell-forms' ); ?></h2><ul>
					<?php foreach ( $errors as $key => $message ) : ?><li><a href="#<?php echo esc_attr( $instance . '-' . $key ); ?>"><?php echo esc_html( $message ); ?></a></li><?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
			<form id="<?php echo esc_attr( $instance . '-form' ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate data-cw-form-element>
				<input type="hidden" name="action" value="cinderwell_form_submit">
				<input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>">
				<input type="hidden" name="source_url" value="<?php echo esc_attr( self::current_url() ); ?>">
				<input type="hidden" name="started_at" value="<?php echo esc_attr( time() ); ?>">
				<?php wp_nonce_field( 'cinderwell_form_submit_' . $form_id, 'cinderwell_form_token' ); ?>
				<div class="cinderwell-form__trap" aria-hidden="true"><label>Website<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label></div>
				<div class="cinderwell-form__grid">
				<?php foreach ( $form['definition']['fields'] as $field ) self::field( $field, $values[ $field['key'] ] ?? '', $errors[ $field['key'] ] ?? '', $instance ); ?>
				</div>
				<?php if ( $turnstile ) : ?><div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $settings['turnstile_site_key'] ); ?>" data-action="cinderwell-form-<?php echo esc_attr( $form_id ); ?>"></div><?php endif; ?>
				<button class="btn btn--primary btn--md cinderwell-form__submit" type="submit"><?php echo esc_html( $form['definition']['settings']['submit_label'] ?: __( 'Submit', 'cinderwell-forms' ) ); ?></button>
				<p class="cinderwell-form__status screen-reader-text" aria-live="polite" aria-atomic="true" data-cw-form-status></p>
			</form>
			<?php endif; ?>
			<?php endif; ?>
		</div>
		</div>
		<?php
		return ob_get_clean();
	}

	private static function defaults( array $form ) {
		$values = [];
		foreach ( $form['definition']['fields'] as $field ) {
			$value = $field['default'];
			if ( $field['query_param'] && isset( $_GET[ $field['query_param'] ] ) ) {
				$value = wp_unslash( $_GET[ $field['query_param'] ] );
			}
			$values[ $field['key'] ] = $value;
		}
		return $values;
	}

	private static function field( array $field, $value, $error, $instance ) {
		$key = $field['key']; $id = $instance . '-' . $key;
		if ( 'hidden' === $field['type'] ) { echo '<input type="hidden" name="fields[' . esc_attr( $key ) . ']" value="' . esc_attr( is_scalar( $value ) ? $value : '' ) . '">'; return; }
		if ( 'divider' === $field['type'] ) { echo '<hr class="cinderwell-form__divider">'; return; }
		if ( 'content' === $field['type'] ) { echo '<div class="cinderwell-form__content">' . wp_kses_post( wpautop( $field['instructions'] ?: $field['label'] ) ) . '</div>'; return; }
		$described = [];
		if ( $field['instructions'] ) $described[] = $id . '-help';
		if ( $field['conditions'] ) $described[] = $id . '-condition';
		if ( $error ) $described[] = $id . '-error';
		$condition_json = wp_json_encode( [ 'relation' => $field['condition_relation'], 'rules' => $field['conditions'] ] );
		echo '<div class="cinderwell-form__field cinderwell-form__field--' . esc_attr( $field['width'] ) . '" data-cw-field="' . esc_attr( $key ) . '"' . ( $field['conditions'] ? ' data-cw-conditions="' . esc_attr( $condition_json ) . '"' : '' ) . '>';
		if ( in_array( $field['type'], [ 'radio', 'checkboxes' ], true ) ) {
			echo '<fieldset' . ( $described ? ' aria-describedby="' . esc_attr( implode( ' ', $described ) ) . '"' : '' ) . '><legend>' . esc_html( $field['label'] ) . self::required_text( $field ) . '</legend>';
			foreach ( $field['choices'] as $index => $choice ) {
				$choice_id = $id . '-' . $index; $is_multi = 'checkboxes' === $field['type'];
				$checked = $is_multi ? in_array( $choice['value'], (array) $value, true ) : (string) $value === (string) $choice['value'];
				echo '<label class="cinderwell-form__choice" for="' . esc_attr( $choice_id ) . '"><input id="' . esc_attr( $choice_id ) . '" type="' . ( $is_multi ? 'checkbox' : 'radio' ) . '" name="fields[' . esc_attr( $key ) . ']' . ( $is_multi ? '[]' : '' ) . '" value="' . esc_attr( $choice['value'] ) . '"' . checked( $checked, true, false ) . ( $field['required'] && ! $is_multi ? ' required' : '' ) . '> <span>' . esc_html( $choice['label'] ) . '</span></label>';
			}
			echo '</fieldset>';
		} elseif ( 'consent' === $field['type'] ) {
			echo '<label class="cinderwell-form__choice" for="' . esc_attr( $id ) . '"><input id="' . esc_attr( $id ) . '" type="checkbox" name="fields[' . esc_attr( $key ) . ']" value="1"' . checked( ! empty( $value ), true, false ) . ( $field['required'] ? ' required' : '' ) . ( $described ? ' aria-describedby="' . esc_attr( implode( ' ', $described ) ) . '"' : '' ) . ( $error ? ' aria-invalid="true"' : '' ) . '> <span>' . esc_html( $field['label'] ) . self::required_text( $field ) . '</span></label>';
		} else {
			echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . self::required_text( $field ) . '</label>';
			$common = ' id="' . esc_attr( $id ) . '" name="fields[' . esc_attr( $key ) . ']"' . ( $field['required'] ? ' required' : '' ) . ( $field['placeholder'] ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '' ) . ( $described ? ' aria-describedby="' . esc_attr( implode( ' ', $described ) ) . '"' : '' ) . ( $error ? ' aria-invalid="true"' : '' );
			if ( 'textarea' === $field['type'] ) echo '<textarea' . $common . '>' . esc_textarea( is_scalar( $value ) ? $value : '' ) . '</textarea>';
			elseif ( 'select' === $field['type'] ) { echo '<select' . $common . '><option value="">' . esc_html__( 'Select an option', 'cinderwell-forms' ) . '</option>'; foreach ( $field['choices'] as $choice ) echo '<option value="' . esc_attr( $choice['value'] ) . '"' . selected( (string) $value, (string) $choice['value'], false ) . '>' . esc_html( $choice['label'] ) . '</option>'; echo '</select>'; }
			else echo '<input type="' . esc_attr( $field['type'] ) . '"' . $common . ' value="' . esc_attr( is_scalar( $value ) ? $value : '' ) . '">';
		}
		if ( $field['instructions'] ) echo '<small id="' . esc_attr( $id . '-help' ) . '" class="cinderwell-form__help">' . esc_html( $field['instructions'] ) . '</small>';
		if ( $field['conditions'] ) echo '<small id="' . esc_attr( $id . '-condition' ) . '" class="cinderwell-form__condition-note">' . esc_html__( 'This field is shown only when its conditions are met.', 'cinderwell-forms' ) . '</small>';
		if ( $error ) echo '<span id="' . esc_attr( $id . '-error' ) . '" class="cinderwell-form__error">' . esc_html( $error ) . '</span>';
		echo '</div>';
	}

	private static function required_text( array $field ) { return $field['required'] ? ' <span class="cinderwell-form__required">' . esc_html__( '(required)', 'cinderwell-forms' ) . '</span>' : ''; }
	private static function current_url() { return esc_url_raw( ( is_ssl() ? 'https://' : 'http://' ) . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ) ); }
	private static function consume_result( $form_id ) {
		$token = isset( $_GET['cw_form_result'] ) ? sanitize_key( wp_unslash( $_GET['cw_form_result'] ) ) : '';
		if ( ! $token ) return [];
		$result = get_transient( 'cw_form_result_' . $token );
		if ( ! is_array( $result ) || absint( $result['form_id'] ?? 0 ) !== $form_id ) return [];
		delete_transient( 'cw_form_result_' . $token );
		return $result;
	}
	public static function turnstile_secret() { return defined( 'CINDERWELL_FORMS_TURNSTILE_SECRET' ) ? CINDERWELL_FORMS_TURNSTILE_SECRET : ( get_option( 'cinderwell_forms_settings', [] )['turnstile_secret'] ?? '' ); }
}
