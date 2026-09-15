<?php
/**
 * Replace an attachment file without changing its attachment ID or URL.
 *
 * @package Cinderwell_Utilities
 */

namespace Cinderwell_Utilities\Modules;

defined( 'ABSPATH' ) || exit;

class Media_Replacement {

	/** @var array */
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'add_meta_boxes_attachment', [ $this, 'register_edit_screen_meta_box' ] );
		add_filter( 'attachment_fields_to_edit', [ $this, 'register_media_library_field' ], 20, 2 );
		add_action( 'wp_ajax_cinderwell_replace_media', [ $this, 'handle_replace' ] );
	}

	/**
	 * Check both the configured role policy and WordPress capabilities.
	 */
	private function user_can_replace( $attachment_id = 0 ) {
		$user          = wp_get_current_user();
		$allowed_roles = (array) ( $this->settings['roles'] ?? [ 'administrator', 'editor' ] );

		if ( ! array_intersect( $allowed_roles, (array) $user->roles ) || ! current_user_can( 'upload_files' ) ) {
			return false;
		}

		return ! $attachment_id || current_user_can( 'edit_post', $attachment_id );
	}

	public function enqueue_assets( $hook ) {
		$screen             = get_current_screen();
		$is_attachment_edit = 'post.php' === $hook && $screen && 'attachment' === $screen->post_type;
		$is_media_library   = 'upload.php' === $hook;

		if ( ! $this->user_can_replace() ) {
			return;
		}

		if ( ( $is_attachment_edit && empty( $this->settings['replace_from_edit'] ) ) ||
			( $is_media_library && empty( $this->settings['replace_from_grid'] ) ) ||
			( ! $is_attachment_edit && ! $is_media_library ) ) {
			return;
		}

		wp_enqueue_script(
			'cinderwell-media-replace',
			CINDERWELL_UTILITIES_URL . 'assets/js/media-replace.js',
			[],
			(string) filemtime( CINDERWELL_UTILITIES_PATH . 'assets/js/media-replace.js' ),
			true
		);
		wp_enqueue_style(
			'cinderwell-media-replace',
			CINDERWELL_UTILITIES_URL . 'assets/css/media-replace.css',
			[],
			(string) filemtime( CINDERWELL_UTILITIES_PATH . 'assets/css/media-replace.css' )
		);
		wp_localize_script( 'cinderwell-media-replace', 'cinderwellUtilitiesMedia', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'cinderwell_replace_media' ),
			'i18n'    => [
				'confirm'      => __( 'Replace “%1$s” with “%2$s”? This cannot be undone.', 'cinderwell-utilities' ),
				'replacing'    => __( 'Replacing…', 'cinderwell-utilities' ),
				'success'      => __( 'File replaced. Refreshing…', 'cinderwell-utilities' ),
				'error'        => __( 'The file could not be replaced.', 'cinderwell-utilities' ),
				'networkError' => __( 'The upload failed because of a network error.', 'cinderwell-utilities' ),
			],
		] );
	}

	public function register_edit_screen_meta_box() {
		if ( empty( $this->settings['replace_from_edit'] ) || ! $this->user_can_replace() ) {
			return;
		}

		add_meta_box(
			'cinderwell-replace-media',
			__( 'Replace file', 'cinderwell-utilities' ),
			[ $this, 'render_edit_screen_meta_box' ],
			'attachment',
			'side',
			'default'
		);
	}

	public function render_edit_screen_meta_box( $post ) {
		if ( ! $this->user_can_replace( $post->ID ) ) {
			return;
		}

		echo $this->get_control_markup( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in helper.
	}

	/**
	 * Add a supported compatibility field to the Media Library details panel.
	 */
	public function register_media_library_field( $fields, $post ) {
		if ( empty( $this->settings['replace_from_grid'] ) || ! wp_doing_ajax() || ! $this->user_can_replace( $post->ID ) ) {
			return $fields;
		}

		$fields['cinderwell_replace_media'] = [
			'label' => __( 'Replace file', 'cinderwell-utilities' ),
			'input' => 'html',
			'html'  => $this->get_control_markup( $post->ID ),
			'helps' => __( 'Use the same file extension to preserve the existing URL.', 'cinderwell-utilities' ),
		];

		return $fields;
	}

	private function get_control_markup( $attachment_id ) {
		$file      = get_attached_file( $attachment_id );
		$filename  = $file ? wp_basename( $file ) : get_the_title( $attachment_id );
		$extension = strtolower( pathinfo( (string) $file, PATHINFO_EXTENSION ) );
		$input_id  = 'cinderwell-replace-file-' . absint( $attachment_id );
		$accept    = $extension ? '.' . $extension : '';

		return sprintf(
			'<div class="cinderwell-media-replace" data-attachment-id="%1$d" data-filename="%2$s">' .
			'<input class="cinderwell-media-replace__input screen-reader-text" id="%3$s" type="file" accept="%4$s">' .
			'<label class="button cinderwell-media-replace__button" for="%3$s">%5$s</label>' .
			'<p class="cinderwell-media-replace__file">%6$s <code>.%7$s</code></p>' .
			'<p class="cinderwell-media-replace__status" role="status" aria-live="polite"></p>' .
			'</div>',
			absint( $attachment_id ),
			esc_attr( $filename ),
			esc_attr( $input_id ),
			esc_attr( $accept ),
			esc_html__( 'Choose replacement', 'cinderwell-utilities' ),
			esc_html__( 'Required type:', 'cinderwell-utilities' ),
			esc_html( $extension )
		);
	}

	public function handle_replace() {
		check_ajax_referer( 'cinderwell_replace_media', 'nonce' );

		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

		if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
			wp_send_json_error( [ 'message' => __( 'The selected attachment is invalid.', 'cinderwell-utilities' ) ], 400 );
		}

		if ( ! $this->user_can_replace( $attachment_id ) ) {
			wp_send_json_error( [ 'message' => __( 'You are not allowed to replace this file.', 'cinderwell-utilities' ) ], 403 );
		}

		if ( empty( $_FILES['file'] ) || ! is_array( $_FILES['file'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Choose a replacement file.', 'cinderwell-utilities' ) ], 400 );
		}

		$upload_error = isset( $_FILES['file']['error'] ) ? (int) $_FILES['file']['error'] : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $upload_error ) {
			wp_send_json_error( [ 'message' => $this->get_upload_error_message( $upload_error ) ], 400 );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$old_file = get_attached_file( $attachment_id );
		if ( ! $old_file || ! is_file( $old_file ) || ! is_readable( $old_file ) || ! is_writable( $old_file ) ) {
			wp_send_json_error( [ 'message' => __( 'The existing file is missing or is not writable.', 'cinderwell-utilities' ) ], 500 );
		}

		$old_extension = strtolower( pathinfo( $old_file, PATHINFO_EXTENSION ) );
		$new_name      = sanitize_file_name( wp_unslash( $_FILES['file']['name'] ?? '' ) );
		$new_extension = strtolower( pathinfo( $new_name, PATHINFO_EXTENSION ) );

		if ( ! $old_extension || $old_extension !== $new_extension ) {
			wp_send_json_error( [
				'message' => sprintf(
					/* translators: %s: required file extension. */
					__( 'Choose a .%s file. The extension must stay the same to preserve the media URL.', 'cinderwell-utilities' ),
					$old_extension
				),
			], 400 );
		}

		$uploaded = wp_handle_upload( $_FILES['file'], [ 'test_form' => false ] );
		if ( ! empty( $uploaded['error'] ) || empty( $uploaded['file'] ) ) {
			wp_send_json_error( [
				'message' => sanitize_text_field( $uploaded['error'] ?? __( 'WordPress rejected the uploaded file.', 'cinderwell-utilities' ) ),
			], 400 );
		}

		$staged_file = $uploaded['file'];
		$backup_file = wp_tempnam( wp_basename( $old_file ), dirname( $old_file ) );

		if ( ! $backup_file || ! copy( $old_file, $backup_file ) ) {
			wp_delete_file( $staged_file );
			wp_send_json_error( [ 'message' => __( 'A safety backup of the existing file could not be created.', 'cinderwell-utilities' ) ], 500 );
		}

		$old_metadata = wp_get_attachment_metadata( $attachment_id );
		if ( ! copy( $staged_file, $old_file ) ) {
			copy( $backup_file, $old_file );
			wp_delete_file( $backup_file );
			wp_delete_file( $staged_file );
			wp_send_json_error( [ 'message' => __( 'The replacement could not be written. The original file was preserved.', 'cinderwell-utilities' ) ], 500 );
		}

		$new_metadata = wp_generate_attachment_metadata( $attachment_id, $old_file );
		if ( is_wp_error( $new_metadata ) || ( wp_attachment_is_image( $attachment_id ) && ! is_array( $new_metadata ) ) ) {
			copy( $backup_file, $old_file );
			wp_delete_file( $backup_file );
			wp_delete_file( $staged_file );
			$message = is_wp_error( $new_metadata )
				? $new_metadata->get_error_message()
				: __( 'WordPress could not generate image metadata. The original file was restored.', 'cinderwell-utilities' );
			wp_send_json_error( [ 'message' => $message ], 500 );
		}

		$this->delete_stale_derivatives( $old_file, $old_metadata, $new_metadata );
		wp_update_attachment_metadata( $attachment_id, $new_metadata );
		update_attached_file( $attachment_id, $old_file );

		if ( ! empty( $uploaded['type'] ) ) {
			wp_update_post( [
				'ID'             => $attachment_id,
				'post_mime_type' => sanitize_mime_type( $uploaded['type'] ),
			] );
		}

		wp_delete_file( $backup_file );
		wp_delete_file( $staged_file );
		clean_attachment_cache( $attachment_id );
		clean_post_cache( $attachment_id );

		$attachment_url = wp_get_attachment_url( $attachment_id );
		$preview_url    = $attachment_url ? add_query_arg( 'cw-replaced', (string) time(), $attachment_url ) : '';

		do_action( 'cinderwell_utilities_media_replaced', $attachment_id, $old_metadata, $new_metadata );

		wp_send_json_success( [
			'message'      => __( 'File replaced successfully.', 'cinderwell-utilities' ),
			'new_url'      => $attachment_url,
			'preview_url'  => $preview_url,
			'new_filename' => wp_basename( $old_file ),
		] );
	}

	/**
	 * Remove old generated files that are not part of the new metadata.
	 */
	private function delete_stale_derivatives( $attached_file, $old_metadata, $new_metadata ) {
		$old_files = $this->get_derivative_paths( $attached_file, $old_metadata );
		$new_files = $this->get_derivative_paths( $attached_file, $new_metadata );

		foreach ( array_diff( $old_files, $new_files ) as $file ) {
			if ( $file !== $attached_file && is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}

	private function get_derivative_paths( $attached_file, $metadata ) {
		if ( ! is_array( $metadata ) ) {
			return [];
		}

		$directory = dirname( $attached_file );
		$paths     = [];

		foreach ( (array) ( $metadata['sizes'] ?? [] ) as $size ) {
			if ( ! empty( $size['file'] ) ) {
				$paths[] = $directory . DIRECTORY_SEPARATOR . wp_basename( $size['file'] );
			}
		}

		if ( ! empty( $metadata['original_image'] ) ) {
			$paths[] = $directory . DIRECTORY_SEPARATOR . wp_basename( $metadata['original_image'] );
		}

		return array_values( array_unique( $paths ) );
	}

	private function get_upload_error_message( $code ) {
		$messages = [
			UPLOAD_ERR_INI_SIZE   => __( 'The file exceeds the server upload limit.', 'cinderwell-utilities' ),
			UPLOAD_ERR_FORM_SIZE  => __( 'The file exceeds the form upload limit.', 'cinderwell-utilities' ),
			UPLOAD_ERR_PARTIAL    => __( 'The file was only partially uploaded.', 'cinderwell-utilities' ),
			UPLOAD_ERR_NO_FILE    => __( 'Choose a replacement file.', 'cinderwell-utilities' ),
			UPLOAD_ERR_NO_TMP_DIR => __( 'The server is missing a temporary upload directory.', 'cinderwell-utilities' ),
			UPLOAD_ERR_CANT_WRITE => __( 'The server could not write the uploaded file.', 'cinderwell-utilities' ),
			UPLOAD_ERR_EXTENSION  => __( 'A server extension stopped the upload.', 'cinderwell-utilities' ),
		];

		return $messages[ $code ] ?? __( 'The file upload failed.', 'cinderwell-utilities' );
	}
}
