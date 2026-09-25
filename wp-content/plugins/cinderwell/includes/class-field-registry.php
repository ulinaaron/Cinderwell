<?php
/**
 * Code-first content field registry.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

/**
 * Stores normalized field groups and owns their native WordPress storage.
 */
class Field_Registry {

	/** @var array<string,array> */
	private $groups = [];

	/**
	 * Register a field group.
	 *
	 * @param string $id   Stable, namespaced group ID such as client/property_details.
	 * @param array  $args Group definition.
	 * @return bool
	 */
	public function register_group( $id, $args ) {
		$id = $this->sanitize_group_id( $id );
		if ( ! $id || ! is_array( $args ) || empty( $args['label'] ) ) {
			return false;
		}

		$object_type = sanitize_key( $args['object_type'] ?? 'post' );
		if ( ! in_array( $object_type, [ 'post', 'term', 'user', 'site' ], true ) ) {
			return false;
		}

		$location = sanitize_key( $args['editor_location'] ?? 'normal' );
		if ( ! in_array( $location, [ 'sidebar', 'normal', 'side' ], true ) ) {
			$location = 'normal';
		}

		$fields = Admin_Fields::normalize_fields( $args['fields'] ?? [] );
		foreach ( $fields as $key => &$field ) {
			$default_storage_key   = 'site' === $object_type ? $key : '_cw_' . str_replace( '/', '_', $id ) . '_' . $key;
			$field['storage_key']  = sanitize_key( $field['storage_key'] ?? $default_storage_key );
			$field['show_in_rest'] = array_key_exists( 'show_in_rest', $field ) ? (bool) $field['show_in_rest'] : true;
			$field['required']     = ! empty( $field['required'] );
			$field['admin_column'] = $this->normalize_column( $field['admin_column'] ?? false, $field['label'] );
		}
		unset( $field );
		if ( 'sidebar' === $location ) {
			foreach ( $fields as $field ) {
				if ( in_array( $field['type'], [ 'repeater', 'business_hours' ], true ) ) {
					$location = 'normal';
					break;
				}
			}
		}

		$this->groups[ $id ] = [
			'id'              => $id,
			'label'           => sanitize_text_field( $args['label'] ),
			'description'     => wp_kses_post( $args['description'] ?? '' ),
			'object_type'     => $object_type,
			'object_subtypes' => array_values( array_filter( array_map( 'sanitize_key', (array) ( $args['object_subtypes'] ?? [] ) ) ) ),
			'editor_location' => $location,
			'capability'      => sanitize_key( $args['capability'] ?? $this->default_capability( $object_type ) ),
			'option_name'     => sanitize_key( $args['option_name'] ?? 'cinderwell_fields_' . str_replace( '/', '_', $id ) ),
			'ui'              => array_key_exists( 'ui', $args ) ? (bool) $args['ui'] : true,
			'fields'          => $fields,
		];

		return true;
	}

	/** Register native meta after all clients have declared their groups. */
	public function register_storage() {
		foreach ( $this->groups as $group ) {
			if ( 'site' === $group['object_type'] ) {
				continue;
			}
			$subtypes = $group['object_subtypes'] ?: [ '' ];
			if ( 'post' === $group['object_type'] ) {
				foreach ( array_filter( $subtypes ) as $subtype ) {
					add_post_type_support( $subtype, 'custom-fields' );
				}
			}
			foreach ( $group['fields'] as $field ) {
				foreach ( $subtypes as $subtype ) {
					$this->register_meta_field( $group, $field, $subtype );
				}
			}
		}
	}

	/** @return array<string,array> */
	public function get_groups() {
		return $this->groups;
	}

	/** @return array<string,array> */
	public function get_groups_for( $object_type, $subtype = '', $ui_only = false ) {
		return array_filter( $this->groups, static function ( $group ) use ( $object_type, $subtype, $ui_only ) {
			if ( $group['object_type'] !== $object_type || ( $ui_only && ! $group['ui'] ) ) {
				return false;
			}
			return ! $subtype || ! $group['object_subtypes'] || in_array( $subtype, $group['object_subtypes'], true );
		} );
	}

	public function get_group( $id ) {
		return $this->groups[ $this->sanitize_group_id( $id ) ] ?? null;
	}

	/** Read one field without exposing storage implementation to consumers. */
	public function get_value( $group_id, $field_key, $object_id = 0 ) {
		$group = $this->get_group( $group_id );
		if ( ! $group ) {
			return null;
		}
		$field = $group['fields'][ sanitize_key( $field_key ) ] ?? null;
		if ( ! $field ) {
			return null;
		}
		if ( 'site' === $group['object_type'] ) {
			$values = (array) get_option( $group['option_name'], [] );
			return array_key_exists( $field['storage_key'], $values ) ? $values[ $field['storage_key'] ] : $field['default'];
		}
		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return $field['default'];
		}
		$value = get_metadata( $group['object_type'], $object_id, $field['storage_key'], true );
		return '' === $value ? $field['default'] : $value;
	}

	/** Persist one whole group after applying its schema sanitizers. */
	public function update_values( $group_id, $object_id, $submitted ) {
		$group = $this->get_group( $group_id );
		if ( ! $group ) {
			return false;
		}
		$sanitized = Admin_Fields::sanitize_values( $group['fields'], $submitted );
		if ( 'site' === $group['object_type'] ) {
			$stored = [];
			foreach ( $group['fields'] as $key => $field ) {
				$stored[ $field['storage_key'] ] = $sanitized[ $key ];
			}
			return update_option( $group['option_name'], $stored, false );
		}
		foreach ( $group['fields'] as $key => $field ) {
			$value = $sanitized[ $key ];
			if ( '' === $value || [] === $value || false === $value ) {
				delete_metadata( $group['object_type'], absint( $object_id ), $field['storage_key'] );
			} else {
				update_metadata( $group['object_type'], absint( $object_id ), $field['storage_key'], $value );
			}
		}
		return true;
	}

	/** Config consumed by the block-editor document panels. */
	public function get_editor_config( $post_type, $post_id = 0 ) {
		$groups = [];
		foreach ( $this->get_groups_for( 'post', $post_type, true ) as $group ) {
			if ( 'sidebar' !== $group['editor_location'] || ! current_user_can( $group['capability'], $post_id ) ) {
				continue;
			}
			$groups[] = [
				'id'          => $group['id'],
				'label'       => $group['label'],
				'description' => wp_strip_all_tags( $group['description'] ),
				'fields'      => array_map( [ $this, 'field_for_editor' ], array_values( $group['fields'] ) ),
			];
		}
		return $groups;
	}

	/** Dynamic-data field catalog. */
	public function get_dynamic_fields( $object_type = 'post', $subtype = '' ) {
		$fields = [];
		foreach ( $this->get_groups_for( $object_type, $subtype ) as $group ) {
			foreach ( $group['fields'] as $key => $field ) {
				if ( empty( $field['dynamic'] ) ) {
					continue;
				}
				$fields[] = [
					'name'  => $group['id'] . ':' . $key,
					'label' => $field['label'],
					'group' => $group['label'],
				];
			}
		}
		return $fields;
	}

	private function register_meta_field( $group, $field, $subtype ) {
		$type = $this->rest_type( $field );
		$schema = [ 'type' => $type ];
		if ( 'array' === $type ) {
			$schema['items'] = [
				'type' => in_array( $field['type'], [ 'posts', 'terms', 'users' ], true ) ? 'integer' : ( 'repeater' === $field['type'] ? 'object' : 'string' ),
			];
			if ( 'object' === $schema['items']['type'] ) {
				$schema['items']['additionalProperties'] = true;
			}
		} elseif ( 'object' === $type ) {
			$schema['additionalProperties'] = true;
		}
		$args = [
			'single'            => true,
			'type'              => $type,
			'default'           => 'object' === $type ? new \stdClass() : $field['default'],
			'sanitize_callback' => function ( $value ) use ( $field ) {
				$result = Admin_Fields::sanitize_values( [ 'value' => $field ], [ 'value' => $value ] );
				return $result['value'];
			},
			'auth_callback'     => function () use ( $group ) {
				return current_user_can( $group['capability'] );
			},
			'show_in_rest'      => $field['show_in_rest'] ? [ 'schema' => $schema ] : false,
		];
		if ( 'post' === $group['object_type'] ) {
			register_post_meta( $subtype, $field['storage_key'], $args );
		} elseif ( 'term' === $group['object_type'] ) {
			register_term_meta( $subtype, $field['storage_key'], $args );
		} else {
			register_meta( 'user', $field['storage_key'], $args );
		}
	}

	private function rest_type( $field ) {
		if ( in_array( $field['type'], [ 'checkbox' ], true ) ) {
			return 'boolean';
		}
		if ( in_array( $field['type'], [ 'media', 'number', 'post', 'term', 'user' ], true ) ) {
			return 'integer';
		}
		if ( 'business_hours' === $field['type'] ) {
			return 'object';
		}
		if ( in_array( $field['type'], [ 'checkboxes', 'posts', 'terms', 'users', 'repeater' ], true ) ) {
			return 'array';
		}
		return 'string';
	}

	private function field_for_editor( $field ) {
		$options = $field['options'];
		if ( in_array( $field['type'], [ 'post', 'posts', 'term', 'terms', 'user', 'users' ], true ) ) {
			$options = $this->reference_options( $field );
		}
		return [
			'key'         => $field['storage_key'],
			'type'        => $field['type'],
			'label'       => $field['label'],
			'description' => wp_strip_all_tags( $field['description'] ),
			'default'     => $field['default'],
			'required'    => $field['required'],
			'options'     => $options,
			'multiple'    => ! empty( $field['multiple'] ) || in_array( $field['type'], [ 'posts', 'terms', 'users' ], true ),
		];
	}

	private function reference_options( $field ) {
		$options = [];
		if ( in_array( $field['type'], [ 'post', 'posts' ], true ) ) {
			foreach ( get_posts( [
				'post_type'      => $field['object_subtype'] ?: 'any',
				'post_status'    => [ 'publish', 'draft', 'private' ],
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
			] ) as $post ) {
				$options[ (string) $post->ID ] = $post->post_title ?: sprintf( __( '(no title) #%d', 'cinderwell' ), $post->ID );
			}
		} elseif ( in_array( $field['type'], [ 'term', 'terms' ], true ) ) {
			$terms = get_terms( [ 'taxonomy' => $field['object_subtype'], 'hide_empty' => false ] );
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$options[ (string) $term->term_id ] = $term->name;
				}
			}
		} else {
			foreach ( get_users( [ 'number' => 200, 'orderby' => 'display_name' ] ) as $user ) {
				$options[ (string) $user->ID ] = $user->display_name;
			}
		}
		return $options;
	}

	private function normalize_column( $column, $label ) {
		if ( ! $column ) {
			return false;
		}
		return wp_parse_args( is_array( $column ) ? $column : [], [
			'label'      => $label,
			'sortable'   => false,
			'filterable' => false,
		] );
	}

	private function sanitize_group_id( $id ) {
		$parts = array_filter( array_map( 'sanitize_key', explode( '/', (string) $id ) ) );
		return implode( '/', $parts );
	}

	private function default_capability( $object_type ) {
		$capabilities = [
			'post' => 'edit_posts',
			'term' => 'manage_categories',
			'user' => 'edit_users',
			'site' => 'manage_options',
		];
		return $capabilities[ $object_type ] ?? 'manage_options';
	}
}
