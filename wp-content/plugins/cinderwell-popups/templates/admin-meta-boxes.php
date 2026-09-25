<?php
/**
 * Popup editor side panels.
 *
 * @var WP_Post $post
 * @var string  $context
 *
 * @package Cinderwell_Popups
 */

defined( 'ABSPATH' ) || exit;

use Cinderwell_Popups\Popup_Meta;

$cw_popup_segmented = static function ( $name, $label, $value, $options ) {
	?>
	<fieldset class="cw-editor-field">
		<legend class="cw-editor-field__label"><?php echo esc_html( $label ); ?></legend>
		<div class="cw-editor-segmented">
			<?php foreach ( $options as $option_value => $option_label ) : ?>
				<label>
					<input class="screen-reader-text" type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $option_value ); ?>" <?php checked( $value, $option_value ); ?>>
					<span><?php echo esc_html( $option_label ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
	</fieldset>
	<?php
};

if ( 'trigger' === $context ) :
	wp_nonce_field( 'cinderwell_popup_meta', 'cinderwell_popup_meta_nonce' );
	$trigger_type = Popup_Meta::get_value( $post->ID, 'trigger_type' );
	$location     = Popup_Meta::get_value( $post->ID, 'display_location' );
	$urls         = Popup_Meta::get_value( $post->ID, 'display_urls' );
	$post_types   = Popup_Meta::get_value( $post->ID, 'display_post_types' );
	$posts        = Popup_Meta::get_value( $post->ID, 'display_posts' );
	$exclude_urls = Popup_Meta::get_value( $post->ID, 'exclude_urls' );
	$exclude_posts= Popup_Meta::get_value( $post->ID, 'exclude_posts' );
	$public_types = get_post_types( [ 'public' => true ], 'objects' );
	unset( $public_types['attachment'] );
	$available_posts = get_posts( [
		'post_type'      => array_keys( $public_types ),
		'post_status'    => 'publish',
		'posts_per_page' => 250,
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );
	?>
	<div class="cw-editor-fields cw-popup-fields">
		<?php $cw_popup_segmented( 'cinderwell_popup_trigger_type', __( 'Trigger', 'cinderwell-popups' ), $trigger_type, [ 'manual' => __( 'Manual', 'cinderwell-popups' ), 'auto' => __( 'Automatic', 'cinderwell-popups' ) ] ); ?>

		<div id="cinderwell-popup-auto-rules" class="cw-editor-fields" <?php echo 'auto' === $trigger_type ? '' : 'hidden'; ?>>
		<label class="cw-editor-field" for="cinderwell-popup-display-location">
			<span class="cw-editor-field__label"><?php esc_html_e( 'Show on', 'cinderwell-popups' ); ?></span>
			<select class="widefat" name="cinderwell_popup_display_location" id="cinderwell-popup-display-location">
				<option value="all" <?php selected( $location, 'all' ); ?>><?php esc_html_e( 'All pages', 'cinderwell-popups' ); ?></option>
				<option value="specific_urls" <?php selected( $location, 'specific_urls' ); ?>><?php esc_html_e( 'Specific URLs', 'cinderwell-popups' ); ?></option>
				<option value="specific_post_types" <?php selected( $location, 'specific_post_types' ); ?>><?php esc_html_e( 'Specific post types', 'cinderwell-popups' ); ?></option>
				<option value="specific_posts" <?php selected( $location, 'specific_posts' ); ?>><?php esc_html_e( 'Specific pages or posts', 'cinderwell-popups' ); ?></option>
			</select>
		</label>

		<label class="cw-editor-field" data-cw-popup-rule="specific_urls" for="cinderwell-popup-display-urls" <?php echo 'specific_urls' === $location ? '' : 'hidden'; ?>>
			<span class="cw-editor-field__label"><?php esc_html_e( 'URL patterns', 'cinderwell-popups' ); ?></span>
			<textarea id="cinderwell-popup-display-urls" name="cinderwell_popup_display_urls" rows="4" class="widefat" placeholder="/contact/&#10;/blog/*"><?php echo esc_textarea( implode( "\n", $urls ) ); ?></textarea>
			<small><?php esc_html_e( 'One path per line. Use * as a wildcard.', 'cinderwell-popups' ); ?></small>
		</label>

		<div data-cw-popup-rule="specific_post_types" <?php echo 'specific_post_types' === $location ? '' : 'hidden'; ?>>
			<fieldset class="cw-editor-check-list">
				<legend class="cw-editor-field__label"><?php esc_html_e( 'Content types', 'cinderwell-popups' ); ?></legend>
				<?php foreach ( $public_types as $type ) : ?>
					<label><input type="checkbox" name="cinderwell_popup_display_post_types[]" value="<?php echo esc_attr( $type->name ); ?>" <?php checked( in_array( $type->name, $post_types, true ) ); ?>> <span><?php echo esc_html( $type->labels->singular_name ); ?></span></label>
				<?php endforeach; ?>
			</fieldset>
		</div>

		<div data-cw-popup-rule="specific_posts" <?php echo 'specific_posts' === $location ? '' : 'hidden'; ?>>
			<?php
			$cw_popup_list_name = 'cinderwell_popup_display_posts';
			$cw_popup_list_ids  = $posts;
			$cw_popup_list_id   = 'cinderwell-popup-display-posts';
			include CINDERWELL_POPUPS_PATH . 'templates/post-picker.php';
			?>
		</div>

		<details class="cw-editor-disclosure" <?php echo ( $exclude_urls || $exclude_posts ) ? 'open' : ''; ?>>
			<summary><?php esc_html_e( 'Exclusions', 'cinderwell-popups' ); ?></summary>
			<label class="cw-editor-field" for="cinderwell-popup-exclude-urls">
				<span class="cw-editor-field__label"><?php esc_html_e( 'Excluded URL patterns', 'cinderwell-popups' ); ?></span>
				<textarea id="cinderwell-popup-exclude-urls" name="cinderwell_popup_exclude_urls" rows="3" class="widefat" placeholder="/checkout/*"><?php echo esc_textarea( implode( "\n", $exclude_urls ) ); ?></textarea>
				<small><?php esc_html_e( 'Optional. One path per line.', 'cinderwell-popups' ); ?></small>
			</label>
			<?php
			$cw_popup_list_name = 'cinderwell_popup_exclude_posts';
			$cw_popup_list_ids  = $exclude_posts;
			$cw_popup_list_id   = 'cinderwell-popup-exclude-posts';
			include CINDERWELL_POPUPS_PATH . 'templates/post-picker.php';
			?>
		</details>
		</div>
	</div>
	<?php
else :
	$frequency = Popup_Meta::get_value( $post->ID, 'frequency' );
	$animation = Popup_Meta::get_value( $post->ID, 'animation' );
	$width     = Popup_Meta::get_value( $post->ID, 'width' );
	?>
	<div class="cw-editor-fields cw-popup-fields">
		<?php $cw_popup_segmented( 'cinderwell_popup_frequency', __( 'Frequency', 'cinderwell-popups' ), $frequency, [ 'once_session' => __( 'Session', 'cinderwell-popups' ), 'once_ever' => __( 'Year', 'cinderwell-popups' ), 'always' => __( 'Always', 'cinderwell-popups' ) ] ); ?>
		<?php $cw_popup_segmented( 'cinderwell_popup_animation', __( 'Animation', 'cinderwell-popups' ), $animation, [ 'fade' => __( 'Fade', 'cinderwell-popups' ), 'slide' => __( 'Slide', 'cinderwell-popups' ), 'scale' => __( 'Scale', 'cinderwell-popups' ) ] ); ?>
		<div class="cw-editor-field">
			<?php $cw_popup_segmented( 'cinderwell_popup_width', __( 'Size preset', 'cinderwell-popups' ), $width, [ 'small' => __( 'Compact', 'cinderwell-popups' ), 'medium' => __( 'Standard', 'cinderwell-popups' ), 'large' => __( 'Wide', 'cinderwell-popups' ), 'full' => __( 'Full', 'cinderwell-popups' ) ] ); ?>
			<small><?php esc_html_e( '400px, 600px, 800px, or 90% of the viewport. All presets fill small screens.', 'cinderwell-popups' ); ?></small>
		</div>
	</div>
<?php endif; ?>
