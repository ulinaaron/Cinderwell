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
	<fieldset class="cw-popup-fieldset">
		<legend><?php esc_html_e( 'Trigger', 'cinderwell-popups' ); ?></legend>
		<label><input type="radio" name="cinderwell_popup_trigger_type" value="manual" <?php checked( $trigger_type, 'manual' ); ?>> <?php esc_html_e( 'Manual only', 'cinderwell-popups' ); ?></label>
		<label><input type="radio" name="cinderwell_popup_trigger_type" value="auto" <?php checked( $trigger_type, 'auto' ); ?>> <?php esc_html_e( 'Automatic on page match', 'cinderwell-popups' ); ?></label>
	</fieldset>

	<div id="cinderwell-popup-auto-rules" <?php echo 'auto' === $trigger_type ? '' : 'hidden'; ?>>
		<p class="cw-popup-field">
			<label for="cinderwell-popup-display-location"><?php esc_html_e( 'Show on', 'cinderwell-popups' ); ?></label>
			<select class="widefat" name="cinderwell_popup_display_location" id="cinderwell-popup-display-location">
				<option value="all" <?php selected( $location, 'all' ); ?>><?php esc_html_e( 'All pages', 'cinderwell-popups' ); ?></option>
				<option value="specific_urls" <?php selected( $location, 'specific_urls' ); ?>><?php esc_html_e( 'Specific URLs', 'cinderwell-popups' ); ?></option>
				<option value="specific_post_types" <?php selected( $location, 'specific_post_types' ); ?>><?php esc_html_e( 'Specific post types', 'cinderwell-popups' ); ?></option>
				<option value="specific_posts" <?php selected( $location, 'specific_posts' ); ?>><?php esc_html_e( 'Specific pages or posts', 'cinderwell-popups' ); ?></option>
			</select>
		</p>

		<div data-cw-popup-rule="specific_urls" <?php echo 'specific_urls' === $location ? '' : 'hidden'; ?>>
			<label for="cinderwell-popup-display-urls"><?php esc_html_e( 'URL patterns', 'cinderwell-popups' ); ?></label>
			<textarea id="cinderwell-popup-display-urls" name="cinderwell_popup_display_urls" rows="4" class="widefat" placeholder="/contact/&#10;/blog/*"><?php echo esc_textarea( implode( "\n", $urls ) ); ?></textarea>
			<p class="description"><?php esc_html_e( 'One path per line. Use * as a wildcard.', 'cinderwell-popups' ); ?></p>
		</div>

		<div data-cw-popup-rule="specific_post_types" <?php echo 'specific_post_types' === $location ? '' : 'hidden'; ?>>
			<fieldset class="cw-popup-fieldset">
				<legend><?php esc_html_e( 'Post types', 'cinderwell-popups' ); ?></legend>
				<?php foreach ( $public_types as $type ) : ?>
					<label><input type="checkbox" name="cinderwell_popup_display_post_types[]" value="<?php echo esc_attr( $type->name ); ?>" <?php checked( in_array( $type->name, $post_types, true ) ); ?>> <?php echo esc_html( $type->labels->singular_name ); ?></label>
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

		<hr>
		<p class="cw-popup-field">
			<label for="cinderwell-popup-exclude-urls"><?php esc_html_e( 'Excluded URL patterns', 'cinderwell-popups' ); ?></label>
			<textarea id="cinderwell-popup-exclude-urls" name="cinderwell_popup_exclude_urls" rows="3" class="widefat" placeholder="/checkout/*"><?php echo esc_textarea( implode( "\n", $exclude_urls ) ); ?></textarea>
		</p>
		<?php
		$cw_popup_list_name = 'cinderwell_popup_exclude_posts';
		$cw_popup_list_ids  = $exclude_posts;
		$cw_popup_list_id   = 'cinderwell-popup-exclude-posts';
		include CINDERWELL_POPUPS_PATH . 'templates/post-picker.php';
		?>
	</div>
	<?php
else :
	$frequency = Popup_Meta::get_value( $post->ID, 'frequency' );
	$animation = Popup_Meta::get_value( $post->ID, 'animation' );
	$width     = Popup_Meta::get_value( $post->ID, 'width' );
	?>
	<p class="cw-popup-field">
		<label for="cinderwell-popup-frequency"><?php esc_html_e( 'Frequency', 'cinderwell-popups' ); ?></label>
		<select class="widefat" id="cinderwell-popup-frequency" name="cinderwell_popup_frequency">
			<option value="once_session" <?php selected( $frequency, 'once_session' ); ?>><?php esc_html_e( 'Once per session', 'cinderwell-popups' ); ?></option>
			<option value="once_ever" <?php selected( $frequency, 'once_ever' ); ?>><?php esc_html_e( 'Once per year', 'cinderwell-popups' ); ?></option>
			<option value="always" <?php selected( $frequency, 'always' ); ?>><?php esc_html_e( 'Always', 'cinderwell-popups' ); ?></option>
		</select>
	</p>
	<p class="cw-popup-field">
		<label for="cinderwell-popup-animation"><?php esc_html_e( 'Animation', 'cinderwell-popups' ); ?></label>
		<select class="widefat" id="cinderwell-popup-animation" name="cinderwell_popup_animation">
			<option value="fade" <?php selected( $animation, 'fade' ); ?>><?php esc_html_e( 'Fade', 'cinderwell-popups' ); ?></option>
			<option value="slide" <?php selected( $animation, 'slide' ); ?>><?php esc_html_e( 'Slide up', 'cinderwell-popups' ); ?></option>
			<option value="scale" <?php selected( $animation, 'scale' ); ?>><?php esc_html_e( 'Scale', 'cinderwell-popups' ); ?></option>
		</select>
	</p>
	<p class="cw-popup-field">
		<label for="cinderwell-popup-width"><?php esc_html_e( 'Size preset', 'cinderwell-popups' ); ?></label>
		<select class="widefat" id="cinderwell-popup-width" name="cinderwell_popup_width">
			<option value="small" <?php selected( $width, 'small' ); ?>><?php esc_html_e( 'Compact — 400px', 'cinderwell-popups' ); ?></option>
			<option value="medium" <?php selected( $width, 'medium' ); ?>><?php esc_html_e( 'Standard — 600px', 'cinderwell-popups' ); ?></option>
			<option value="large" <?php selected( $width, 'large' ); ?>><?php esc_html_e( 'Wide — 800px', 'cinderwell-popups' ); ?></option>
			<option value="full" <?php selected( $width, 'full' ); ?>><?php esc_html_e( 'Full — 90% viewport', 'cinderwell-popups' ); ?></option>
		</select>
		<span class="description"><?php esc_html_e( 'All presets become full screen on small devices.', 'cinderwell-popups' ); ?></span>
	</p>
<?php endif; ?>
