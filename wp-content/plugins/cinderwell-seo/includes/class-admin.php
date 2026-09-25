<?php
namespace Cinderwell_SEO;

defined( 'ABSPATH' ) || exit;

/** Settings, editor controls, term fields, list columns, and help integration. */
class Admin {
	private $compatibility;

	public function __construct( Compatibility $compatibility ) {
		$this->compatibility = $compatibility;
		add_filter( 'cinderwell_settings_tabs', [ $this, 'settings_tab' ], 12 );
		add_filter( 'cinderwell_settings_tab_description', [ $this, 'settings_description' ], 10, 2 );
		add_action( 'admin_post_cinderwell_save_seo', [ $this, 'save_settings' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'editor_assets' ], 35 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );
		add_action( 'admin_init', [ $this, 'register_admin_interfaces' ], 30 );
		add_action( 'pre_get_posts', [ $this, 'order_columns' ] );
		add_action( 'cinderwell_register_documentation', [ $this, 'register_documentation' ] );
	}

	public function settings_tab( $tabs ) {
		$tabs['seo'] = [
			'label'       => __( 'SEO', 'cinderwell-seo' ),
			'group'       => 'extensions',
			'description' => __( 'Control search appearance, social metadata, native sitemaps, and editor guidance.', 'cinderwell-seo' ),
			'callback'    => [ $this, 'render_settings' ],
		];
		return $tabs;
	}

	public function settings_description( $description, $tab_key ) {
		return 'seo' === $tab_key ? __( 'Control search appearance, social metadata, native sitemaps, and editor guidance.', 'cinderwell-seo' ) : $description;
	}

	public function admin_assets( $hook_suffix ) {
		$screen = get_current_screen();
		$is_seo = 'toplevel_page_cinderwell' === $hook_suffix && isset( $_GET['tab'] ) && 'seo' === sanitize_key( wp_unslash( $_GET['tab'] ) );
		$is_term = $screen && 'edit-tags' === $screen->base && in_array( $screen->taxonomy, Settings::enabled_taxonomies(), true );
		$is_list = $screen && 'edit' === $screen->base && in_array( $screen->post_type, Settings::enabled_post_types(), true );
		if ( ! $is_seo && ! $is_term && ! $is_list ) {
			return;
		}
		wp_enqueue_style( 'cinderwell-seo-admin', CINDERWELL_SEO_URL . 'assets/admin.css', [], CINDERWELL_SEO_VERSION );
		if ( $is_seo || $is_term ) {
			wp_enqueue_media();
		}
	}

	public function render_settings() {
		$settings   = Settings::get();
		$post_types = Settings::supported_post_types();
		$taxonomies = Settings::supported_taxonomies();
		$conflict   = $this->compatibility->get_conflict();
		$sitemap    = function_exists( 'get_sitemap_url' ) ? get_sitemap_url( 'index' ) : '';
		$subtabs    = [
			'appearance' => __( 'Search appearance', 'cinderwell-seo' ),
			'sitemaps'   => __( 'Sitemaps', 'cinderwell-seo' ),
			'schema'     => __( 'Schema & verification', 'cinderwell-seo' ),
		];
		$submit_labels = [
			'appearance' => __( 'Save Search Appearance', 'cinderwell-seo' ),
			'sitemaps'   => __( 'Save Sitemap Settings', 'cinderwell-seo' ),
			'schema'     => __( 'Save Schema Settings', 'cinderwell-seo' ),
		];
		$active      = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'appearance';
		$active      = array_key_exists( $active, $subtabs ) ? $active : 'appearance';
		$archives    = array_filter( $post_types, static function ( $object ) { return ! empty( $object->has_archive ); } );
		$company     = class_exists( '\\Cinderwell\\Company_Details' ) && method_exists( '\\Cinderwell\\Company_Details', 'get_settings' ) ? \Cinderwell\Company_Details::get_settings() : [];
		$organization_schema = ! empty( $company['schema_enabled'] ) && ! empty( $company['name'] );
		?>
		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'SEO settings saved.', 'cinderwell-seo' ); ?></p></div>
		<?php endif; ?>
		<?php if ( $conflict ) : ?>
			<div class="notice notice-warning inline"><p>
				<?php
				echo esc_html( sprintf(
					/* translators: %s: SEO provider name. */
					__( 'Safe standby is active because %s is managing SEO. Cinderwell settings are preserved, but no Cinderwell SEO output or editor controls are loaded.', 'cinderwell-seo' ),
					$conflict
				) );
				?>
			</p></div>
		<?php endif; ?>
		<?php if ( ! get_option( 'blog_public' ) ) : ?>
			<div class="notice notice-warning inline"><p>
				<?php esc_html_e( 'Search engines are currently discouraged from indexing this site.', 'cinderwell-seo' ); ?>
				<a href="<?php echo esc_url( admin_url( 'options-reading.php' ) ); ?>"><?php esc_html_e( 'Review Reading settings', 'cinderwell-seo' ); ?></a>
			</p></div>
		<?php endif; ?>

		<nav class="nav-tab-wrapper cw-seo-subtabs" aria-label="<?php esc_attr_e( 'SEO settings sections', 'cinderwell-seo' ); ?>">
			<?php foreach ( $subtabs as $subtab => $label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'cinderwell', 'tab' => 'seo', 'subtab' => $subtab ], admin_url( 'admin.php' ) ) ); ?>" class="nav-tab <?php echo $active === $subtab ? 'nav-tab-active' : ''; ?>" <?php echo $active === $subtab ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="cinderwell_save_seo">
			<input type="hidden" name="seo_subtab" value="<?php echo esc_attr( $active ); ?>">
			<?php wp_nonce_field( 'cinderwell_save_seo' ); ?>
			<div class="cw-settings-card-grid cw-settings-card-grid--single">
				<?php if ( 'appearance' === $active ) : ?>
				<section class="card cw-settings-card">
					<h2><?php esc_html_e( 'Search appearance', 'cinderwell-seo' ); ?></h2>
					<p><?php esc_html_e( 'These site-level values are fallbacks. Editors can customize individual public entries and terms.', 'cinderwell-seo' ); ?></p>
					<?php
					\Cinderwell\Admin_Fields::render_table( [
						'home_title' => [ 'label' => __( 'Homepage search title', 'cinderwell-seo' ), 'placeholder' => get_bloginfo( 'name' ), 'description' => __( 'The site title is shown as the placeholder and is inherited when this field is blank.', 'cinderwell-seo' ) ],
						'home_description' => [ 'label' => __( 'Homepage meta description', 'cinderwell-seo' ), 'type' => 'textarea', 'description' => __( 'Leave blank to use the WordPress tagline.', 'cinderwell-seo' ) ],
						'default_social_image_id' => [ 'label' => __( 'Default social image', 'cinderwell-seo' ), 'type' => 'media', 'description' => __( 'Used when an entry has no social image or featured image.', 'cinderwell-seo' ) ],
					], $settings, 'seo', 'cw-seo' );
					?>
				</section>

				<?php if ( $archives ) : ?>
					<section class="card cw-settings-card">
						<h2><?php esc_html_e( 'Archive metadata', 'cinderwell-seo' ); ?></h2>
						<?php foreach ( $archives as $name => $object ) : $archive = $settings['archives'][ $name ] ?? []; ?>
							<fieldset class="cw-seo-archive">
								<legend><?php echo esc_html( $object->labels->name ); ?></legend>
								<label for="cw-seo-archive-<?php echo esc_attr( $name ); ?>-title"><?php esc_html_e( 'Search title', 'cinderwell-seo' ); ?></label>
								<input class="regular-text" id="cw-seo-archive-<?php echo esc_attr( $name ); ?>-title" type="text" name="seo[archives][<?php echo esc_attr( $name ); ?>][title]" value="<?php echo esc_attr( $archive['title'] ?? '' ); ?>">
								<label for="cw-seo-archive-<?php echo esc_attr( $name ); ?>-description"><?php esc_html_e( 'Meta description', 'cinderwell-seo' ); ?></label>
								<textarea class="large-text" rows="3" id="cw-seo-archive-<?php echo esc_attr( $name ); ?>-description" name="seo[archives][<?php echo esc_attr( $name ); ?>][description]" ><?php echo esc_textarea( $archive['description'] ?? '' ); ?></textarea>
							</fieldset>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>
				<?php elseif ( 'sitemaps' === $active ) : ?>
				<section class="card cw-settings-card">
					<h2><?php esc_html_e( 'Content and sitemaps', 'cinderwell-seo' ); ?></h2>
					<p><?php esc_html_e( 'Cinderwell configures WordPress’s native XML sitemap. No separate sitemap engine is created.', 'cinderwell-seo' ); ?></p>
					<?php if ( $sitemap ) : ?>
						<p><a href="<?php echo esc_url( $sitemap ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View XML sitemap', 'cinderwell-seo' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'cinderwell-seo' ); ?></span></a></p>
					<?php endif; ?>
					<fieldset class="cw-seo-checks">
						<legend><?php esc_html_e( 'Public content types', 'cinderwell-seo' ); ?></legend>
						<p class="description"><?php esc_html_e( 'Enabled types receive editor controls and appear in the sitemap unless an individual item is excluded.', 'cinderwell-seo' ); ?></p>
						<?php foreach ( $post_types as $name => $object ) : ?>
							<label><input type="checkbox" name="seo[post_types][]" value="<?php echo esc_attr( $name ); ?>" <?php checked( in_array( $name, $settings['post_types'], true ) ); ?>> <?php echo esc_html( $object->labels->name ); ?></label>
						<?php endforeach; ?>
					</fieldset>
					<fieldset class="cw-seo-checks">
						<legend><?php esc_html_e( 'Public taxonomies', 'cinderwell-seo' ); ?></legend>
						<?php foreach ( $taxonomies as $name => $object ) : ?>
							<label><input type="checkbox" name="seo[taxonomies][]" value="<?php echo esc_attr( $name ); ?>" <?php checked( in_array( $name, $settings['taxonomies'], true ) ); ?>> <?php echo esc_html( $object->labels->name ); ?></label>
						<?php endforeach; ?>
					</fieldset>
				</section>
				<?php else : ?>
				<section class="card cw-settings-card">
					<h2><?php esc_html_e( 'Structured data', 'cinderwell-seo' ); ?></h2>
					<p><?php esc_html_e( 'Cinderwell outputs a small linked JSON-LD graph and leaves specialized schema to future modules and integrations.', 'cinderwell-seo' ); ?></p>
					<ul class="cw-seo-schema-status">
						<li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><span><strong><?php esc_html_e( 'WebSite', 'cinderwell-seo' ); ?></strong> <?php esc_html_e( 'Active for the site.', 'cinderwell-seo' ); ?></span></li>
						<li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><span><strong><?php esc_html_e( 'WebPage and Article', 'cinderwell-seo' ); ?></strong> <?php esc_html_e( 'Selected automatically from the current public entry.', 'cinderwell-seo' ); ?></span></li>
						<li class="<?php echo $organization_schema ? 'is-active' : 'is-inactive'; ?>"><span class="dashicons <?php echo $organization_schema ? 'dashicons-yes-alt' : 'dashicons-minus'; ?>" aria-hidden="true"></span><span><strong><?php esc_html_e( 'Organization', 'cinderwell-seo' ); ?></strong> <?php echo esc_html( $organization_schema ? __( 'Active from Company Details.', 'cinderwell-seo' ) : __( 'Enable Organization schema in Company Details when the site represents an organization.', 'cinderwell-seo' ) ); ?></span></li>
					</ul>
					<p class="description"><?php esc_html_e( 'Product, Event, Person, breadcrumb, and other specialized schema are not generated by the SEO add-on yet. Developers can extend the graph with the cinderwell_seo_schema_graph filter.', 'cinderwell-seo' ); ?></p>
				</section>

				<section class="card cw-settings-card">
					<h2><?php esc_html_e( 'Site verification', 'cinderwell-seo' ); ?></h2>
					<p><?php esc_html_e( 'Paste the verification token or the complete verification meta tag. Cinderwell stores and outputs only the token.', 'cinderwell-seo' ); ?></p>
					<?php
					\Cinderwell\Admin_Fields::render_table( [
						'google_verification' => [ 'label' => __( 'Google Search Console', 'cinderwell-seo' ) ],
						'bing_verification' => [ 'label' => __( 'Bing Webmaster Tools', 'cinderwell-seo' ) ],
					], $settings, 'seo', 'cw-seo' );
					?>
				</section>

				<section class="card cw-settings-card">
					<h2><?php esc_html_e( 'Data retention', 'cinderwell-seo' ); ?></h2>
					<label><input type="checkbox" name="seo[remove_data]" value="1" <?php checked( $settings['remove_data'] ); ?>> <?php esc_html_e( 'Remove all Cinderwell SEO settings and metadata when the plugin is deleted', 'cinderwell-seo' ); ?></label>
					<p class="description"><?php esc_html_e( 'Deactivation always preserves data.', 'cinderwell-seo' ); ?></p>
				</section>
				<?php endif; ?>
			</div>
			<?php submit_button( $submit_labels[ $active ] ); ?>
		</form>
		<?php
	}

	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage SEO settings.', 'cinderwell-seo' ) );
		}
		check_admin_referer( 'cinderwell_save_seo' );
		$submitted = isset( $_POST['seo'] ) ? (array) wp_unslash( $_POST['seo'] ) : [];
		$subtab    = isset( $_POST['seo_subtab'] ) ? sanitize_key( wp_unslash( $_POST['seo_subtab'] ) ) : 'appearance';
		$subtab    = in_array( $subtab, [ 'appearance', 'sitemaps', 'schema' ], true ) ? $subtab : 'appearance';
		$merged    = Settings::get();

		if ( 'appearance' === $subtab ) {
			foreach ( [ 'home_title', 'home_description', 'default_social_image_id', 'archives' ] as $key ) {
				$merged[ $key ] = $submitted[ $key ] ?? ( 'archives' === $key ? [] : '' );
			}
		} elseif ( 'sitemaps' === $subtab ) {
			$merged['post_types'] = $submitted['post_types'] ?? [];
			$merged['taxonomies'] = $submitted['taxonomies'] ?? [];
		} else {
			foreach ( [ 'google_verification', 'bing_verification' ] as $key ) {
				$merged[ $key ] = $submitted[ $key ] ?? '';
			}
			$merged['remove_data'] = ! empty( $submitted['remove_data'] );
		}

		update_option( Settings::OPTION, Settings::sanitize( $merged ), false );
		wp_safe_redirect( add_query_arg( [ 'page' => 'cinderwell', 'tab' => 'seo', 'subtab' => $subtab, 'updated' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function editor_assets() {
		if ( $this->compatibility->has_conflict() ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, Settings::enabled_post_types(), true ) ) {
			return;
		}
		$asset_path = CINDERWELL_SEO_DIR . 'build/editor/index.asset.php';
		if ( ! is_readable( $asset_path ) || ! is_readable( CINDERWELL_SEO_DIR . 'build/editor/index.js' ) ) {
			return;
		}
		$asset = include $asset_path;
		$post_id = get_the_ID();
		$search_policy = $post_id && function_exists( 'cinderwell_portal_get_search_policy' ) ? (array) cinderwell_portal_get_search_policy( $post_id ) : [];
		wp_enqueue_script( 'cinderwell-seo-editor', CINDERWELL_SEO_URL . 'build/editor/index.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'cinderwell-seo-editor', 'cinderwell-seo' );
		wp_localize_script( 'cinderwell-seo-editor', 'cinderwellSeoEditor', [
			'metaPrefix' => Meta::PREFIX,
			'siteName'   => get_bloginfo( 'name' ),
			'homeUrl'    => home_url( '/' ),
			'searchPolicy' => $search_policy,
		] );
		if ( is_readable( CINDERWELL_SEO_DIR . 'build/editor/style-index.css' ) ) {
			wp_enqueue_style( 'cinderwell-seo-editor', CINDERWELL_SEO_URL . 'build/editor/style-index.css', [ 'wp-components', 'cinderwell-editor-controls' ], $asset['version'] );
		}
	}

	public function register_admin_interfaces() {
		foreach ( Settings::enabled_taxonomies() as $taxonomy ) {
			add_action( $taxonomy . '_add_form_fields', function () use ( $taxonomy ) { $this->term_fields( null, $taxonomy ); } );
			add_action( $taxonomy . '_edit_form_fields', function ( $term ) use ( $taxonomy ) { $this->term_fields( $term, $taxonomy ); } );
			add_action( 'created_' . $taxonomy, [ $this, 'save_term' ] );
			add_action( 'edited_' . $taxonomy, [ $this, 'save_term' ] );
		}
		foreach ( Settings::enabled_post_types() as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", [ $this, 'columns' ] );
			add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'column_value' ], 10, 2 );
			add_filter( "manage_edit-{$post_type}_sortable_columns", [ $this, 'sortable_columns' ] );
		}
	}

	public function term_fields( $term, $taxonomy ) {
		$term_id = $term instanceof \WP_Term ? $term->term_id : 0;
		$values  = $term_id ? Meta::values( $term_id, 'term' ) : [];
		$fields  = $this->field_schema();
		echo '<div class="cw-seo-term-fields"><h2>' . esc_html__( 'Search appearance', 'cinderwell-seo' ) . '</h2>';
		wp_nonce_field( 'cinderwell_seo_term', 'cinderwell_seo_term_nonce' );
		\Cinderwell\Admin_Fields::render_table( $fields, $values, 'cinderwell_seo', 'cw-seo-term' );
		echo '</div>';
	}

	public function save_term( $term_id ) {
		$nonce = isset( $_POST['cinderwell_seo_term_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['cinderwell_seo_term_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'cinderwell_seo_term' ) ) {
			return;
		}
		$term = get_term( $term_id );
		$tax  = $term && ! is_wp_error( $term ) ? get_taxonomy( $term->taxonomy ) : null;
		if ( ! $tax || ! current_user_can( $tax->cap->manage_terms ) ) {
			return;
		}
		$submitted = isset( $_POST['cinderwell_seo'] ) ? (array) wp_unslash( $_POST['cinderwell_seo'] ) : [];
		$values    = \Cinderwell\Admin_Fields::sanitize_values( $this->field_schema(), $submitted );
		foreach ( $values as $key => $value ) {
			update_term_meta( $term_id, Meta::key( $key ), $value );
		}
	}

	private function field_schema() {
		return [
			'title' => [ 'label' => __( 'Search title', 'cinderwell-seo' ), 'description' => __( 'Leave blank to use the WordPress title.', 'cinderwell-seo' ) ],
			'description' => [ 'label' => __( 'Meta description', 'cinderwell-seo' ), 'type' => 'textarea', 'description' => __( 'Leave blank to inherit the WordPress description.', 'cinderwell-seo' ) ],
			'focus_phrase' => [ 'label' => __( 'Focus phrase', 'cinderwell-seo' ), 'description' => __( 'Used for analysis only; it is not output as a meta keyword.', 'cinderwell-seo' ) ],
			'canonical' => [ 'label' => __( 'Canonical URL', 'cinderwell-seo' ), 'type' => 'url' ],
			'robots_index' => [ 'label' => __( 'Indexing', 'cinderwell-seo' ), 'type' => 'select', 'options' => [ '' => __( 'Inherit', 'cinderwell-seo' ), 'index' => __( 'Index', 'cinderwell-seo' ), 'noindex' => __( 'Noindex', 'cinderwell-seo' ) ] ],
			'robots_follow' => [ 'label' => __( 'Link following', 'cinderwell-seo' ), 'type' => 'select', 'options' => [ '' => __( 'Inherit', 'cinderwell-seo' ), 'follow' => __( 'Follow', 'cinderwell-seo' ), 'nofollow' => __( 'Nofollow', 'cinderwell-seo' ) ] ],
			'social_title' => [ 'label' => __( 'Social title', 'cinderwell-seo' ) ],
			'social_description' => [ 'label' => __( 'Social description', 'cinderwell-seo' ), 'type' => 'textarea' ],
			'social_image_id' => [ 'label' => __( 'Social image', 'cinderwell-seo' ), 'type' => 'media' ],
		];
	}

	public function columns( $columns ) {
		$columns['cinderwell_seo'] = __( 'Search visibility', 'cinderwell-seo' );
		return $columns;
	}

	public function column_value( $column, $post_id ) {
		if ( 'cinderwell_seo' !== $column ) {
			return;
		}
		$status  = Visibility::resolve( $post_id );
		$score   = absint( get_post_meta( $post_id, Meta::key( 'score' ), true ) );
		$version = get_post_meta( $post_id, Meta::key( 'analysis_version' ), true );
		$icons   = [
			'indexable'          => 'dashicons-yes-alt',
			'site-hidden'        => 'dashicons-warning',
			'noindex'            => 'dashicons-hidden',
			'canonical-elsewhere'=> 'dashicons-external',
			'not-public'         => 'dashicons-lock',
			'managed'            => 'dashicons-info-outline',
			'portal-protected'   => 'dashicons-lock',
			'portal-utility'     => 'dashicons-lock',
		];
		$icon    = $icons[ $status['code'] ] ?? 'dashicons-info-outline';
		$status_tooltip_id = 'cw-seo-status-help-' . $post_id;
		$score_tooltip_id  = 'cw-seo-score-help-' . $post_id;
		$is_portal_policy  = in_array( $status['code'], [ 'portal-protected', 'portal-utility' ], true );
		if ( $is_portal_policy ) {
			$score_output = sprintf(
				'%1$s <span class="cw-seo-tooltip-trigger cw-seo-score-pending" tabindex="0" aria-describedby="%2$s"><span aria-hidden="true">—</span><span class="screen-reader-text">%3$s</span><span class="cw-seo-tooltip" id="%2$s" role="tooltip">%3$s</span></span>',
				esc_html__( 'SEO:', 'cinderwell-seo' ),
				esc_attr( $score_tooltip_id ),
				esc_html__( 'Not applicable while Members Portal enforces noindex', 'cinderwell-seo' )
			);
		} elseif ( Meta::ANALYSIS_VERSION === $version ) {
			$score_output = esc_html( sprintf( __( 'SEO: %d/100', 'cinderwell-seo' ), $score ) );
		} else {
			$score_output = sprintf(
				'%1$s <span class="cw-seo-tooltip-trigger cw-seo-score-pending" tabindex="0" aria-describedby="%2$s"><span aria-hidden="true">—</span><span class="screen-reader-text">%3$s</span><span class="cw-seo-tooltip" id="%2$s" role="tooltip">%3$s</span></span>',
				esc_html__( 'SEO:', 'cinderwell-seo' ),
				esc_attr( $score_tooltip_id ),
				esc_html__( 'Analyze on next save', 'cinderwell-seo' )
			);
		}
		printf(
			'<span class="cw-seo-status cw-seo-status--%1$s"><span class="cw-seo-tooltip-trigger cw-seo-status__indicator" tabindex="0" role="img" aria-label="%2$s" aria-describedby="%3$s"><span class="dashicons %4$s" aria-hidden="true"></span><span class="cw-seo-tooltip" id="%3$s" role="tooltip">%5$s</span></span><span class="cw-seo-status__score">%6$s</span></span>',
			esc_attr( $status['code'] ),
			esc_attr( $status['label'] ),
			esc_attr( $status_tooltip_id ),
			esc_attr( $icon ),
			esc_html( trim( $status['label'] . '. ' . $status['description'] ) ),
			$score_output
		);
	}

	public function sortable_columns( $columns ) {
		$columns['cinderwell_seo'] = 'cinderwell_seo_score';
		return $columns;
	}

	public function order_columns( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || 'cinderwell_seo_score' !== $query->get( 'orderby' ) ) {
			return;
		}
		$query->set( 'meta_key', Meta::key( 'score' ) );
		$query->set( 'orderby', 'meta_value_num' );
	}

	public function register_documentation( $registry ) {
		$registry->register_directory( 'cinderwell-seo', CINDERWELL_SEO_DIR . 'help' );
	}
}
