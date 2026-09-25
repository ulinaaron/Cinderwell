<?php
namespace Cinderwell_Events;

defined( 'ABSPATH' ) || exit;

/** Settings, editor assets, and Cinderwell help integration. */
class Admin {
	private $sessions;

	public function __construct( Session_Repository $sessions ) {
		$this->sessions = $sessions;
		add_filter( 'cinderwell_settings_tabs', [ $this, 'settings_tab' ], 11 );
		add_action( 'admin_post_cinderwell_save_events', [ $this, 'save_settings' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'editor_assets' ], 30 );
		add_action( 'cinderwell_register_documentation', [ $this, 'register_documentation' ] );
	}

	public function settings_tab( $tabs ) {
		$tabs['events'] = [
			'label'       => __( 'Events', 'cinderwell-events' ),
			'group'       => 'extensions',
			'description' => __( 'Configure Event publishing and the URL structure used by Event pages.', 'cinderwell-events' ),
			'callback'    => [ $this, 'render_settings' ],
		];
		return $tabs;
	}

	public function render_settings() {
		$settings = Event_Post_Type::settings();
		?>
		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Event settings saved.', 'cinderwell-events' ); ?></p></div>
		<?php endif; ?>
		<section class="card cw-settings-card">
			<h2><?php esc_html_e( 'Events', 'cinderwell-events' ); ?></h2>
			<p><?php esc_html_e( 'Publish simple one-time Events by default, with an optional Session mode for Events that happen more than once.', 'cinderwell-events' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cinderwell_save_events">
				<?php
				wp_nonce_field( 'cinderwell_save_events' );
				\Cinderwell\Admin_Fields::render_table( [
					'slug' => [
						'label'       => __( 'Event URL base', 'cinderwell-events' ),
						'type'        => 'slug',
						'default'     => 'events',
						'description' => __( 'Used for the archive, categories, and individual Event URLs.', 'cinderwell-events' ),
					],
				], $settings, 'events', 'cw-events' );
				submit_button( __( 'Save Event Settings', 'cinderwell-events' ) );
				?>
			</form>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . Event_Post_Type::POST_TYPE ) ); ?>"><?php esc_html_e( 'Manage Events', 'cinderwell-events' ); ?></a></p>
		</section>
		<?php
	}

	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage Event settings.', 'cinderwell-events' ) );
		}
		check_admin_referer( 'cinderwell_save_events' );
		$submitted = isset( $_POST['events'] ) ? (array) wp_unslash( $_POST['events'] ) : [];
		$slug      = Event_Post_Type::sanitize_rewrite_base( $submitted['slug'] ?? '' );
		update_option( Event_Post_Type::OPTION, [ 'slug' => $slug ] );
		update_option( 'cinderwell_flush_rewrite_rules', 1, false );
		wp_safe_redirect( add_query_arg( [ 'page' => 'cinderwell', 'tab' => 'events', 'updated' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function editor_assets() {
		$screen = get_current_screen();
		if ( ! $screen || Event_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}
		$asset_path = CINDERWELL_EVENTS_PATH . 'build/editor/sessions-panel.asset.php';
		$script     = CINDERWELL_EVENTS_PATH . 'build/editor/sessions-panel.js';
		if ( ! is_readable( $asset_path ) || ! is_readable( $script ) ) {
			return;
		}
		$asset = include $asset_path;
		wp_enqueue_script(
			'cinderwell-events-editor',
			CINDERWELL_EVENTS_URL . 'build/editor/sessions-panel.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);
		wp_set_script_translations( 'cinderwell-events-editor', 'cinderwell-events' );
		wp_localize_script( 'cinderwell-events-editor', 'cinderwellEventsEditor', [
			'timezone' => wp_timezone()->getName(),
			'modeMeta' => Event_Post_Type::MODE_META,
		] );
		$style = CINDERWELL_EVENTS_PATH . 'build/editor/sessions-panel.css';
		if ( is_readable( $style ) ) {
			wp_enqueue_style( 'cinderwell-events-editor', CINDERWELL_EVENTS_URL . 'build/editor/sessions-panel.css', [ 'wp-components', 'cinderwell-editor-controls' ], $asset['version'] );
		}
	}

	public function register_documentation( $registry ) {
		$registry->register_directory( 'cinderwell-events', CINDERWELL_EVENTS_PATH . 'help' );
	}
}
