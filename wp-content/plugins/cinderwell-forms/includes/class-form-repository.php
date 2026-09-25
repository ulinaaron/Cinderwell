<?php
namespace Cinderwell_Forms;

defined( 'ABSPATH' ) || exit;

/** Form definition persistence and normalization. */
class Form_Repository {
	public static function defaults() {
		return [
			'version' => 1,
			'fields' => [],
			'notifications' => [],
			'confirmation' => [ 'type' => 'message', 'message' => __( 'Thank you. Your form has been submitted.', 'cinderwell-forms' ), 'page_id' => 0 ],
			'settings' => [ 'type' => 'standard', 'submit_label' => __( 'Submit', 'cinderwell-forms' ), 'turnstile' => false ],
		];
	}

	public static function starter_templates() {
		$newsletter = self::defaults();
		$newsletter['fields'] = [
			[
				'id'                 => wp_generate_uuid4(),
				'key'                => 'email',
				'type'               => 'email',
				'label'              => __( 'Email address', 'cinderwell-forms' ),
				'instructions'       => '',
				'placeholder'        => __( 'you@example.com', 'cinderwell-forms' ),
				'default'            => '',
				'required'           => true,
				'width'              => 'full',
				'choices'            => [],
				'query_param'        => '',
				'condition_relation' => 'all',
				'conditions'         => [],
			],
		];
		$newsletter['confirmation']['message'] = __( 'Thanks for subscribing. Please check your inbox for future updates.', 'cinderwell-forms' );
		$newsletter['settings'] = [
			'type'         => 'newsletter',
			'submit_label' => __( 'Subscribe', 'cinderwell-forms' ),
			'turnstile'    => false,
		];

		return apply_filters( 'cinderwell_forms_starter_templates', [
			'newsletter' => [
				'label'       => __( 'Newsletter signup', 'cinderwell-forms' ),
				'description' => __( 'A compact email signup form with an accessible newsletter presentation.', 'cinderwell-forms' ),
				'title'       => __( 'Newsletter signup', 'cinderwell-forms' ),
				'definition'  => $newsletter,
			],
		] );
	}

	public static function starter_template( $key ) {
		$templates = self::starter_templates();
		return $templates[ sanitize_key( $key ) ] ?? null;
	}

	public static function get( $form_id, $published_only = false ) {
		$post = get_post( $form_id );
		if ( ! $post || Post_Type::POST_TYPE !== $post->post_type || ( $published_only && 'publish' !== $post->post_status ) ) {
			return null;
		}
		$definition = get_post_meta( $post->ID, Post_Type::META_DEFINITION, true );
		return [
			'id' => (int) $post->ID,
			'title' => get_the_title( $post ),
			'status' => $post->post_status,
			'definition' => self::sanitize_definition( is_array( $definition ) ? $definition : [] ),
		];
	}

	public static function sanitize_definition( $input ) {
		$input = is_array( $input ) ? $input : [];
		$out = self::defaults();
		$seen = [];
		$seen_keys = [];
		foreach ( (array) ( $input['fields'] ?? [] ) as $field ) {
			$field = self::sanitize_field( $field );
			if ( ! $field || isset( $seen[ $field['id'] ] ) ) continue;
			$base_key = $field['key'];
			$suffix = 2;
			while ( isset( $seen_keys[ $field['key'] ] ) ) {
				$field['key'] = $base_key . '_' . $suffix++;
			}
			$seen[ $field['id'] ] = true;
			$seen_keys[ $field['key'] ] = true;
			$out['fields'][] = $field;
		}
		foreach ( (array) ( $input['notifications'] ?? [] ) as $rule ) {
			$rule = self::sanitize_notification( $rule, $out['fields'] );
			if ( $rule ) $out['notifications'][] = $rule;
		}
		$confirmation = is_array( $input['confirmation'] ?? null ) ? $input['confirmation'] : [];
		$out['confirmation'] = [
			'type' => 'page' === ( $confirmation['type'] ?? '' ) ? 'page' : 'message',
			'message' => sanitize_textarea_field( $confirmation['message'] ?? $out['confirmation']['message'] ),
			'page_id' => absint( $confirmation['page_id'] ?? 0 ),
		];
		$settings = is_array( $input['settings'] ?? null ) ? $input['settings'] : [];
		$out['settings'] = [
			'type' => 'newsletter' === ( $settings['type'] ?? '' ) ? 'newsletter' : 'standard',
			'submit_label' => sanitize_text_field( $settings['submit_label'] ?? $out['settings']['submit_label'] ),
			'turnstile' => ! empty( $settings['turnstile'] ),
		];
		return apply_filters( 'cinderwell_forms_sanitize_definition', $out, $input );
	}

	private static function sanitize_field( $field ) {
		if ( ! is_array( $field ) ) return null;
		$types = array_keys( self::field_types() );
		$type = sanitize_key( $field['type'] ?? 'text' );
		if ( ! in_array( $type, $types, true ) ) return null;
		$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) ( $field['id'] ?? '' ) );
		if ( ! $id ) $id = wp_generate_uuid4();
		$key = sanitize_key( $field['key'] ?? '' );
		if ( ! $key ) $key = 'field_' . substr( md5( $id ), 0, 8 );
		$choices = [];
		foreach ( (array) ( $field['choices'] ?? [] ) as $choice ) {
			if ( is_string( $choice ) ) $choice = [ 'label' => $choice, 'value' => $choice ];
			if ( ! is_array( $choice ) ) continue;
			$label = sanitize_text_field( $choice['label'] ?? '' );
			$value = sanitize_text_field( $choice['value'] ?? $label );
			if ( '' !== $label && '' !== $value ) $choices[] = [ 'label' => $label, 'value' => $value ];
		}
		$conditions = [];
		foreach ( (array) ( $field['conditions'] ?? [] ) as $condition ) {
			if ( ! is_array( $condition ) ) continue;
			$conditions[] = [
				'field' => sanitize_key( $condition['field'] ?? '' ),
				'operator' => in_array( $condition['operator'] ?? '', [ 'is', 'is_not', 'contains', 'empty', 'not_empty' ], true ) ? $condition['operator'] : 'is',
				'value' => sanitize_text_field( $condition['value'] ?? '' ),
			];
		}
		return [
			'id' => $id, 'key' => $key, 'type' => $type,
			'label' => sanitize_text_field( $field['label'] ?? self::field_types()[ $type ] ),
			'instructions' => sanitize_text_field( $field['instructions'] ?? '' ),
			'placeholder' => sanitize_text_field( $field['placeholder'] ?? '' ),
			'default' => is_array( $field['default'] ?? null ) ? array_map( 'sanitize_text_field', $field['default'] ) : sanitize_text_field( $field['default'] ?? '' ),
			'required' => ! empty( $field['required'] ),
			'width' => 'half' === ( $field['width'] ?? '' ) ? 'half' : 'full',
			'choices' => $choices,
			'query_param' => sanitize_key( $field['query_param'] ?? '' ),
			'condition_relation' => 'any' === ( $field['condition_relation'] ?? '' ) ? 'any' : 'all',
			'conditions' => $conditions,
		];
	}

	private static function sanitize_notification( $rule, $fields ) {
		if ( ! is_array( $rule ) ) return null;
		$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) ( $rule['id'] ?? '' ) );
		if ( ! $id ) $id = wp_generate_uuid4();
		$mode = 'field' === ( $rule['recipient_mode'] ?? '' ) ? 'field' : 'custom';
		$recipients = [];
		foreach ( preg_split( '/[,\s]+/', (string) ( $rule['recipients'] ?? '' ) ) as $email ) {
			$email = sanitize_email( $email );
			if ( $email ) $recipients[] = $email;
		}
		return [
			'id' => $id,
			'name' => sanitize_text_field( $rule['name'] ?? __( 'Notification', 'cinderwell-forms' ) ),
			'enabled' => ! empty( $rule['enabled'] ),
			'recipient_mode' => $mode,
			'recipients' => implode( ', ', array_unique( $recipients ) ),
			'recipient_field' => sanitize_key( $rule['recipient_field'] ?? '' ),
			'reply_to_field' => sanitize_key( $rule['reply_to_field'] ?? '' ),
			'subject' => sanitize_text_field( $rule['subject'] ?? '{form_title}: new submission' ),
			'body' => sanitize_textarea_field( $rule['body'] ?? "{all_fields}" ),
			'condition_relation' => 'any' === ( $rule['condition_relation'] ?? '' ) ? 'any' : 'all',
			'conditions' => array_values( array_filter( array_map( static function ( $condition ) {
				if ( ! is_array( $condition ) ) return null;
				return [ 'field' => sanitize_key( $condition['field'] ?? '' ), 'operator' => sanitize_key( $condition['operator'] ?? 'is' ), 'value' => sanitize_text_field( $condition['value'] ?? '' ) ];
			}, (array) ( $rule['conditions'] ?? [] ) ) ) ),
		];
	}

	public static function field_types() {
		return apply_filters( 'cinderwell_forms_field_types', [
			'text' => __( 'Text', 'cinderwell-forms' ), 'email' => __( 'Email', 'cinderwell-forms' ),
			'tel' => __( 'Phone', 'cinderwell-forms' ), 'number' => __( 'Number', 'cinderwell-forms' ),
			'textarea' => __( 'Textarea', 'cinderwell-forms' ), 'select' => __( 'Select', 'cinderwell-forms' ),
			'radio' => __( 'Radio buttons', 'cinderwell-forms' ), 'checkboxes' => __( 'Checkboxes', 'cinderwell-forms' ),
			'consent' => __( 'Consent', 'cinderwell-forms' ), 'hidden' => __( 'Hidden', 'cinderwell-forms' ),
			'content' => __( 'Content', 'cinderwell-forms' ), 'divider' => __( 'Divider', 'cinderwell-forms' ),
		] );
	}
}
