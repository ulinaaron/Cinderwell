<?php
/**
 * Shared popup post picker.
 *
 * @var string $cw_popup_list_name
 * @var array  $cw_popup_list_ids
 * @var string $cw_popup_list_id
 * @var array  $available_posts
 *
 * @package Cinderwell_Popups
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cw-popup-post-picker" data-list-name="<?php echo esc_attr( $cw_popup_list_name ); ?>">
	<label for="<?php echo esc_attr( $cw_popup_list_id ); ?>-select"><?php esc_html_e( 'Pages or posts', 'cinderwell-popups' ); ?></label>
	<div class="cw-popup-post-picker__add">
		<select id="<?php echo esc_attr( $cw_popup_list_id ); ?>-select" class="widefat">
			<option value=""><?php esc_html_e( 'Select content…', 'cinderwell-popups' ); ?></option>
			<?php foreach ( $available_posts as $available_post ) : ?>
				<option value="<?php echo esc_attr( $available_post->ID ); ?>"><?php echo esc_html( get_post_type_object( $available_post->post_type )->labels->singular_name . ': ' . get_the_title( $available_post ) ); ?></option>
			<?php endforeach; ?>
		</select>
		<button type="button" class="button cw-popup-add-post"><?php esc_html_e( 'Add', 'cinderwell-popups' ); ?></button>
	</div>
	<ul class="cw-popup-post-list" id="<?php echo esc_attr( $cw_popup_list_id ); ?>-list">
		<?php foreach ( $cw_popup_list_ids as $selected_id ) : ?>
			<?php if ( get_post( $selected_id ) ) : ?>
				<li data-id="<?php echo esc_attr( $selected_id ); ?>">
					<span><?php echo esc_html( get_the_title( $selected_id ) ); ?></span>
					<button type="button" class="button-link-delete cw-popup-remove-post"><?php esc_html_e( 'Remove', 'cinderwell-popups' ); ?></button>
					<input type="hidden" name="<?php echo esc_attr( $cw_popup_list_name ); ?>[]" value="<?php echo esc_attr( $selected_id ); ?>">
				</li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
</div>
