<?php
/**
 * Settings for shared editor utilities.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Editor_Utilities {

	const OPTION = 'cinderwell_advanced_settings';
	const GROUP  = 'cinderwell_advanced';

	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public static function get_defaults() {
		return [
			'block_settings_clipboard' => true,
		];
	}

	public static function get_settings() {
		$saved = get_option( self::OPTION, [] );
		return wp_parse_args( is_array( $saved ) ? $saved : [], self::get_defaults() );
	}

	public static function block_settings_clipboard_enabled() {
		$settings = self::get_settings();
		return (bool) apply_filters( 'cinderwell_enable_block_settings_clipboard', ! empty( $settings['block_settings_clipboard'] ) );
	}

	public function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION,
			[
				'type'              => 'array',
				'default'           => self::get_defaults(),
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
			]
		);
	}

	public function sanitize_settings( $value ) {
		$value = is_array( $value ) ? $value : [];
		return [
			'block_settings_clipboard' => ! empty( $value['block_settings_clipboard'] ),
		];
	}

	public static function render_settings() {
		$settings = self::get_settings();
		?>
		<div class="card" style="max-width: 760px; margin-top: 20px;">
			<h2><?php esc_html_e( 'Advanced', 'cinderwell' ); ?></h2>
			<p><?php esc_html_e( 'Control optional editor conveniences and integration behavior.', 'cinderwell' ); ?></p>
			<form action="options.php" method="post">
				<?php settings_fields( self::GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Block settings clipboard', 'cinderwell' ); ?></th>
						<td>
							<label for="cinderwell-block-settings-clipboard">
								<input
									type="checkbox"
									id="cinderwell-block-settings-clipboard"
									name="<?php echo esc_attr( self::OPTION ); ?>[block_settings_clipboard]"
									value="1"
									<?php checked( ! empty( $settings['block_settings_clipboard'] ) ); ?>
								>
								<?php esc_html_e( 'Enable Copy settings and Paste settings for Cinderwell blocks', 'cinderwell' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Transfers compatible layout, appearance, spacing, visibility, animation, and image-presentation settings without replacing text, links, or media.', 'cinderwell' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Advanced Settings', 'cinderwell' ) ); ?>
			</form>
		</div>
		<?php
	}
}
