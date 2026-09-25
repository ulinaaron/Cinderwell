<?php
/**
 * Cinderwell Snippet Manager admin screens.
 *
 * @package Cinderwell_Snippets
 */

namespace Cinderwell_Snippets;

defined( 'ABSPATH' ) || exit;

class Admin {
	const PAGE = 'cinderwell-snippets';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_post_cinderwell_snippet_save', [ $this, 'handle_save' ] );
		add_action( 'admin_post_cinderwell_snippet_delete', [ $this, 'handle_delete' ] );
		add_action( 'admin_post_cinderwell_snippet_bulk', [ $this, 'handle_bulk' ] );
		add_action( 'admin_post_cinderwell_snippets_safe_mode', [ $this, 'handle_safe_mode' ] );
		add_filter( 'parent_file', [ $this, 'highlight_parent_menu' ] );
	}

	public function add_menu() {
		add_submenu_page(
			'cinderwell',
			__( 'Snippet Manager', 'cinderwell-snippets' ),
			__( 'Snippets', 'cinderwell-snippets' ),
			'manage_options',
			self::PAGE,
			[ $this, 'render' ]
		);
	}

	public function highlight_parent_menu( $parent_file ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		return self::PAGE === $page ? 'cinderwell' : $parent_file;
	}

	public function enqueue_assets( $hook ) {
		if ( 'cinderwell_page_' . self::PAGE !== $hook ) return;

		wp_enqueue_style(
			'cinderwell-snippets-admin',
			CINDERWELL_SNIPPETS_URL . 'assets/admin.css',
			wp_style_is( 'cinderwell-editor-controls', 'registered' ) ? [ 'cinderwell-editor-controls' ] : [],
			CINDERWELL_SNIPPETS_VERSION
		);

		$action          = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$editor_settings = [];
		if ( in_array( $action, [ 'edit', 'new' ], true ) ) {
			foreach ( [ 'php' => 'text/x-php', 'js' => 'text/html', 'css' => 'text/css', 'html' => 'text/html' ] as $type => $mime ) {
				$settings = wp_enqueue_code_editor( [ 'type' => $mime ] );
				if ( is_array( $settings ) ) {
					$settings['codemirror']['theme'] = 'cinderwell-one-dark';
				}
				$editor_settings[ $type ] = $settings;
			}
		}
		wp_enqueue_script(
			'cinderwell-snippets-admin',
			CINDERWELL_SNIPPETS_URL . 'assets/admin.js',
			[ 'jquery' ],
			CINDERWELL_SNIPPETS_VERSION,
			true
		);
		wp_localize_script( 'cinderwell-snippets-admin', 'cinderwellSnippetsAdmin', [
			'editorSettings' => $editor_settings,
			'locations'      => Repository::get_locations(),
			'queryLocations' => [ 'frontend', 'shortcode', 'frontend_head', 'frontend_footer', 'body_open', 'before_content', 'after_content' ],
			'shortcodeTypes' => [ 'php', 'html' ],
			'isNew'          => empty( $_GET['snippet'] ),
			'starterCode'    => [
				'php'  => "<?php\n\n",
				'js'   => "<script>\n\n</script>",
				'css'  => '',
				'html' => '',
			],
			'strings'        => [
				'changeType' => __( 'Change the snippet type? The code will be kept, but its editor mode and run location will change.', 'cinderwell-snippets' ),
				'codeHelp'   => [
					'php'  => __( 'New PHP snippets include the opening tag. A closing PHP tag is not needed.', 'cinderwell-snippets' ),
					'js'   => __( 'The script tags are shown for context and normalized automatically when the snippet runs.', 'cinderwell-snippets' ),
					'css'  => __( 'CSS is automatically output inside a style element.', 'cinderwell-snippets' ),
					'html' => __( 'HTML is output as entered at the selected location.', 'cinderwell-snippets' ),
				],
			],
		] );
	}

	public function render() {
		$this->guard();
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		if ( in_array( $action, [ 'new', 'edit' ], true ) ) {
			$this->render_editor();
			return;
		}
		$this->render_list();
	}

	private function render_header( $title, $description = '' ) {
		?>
		<header class="cw-snippets-header">
			<div>
				<span class="cw-snippets-eyebrow"><?php esc_html_e( 'Cinderwell Add-On', 'cinderwell-snippets' ); ?></span>
				<h1><?php echo esc_html( $title ); ?></h1>
				<?php if ( $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
			</div>
			<span class="cw-snippets-version"><?php echo esc_html( 'v' . CINDERWELL_SNIPPETS_VERSION ); ?></span>
		</header>
		<?php
	}

	private function render_list() {
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$type   = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$paged  = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$args   = [
			'post_type'      => Post_Type::POST_TYPE,
			'post_status'    => [ 'publish', 'draft' ],
			'posts_per_page' => 20,
			'paged'          => $paged,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		];
		if ( 'active' === $status ) $args['post_status'] = 'publish';
		if ( 'disabled' === $status ) $args['post_status'] = 'draft';
		if ( 'failed' === $status ) {
			$args['post_status'] = 'draft';
			$args['meta_query']   = [ [ 'key' => Repository::META_FAILED, 'value' => '1' ] ];
		}
		if ( $type && isset( Repository::get_types()[ $type ] ) ) {
			$args['meta_query'][] = [ 'key' => Repository::META_TYPE, 'value' => $type ];
		}
		if ( $search ) $args['s'] = $search;
		$query = new \WP_Query( $args );

		$new_url    = add_query_arg( [ 'page' => self::PAGE, 'action' => 'new' ], admin_url( 'admin.php' ) );
		$export_url = wp_nonce_url( add_query_arg( 'action', 'cinderwell_snippets_export', admin_url( 'admin-post.php' ) ), 'cinderwell_snippets_export' );
		?>
		<div class="wrap cw-snippets-wrap">
			<?php $this->render_header( __( 'Snippet Manager', 'cinderwell-snippets' ), __( 'Add focused site behavior without editing theme or plugin files.', 'cinderwell-snippets' ) ); ?>
			<?php $this->render_notice(); ?>
			<?php $this->render_safe_mode(); ?>

			<div class="cw-snippets-toolbar">
				<a class="button button-primary" href="<?php echo esc_url( $new_url ); ?>"><?php esc_html_e( 'Add snippet', 'cinderwell-snippets' ); ?></a>
				<a class="button" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export all', 'cinderwell-snippets' ); ?></a>
			</div>

			<form method="get" class="cw-snippets-filters">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE ); ?>">
				<label class="screen-reader-text" for="cw-snippet-status"><?php esc_html_e( 'Filter by status', 'cinderwell-snippets' ); ?></label>
				<select id="cw-snippet-status" name="status">
					<option value=""><?php esc_html_e( 'All statuses', 'cinderwell-snippets' ); ?></option>
					<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'cinderwell-snippets' ); ?></option>
					<option value="disabled" <?php selected( $status, 'disabled' ); ?>><?php esc_html_e( 'Disabled', 'cinderwell-snippets' ); ?></option>
					<option value="failed" <?php selected( $status, 'failed' ); ?>><?php esc_html_e( 'Needs attention', 'cinderwell-snippets' ); ?></option>
				</select>
				<label class="screen-reader-text" for="cw-snippet-type-filter"><?php esc_html_e( 'Filter by type', 'cinderwell-snippets' ); ?></label>
				<select id="cw-snippet-type-filter" name="type"><option value=""><?php esc_html_e( 'All types', 'cinderwell-snippets' ); ?></option>
					<?php foreach ( Repository::get_types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $type, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
				</select>
				<label class="screen-reader-text" for="cw-snippet-search"><?php esc_html_e( 'Search snippets', 'cinderwell-snippets' ); ?></label>
				<input id="cw-snippet-search" type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search snippets', 'cinderwell-snippets' ); ?>">
				<button class="button" type="submit"><?php esc_html_e( 'Filter', 'cinderwell-snippets' ); ?></button>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cinderwell_snippet_bulk">
				<?php wp_nonce_field( 'cinderwell_snippet_bulk' ); ?>
				<div class="cw-snippets-bulk">
					<label class="screen-reader-text" for="cw-snippet-bulk-action"><?php esc_html_e( 'Bulk action', 'cinderwell-snippets' ); ?></label>
					<select id="cw-snippet-bulk-action" name="bulk_action" required>
						<option value=""><?php esc_html_e( 'Bulk actions', 'cinderwell-snippets' ); ?></option>
						<option value="enable"><?php esc_html_e( 'Enable', 'cinderwell-snippets' ); ?></option>
						<option value="disable"><?php esc_html_e( 'Disable', 'cinderwell-snippets' ); ?></option>
						<option value="export"><?php esc_html_e( 'Export', 'cinderwell-snippets' ); ?></option>
						<option value="trash"><?php esc_html_e( 'Move to Trash', 'cinderwell-snippets' ); ?></option>
					</select>
					<button class="button" type="submit"><?php esc_html_e( 'Apply', 'cinderwell-snippets' ); ?></button>
				</div>
				<div class="cw-snippets-table-wrap">
				<table class="widefat striped cw-snippets-table">
					<thead><tr><td class="check-column"><input type="checkbox" data-cw-check-all aria-label="<?php esc_attr_e( 'Select all snippets', 'cinderwell-snippets' ); ?>"></td><th><?php esc_html_e( 'Snippet', 'cinderwell-snippets' ); ?></th><th><?php esc_html_e( 'Type', 'cinderwell-snippets' ); ?></th><th><?php esc_html_e( 'Runs', 'cinderwell-snippets' ); ?></th><th><?php esc_html_e( 'Priority', 'cinderwell-snippets' ); ?></th><th><?php esc_html_e( 'Status', 'cinderwell-snippets' ); ?></th></tr></thead>
					<tbody>
					<?php if ( ! $query->posts ) : ?><tr><td colspan="6" class="cw-snippets-empty"><?php esc_html_e( 'No snippets found.', 'cinderwell-snippets' ); ?></td></tr><?php endif; ?>
					<?php foreach ( $query->posts as $post ) : $item = Repository::normalize_post( $post ); ?>
						<tr>
							<th class="check-column"><input type="checkbox" name="snippet_ids[]" value="<?php echo esc_attr( $item['id'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Select %s', 'cinderwell-snippets' ), $item['title'] ) ); ?>"></th>
							<td><strong><a href="<?php echo esc_url( add_query_arg( [ 'page' => self::PAGE, 'action' => 'edit', 'snippet' => $item['id'] ], admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $item['title'] ); ?></a></strong><code>[cinderwell_snippet id=&quot;<?php echo esc_attr( $item['slug'] ); ?>&quot;]</code>
								<div class="row-actions"><span><a href="<?php echo esc_url( add_query_arg( [ 'page' => self::PAGE, 'action' => 'edit', 'snippet' => $item['id'] ], admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'cinderwell-snippets' ); ?></a> | </span><span class="trash"><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'cinderwell_snippet_delete', 'snippet' => $item['id'] ], admin_url( 'admin-post.php' ) ), 'cinderwell_snippet_delete_' . $item['id'] ) ); ?>"><?php esc_html_e( 'Trash', 'cinderwell-snippets' ); ?></a></span></div>
								<?php if ( $item['auto_disabled'] && is_array( $item['last_error'] ) ) : ?><p class="cw-snippet-error-summary"><span class="dashicons dashicons-warning"></span><?php echo esc_html( $item['last_error']['message'] ?? __( 'Runtime failure', 'cinderwell-snippets' ) ); ?></p><?php endif; ?>
							</td>
							<td><span class="cw-snippet-type cw-snippet-type--<?php echo esc_attr( $item['type'] ); ?>"><?php echo esc_html( Repository::get_types()[ $item['type']] ?? strtoupper( $item['type'] ) ); ?></span></td>
							<td><?php echo esc_html( Repository::get_locations( $item['type'] )[ $item['location'] ] ?? $item['location'] ); ?></td>
							<td><?php echo esc_html( $item['priority'] ); ?></td>
							<td><?php if ( $item['auto_disabled'] ) : ?><span class="cw-snippet-status is-error"><?php esc_html_e( 'Needs attention', 'cinderwell-snippets' ); ?></span><?php elseif ( 'publish' === $item['status'] ) : ?><span class="cw-snippet-status is-active"><?php esc_html_e( 'Active', 'cinderwell-snippets' ); ?></span><?php else : ?><span class="cw-snippet-status"><?php esc_html_e( 'Disabled', 'cinderwell-snippets' ); ?></span><?php endif; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				</div>
			</form>
			<?php $this->render_pagination( $query, $paged ); ?>
			<?php Transfer::render_import(); ?>
		</div>
		<?php
	}

	private function render_editor() {
		$post_id = absint( $_GET['snippet'] ?? 0 );
		$item    = $post_id ? Repository::get( $post_id ) : null;
		if ( $post_id && ! $item ) wp_die( esc_html__( 'Snippet not found.', 'cinderwell-snippets' ), '', [ 'response' => 404 ] );
		$item = $item ?: [
			'id' => 0, 'title' => '', 'slug' => '', 'code' => "<?php\n\n", 'status' => 'draft', 'type' => 'php',
			'location' => 'frontend', 'priority' => 10, 'conditions' => Condition_Evaluator::defaults(),
			'last_error' => [], 'auto_disabled' => false,
		];
		$conditions = wp_parse_args( $item['conditions'], Condition_Evaluator::defaults() );
		$back_url   = add_query_arg( 'page', self::PAGE, admin_url( 'admin.php' ) );
		?>
		<div class="wrap cw-snippets-wrap">
			<?php $this->render_header( $post_id ? __( 'Edit snippet', 'cinderwell-snippets' ) : __( 'Add snippet', 'cinderwell-snippets' ), __( 'Executable snippets have the same power as plugin code. Review every change before enabling it.', 'cinderwell-snippets' ) ); ?>
			<?php $this->render_notice(); ?>
			<p><a href="<?php echo esc_url( $back_url ); ?>">&larr; <?php esc_html_e( 'Back to snippets', 'cinderwell-snippets' ); ?></a></p>
			<?php if ( $item['auto_disabled'] && is_array( $item['last_error'] ) ) : ?>
				<div class="notice notice-error inline cw-snippet-runtime-error"><p><strong><?php esc_html_e( 'This snippet was automatically disabled.', 'cinderwell-snippets' ); ?></strong></p><p><?php echo esc_html( $item['last_error']['message'] ?? '' ); ?></p><code><?php echo esc_html( ( $item['last_error']['file'] ?? '' ) . ':' . ( $item['last_error']['line'] ?? 0 ) ); ?></code></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cw-snippet-editor-form">
				<input type="hidden" name="action" value="cinderwell_snippet_save"><input type="hidden" name="snippet_id" value="<?php echo esc_attr( $item['id'] ); ?>">
				<?php wp_nonce_field( 'cinderwell_snippet_save' ); ?>
				<div class="cw-snippet-editor-grid">
					<main class="cw-snippet-editor-main">
						<label class="cw-snippet-field"><span><?php esc_html_e( 'Snippet name', 'cinderwell-snippets' ); ?></span><input type="text" name="snippet_title" value="<?php echo esc_attr( $item['title'] ); ?>" required></label>
						<fieldset class="cw-snippet-field"><legend><?php esc_html_e( 'Code type', 'cinderwell-snippets' ); ?></legend><div class="cw-snippet-segmented" data-cw-snippet-types>
							<?php foreach ( Repository::get_types() as $key => $label ) : ?><label><input class="screen-reader-text" type="radio" name="snippet_type" value="<?php echo esc_attr( $key ); ?>" <?php checked( $item['type'], $key ); ?>><span><?php echo esc_html( $label ); ?></span></label><?php endforeach; ?>
						</div></fieldset>
						<label class="cw-snippet-field cw-snippet-code-field"><span><?php esc_html_e( 'Code', 'cinderwell-snippets' ); ?></span><textarea id="cw-snippet-code" name="snippet_code" rows="24" spellcheck="false"><?php echo esc_textarea( $item['code'] ); ?></textarea><small data-cw-code-help><?php esc_html_e( 'New PHP snippets include the opening tag. A closing PHP tag is not needed.', 'cinderwell-snippets' ); ?></small></label>
					</main>
					<aside class="cw-snippet-editor-sidebar">
						<section class="cw-snippet-panel"><h2><?php esc_html_e( 'Execution', 'cinderwell-snippets' ); ?></h2>
							<label class="cw-snippet-toggle"><span><strong><?php esc_html_e( 'Enabled', 'cinderwell-snippets' ); ?></strong><small><?php esc_html_e( 'Run this snippet when its location and conditions match.', 'cinderwell-snippets' ); ?></small></span><input class="screen-reader-text" type="checkbox" name="snippet_enabled" value="1" <?php checked( 'publish', $item['status'] ); ?>><i aria-hidden="true"><b></b></i></label>
							<label class="cw-snippet-field" for="cw-snippet-location"><span><?php esc_html_e( 'Run location', 'cinderwell-snippets' ); ?></span><select id="cw-snippet-location" name="snippet_location" data-current="<?php echo esc_attr( $item['location'] ); ?>"></select></label>
							<label class="cw-snippet-field"><span><?php esc_html_e( 'Priority', 'cinderwell-snippets' ); ?></span><input type="number" name="snippet_priority" min="1" max="999" value="<?php echo esc_attr( $item['priority'] ); ?>"><small><?php esc_html_e( 'Lower numbers run first at the same location.', 'cinderwell-snippets' ); ?></small></label>
							<div class="cw-snippet-shortcode" data-cw-shortcode <?php echo 'shortcode' === $item['location'] ? '' : 'hidden'; ?>><span><?php esc_html_e( 'Shortcode', 'cinderwell-snippets' ); ?></span><code>[cinderwell_snippet id="<?php echo esc_attr( $item['slug'] ?: 'snippet-slug' ); ?>"]</code></div>
						</section>
						<?php $this->render_conditions( $conditions, $item['location'] ); ?>
						<section class="cw-snippet-panel"><h2><?php esc_html_e( 'Save', 'cinderwell-snippets' ); ?></h2><label class="cw-snippet-field"><span><?php esc_html_e( 'Shortcode slug', 'cinderwell-snippets' ); ?></span><input type="text" name="snippet_slug" value="<?php echo esc_attr( $item['slug'] ); ?>" placeholder="my-snippet"><small><?php esc_html_e( 'Generated from the name when empty.', 'cinderwell-snippets' ); ?></small></label><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save snippet', 'cinderwell-snippets' ); ?></button></section>
					</aside>
				</div>
			</form>
		</div>
		<?php
	}

	private function render_conditions( array $conditions, $location ) {
		$roles = wp_roles()->get_names();
		?>
		<section class="cw-snippet-panel"><h2><?php esc_html_e( 'Conditions', 'cinderwell-snippets' ); ?></h2>
			<label class="cw-snippet-field"><span><?php esc_html_e( 'Match', 'cinderwell-snippets' ); ?></span><select name="conditions[relation]"><option value="and" <?php selected( $conditions['relation'], 'and' ); ?>><?php esc_html_e( 'All selected conditions', 'cinderwell-snippets' ); ?></option><option value="or" <?php selected( $conditions['relation'], 'or' ); ?>><?php esc_html_e( 'Any selected condition', 'cinderwell-snippets' ); ?></option></select></label>
			<div data-cw-query-conditions <?php echo Condition_Evaluator::supports_query_conditions( $location ) ? '' : 'hidden'; ?>>
				<fieldset class="cw-snippet-checks"><legend><?php esc_html_e( 'Page context', 'cinderwell-snippets' ); ?></legend><?php foreach ( [ 'home' => __( 'Homepage', 'cinderwell-snippets' ), 'singular' => __( 'Singular content', 'cinderwell-snippets' ), 'archive' => __( 'Archives', 'cinderwell-snippets' ), 'search' => __( 'Search results', 'cinderwell-snippets' ), '404' => __( '404 pages', 'cinderwell-snippets' ) ] as $key => $label ) : ?><label><input type="checkbox" name="conditions[contexts][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $conditions['contexts'], true ) ); ?>><?php echo esc_html( $label ); ?></label><?php endforeach; ?></fieldset>
				<details class="cw-snippet-details"><summary><?php esc_html_e( 'Content targeting', 'cinderwell-snippets' ); ?></summary><fieldset class="cw-snippet-checks"><legend><?php esc_html_e( 'Post types', 'cinderwell-snippets' ); ?></legend><?php foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $post_type ) : if ( 'attachment' === $post_type->name ) continue; ?><label><input type="checkbox" name="conditions[post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $conditions['post_types'], true ) ); ?>><?php echo esc_html( $post_type->labels->singular_name ); ?></label><?php endforeach; ?></fieldset><label class="cw-snippet-field"><span><?php esc_html_e( 'Specific content IDs', 'cinderwell-snippets' ); ?></span><input type="text" name="conditions[content_ids]" value="<?php echo esc_attr( implode( ', ', $conditions['content_ids'] ) ); ?>" placeholder="12, 34"><small><?php esc_html_e( 'Comma-separated post or page IDs.', 'cinderwell-snippets' ); ?></small></label></details>
			</div>
			<details class="cw-snippet-details"><summary><?php esc_html_e( 'URL paths', 'cinderwell-snippets' ); ?></summary><label class="cw-snippet-field"><span><?php esc_html_e( 'Included paths', 'cinderwell-snippets' ); ?></span><textarea name="conditions[include_paths]" rows="3" placeholder="/services/*"><?php echo esc_textarea( implode( "\n", $conditions['include_paths'] ) ); ?></textarea></label><label class="cw-snippet-field"><span><?php esc_html_e( 'Excluded paths', 'cinderwell-snippets' ); ?></span><textarea name="conditions[exclude_paths]" rows="3" placeholder="/checkout/*"><?php echo esc_textarea( implode( "\n", $conditions['exclude_paths'] ) ); ?></textarea><small><?php esc_html_e( 'Exclusions always win. Use * as a wildcard.', 'cinderwell-snippets' ); ?></small></label></details>
			<details class="cw-snippet-details"><summary><?php esc_html_e( 'Visitors and schedule', 'cinderwell-snippets' ); ?></summary><label class="cw-snippet-field"><span><?php esc_html_e( 'User state', 'cinderwell-snippets' ); ?></span><select name="conditions[user_state]"><option value="any" <?php selected( $conditions['user_state'], 'any' ); ?>><?php esc_html_e( 'Everyone', 'cinderwell-snippets' ); ?></option><option value="logged_in" <?php selected( $conditions['user_state'], 'logged_in' ); ?>><?php esc_html_e( 'Logged-in users', 'cinderwell-snippets' ); ?></option><option value="logged_out" <?php selected( $conditions['user_state'], 'logged_out' ); ?>><?php esc_html_e( 'Logged-out visitors', 'cinderwell-snippets' ); ?></option></select></label><fieldset class="cw-snippet-checks"><legend><?php esc_html_e( 'User roles', 'cinderwell-snippets' ); ?></legend><?php foreach ( $roles as $key => $label ) : ?><label><input type="checkbox" name="conditions[roles][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $conditions['roles'], true ) ); ?>><?php echo esc_html( $label ); ?></label><?php endforeach; ?></fieldset><div class="cw-snippet-date-grid"><label class="cw-snippet-field"><span><?php esc_html_e( 'Starts', 'cinderwell-snippets' ); ?></span><input type="datetime-local" name="conditions[start]" value="<?php echo esc_attr( $this->format_datetime( $conditions['start'] ) ); ?>"></label><label class="cw-snippet-field"><span><?php esc_html_e( 'Ends', 'cinderwell-snippets' ); ?></span><input type="datetime-local" name="conditions[end]" value="<?php echo esc_attr( $this->format_datetime( $conditions['end'] ) ); ?>"></label></div></details>
		</section>
		<?php
	}

	private function render_safe_mode() {
		$safe     = Repository::safe_mode_enabled();
		$constant = defined( 'CINDERWELL_SNIPPETS_SAFE_MODE' ) && CINDERWELL_SNIPPETS_SAFE_MODE;
		$recovery = function_exists( 'wp_is_recovery_mode' ) && wp_is_recovery_mode();
		?>
		<section class="cw-snippets-safe-mode <?php echo $safe ? 'is-active' : ''; ?>">
			<div><span class="dashicons dashicons-shield"></span><span><strong><?php echo $safe ? esc_html__( 'Safe Mode is on', 'cinderwell-snippets' ) : esc_html__( 'Safe Mode is off', 'cinderwell-snippets' ); ?></strong><small><?php echo $safe ? esc_html__( 'No snippets are executing.', 'cinderwell-snippets' ) : esc_html__( 'Enabled snippets run when their conditions match.', 'cinderwell-snippets' ); ?></small></span></div>
			<?php if ( $constant || $recovery ) : ?><span class="cw-snippets-safe-mode__locked"><?php echo $constant ? esc_html__( 'Controlled by wp-config.php', 'cinderwell-snippets' ) : esc_html__( 'WordPress Recovery Mode', 'cinderwell-snippets' ); ?></span><?php else : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="cinderwell_snippets_safe_mode"><input type="hidden" name="safe_mode" value="<?php echo $safe ? '0' : '1'; ?>"><?php wp_nonce_field( 'cinderwell_snippets_safe_mode' ); ?><button type="submit" class="button"><?php echo $safe ? esc_html__( 'Turn off Safe Mode', 'cinderwell-snippets' ) : esc_html__( 'Turn on Safe Mode', 'cinderwell-snippets' ); ?></button></form><?php endif; ?>
		</section>
		<?php
	}

	public function handle_save() {
		$this->guard();
		check_admin_referer( 'cinderwell_snippet_save' );
		$post_id    = absint( $_POST['snippet_id'] ?? 0 );
		$conditions = isset( $_POST['conditions'] ) && is_array( $_POST['conditions'] ) ? wp_unslash( $_POST['conditions'] ) : [];
		if ( isset( $conditions['content_ids'] ) ) $conditions['content_ids'] = preg_split( '/[\s,]+/', (string) $conditions['content_ids'] );
		$result = Repository::save( [
			'title'      => sanitize_text_field( wp_unslash( $_POST['snippet_title'] ?? '' ) ),
			'slug'       => sanitize_title( wp_unslash( $_POST['snippet_slug'] ?? '' ) ),
			'code'       => wp_unslash( $_POST['snippet_code'] ?? '' ),
			'type'       => sanitize_key( wp_unslash( $_POST['snippet_type'] ?? 'php' ) ),
			'location'   => sanitize_key( wp_unslash( $_POST['snippet_location'] ?? '' ) ),
			'priority'   => absint( $_POST['snippet_priority'] ?? 10 ),
			'conditions' => $conditions,
			'enabled'    => ! empty( $_POST['snippet_enabled'] ),
			'clear_error'=> true,
		], $post_id );

		if ( is_wp_error( $result ) ) {
			set_transient( 'cw_snippet_error_' . get_current_user_id(), $result->get_error_message(), MINUTE_IN_SECONDS );
			$this->redirect( [ 'action' => $post_id ? 'edit' : 'new', 'snippet' => $post_id, 'notice' => 'error' ] );
		}
		$this->redirect( [ 'action' => 'edit', 'snippet' => $result, 'notice' => 'saved' ] );
	}

	public function handle_delete() {
		$this->guard();
		$post_id = absint( $_GET['snippet'] ?? 0 );
		check_admin_referer( 'cinderwell_snippet_delete_' . $post_id );
		if ( Post_Type::POST_TYPE === get_post_type( $post_id ) ) wp_trash_post( $post_id );
		Repository::rebuild_manifest();
		$this->redirect( [ 'notice' => 'trashed' ] );
	}

	public function handle_bulk() {
		$this->guard();
		check_admin_referer( 'cinderwell_snippet_bulk' );
		$ids    = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $_POST['snippet_ids'] ?? [] ) ) ) ) );
		$action = sanitize_key( wp_unslash( $_POST['bulk_action'] ?? '' ) );
		$ids    = array_values( array_filter( $ids, static function ( $id ) { return Post_Type::POST_TYPE === get_post_type( $id ); } ) );
		if ( 'export' === $action ) Transfer::download( $ids );

		$errors = 0;
		foreach ( $ids as $id ) {
			if ( 'trash' === $action ) { wp_trash_post( $id ); continue; }
			$item = Repository::get( $id );
			if ( ! $item || ! in_array( $action, [ 'enable', 'disable' ], true ) ) continue;
			$item['enabled']     = 'enable' === $action;
			$item['clear_error'] = 'enable' === $action;
			$result = Repository::save( $item, $id );
			if ( is_wp_error( $result ) ) $errors++;
		}
		Repository::rebuild_manifest();
		$this->redirect( [ 'notice' => $errors ? 'bulk_error' : 'bulk', 'errors' => $errors ] );
	}

	public function handle_safe_mode() {
		$this->guard();
		check_admin_referer( 'cinderwell_snippets_safe_mode' );
		update_option( Repository::SAFE_MODE_OPTION, ! empty( $_POST['safe_mode'] ), false );
		$this->redirect( [ 'notice' => 'safe_mode' ] );
	}

	private function render_notice() {
		$notice = isset( $_GET['notice'] ) ? sanitize_key( wp_unslash( $_GET['notice'] ) ) : '';
		$messages = [
			'saved'       => __( 'Snippet saved.', 'cinderwell-snippets' ),
			'trashed'     => __( 'Snippet moved to Trash.', 'cinderwell-snippets' ),
			'bulk'        => __( 'Snippets updated.', 'cinderwell-snippets' ),
			'safe_mode'   => __( 'Safe Mode updated.', 'cinderwell-snippets' ),
			'imported'    => sprintf(
				__( '%1$d snippets imported as disabled; %2$d skipped.', 'cinderwell-snippets' ),
				absint( $_GET['count'] ?? 0 ),
				absint( $_GET['rejected'] ?? 0 )
			),
			'import_error'=> __( 'The snippets file could not be imported.', 'cinderwell-snippets' ),
			'bulk_error'  => sprintf( __( '%d PHP snippets could not be enabled because their code is invalid.', 'cinderwell-snippets' ), absint( $_GET['errors'] ?? 0 ) ),
		];
		if ( 'error' === $notice ) {
			$message = get_transient( 'cw_snippet_error_' . get_current_user_id() );
			delete_transient( 'cw_snippet_error_' . get_current_user_id() );
			if ( $message ) echo '<div class="notice notice-error inline"><p>' . esc_html( $message ) . '</p></div>';
		} elseif ( isset( $messages[ $notice ] ) ) {
			$class = in_array( $notice, [ 'import_error', 'bulk_error' ], true ) ? 'notice-error' : 'notice-success';
			echo '<div class="notice ' . esc_attr( $class ) . ' inline"><p>' . esc_html( $messages[ $notice ] ) . '</p>';
			if ( 'imported' === $notice ) {
				$report = get_transient( 'cw_snippet_import_report_' . get_current_user_id() );
				delete_transient( 'cw_snippet_import_report_' . get_current_user_id() );
				if ( ! empty( $report['rejected'] ) ) {
					echo '<details><summary>' . esc_html__( 'Review skipped records', 'cinderwell-snippets' ) . '</summary><ul>';
					foreach ( (array) $report['rejected'] as $reason ) echo '<li>' . esc_html( $reason ) . '</li>';
					echo '</ul></details>';
				}
			}
			echo '</div>';
		}
	}

	private function render_pagination( \WP_Query $query, $paged ) {
		if ( $query->max_num_pages < 2 ) return;
		echo '<div class="tablenav"><div class="tablenav-pages">';
		echo wp_kses_post( paginate_links( [ 'base' => add_query_arg( 'paged', '%#%' ), 'current' => $paged, 'total' => $query->max_num_pages ] ) );
		echo '</div></div>';
	}

	private function format_datetime( $timestamp ) {
		return $timestamp ? wp_date( 'Y-m-d\TH:i', absint( $timestamp ), wp_timezone() ) : '';
	}

	private function redirect( array $args = [] ) {
		wp_safe_redirect( add_query_arg( array_merge( [ 'page' => self::PAGE ], $args ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private function guard() {
		if ( ! Plugin::can_manage() ) {
			wp_die( esc_html__( 'You are not allowed to manage executable snippets.', 'cinderwell-snippets' ), '', [ 'response' => 403 ] );
		}
	}
}
