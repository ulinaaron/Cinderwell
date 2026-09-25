<?php
namespace Cinderwell_Forms;

defined( 'ABSPATH' ) || exit;

/** Notification creation and retry. */
class Notifications {
	public static function create_and_send( $entry_id, array $form, array $values ) {
		foreach ( $form['definition']['notifications'] as $rule ) {
			if ( empty( $rule['enabled'] ) || ! Condition_Evaluator::matches( $rule['conditions'], $rule['condition_relation'], $values ) ) continue;
			$recipients = self::recipients( $rule, $values, $form );
			foreach ( $recipients as $recipient ) self::create_delivery( $entry_id, $rule, $recipient, $form, $values );
		}
	}

	private static function recipients( array $rule, array $values, array $form ) {
		if ( 'field' === $rule['recipient_mode'] ) {
			$allowed = false;
			foreach ( $form['definition']['fields'] as $field ) {
				if ( $field['key'] === $rule['recipient_field'] && 'email' === $field['type'] && empty( $field['query_param'] ) ) {
					$allowed = true;
					break;
				}
			}
			if ( ! $allowed ) return [];
			$email = sanitize_email( $values[ $rule['recipient_field'] ] ?? '' );
			return $email ? [ $email ] : [];
		}
		return array_values( array_filter( array_map( 'sanitize_email', preg_split( '/[,\s]+/', $rule['recipients'] ) ) ) );
	}

	private static function create_delivery( $entry_id, array $rule, $recipient, array $form, array $values ) {
		global $wpdb;
		$reply_to = '';
		foreach ( $form['definition']['fields'] as $field ) {
			if ( $field['key'] === $rule['reply_to_field'] && 'email' === $field['type'] && empty( $field['query_param'] ) ) {
				$reply_to = sanitize_email( $values[ $field['key'] ] ?? '' );
				break;
			}
		}
		$payload = [
			'subject' => self::merge( $rule['subject'], $entry_id, $form, $values ),
			'body' => self::merge( $rule['body'], $entry_id, $form, $values ),
			'reply_to' => $reply_to,
		];
		$now = current_time( 'mysql', true );
		$wpdb->insert( Database::table( 'deliveries' ), [ 'entry_id' => $entry_id, 'rule_id' => $rule['id'], 'rule_name' => $rule['name'], 'recipient' => $recipient, 'status' => 'pending', 'attempts' => 0, 'last_error' => '', 'payload' => wp_json_encode( $payload ), 'created_utc' => $now, 'updated_utc' => $now ] );
		self::send( (int) $wpdb->insert_id );
	}

	public static function send( $delivery_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . Database::table( 'deliveries' ) . ' WHERE id=%d', $delivery_id ), ARRAY_A );
		if ( ! $row || 'accepted' === $row['status'] ) return false;
		$payload = json_decode( $row['payload'], true );
		$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
		if ( ! empty( $payload['reply_to'] ) ) $headers[] = 'Reply-To: ' . sanitize_email( $payload['reply_to'] );
		$error = '';
		$listener = static function ( $wp_error ) use ( &$error ) { $error = $wp_error->get_error_message(); };
		add_action( 'wp_mail_failed', $listener );
		$sent = wp_mail( sanitize_email( $row['recipient'] ), sanitize_text_field( $payload['subject'] ?? '' ), (string) ( $payload['body'] ?? '' ), $headers );
		remove_action( 'wp_mail_failed', $listener );
		$wpdb->update( Database::table( 'deliveries' ), [ 'status' => $sent ? 'accepted' : 'failed', 'attempts' => (int) $row['attempts'] + 1, 'last_error' => sanitize_text_field( $error ?: ( $sent ? '' : __( 'WordPress rejected the message.', 'cinderwell-forms' ) ) ), 'updated_utc' => current_time( 'mysql', true ) ], [ 'id' => $delivery_id ] );
		return $sent;
	}

	private static function merge( $template, $entry_id, array $form, array $values ) {
		$entry_url = add_query_arg( [ 'page' => 'cinderwell-form-submissions', 'entry' => $entry_id ], admin_url( 'admin.php' ) );
		$entry = Entry_Repository::get( $entry_id );
		$replace = [ '{form_title}' => $form['title'], '{submission_id}' => (string) $entry_id, '{submission_date}' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ), '{source_url}' => esc_url_raw( $entry['source_url'] ?? '' ), '{admin_entry_url}' => $entry_url ];
		$lines = [];
		foreach ( $form['definition']['fields'] as $field ) {
			if ( ! array_key_exists( $field['key'], $values ) ) continue;
			$value = is_array( $values[ $field['key'] ] ) ? implode( ', ', $values[ $field['key'] ] ) : $values[ $field['key'] ];
			$replace[ '{field:' . $field['key'] . '}' ] = (string) $value;
			$lines[] = $field['label'] . ': ' . $value;
		}
		$replace['{all_fields}'] = implode( "\n", $lines );
		return apply_filters( 'cinderwell_forms_notification_content', strtr( (string) $template, $replace ), $template, $replace, $entry_id );
	}
}
