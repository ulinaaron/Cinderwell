<?php
/**
 * Dynamic page-header defaults, per-page overrides, and breadcrumbs.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Page_Header {

	const OPTION = 'cinderwell_page_header_settings';

	const META_VISIBILITY       = '_cw_page_header_visibility';
	const META_TITLE            = '_cw_page_header_title';
	const META_DESCRIPTION_MODE = '_cw_page_header_description_mode';
	const META_DESCRIPTION      = '_cw_page_header_description';
	const META_BACKGROUND       = '_cw_page_header_background';
	const META_BREADCRUMBS      = '_cw_page_header_breadcrumbs';
	const META_POST_DATE        = '_cw_page_header_post_date';
	const META_POST_TERMS       = '_cw_page_header_post_terms';

	public function __construct() {
		add_action( 'init', [ $this, 'register_meta' ] );
		add_filter( 'cinderwell_settings_tabs', [ $this, 'add_settings_tab' ], 12 );
		add_action( 'admin_post_cinderwell_save_page_header', [ $this, 'save_settings' ] );
		add_filter( 'cinderwell_settings_export_options', [ $this, 'add_export_option' ] );
	}

	public static function get_defaults() {
		return (array) apply_filters( 'cinderwell_page_header_defaults', [
			'enabled'     => true,
			'background'  => 'light',
			'breadcrumbs' => true,
			'post_date'   => true,
			'post_terms'  => true,
			'alignment'   => 'left',
			'width'       => 'wide',
			'spacing'     => 'md',
		] );
	}

	public static function get_settings() {
		return self::sanitize_settings( wp_parse_args( (array) get_option( self::OPTION, [] ), self::get_defaults() ) );
	}

	/**
	 * Post types that can inherit the shared Page Header contract.
	 *
	 * Add-ons may opt their public content types in without duplicating the
	 * Page Header meta keys or editor controls.
	 */
	public static function get_supported_post_types() {
		$post_types = (array) apply_filters( 'cinderwell_page_header_post_types', [ 'page', 'post' ] );
		return array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );
	}

	/**
	 * Settings and per-type capabilities consumed by the editor panel.
	 */
	public static function get_editor_settings() {
		$features = [
			'page' => [
				'date'  => false,
				'terms' => false,
			],
			'post' => [
				'date'        => true,
				'terms'       => true,
				'dateLabel'   => __( 'Post date', 'cinderwell' ),
				'termsLabel'  => __( 'Post categories', 'cinderwell' ),
				'titleHelp'   => __( 'Leave blank to use the WordPress post title.', 'cinderwell' ),
			],
		];

		$features = (array) apply_filters( 'cinderwell_page_header_post_type_features', $features );

		return array_merge( self::get_settings(), [
			'postTypes'        => self::get_supported_post_types(),
			'postTypeFeatures' => $features,
		] );
	}

	public static function sanitize_settings( $value ) {
		$value    = is_array( $value ) ? $value : [];
		$defaults = self::get_defaults();
		$colors   = Design_Tokens::get_color_slugs( 'background' );
		$color    = sanitize_key( $value['background'] ?? $defaults['background'] );

		return [
			'enabled'     => ! empty( $value['enabled'] ),
			'background'  => in_array( $color, $colors, true ) ? $color : $defaults['background'],
			'breadcrumbs' => ! empty( $value['breadcrumbs'] ),
			'post_date'   => ! empty( $value['post_date'] ),
			'post_terms'  => ! empty( $value['post_terms'] ),
			'alignment'   => self::allow( $value['alignment'] ?? '', [ 'left', 'center', 'right' ], $defaults['alignment'] ),
			'width'       => self::allow( $value['width'] ?? '', [ 'narrow', 'standard', 'wide', 'full' ], $defaults['width'] ),
			'spacing'     => self::allow( $value['spacing'] ?? '', [ 'none', 'xs', 'sm', 'md', 'lg', 'xl' ], $defaults['spacing'] ),
		];
	}

	public function register_meta() {
		$definitions = [
			self::META_VISIBILITY       => [ 'sanitize_callback' => [ $this, 'sanitize_visibility' ] ],
			self::META_TITLE            => [ 'sanitize_callback' => 'sanitize_text_field' ],
			self::META_DESCRIPTION_MODE => [ 'sanitize_callback' => [ $this, 'sanitize_description_mode' ] ],
			self::META_DESCRIPTION      => [ 'sanitize_callback' => 'sanitize_textarea_field' ],
			self::META_BACKGROUND       => [ 'sanitize_callback' => [ $this, 'sanitize_background' ] ],
			self::META_BREADCRUMBS      => [ 'sanitize_callback' => [ $this, 'sanitize_toggle_override' ] ],
			self::META_POST_DATE        => [ 'sanitize_callback' => [ $this, 'sanitize_toggle_override' ] ],
			self::META_POST_TERMS       => [ 'sanitize_callback' => [ $this, 'sanitize_toggle_override' ] ],
		];

		foreach ( self::get_supported_post_types() as $post_type ) {
			add_post_type_support( $post_type, 'custom-fields' );

			foreach ( $definitions as $key => $definition ) {
				register_post_meta( $post_type, $key, [
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'sanitize_callback' => $definition['sanitize_callback'],
					'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				] );
			}
		}
	}

	public function sanitize_visibility( $value ) {
		return self::allow( $value, [ '', 'show', 'hide' ], '' );
	}

	public function sanitize_description_mode( $value ) {
		return self::allow( $value, [ '', 'custom', 'hide' ], '' );
	}

	public function sanitize_toggle_override( $value ) {
		return self::allow( $value, [ '', 'show', 'hide' ], '' );
	}

	public function sanitize_background( $value ) {
		$value = sanitize_key( $value );
		return in_array( $value, Design_Tokens::get_color_slugs( 'background' ), true ) ? $value : '';
	}

	public function add_settings_tab( $tabs ) {
		$tabs['page-headers'] = [
			'label'    => __( 'Page Headers', 'cinderwell' ),
			'group'    => 'content',
			'callback' => [ $this, 'render_settings' ],
		];
		return $tabs;
	}

	public function add_export_option( $options ) {
		$options[] = self::OPTION;
		return $options;
	}

	public function render_settings() {
		$settings = self::get_settings();
		$colors   = [];
		foreach ( Design_Tokens::get_color_registry() as $color ) {
			if ( in_array( 'background', $color['contexts'], true ) ) {
				$colors[ $color['slug'] ] = [
					'label' => $color['label'],
					'color' => $color['resolvedColor'] ?: $color['value'],
				];
			}
		}
		?>
		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Page Header settings saved.', 'cinderwell' ); ?></p></div>
		<?php endif; ?>
		<section class="card cw-settings-card">
			<h2><?php esc_html_e( 'Page Header defaults', 'cinderwell' ); ?></h2>
			<p><?php esc_html_e( 'These values flow into Page Header blocks set to inherit. Individual pages and posts can override content, background, breadcrumbs, post meta, or hide the header.', 'cinderwell' ); ?></p>
			<form class="cw-page-header-settings" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cinderwell_save_page_header">
				<?php wp_nonce_field( 'cinderwell_save_page_header' ); ?>
				<?php
				Admin_Fields::render_table( [
					'enabled' => [
						'type'           => 'segmented',
						'label'          => __( 'Default visibility', 'cinderwell' ),
						'options'        => [ '1' => __( 'Show', 'cinderwell' ), '0' => __( 'Hide', 'cinderwell' ) ],
						'default'        => true,
					],
					'background' => [
						'type'        => 'color_chips',
						'label'       => __( 'Background', 'cinderwell' ),
						'options'     => $colors,
						'default'     => 'light',
						'description' => __( 'Uses enabled Cinderwell color tokens and their automatic contrast-safe foreground.', 'cinderwell' ),
					],
					'breadcrumbs' => [
						'type'           => 'segmented',
						'label'          => __( 'Breadcrumbs', 'cinderwell' ),
						'options'        => [ '1' => __( 'Show', 'cinderwell' ), '0' => __( 'Hide', 'cinderwell' ) ],
						'default'        => true,
						'description'    => __( 'Shows the relevant page hierarchy or blog path and the current title.', 'cinderwell' ),
					],
					'post_date' => [
						'type'           => 'segmented',
						'label'          => __( 'Post date', 'cinderwell' ),
						'options'        => [ '1' => __( 'Show', 'cinderwell' ), '0' => __( 'Hide', 'cinderwell' ) ],
						'default'        => true,
						'description'    => __( 'Shown on single blog post headers.', 'cinderwell' ),
					],
					'post_terms' => [
						'type'           => 'segmented',
						'label'          => __( 'Post categories', 'cinderwell' ),
						'options'        => [ '1' => __( 'Show', 'cinderwell' ), '0' => __( 'Hide', 'cinderwell' ) ],
						'default'        => true,
						'description'    => __( 'Shown as linked badges on single blog post headers.', 'cinderwell' ),
					],
					'alignment' => [
						'type'    => 'segmented',
						'label'   => __( 'Alignment', 'cinderwell' ),
						'options' => [ 'left' => __( 'Left', 'cinderwell' ), 'center' => __( 'Center', 'cinderwell' ), 'right' => __( 'Right', 'cinderwell' ) ],
						'default' => 'left',
					],
					'width' => [
						'type'    => 'segmented',
						'label'   => __( 'Inner width', 'cinderwell' ),
						'options' => [ 'narrow' => __( 'Narrow', 'cinderwell' ), 'standard' => __( 'Standard', 'cinderwell' ), 'wide' => __( 'Wide', 'cinderwell' ), 'full' => __( 'Full', 'cinderwell' ) ],
						'default' => 'wide',
					],
					'spacing' => [
						'type'    => 'segmented',
						'label'   => __( 'Vertical spacing', 'cinderwell' ),
						'options' => [ 'none' => __( 'None', 'cinderwell' ), 'xs' => __( 'XS', 'cinderwell' ), 'sm' => __( 'SM', 'cinderwell' ), 'md' => __( 'MD', 'cinderwell' ), 'lg' => __( 'LG', 'cinderwell' ), 'xl' => __( 'XL', 'cinderwell' ) ],
						'default' => 'md',
					],
				], $settings, 'page_header', 'cw-page-header' );
				submit_button( __( 'Save Page Header settings', 'cinderwell' ) );
				?>
			</form>
		</section>
		<?php
	}

	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage Page Header settings.', 'cinderwell' ) );
		}
		check_admin_referer( 'cinderwell_save_page_header' );
		$submitted = isset( $_POST['page_header'] ) ? (array) wp_unslash( $_POST['page_header'] ) : [];
		update_option( self::OPTION, self::sanitize_settings( $submitted ) );
		wp_safe_redirect( add_query_arg( [ 'page' => 'cinderwell', 'tab' => 'page-headers', 'updated' => '1' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function resolve( $attributes, $post_id = 0 ) {
		$attributes = is_array( $attributes ) ? $attributes : [];
		$settings   = self::get_settings();
		$is_search  = is_search();
		$post_id    = $is_search ? 0 : ( $post_id ? absint( $post_id ) : absint( get_queried_object_id() ?: get_the_ID() ) );
		$post_type  = $post_id ? get_post_type( $post_id ) : '';
		$is_entry   = in_array( $post_type, self::get_supported_post_types(), true );
		$meta       = $is_entry ? [
			'visibility'       => (string) get_post_meta( $post_id, self::META_VISIBILITY, true ),
			'title'            => (string) get_post_meta( $post_id, self::META_TITLE, true ),
			'description_mode' => (string) get_post_meta( $post_id, self::META_DESCRIPTION_MODE, true ),
			'description'      => (string) get_post_meta( $post_id, self::META_DESCRIPTION, true ),
			'background'       => (string) get_post_meta( $post_id, self::META_BACKGROUND, true ),
			'breadcrumbs'      => (string) get_post_meta( $post_id, self::META_BREADCRUMBS, true ),
			'post_date'        => (string) get_post_meta( $post_id, self::META_POST_DATE, true ),
			'post_terms'       => (string) get_post_meta( $post_id, self::META_POST_TERMS, true ),
		] : [];

		$visible = 'show' === ( $meta['visibility'] ?? '' ) || ( 'hide' !== ( $meta['visibility'] ?? '' ) && $settings['enabled'] );
		$title   = $meta['title'] ?? '';
		if ( '' === trim( $title ) ) {
			$title = sanitize_text_field( $attributes['titleOverride'] ?? '' );
		}
		if ( '' === trim( $title ) ) {
			if ( $is_search ) {
				$title = sprintf(
					/* translators: %s: search query. */
					__( 'Search results for “%s”', 'cinderwell' ),
					get_search_query( false )
				);
			} else {
				$title = $post_id ? get_the_title( $post_id ) : '';
			}
		}

		$description = '';
		if ( 'hide' !== ( $meta['description_mode'] ?? '' ) ) {
			if ( 'custom' === ( $meta['description_mode'] ?? '' ) ) {
				$description = $meta['description'] ?? '';
			} else {
				$binding = $attributes['dynamicData']['description'] ?? [];
				if ( ! empty( $binding['source'] ) && 'static' !== $binding['source'] ) {
					$description = Renderer::resolve_data_source( '', $binding['source'], [ 'post_id' => $post_id, 'field' => $binding['field'] ?? '' ] );
					if ( '' === $description ) {
						$description = $binding['fallback'] ?? '';
					}
				} else {
					$description = $attributes['description'] ?? '';
				}
			}
		}

		$background = $meta['background'] ?? '';
		if ( ! in_array( $background, Design_Tokens::get_color_slugs( 'background' ), true ) ) {
			$background = self::allow( $attributes['background'] ?? '', array_merge( [ 'inherit' ], Design_Tokens::get_color_slugs( 'background' ) ), 'inherit' );
			$background = 'inherit' === $background ? $settings['background'] : $background;
		}

		$breadcrumb_setting = $meta['breadcrumbs'] ?? '';
		if ( ! in_array( $breadcrumb_setting, [ 'show', 'hide' ], true ) ) {
			$breadcrumb_setting = self::allow( $attributes['breadcrumbs'] ?? '', [ 'inherit', 'show', 'hide' ], 'inherit' );
			$show_breadcrumbs   = 'inherit' === $breadcrumb_setting ? $settings['breadcrumbs'] : 'show' === $breadcrumb_setting;
		} else {
			$show_breadcrumbs = 'show' === $breadcrumb_setting;
		}

		$show_post_date  = self::resolve_toggle( $meta['post_date'] ?? '', $attributes['postDate'] ?? 'inherit', $settings['post_date'] );
		$show_post_terms = self::resolve_toggle( $meta['post_terms'] ?? '', $attributes['postTerms'] ?? 'inherit', $settings['post_terms'] );
		$post_date       = [];
		$terms           = [];
		if ( 'post' === $post_type ) {
			if ( $show_post_date ) {
				$post_date = [
					'datetime' => get_the_date( DATE_W3C, $post_id ),
					'label'    => get_the_date( '', $post_id ),
				];
			}
			if ( $show_post_terms ) {
				$categories = get_the_category( $post_id );
				if ( ! is_wp_error( $categories ) ) {
					foreach ( $categories as $category ) {
						$terms[] = [
							'label' => $category->name,
							'url'   => get_category_link( $category ),
						];
					}
				}
			}
		}
		if ( $is_entry && $show_post_terms ) {
			$terms = (array) apply_filters( 'cinderwell_page_header_terms', $terms, $post_id, $post_type );
		}

		$data = [
			'visible'      => $visible,
			'post_id'      => $post_id,
			'title'        => $title,
			'description'  => $description,
			'background'   => $background,
			'breadcrumbs'  => $show_breadcrumbs ? self::get_breadcrumbs( $post_id, $is_search ? __( 'Search', 'cinderwell' ) : $title ) : [],
			'post_date'    => $post_date,
			'terms'        => $terms,
			'alignment'    => self::inherit_attribute( $attributes, 'alignment', [ 'left', 'center', 'right' ], $settings['alignment'] ),
			'width'        => self::inherit_attribute( $attributes, 'width', [ 'narrow', 'standard', 'wide', 'full' ], $settings['width'] ),
			'spacing'      => self::inherit_attribute( $attributes, 'spacing', [ 'none', 'xs', 'sm', 'md', 'lg', 'xl' ], $settings['spacing'] ),
		];

		return (array) apply_filters( 'cinderwell_page_header_context', $data, $attributes, $post_id );
	}

	public static function get_breadcrumbs( $post_id, $current_title = '' ) {
		$items = [ [ 'label' => __( 'Home', 'cinderwell' ), 'url' => home_url( '/' ) ] ];
		$post_type = $post_id ? get_post_type( $post_id ) : '';

		if ( 'page' === $post_type ) {
			foreach ( array_reverse( get_post_ancestors( $post_id ) ) as $ancestor_id ) {
				$items[] = [ 'label' => get_the_title( $ancestor_id ), 'url' => get_permalink( $ancestor_id ) ];
			}
		} elseif ( 'post' === $post_type ) {
			$posts_page_id = absint( get_option( 'page_for_posts' ) );
			if ( $posts_page_id && $posts_page_id !== absint( get_option( 'page_on_front' ) ) ) {
				$items[] = [ 'label' => get_the_title( $posts_page_id ), 'url' => get_permalink( $posts_page_id ) ];
			}

			$categories = get_the_category( $post_id );
			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
				$category = $categories[0];
				$items[]  = [ 'label' => $category->name, 'url' => get_category_link( $category ) ];
			}
		}
		$items[] = [ 'label' => $current_title ?: get_the_title( $post_id ), 'url' => '' ];
		return (array) apply_filters( 'cinderwell_page_header_breadcrumbs', $items, $post_id );
	}

	private static function inherit_attribute( $attributes, $key, $allowed, $fallback ) {
		$value = sanitize_key( $attributes[ $key ] ?? 'inherit' );
		return 'inherit' === $value ? $fallback : self::allow( $value, $allowed, $fallback );
	}

	private static function resolve_toggle( $meta_value, $attribute_value, $fallback ) {
		if ( in_array( $meta_value, [ 'show', 'hide' ], true ) ) {
			return 'show' === $meta_value;
		}
		$attribute_value = self::allow( $attribute_value, [ 'inherit', 'show', 'hide' ], 'inherit' );
		return 'inherit' === $attribute_value ? (bool) $fallback : 'show' === $attribute_value;
	}

	private static function allow( $value, $allowed, $fallback ) {
		$value = sanitize_key( (string) $value );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}
}
