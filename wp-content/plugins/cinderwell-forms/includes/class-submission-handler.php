<?php
namespace Cinderwell_Forms;

defined( 'ABSPATH' ) || exit;

/** Public form POST processing. */
class Submission_Handler {
	public function __construct() {
		add_action( 'admin_post_cinderwell_form_submit', [ $this, 'submit' ] );
		add_action( 'admin_post_nopriv_cinderwell_form_submit', [ $this, 'submit' ] );
	}

	public function submit() {
		$form_id = absint( $_POST['form_id'] ?? 0 );
		$form = Form_Repository::get( $form_id, true );
		$source = esc_url_raw( wp_unslash( $_POST['source_url'] ?? home_url( '/' ) ) );
		if ( ! $form || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cinderwell_form_token'] ?? '' ) ), 'cinderwell_form_submit_' . $form_id ) ) {
			$this->redirect( $source, [ 'form_id' => $form_id, 'errors' => [ 'form' => __( 'This form expired. Refresh the page and try again.', 'cinderwell-forms' ) ] ] );
		}
		if ( ! empty( $_POST['company_website'] ) || time() - absint( $_POST['started_at'] ?? 0 ) < 2 || ! $this->rate_limit( $form_id ) ) {
			$this->redirect( $source, [ 'form_id' => $form_id, 'errors' => [ 'form' => __( 'We could not accept this submission. Please wait and try again.', 'cinderwell-forms' ) ] ] );
		}
		$raw = isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : [];
		list( $values, $errors ) = $this->validate( $form, $raw );
		if ( ! $errors && ! $this->verify_turnstile( $form_id, $form ) ) $errors['form'] = __( 'Verification failed. Refresh the page and try again.', 'cinderwell-forms' );
		if ( $errors ) $this->redirect( $source, [ 'form_id' => $form_id, 'errors' => $errors, 'values' => $values ] );
		$fingerprint = 'cw_form_duplicate_' . md5( $form_id . '|' . wp_json_encode( $values ) . '|' . $this->client_hash() );
		if ( get_transient( $fingerprint ) ) $this->redirect( $source, [ 'form_id' => $form_id, 'status' => 'success', 'message' => $form['definition']['confirmation']['message'] ] );
		$entry_id = Entry_Repository::create( $form, $values, $source );
		if ( is_wp_error( $entry_id ) ) $this->redirect( $source, [ 'form_id' => $form_id, 'errors' => [ 'form' => $entry_id->get_error_message() ], 'values' => $values ] );
		set_transient( $fingerprint, 1, MINUTE_IN_SECONDS );
		Notifications::create_and_send( $entry_id, $form, $values );
		do_action( 'cinderwell_forms_after_submission', $entry_id, $form, $values );
		$confirmation = $form['definition']['confirmation'];
		if ( 'page' === $confirmation['type'] && $confirmation['page_id'] && get_permalink( $confirmation['page_id'] ) ) {
			wp_safe_redirect( get_permalink( $confirmation['page_id'] ), 303 ); exit;
		}
		$this->redirect( $source, [ 'form_id' => $form_id, 'status' => 'success', 'message' => $confirmation['message'] ] );
	}

	private function validate( array $form, array $raw ) {
		$values = []; $errors = [];
		foreach ( $form['definition']['fields'] as $field ) {
			if ( in_array( $field['type'], [ 'content', 'divider' ], true ) ) continue;
			if ( $field['conditions'] && ! Condition_Evaluator::matches( $field['conditions'], $field['condition_relation'], $values ) ) continue;
			$value = $raw[ $field['key'] ] ?? '';
			if ( 'checkboxes' === $field['type'] ) $value = array_values( array_map( 'sanitize_text_field', (array) $value ) );
			elseif ( 'email' === $field['type'] ) { $raw_email = sanitize_text_field( $value ); $value = is_email( $raw_email ) ? sanitize_email( $raw_email ) : $raw_email; if ( $raw_email && ! is_email( $raw_email ) ) $errors[ $field['key'] ] = sprintf( __( '%s must be a valid email address.', 'cinderwell-forms' ), $field['label'] ); }
			elseif ( 'textarea' === $field['type'] ) $value = sanitize_textarea_field( $value );
			elseif ( 'number' === $field['type'] ) $value = is_numeric( $value ) ? (string) $value : '';
			else $value = sanitize_text_field( is_scalar( $value ) ? $value : '' );
			if ( in_array( $field['type'], [ 'select', 'radio', 'checkboxes' ], true ) ) {
				$allowed = wp_list_pluck( $field['choices'], 'value' );
				$value = is_array( $value ) ? array_values( array_intersect( $value, $allowed ) ) : ( in_array( $value, $allowed, true ) ? $value : '' );
			}
			$values[ $field['key'] ] = $value;
			if ( ! isset( $errors[ $field['key'] ] ) && $field['required'] && ( is_array( $value ) ? ! $value : '' === trim( (string) $value ) ) ) $errors[ $field['key'] ] = sprintf( __( '%s is required.', 'cinderwell-forms' ), $field['label'] );
		}
		return [ apply_filters( 'cinderwell_forms_validated_values', $values, $form, $raw ), $errors ];
	}

	private function verify_turnstile( $form_id, array $form ) {
		if ( empty( $form['definition']['settings']['turnstile'] ) ) return true;
		$settings = get_option( 'cinderwell_forms_settings', [] ); $secret = Renderer::turnstile_secret();
		if ( empty( $settings['turnstile_site_key'] ) || ! $secret ) return false;
		$token = sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ?? '' ) );
		if ( ! $token || strlen( $token ) > 2048 ) return false;
		$response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [ 'timeout' => 10, 'body' => [ 'secret' => $secret, 'response' => $token, 'idempotency_key' => wp_generate_uuid4() ] ] );
		if ( is_wp_error( $response ) ) return false;
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		$expected_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		return ! empty( $data['success'] ) && ( empty( $data['action'] ) || 'cinderwell-form-' . $form_id === $data['action'] ) && ( empty( $data['hostname'] ) || $expected_host === $data['hostname'] );
	}

	private function rate_limit( $form_id ) { $key = 'cw_form_rate_' . md5( $form_id . '|' . $this->client_hash() ); $count = absint( get_transient( $key ) ); if ( $count >= 8 ) return false; set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS ); return true; }
	private function client_hash() { $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ); return hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ); }
	private function redirect( $url, array $result ) { $token = wp_generate_password( 24, false, false ); set_transient( 'cw_form_result_' . $token, $result, 10 * MINUTE_IN_SECONDS ); $url = remove_query_arg( 'cw_form_result', $url ); wp_safe_redirect( add_query_arg( 'cw_form_result', $token, $url ) . '#cw-form-' . absint( $result['form_id'] ) . '-1', 303 ); exit; }
}
