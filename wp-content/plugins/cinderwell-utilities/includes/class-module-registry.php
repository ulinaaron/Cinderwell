<?php
/**
 * Self-describing registry and settings schema for Site Utilities modules.
 *
 * @package Cinderwell_Utilities
 */

namespace Cinderwell_Utilities;

defined( 'ABSPATH' ) || exit;

class Module_Registry {

	/**
	 * Return registered utility module descriptors.
	 *
	 * Extensions may add descriptors with the cinderwell_utilities_modules
	 * filter. A descriptor must include a class and settings schema.
	 */
	public static function get_modules() {
		$modules = [
			'content_duplication' => [
				'class'       => 'Cinderwell_Utilities\\Modules\\Content_Duplication',
				'label'       => __( 'Content Duplication', 'cinderwell-utilities' ),
				'group'       => 'content',
				'description' => __( 'One-click duplicate posts and pages from the admin.', 'cinderwell-utilities' ),
				'icon'        => 'dashicons-admin-page',
				'settings'    => [
					'enabled'      => [ 'type' => 'boolean', 'default' => false ],
					'post_types'   => [ 'type' => 'post_type_list', 'default' => [ 'page', 'post' ] ],
					'roles'        => [ 'type' => 'role_list', 'default' => [ 'administrator', 'editor' ] ],
					'show_in'      => [ 'type' => 'key_list', 'default' => [ 'list', 'edit', 'admin_bar' ], 'choices' => [ 'list', 'edit', 'admin_bar' ] ],
					'new_status'   => [ 'type' => 'enum', 'default' => 'draft', 'choices' => [ 'draft', 'same', 'publish' ] ],
					'title_suffix' => [ 'type' => 'text', 'default' => 'Copy of' ],
				],
			],
			'content_order' => [
				'class'       => 'Cinderwell_Utilities\\Modules\\Content_Order',
				'label'       => __( 'Content Order', 'cinderwell-utilities' ),
				'group'       => 'content',
				'description' => __( 'Drag-and-drop ordering for hierarchical post types.', 'cinderwell-utilities' ),
				'icon'        => 'dashicons-sort',
				'settings'    => [
					'enabled'        => [ 'type' => 'boolean', 'default' => false ],
					'post_types'     => [ 'type' => 'hierarchical_post_type_list', 'default' => [ 'page' ] ],
					'apply_frontend' => [ 'type' => 'boolean', 'default' => true ],
				],
			],
			'terms_order' => [
				'class'       => 'Cinderwell_Utilities\\Modules\\Terms_Order',
				'label'       => __( 'Taxonomy Terms Order', 'cinderwell-utilities' ),
				'group'       => 'content',
				'description' => __( 'Drag-and-drop ordering for taxonomy terms.', 'cinderwell-utilities' ),
				'icon'        => 'dashicons-category',
				'settings'    => [
					'enabled'        => [ 'type' => 'boolean', 'default' => false ],
					'taxonomies'     => [ 'type' => 'hierarchical_taxonomy_list', 'default' => [ 'category' ] ],
					'apply_frontend' => [ 'type' => 'boolean', 'default' => true ],
				],
			],
			'media_replacement' => [
				'class'       => 'Cinderwell_Utilities\\Modules\\Media_Replacement',
				'label'       => __( 'Media Replacement', 'cinderwell-utilities' ),
				'group'       => 'media',
				'description' => __( 'Replace media files while keeping the same URL and ID.', 'cinderwell-utilities' ),
				'icon'        => 'dashicons-update',
				'settings'    => [
					'enabled'           => [ 'type' => 'boolean', 'default' => false ],
					'roles'             => [ 'type' => 'role_list', 'default' => [ 'administrator', 'editor' ] ],
					'replace_from_grid' => [ 'type' => 'boolean', 'default' => true ],
					'replace_from_edit' => [ 'type' => 'boolean', 'default' => true ],
				],
			],
			'allow_svgs' => [
				'class'       => 'Cinderwell_Utilities\\Modules\\Allow_SVGs',
				'label'       => __( 'Allow SVGs', 'cinderwell-utilities' ),
				'group'       => 'media',
				'description' => __( 'Enable SVG uploads with automatic sanitization.', 'cinderwell-utilities' ),
				'icon'        => 'dashicons-format-image',
				'settings'    => [
					'enabled' => [ 'type' => 'boolean', 'default' => false ],
					'roles'   => [ 'type' => 'role_list', 'default' => [ 'administrator' ] ],
				],
			],
			'disable_comments' => [
				'class'       => 'Cinderwell_Utilities\\Modules\\Disable_Comments',
				'label'       => __( 'Disable Comments', 'cinderwell-utilities' ),
				'group'       => 'disable',
				'description' => __( 'Site-wide comment disabling. Hides forms, admin menus, and closes pings.', 'cinderwell-utilities' ),
				'icon'        => 'dashicons-admin-comments',
				'settings'    => [
					'enabled'         => [ 'type' => 'boolean', 'default' => false ],
					'hide_existing'   => [ 'type' => 'boolean', 'default' => false ],
					'closed_existing' => [ 'type' => 'boolean', 'default' => false ],
				],
			],
			'disable_feeds' => [
				'class'       => 'Cinderwell_Utilities\\Modules\\Disable_Feeds',
				'label'       => __( 'Disable Feeds', 'cinderwell-utilities' ),
				'group'       => 'disable',
				'description' => __( 'Disable all RSS/Atom/RDF feeds and remove feed links from the head.', 'cinderwell-utilities' ),
				'icon'        => 'dashicons-rss',
				'settings'    => [
					'enabled'          => [ 'type' => 'boolean', 'default' => false ],
					'redirect_to_home' => [ 'type' => 'boolean', 'default' => true ],
				],
			],
			'disable_smaller' => [
				'class'       => 'Cinderwell_Utilities\\Modules\\Disable_Smaller',
				'label'       => __( 'Disable Smaller Components', 'cinderwell-utilities' ),
				'group'       => 'disable',
				'description' => __( 'Bundle of micro-disablers: emoji, embed, jQuery Migrate, generator tags, and more.', 'cinderwell-utilities' ),
				'icon'        => 'dashicons-performance',
				'settings'    => self::boolean_settings( [
					'enabled'                => false,
					'remove_generator'       => true,
					'remove_wp_version'      => true,
					'remove_wlw'             => true,
					'remove_rsd'             => true,
					'remove_shortlink'       => true,
					'remove_adjacent'        => true,
					'disable_emoji'          => true,
					'disable_wp_embed'       => true,
					'disable_block_css'      => false,
					'disable_jquery_migrate' => true,
					'disable_wc_assets'      => false,
				] ),
			],
			'login_branding' => [
				'class'       => 'Cinderwell_Utilities\\Modules\\Login_Branding',
				'label'       => __( 'Login Branding', 'cinderwell-utilities' ),
				'group'       => 'admin',
				'description' => __( 'Use the Company Details logo and name on the WordPress login screen.', 'cinderwell-utilities' ),
				'icon'        => 'dashicons-admin-network',
				'settings'    => [ 'enabled' => [ 'type' => 'boolean', 'default' => false ] ],
			],
			'search_visibility_status' => [
				'class'       => 'Cinderwell_Utilities\\Modules\\Search_Visibility_Status',
				'label'       => __( 'Search Visibility Status', 'cinderwell-utilities' ),
				'group'       => 'admin',
				'description' => __( 'Show a compact admin-bar status when WordPress discourages search indexing.', 'cinderwell-utilities' ),
				'icon'        => 'dashicons-visibility',
				'settings'    => [ 'enabled' => [ 'type' => 'boolean', 'default' => false ] ],
			],
			'plugin_update_control' => [
				'class'       => 'Cinderwell_Utilities\\Modules\\Plugin_Update_Control',
				'label'       => __( 'Plugin Update Control', 'cinderwell-utilities' ),
				'group'       => 'admin',
				'description' => __( 'Disable plugin auto-updates and lock selected plugins against manual updates.', 'cinderwell-utilities' ),
				'icon'        => 'dashicons-lock',
				'settings'    => [
					'enabled'        => [ 'type' => 'boolean', 'default' => false ],
					'locked_plugins' => [ 'type' => 'plugin_list', 'default' => [] ],
				],
			],
			'mail_delivery' => [
				'class'       => 'Cinderwell_Utilities\\Modules\\Mail_Delivery',
				'label'       => __( 'Mail Delivery', 'cinderwell-utilities' ),
				'group'       => 'communication',
				'description' => __( 'Send all WordPress email through SendGrid with testing and a metadata-only log.', 'cinderwell-utilities' ),
				'icon'        => 'dashicons-email-alt',
				'settings'    => [
					'enabled'        => [ 'type' => 'boolean', 'default' => false ],
					'provider'       => [ 'type' => 'enum', 'default' => 'sendgrid', 'choices' => [ 'sendgrid' ] ],
					'sending_domain' => [ 'type' => 'domain', 'default' => '' ],
					'from_name'      => [ 'type' => 'text', 'default' => '' ],
					'from_email'     => [ 'type' => 'email', 'default' => '' ],
					'force_from'     => [ 'type' => 'boolean', 'default' => true ],
					'retention_days' => [ 'type' => 'integer', 'default' => 30, 'choices' => [ 7, 30, 90 ] ],
				],
			],
		];

		$modules = apply_filters( 'cinderwell_utilities_modules', $modules );
		$valid   = [];

		foreach ( (array) $modules as $id => $module ) {
			$id = sanitize_key( $id );
			if ( ! $id || ! is_array( $module ) || empty( $module['class'] ) || empty( $module['settings'] ) || ! is_array( $module['settings'] ) ) {
				continue;
			}
			$module['id']          = $id;
			$module['label']       = sanitize_text_field( $module['label'] ?? $id );
			$module['description'] = sanitize_text_field( $module['description'] ?? '' );
			$module['group']       = sanitize_key( $module['group'] ?? 'admin' );
			$module['icon']        = sanitize_html_class( $module['icon'] ?? 'dashicons-admin-generic' );
			$valid[ $id ]          = $module;
		}

		return $valid;
	}

	public static function get_module( $id ) {
		$modules = self::get_modules();
		return $modules[ sanitize_key( $id ) ] ?? null;
	}

	public static function get_defaults() {
		$defaults = [];
		foreach ( self::get_modules() as $id => $module ) {
			$defaults[ $id ] = [];
			foreach ( $module['settings'] as $key => $field ) {
				$defaults[ $id ][ sanitize_key( $key ) ] = $field['default'] ?? null;
			}
		}
		return $defaults;
	}

	public static function sanitize_all( $settings ) {
		$settings = is_array( $settings ) ? $settings : [];
		$clean    = [];
		foreach ( self::get_modules() as $id => $module ) {
			$clean[ $id ] = self::sanitize_module( $id, $settings[ $id ] ?? [] );
		}
		return $clean;
	}

	public static function sanitize_module( $id, $settings ) {
		$module = self::get_module( $id );
		if ( ! $module ) {
			return [];
		}

		$settings = is_array( $settings ) ? $settings : [];
		$clean    = [];
		foreach ( $module['settings'] as $key => $field ) {
			$key           = sanitize_key( $key );
			$value         = array_key_exists( $key, $settings ) ? $settings[ $key ] : ( $field['default'] ?? null );
			$clean[ $key ] = self::sanitize_value( $value, $field );
		}

		return $clean;
	}

	private static function sanitize_value( $value, $field ) {
		if ( ! empty( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
			return call_user_func( $field['sanitize_callback'], $value, $field );
		}

		$type    = sanitize_key( $field['type'] ?? 'text' );
		$default = $field['default'] ?? null;
		$choices = self::resolve_choices( $field['choices'] ?? [] );

		switch ( $type ) {
			case 'boolean':
				return rest_sanitize_boolean( $value );
			case 'integer':
				$value = absint( $value );
				return $choices && ! in_array( $value, array_map( 'absint', $choices ), true ) ? absint( $default ) : $value;
			case 'email':
				return sanitize_email( $value );
			case 'domain':
				return Mail_Manager::sanitize_sending_domain( $value );
			case 'enum':
				$value = sanitize_key( $value );
				return in_array( $value, array_map( 'sanitize_key', $choices ), true ) ? $value : $default;
			case 'plugin_list':
				return Utilities::sanitize_plugin_basenames( $value, true );
			case 'post_type_list':
				return self::sanitize_key_list( $value, get_post_types( [ 'public' => true ], 'names' ) );
			case 'hierarchical_post_type_list':
				$allowed = [];
				foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $post_type ) {
					if ( $post_type->hierarchical || post_type_supports( $post_type->name, 'page-attributes' ) ) {
						$allowed[] = $post_type->name;
					}
				}
				return self::sanitize_key_list( $value, $allowed );
			case 'hierarchical_taxonomy_list':
				return self::sanitize_key_list( $value, get_taxonomies( [ 'public' => true, 'hierarchical' => true ], 'names' ) );
			case 'role_list':
				return self::sanitize_key_list( $value, array_keys( wp_roles()->roles ) );
			case 'key_list':
				return self::sanitize_key_list( $value, $choices );
			case 'text':
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	private static function resolve_choices( $choices ) {
		if ( is_callable( $choices ) ) {
			$choices = call_user_func( $choices );
		}
		return array_values( (array) $choices );
	}

	private static function sanitize_key_list( $values, $allowed ) {
		$values  = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $values ) ) ) );
		$allowed = array_map( 'sanitize_key', (array) $allowed );
		return array_values( array_intersect( $values, $allowed ) );
	}

	private static function boolean_settings( $defaults ) {
		$settings = [];
		foreach ( $defaults as $key => $default ) {
			$settings[ $key ] = [ 'type' => 'boolean', 'default' => (bool) $default ];
		}
		return $settings;
	}
}
