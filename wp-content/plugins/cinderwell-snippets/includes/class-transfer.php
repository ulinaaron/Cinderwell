<?php
/**
 * Versioned snippet import and export.
 *
 * @package Cinderwell_Snippets
 */

namespace Cinderwell_Snippets;

defined( 'ABSPATH' ) || exit;

class Transfer {
	public function __construct() {
		add_action( 'admin_post_cinderwell_snippets_export', [ $this, 'handle_export' ] );
		add_action( 'admin_post_cinderwell_snippets_import', [ $this, 'handle_import' ] );
	}

	public static function render_import() {
		?>
		<section class="cw-snippets-import">
			<div><h2><?php esc_html_e( 'Import snippets', 'cinderwell-snippets' ); ?></h2><p><?php esc_html_e( 'Import a Cinderwell snippets JSON file. Every imported snippet starts disabled and existing snippets are never overwritten.', 'cinderwell-snippets' ); ?></p><p class="description"><?php esc_html_e( 'Exports contain executable code and may contain credentials. Store them securely.', 'cinderwell-snippets' ); ?></p></div>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="cinderwell_snippets_import"><?php wp_nonce_field( 'cinderwell_snippets_import' ); ?><input type="file" name="snippets_file" accept="application/json,.json" required><button class="button" type="submit"><?php esc_html_e( 'Import JSON', 'cinderwell-snippets' ); ?></button></form>
		</section>
		<?php
	}

	public function handle_export() {
		self::guard();
		check_admin_referer( 'cinderwell_snippets_export' );
		self::download();
	}

	public static function download( array $ids = [] ) {
		self::guard();
		$args = [
			'post_type'      => Post_Type::POST_TYPE,
			'post_status'    => [ 'publish', 'draft' ],
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		];
		if ( $ids ) $args['post__in'] = array_map( 'absint', $ids );
		$items = [];
		foreach ( get_posts( $args ) as $post ) {
			$item = Repository::normalize_post( $post );
			$items[] = [
				'title'      => $item['title'],
				'slug'       => $item['slug'],
				'type'       => $item['type'],
				'code'       => $item['code'],
				'location'   => $item['location'],
				'priority'   => $item['priority'],
				'conditions' => $item['conditions'],
				'enabled'    => 'publish' === $item['status'],
			];
		}
		$payload = [
			'format'       => 'cinderwell-snippets',
			'version'      => 1,
			'generated_at' => gmdate( 'c' ),
			'site'         => home_url( '/' ),
			'snippets'     => $items,
		];
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="cinderwell-snippets-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	public function handle_import() {
		self::guard();
		check_admin_referer( 'cinderwell_snippets_import' );
		$file = $_FILES['snippets_file'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! is_array( $file ) || UPLOAD_ERR_OK !== ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) || ( $file['size'] ?? 0 ) > MB_IN_BYTES ) {
			$this->redirect( false );
		}
		$payload = json_decode( (string) file_get_contents( $file['tmp_name'] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $payload ) || 'cinderwell-snippets' !== ( $payload['format'] ?? '' ) || 1 !== absint( $payload['version'] ?? 0 ) || ! is_array( $payload['snippets'] ?? null ) ) {
			$this->redirect( false );
		}

		$report = self::import_payload( $payload );
		set_transient( 'cw_snippet_import_report_' . get_current_user_id(), $report, MINUTE_IN_SECONDS );
		$this->redirect( true, $report['imported'], count( $report['rejected'] ) );
	}

	/**
	 * Import a previously validated snippets payload.
	 *
	 * @param array $payload Versioned export payload.
	 * @return array{imported:int,rejected:array}
	 */
	public static function import_payload( array $payload ) {
		self::guard();
		$imported = 0;
		$rejected = [];
		foreach ( array_slice( (array) ( $payload['snippets'] ?? [] ), 0, 500 ) as $index => $snippet ) {
			if ( ! is_array( $snippet ) ) {
				$rejected[] = sprintf( __( 'Record %d is not a valid snippet.', 'cinderwell-snippets' ), $index + 1 );
				continue;
			}
			$title = sanitize_text_field( $snippet['title'] ?? __( 'Imported snippet', 'cinderwell-snippets' ) );
			$type = sanitize_key( $snippet['type'] ?? '' );
			if ( ! isset( Repository::get_types()[ $type ] ) ) {
				$rejected[] = sprintf( __( '%s uses an unsupported code type.', 'cinderwell-snippets' ), $title );
				continue;
			}
			$code = Repository::sanitize_code( $snippet['code'] ?? '' );
			if ( 'php' === $type ) {
				$valid = Repository::validate_php( $code );
				if ( is_wp_error( $valid ) ) {
					$rejected[] = sprintf( __( '%1$s contains invalid PHP: %2$s', 'cinderwell-snippets' ), $title, $valid->get_error_message() );
					continue;
				}
			}
			$result = Repository::save( [
				'title'      => $title,
				'slug'       => sanitize_title( $snippet['slug'] ?? '' ),
				'type'       => $type,
				'code'       => $code,
				'location'   => sanitize_key( $snippet['location'] ?? '' ),
				'priority'   => absint( $snippet['priority'] ?? 10 ),
				'conditions' => is_array( $snippet['conditions'] ?? null ) ? $snippet['conditions'] : [],
				'enabled'    => false,
			] );
			if ( is_wp_error( $result ) ) {
				$rejected[] = sprintf( __( '%1$s could not be imported: %2$s', 'cinderwell-snippets' ), $title, $result->get_error_message() );
			} else {
				$imported++;
			}
		}
		Repository::rebuild_manifest();
		return [ 'imported' => $imported, 'rejected' => $rejected ];
	}

	private function redirect( $success, $count = 0, $rejected = 0 ) {
		wp_safe_redirect( add_query_arg( [ 'page' => Admin::PAGE, 'notice' => $success ? 'imported' : 'import_error', 'count' => absint( $count ), 'rejected' => absint( $rejected ) ], admin_url( 'admin.php' ) ) );
		exit;
	}

	private static function guard() {
		if ( ! Plugin::can_manage() ) wp_die( esc_html__( 'You are not allowed to import or export snippets.', 'cinderwell-snippets' ), '', [ 'response' => 403 ] );
	}
}
