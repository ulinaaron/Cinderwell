<?php
/**
 * Native Cinderwell content fields service.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Content_Fields {

	/** @var Field_Registry */
	private static $registry;

	public function __construct() {
		self::$registry = new Field_Registry();
		add_action( 'init', [ $this, 'collect_groups' ], 40 );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ], 20, 2 );
		add_action( 'save_post', [ $this, 'save_post' ], 20, 2 );
		add_action( 'admin_init', [ $this, 'register_object_interfaces' ], 20 );
		add_action( 'show_user_profile', [ $this, 'render_user_fields' ] );
		add_action( 'edit_user_profile', [ $this, 'render_user_fields' ] );
		add_action( 'personal_options_update', [ $this, 'save_user_fields' ] );
		add_action( 'edit_user_profile_update', [ $this, 'save_user_fields' ] );
		add_action( 'admin_post_cinderwell_save_content_fields', [ $this, 'save_site_fields' ] );
		add_filter( 'cinderwell_settings_tabs', [ $this, 'add_settings_tab' ] );
		add_filter( 'cinderwell_settings_tab_description', [ $this, 'settings_description' ], 10, 3 );
		add_filter( 'cinderwell_data_sources', [ $this, 'register_data_source' ] );
		add_filter( 'cinderwell_resolve_data_source', [ $this, 'resolve_data_source' ], 10, 4 );
		add_filter( 'cinderwell_editor_preview_values', [ $this, 'editor_preview_values' ], 10, 1 );
	}

	public static function registry() {
		return self::$registry;
	}

	/** Collect declarations only after post types and taxonomies exist. */
	public function collect_groups() {
		/**
		 * Register code-first fields with Cinderwell.
		 *
		 * @param Field_Registry $registry Field registry instance.
		 */
		do_action( 'cinderwell_register_fields', self::$registry );
		self::$registry->register_storage();
	}

	public function add_meta_boxes( $post_type, $post ) {
		if ( self::$registry->get_groups_for( 'post', $post_type, true ) ) {
			remove_meta_box( 'postcustom', $post_type, 'normal' );
		}
		foreach ( self::$registry->get_groups_for( 'post', $post_type, true ) as $group ) {
			if ( 'sidebar' === $group['editor_location'] || ! current_user_can( $group['capability'], $post->ID ) ) {
				continue;
			}
			add_meta_box(
				'cinderwell-fields-' . sanitize_html_class( str_replace( '/', '-', $group['id'] ) ),
				$group['label'],
				[ $this, 'render_post_group' ],
				$post_type,
				$group['editor_location'],
				'default',
				[ 'group_id' => $group['id'] ]
			);
		}
	}

	public function render_post_group( $post, $box ) {
		$group = self::$registry->get_group( $box['args']['group_id'] ?? '' );
		if ( ! $group ) {
			return;
		}
		wp_nonce_field( 'cinderwell_fields_' . $group['id'], 'cinderwell_fields_nonce_' . sanitize_key( str_replace( '/', '_', $group['id'] ) ) );
		if ( $group['description'] ) {
			echo '<p>' . wp_kses_post( $group['description'] ) . '</p>';
		}
		Admin_Fields::render_table( $group['fields'], $this->group_values( $group, $post->ID ), 'cinderwell_fields[' . esc_attr( $group['id'] ) . ']', 'cw-field-' . str_replace( '/', '-', $group['id'] ) );
	}

	public function save_post( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		foreach ( self::$registry->get_groups_for( 'post', $post->post_type, true ) as $group ) {
			if ( 'sidebar' === $group['editor_location'] || ! current_user_can( $group['capability'], $post_id ) ) {
				continue;
			}
			$nonce_key = 'cinderwell_fields_nonce_' . sanitize_key( str_replace( '/', '_', $group['id'] ) );
			$nonce = isset( $_POST[ $nonce_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'cinderwell_fields_' . $group['id'] ) ) {
				continue;
			}
			$all = isset( $_POST['cinderwell_fields'] ) ? wp_unslash( $_POST['cinderwell_fields'] ) : [];
			self::$registry->update_values( $group['id'], $post_id, $all[ $group['id'] ] ?? [] );
		}
	}

	/** Attach term fields and list-table behavior once WordPress has registered screens. */
	public function register_object_interfaces() {
		foreach ( self::$registry->get_groups() as $group ) {
			if ( 'term' !== $group['object_type'] || ! $group['ui'] ) {
				continue;
			}
			foreach ( $group['object_subtypes'] as $taxonomy ) {
				add_action( $taxonomy . '_add_form_fields', function () use ( $group ) { $this->render_term_group( null, $group ); } );
				add_action( $taxonomy . '_edit_form_fields', function ( $term ) use ( $group ) { $this->render_term_group( $term, $group ); } );
				add_action( 'created_' . $taxonomy, function ( $term_id ) use ( $group ) { $this->save_term_group( $term_id, $group ); } );
				add_action( 'edited_' . $taxonomy, function ( $term_id ) use ( $group ) { $this->save_term_group( $term_id, $group ); } );
			}
		}
		$this->register_post_columns();
		$this->register_term_columns();
		$this->register_user_columns();
	}

	public function render_term_group( $term, $group ) {
		if ( ! current_user_can( $group['capability'] ) ) {
			return;
		}
		$term_id = $term instanceof \WP_Term ? $term->term_id : 0;
		echo '<div class="cw-content-fields-group"><h2>' . esc_html( $group['label'] ) . '</h2>';
		wp_nonce_field( 'cinderwell_fields_' . $group['id'], 'cinderwell_fields_nonce_' . sanitize_key( str_replace( '/', '_', $group['id'] ) ) );
		Admin_Fields::render_table( $group['fields'], $this->group_values( $group, $term_id ), 'cinderwell_fields[' . esc_attr( $group['id'] ) . ']', 'cw-field-' . str_replace( '/', '-', $group['id'] ) );
		echo '</div>';
	}

	public function save_term_group( $term_id, $group ) {
		if ( ! current_user_can( $group['capability'] ) ) {
			return;
		}
		$nonce_key = 'cinderwell_fields_nonce_' . sanitize_key( str_replace( '/', '_', $group['id'] ) );
		$nonce = isset( $_POST[ $nonce_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'cinderwell_fields_' . $group['id'] ) ) {
			return;
		}
		$all = isset( $_POST['cinderwell_fields'] ) ? wp_unslash( $_POST['cinderwell_fields'] ) : [];
		self::$registry->update_values( $group['id'], $term_id, $all[ $group['id'] ] ?? [] );
	}

	public function render_user_fields( $user ) {
		foreach ( self::$registry->get_groups_for( 'user', '', true ) as $group ) {
			if ( ! current_user_can( $group['capability'], $user->ID ) ) {
				continue;
			}
			echo '<h2>' . esc_html( $group['label'] ) . '</h2>';
			if ( $group['description'] ) {
				echo '<p>' . wp_kses_post( $group['description'] ) . '</p>';
			}
			wp_nonce_field( 'cinderwell_fields_' . $group['id'], 'cinderwell_fields_nonce_' . sanitize_key( str_replace( '/', '_', $group['id'] ) ) );
			Admin_Fields::render_table( $group['fields'], $this->group_values( $group, $user->ID ), 'cinderwell_fields[' . esc_attr( $group['id'] ) . ']', 'cw-field-' . str_replace( '/', '-', $group['id'] ) );
		}
	}

	public function save_user_fields( $user_id ) {
		foreach ( self::$registry->get_groups_for( 'user', '', true ) as $group ) {
			if ( ! current_user_can( $group['capability'], $user_id ) ) {
				continue;
			}
			$nonce_key = 'cinderwell_fields_nonce_' . sanitize_key( str_replace( '/', '_', $group['id'] ) );
			$nonce = isset( $_POST[ $nonce_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'cinderwell_fields_' . $group['id'] ) ) {
				continue;
			}
			$all = isset( $_POST['cinderwell_fields'] ) ? wp_unslash( $_POST['cinderwell_fields'] ) : [];
			self::$registry->update_values( $group['id'], $user_id, $all[ $group['id'] ] ?? [] );
		}
	}

	public function add_settings_tab( $tabs ) {
		if ( self::$registry->get_groups_for( 'site', '', true ) ) {
			$tabs['content-fields'] = [
				'label'    => __( 'Content Fields', 'cinderwell' ),
				'group'    => 'content',
				'callback' => [ $this, 'render_site_fields' ],
			];
		}
		return $tabs;
	}

	public function settings_description( $description, $tab_key ) {
		return 'content-fields' === $tab_key ? __( 'Manage reusable, code-defined site information stored by WordPress.', 'cinderwell' ) : $description;
	}

	public function render_site_fields() {
		$groups = self::$registry->get_groups_for( 'site', '', true );
		if ( ! $groups ) {
			return;
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="cinderwell_save_content_fields">';
		wp_nonce_field( 'cinderwell_save_site_fields' );
		foreach ( $groups as $group ) {
			echo '<section class="cw-settings-card"><h2>' . esc_html( $group['label'] ) . '</h2>';
			if ( $group['description'] ) {
				echo '<p>' . wp_kses_post( $group['description'] ) . '</p>';
			}
			Admin_Fields::render_table( $group['fields'], $this->group_values( $group, 0 ), 'cinderwell_fields[' . esc_attr( $group['id'] ) . ']', 'cw-field-' . str_replace( '/', '-', $group['id'] ) );
			echo '</section>';
		}
		submit_button( __( 'Save content fields', 'cinderwell' ) );
		echo '</form>';
	}

	public function save_site_fields() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to update content fields.', 'cinderwell' ) );
		}
		check_admin_referer( 'cinderwell_save_site_fields' );
		$all = isset( $_POST['cinderwell_fields'] ) ? wp_unslash( $_POST['cinderwell_fields'] ) : [];
		foreach ( self::$registry->get_groups_for( 'site', '', true ) as $group ) {
			if ( current_user_can( $group['capability'] ) ) {
				self::$registry->update_values( $group['id'], 0, $all[ $group['id'] ] ?? [] );
			}
		}
		wp_safe_redirect( add_query_arg( [ 'page' => 'cinderwell', 'tab' => 'content-fields', 'updated' => '1' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function register_data_source( $sources ) {
		$sources['content_field'] = [
			'label'               => __( 'Cinderwell content field…', 'cinderwell' ),
			'group'               => 'content_fields',
			'context'             => 'post',
			'requires_field_name' => true,
		];
		return $sources;
	}

	public function resolve_data_source( $fallback, $source, $context ) {
		if ( 'content_field' !== $source || empty( $context['field'] ) ) {
			return $fallback;
		}
		$parts = explode( ':', $context['field'], 2 );
		if ( 2 !== count( $parts ) ) {
			return $fallback;
		}
		$post_id = ! empty( $context['post_id'] ) ? absint( $context['post_id'] ) : get_the_ID();
		return self::$registry->get_value( $parts[0], $parts[1], $post_id );
	}

	public function editor_preview_values( $values ) {
		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return $values;
		}
		foreach ( self::$registry->get_dynamic_fields( 'post', $post->post_type ) as $field ) {
			$parts = explode( ':', $field['name'], 2 );
			$values['content_field:' . $field['name']] = Renderer::normalize_value( self::$registry->get_value( $parts[0], $parts[1], $post->ID ) );
		}
		return $values;
	}

	public function get_editor_settings( $post_type, $post_id ) {
		return [
			'groups' => self::$registry->get_editor_config( $post_type, $post_id ),
			'fields' => self::$registry->get_dynamic_fields( 'post', $post_type ),
		];
	}

	private function group_values( $group, $object_id ) {
		$values = [];
		foreach ( $group['fields'] as $key => $field ) {
			$values[ $key ] = self::$registry->get_value( $group['id'], $key, $object_id );
		}
		return $values;
	}

	private function register_post_columns() {
		$post_types = [];
		foreach ( self::$registry->get_groups_for( 'post' ) as $group ) {
			foreach ( $group['object_subtypes'] as $post_type ) {
				$post_types[ $post_type ] = true;
			}
		}
		foreach ( array_keys( $post_types ) as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", function ( $columns ) use ( $post_type ) { return $this->add_columns( $columns, 'post', $post_type ); } );
			add_action( "manage_{$post_type}_posts_custom_column", function ( $column, $post_id ) use ( $post_type ) { $this->render_column( $column, 'post', $post_type, $post_id ); }, 10, 2 );
			add_filter( "manage_edit-{$post_type}_sortable_columns", function ( $columns ) use ( $post_type ) { return $this->sortable_columns( $columns, 'post', $post_type ); } );
		}
		add_action( 'restrict_manage_posts', [ $this, 'render_post_filters' ], 20, 2 );
		add_action( 'pre_get_posts', [ $this, 'apply_post_sorting' ] );
		add_action( 'pre_get_posts', [ $this, 'apply_post_filters' ], 20 );
	}

	private function register_term_columns() {
		foreach ( self::$registry->get_groups_for( 'term' ) as $group ) {
			foreach ( $group['object_subtypes'] as $taxonomy ) {
				add_filter( "manage_edit-{$taxonomy}_columns", function ( $columns ) use ( $taxonomy ) { return $this->add_columns( $columns, 'term', $taxonomy ); } );
				add_filter( "manage_{$taxonomy}_custom_column", function ( $content, $column, $term_id ) use ( $taxonomy ) { return $this->column_value( $column, 'term', $taxonomy, $term_id, $content ); }, 10, 3 );
			}
		}
	}

	private function register_user_columns() {
		if ( ! self::$registry->get_groups_for( 'user' ) ) {
			return;
		}
		add_filter( 'manage_users_columns', function ( $columns ) { return $this->add_columns( $columns, 'user', '' ); } );
		add_filter( 'manage_users_custom_column', function ( $content, $column, $user_id ) { return $this->column_value( $column, 'user', '', $user_id, $content ); }, 10, 3 );
	}

	private function add_columns( $columns, $object_type, $subtype ) {
		foreach ( $this->column_fields( $object_type, $subtype ) as $column => $item ) {
			$columns[ $column ] = $item['field']['admin_column']['label'];
		}
		return $columns;
	}

	private function sortable_columns( $columns, $object_type, $subtype ) {
		foreach ( $this->column_fields( $object_type, $subtype ) as $column => $item ) {
			if ( ! empty( $item['field']['admin_column']['sortable'] ) ) {
				$columns[ $column ] = $column;
			}
		}
		return $columns;
	}

	private function render_column( $column, $object_type, $subtype, $object_id ) {
		echo wp_kses_post( $this->column_value( $column, $object_type, $subtype, $object_id, '' ) );
	}

	private function column_value( $column, $object_type, $subtype, $object_id, $fallback ) {
		$fields = $this->column_fields( $object_type, $subtype );
		if ( ! isset( $fields[ $column ] ) ) {
			return $fallback;
		}
		$item  = $fields[ $column ];
		$value = self::$registry->get_value( $item['group']['id'], $item['key'], $object_id );
		if ( 'checkbox' === $item['field']['type'] ) {
			return $value ? esc_html__( 'Yes', 'cinderwell' ) : esc_html__( 'No', 'cinderwell' );
		}
		if ( 'media' === $item['field']['type'] ) {
			return $value ? wp_get_attachment_image( absint( $value ), [ 48, 48 ], false, [ 'alt' => '' ] ) : '—';
		}
		return esc_html( Renderer::normalize_value( $value ) ?: '—' );
	}

	public function apply_post_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		$post_type = $query->get( 'post_type' );
		$order_by  = $query->get( 'orderby' );
		$fields    = $this->column_fields( 'post', is_string( $post_type ) ? $post_type : '' );
		if ( empty( $fields[ $order_by ]['field']['admin_column']['sortable'] ) ) {
			return;
		}
		$field = $fields[ $order_by ]['field'];
		$query->set( 'meta_key', $field['storage_key'] );
		$query->set( 'orderby', in_array( $field['type'], [ 'number', 'media', 'post', 'term', 'user' ], true ) ? 'meta_value_num' : 'meta_value' );
	}

	public function render_post_filters( $post_type, $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		foreach ( $this->column_fields( 'post', $post_type ) as $column => $item ) {
			$field = $item['field'];
			if ( empty( $field['admin_column']['filterable'] ) || ! in_array( $field['type'], [ 'select', 'segmented', 'color_chips', 'checkbox' ], true ) ) {
				continue;
			}
			$name    = 'cw_field_filter_' . $column;
			$current = isset( $_GET[ $name ] ) ? sanitize_text_field( wp_unslash( $_GET[ $name ] ) ) : '';
			$options = 'checkbox' === $field['type']
				? [ '1' => __( 'Yes', 'cinderwell' ), '0' => __( 'No', 'cinderwell' ) ]
				: $field['options'];
			echo '<label class="screen-reader-text" for="' . esc_attr( $name ) . '">' . esc_html( $field['admin_column']['label'] ) . '</label>';
			echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"><option value="">' . esc_html( sprintf( __( 'All %s', 'cinderwell' ), $field['admin_column']['label'] ) ) . '</option>';
			foreach ( $options as $value => $label ) {
				$label = is_array( $label ) ? ( $label['label'] ?? $value ) : $label;
				echo '<option value="' . esc_attr( $value ) . '" ' . selected( $current, (string) $value, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select>';
		}
	}

	public function apply_post_filters( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		$post_type = $query->get( 'post_type' );
		if ( ! is_string( $post_type ) ) {
			return;
		}
		$meta_query = (array) $query->get( 'meta_query' );
		foreach ( $this->column_fields( 'post', $post_type ) as $column => $item ) {
			$field = $item['field'];
			$name  = 'cw_field_filter_' . $column;
			if ( empty( $field['admin_column']['filterable'] ) || ! isset( $_GET[ $name ] ) || '' === (string) $_GET[ $name ] ) {
				continue;
			}
			$value = sanitize_text_field( wp_unslash( $_GET[ $name ] ) );
			if ( 'checkbox' === $field['type'] && '0' === $value ) {
				$meta_query[] = [
					'relation' => 'OR',
					[ 'key' => $field['storage_key'], 'compare' => 'NOT EXISTS' ],
					[ 'key' => $field['storage_key'], 'value' => 0, 'compare' => '=' ],
				];
			} else {
				$meta_query[] = [
					'key'     => $field['storage_key'],
					'value'   => 'checkbox' === $field['type'] ? 1 : $value,
					'compare' => '=',
				];
			}
		}
		if ( $meta_query ) {
			$query->set( 'meta_query', $meta_query );
		}
	}

	private function column_fields( $object_type, $subtype ) {
		$items = [];
		foreach ( self::$registry->get_groups_for( $object_type, $subtype ) as $group ) {
			foreach ( $group['fields'] as $key => $field ) {
				if ( ! $field['admin_column'] ) {
					continue;
				}
				$column = 'cw_' . sanitize_key( str_replace( '/', '_', $group['id'] ) . '_' . $key );
				$items[ $column ] = compact( 'group', 'field', 'key' );
			}
		}
		return $items;
	}
}
