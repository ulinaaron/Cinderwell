<?php
/**
 * Resolves dynamic text and applies Cinderwell block output filters.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Renderer {

	public function __construct() {
		add_filter( 'render_block', [ $this, 'render_block' ], 20, 2 );
	}

	public static function resolve_data_source( $fallback, $source, $context = [] ) {
		if ( empty( $source ) || 'static' === $source || ! isset( Data_Sources::get_sources()[ $source ] ) ) {
			return $fallback;
		}
		$post_id = ! empty( $context['post_id'] ) ? absint( $context['post_id'] ) : get_the_ID();
		switch ( $source ) {
			case 'post_title': $value = get_the_title( $post_id ); break;
			case 'post_permalink': $value = get_permalink( $post_id ); break;
			case 'post_excerpt': $value = get_the_excerpt( $post_id ); break;
			case 'post_date': $value = get_the_date( '', $post_id ); break;
			case 'post_author': $value = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ); break;
			case 'site_title': $value = get_bloginfo( 'name' ); break;
			case 'site_tagline': $value = get_bloginfo( 'description' ); break;
			case 'current_year': $value = wp_date( 'Y' ); break;
			case 'current_user_name': $user = wp_get_current_user(); $value = $user->exists() ? $user->display_name : ''; break;
			case 'acf_field':
				$value = function_exists( 'get_field' ) && ! empty( $context['field'] ) ? get_field( sanitize_key( $context['field'] ), $post_id ) : '';
				break;
			default:
				$value = apply_filters( 'cinderwell_resolve_data_source', $fallback, $source, $context );
		}
		$value = self::normalize_value( $value );
		return '' !== $value ? $value : $fallback;
	}

	public static function normalize_value( $value ) {
		if ( null === $value || false === $value ) {
			return '';
		}
		if ( is_scalar( $value ) ) {
			return (string) $value;
		}
		if ( $value instanceof \WP_Post ) {
			return get_the_title( $value );
		}
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}
		if ( is_array( $value ) ) {
			foreach ( [ 'label', 'title', 'name', 'url' ] as $preferred_key ) {
				if ( isset( $value[ $preferred_key ] ) && is_scalar( $value[ $preferred_key ] ) ) {
					return (string) $value[ $preferred_key ];
				}
			}
			$parts = array_filter( array_map( [ __CLASS__, 'normalize_value' ], $value ), static function ( $part ) { return '' !== $part; } );
			return implode( ', ', $parts );
		}
		return '';
	}

	public function render_block( $content, $block ) {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || empty( $block['blockName'] ) || 0 !== strpos( $block['blockName'], 'cinderwell/' ) ) {
			return $content;
		}
		$attributes = isset( $block['attrs'] ) ? $block['attrs'] : [];
		if ( ! Condition_Evaluator::should_show( $attributes ) ) {
			return '';
		}
		$content = $this->add_responsive_visibility( $content, $attributes );
		if ( 'cinderwell/card-grid' === $block['blockName'] && false !== strpos( $content, 'data-cw-custom-icon' ) ) {
			$content = $this->sanitize_custom_svgs( $content );
		}
		return $this->replace_dynamic_data( $content, $attributes );
	}

	/**
	 * Add device visibility classes without changing saved block markup.
	 *
	 * @param string $content    Rendered block content.
	 * @param array  $attributes Block attributes.
	 * @return string
	 */
	private function add_responsive_visibility( $content, $attributes ) {
		$config = isset( $attributes['responsiveVisibility'] ) && is_array( $attributes['responsiveVisibility'] ) ? $attributes['responsiveVisibility'] : [];
		if ( empty( $config ) ) {
			return $content;
		}

		$block_classes = $this->get_responsive_visibility_classes( $config['block'] ?? [] );
		if ( $block_classes && class_exists( '\\WP_HTML_Tag_Processor' ) ) {
			$processor = new \WP_HTML_Tag_Processor( $content );
			if ( $processor->next_tag() ) {
				foreach ( $block_classes as $class_name ) {
					$processor->add_class( $class_name );
				}
				$content = $processor->get_updated_html();
			}
		}

		$sections = isset( $config['sections'] ) && is_array( $config['sections'] ) ? $config['sections'] : [];
		if ( ! $sections || ! class_exists( 'DOMDocument' ) ) {
			return $content;
		}

		$map = [
			'eyebrow' => 'cinderwell-eyebrow', 'heading' => 'cinderwell-heading', 'subheading' => 'cinderwell-subheading',
			'caption' => 'cinderwell-caption|cinderwell-atom-image__caption', 'footnote' => 'cinderwell-footnote', 'byline' => 'cinderwell-byline|cinderwell-quote__byline',
			'pullquote' => 'cinderwell-pullquote', 'quote' => 'cinderwell-quote__text', 'attribution' => 'cinderwell-quote__attribution',
			'context' => 'cinderwell-quote__context', 'bodyContent' => 'cinderwell-body__content|cinderwell-cta__body|cinderwell-image-text__body',
			'leftContent' => 'cinderwell-two-column__content', 'rightContent' => 'cinderwell-two-column__content', 'content' => 'wp-block-cinderwell-note|wp-block-cinderwell-heading',
			'text' => 'wp-block-cinderwell-button|wp-block-cinderwell-link|cinderwell-icon-list__content',
			'image' => 'cinderwell-hero__media|cinderwell-image-text__media|cinderwell-atom-image',
			'buttons' => 'cinderwell-buttons', 'icon' => 'cinderwell-icon-list__icon|cinderwell-atom-icon', 'label' => 'cinderwell-mega-menu__label',
		];
		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		libxml_use_internal_errors( true );
		$dom->loadHTML( '<meta charset="utf-8"><div id="cw-responsive-root">' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		$xpath = new \DOMXPath( $dom );

		foreach ( $sections as $slot => $values ) {
			if ( ! isset( $map[ $slot ] ) || ! is_array( $values ) ) {
				continue;
			}
			$classes = $this->get_responsive_visibility_classes( $values );
			if ( ! $classes ) {
				continue;
			}
			foreach ( $xpath->query( $this->get_slot_query( $slot, $map[ $slot ] ) ) as $node ) {
				$current = preg_split( '/\\s+/', trim( $node->getAttribute( 'class' ) ) ) ?: [];
				$node->setAttribute( 'class', implode( ' ', array_values( array_unique( array_merge( $current, $classes ) ) ) ) );
			}
		}

		$root = $dom->getElementById( 'cw-responsive-root' );
		if ( ! $root ) {
			libxml_clear_errors();
			return $content;
		}
		$output = '';
		foreach ( $root->childNodes as $child ) {
			$output .= $dom->saveHTML( $child );
		}
		libxml_clear_errors();
		return $output;
	}

	/**
	 * Resolve inherited device values into frontend utility classes.
	 *
	 * @param array $values Responsive values for one scope.
	 * @return array
	 */
	private function get_responsive_visibility_classes( $values ) {
		if ( ! is_array( $values ) ) {
			return [];
		}
		$desktop = isset( $values['desktop'] ) && 'hide' === $values['desktop'] ? 'hide' : 'show';
		$tablet  = isset( $values['tablet'] ) && in_array( $values['tablet'], [ 'show', 'hide' ], true ) ? $values['tablet'] : $desktop;
		$mobile  = isset( $values['mobile'] ) && in_array( $values['mobile'], [ 'show', 'hide' ], true ) ? $values['mobile'] : $tablet;
		$resolved = compact( 'desktop', 'tablet', 'mobile' );

		return array_map(
			static function ( $device ) {
				return 'cw-hide-' . $device;
			},
			array_keys( array_filter( $resolved, static function ( $value ) { return 'hide' === $value; } ) )
		);
	}

	private function sanitize_custom_svgs( $content ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return $content;
		}
		$allowed_tags = Safe_SVG_Uploads::get_allowed_tags();
		$allowed_attributes = Safe_SVG_Uploads::get_allowed_attributes();
		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		libxml_use_internal_errors( true );
		$dom->loadHTML( '<meta charset="utf-8"><div id="cw-svg-root">' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		$xpath = new \DOMXPath( $dom );
		foreach ( $xpath->query( '//svg[@data-cw-custom-icon]' ) as $svg ) {
			$elements = [];
			foreach ( $svg->getElementsByTagName( '*' ) as $element ) {
				$elements[] = $element;
			}
			foreach ( array_reverse( $elements ) as $element ) {
				if ( ! in_array( strtolower( $element->tagName ), $allowed_tags, true ) ) {
					$element->parentNode->removeChild( $element );
					continue;
				}
				for ( $index = $element->attributes->length - 1; $index >= 0; $index-- ) {
					$attribute = $element->attributes->item( $index );
					$name = strtolower( $attribute->name );
					if ( ! in_array( $name, $allowed_attributes, true ) ) {
						$element->removeAttribute( $attribute->name );
					} elseif ( in_array( $name, [ 'fill', 'stroke' ], true ) && ! preg_match( '/^(none|currentcolor)$/i', $attribute->value ) ) {
						$element->setAttribute( $attribute->name, 'currentColor' );
					}
				}
			}
		}
		$root = $dom->getElementById( 'cw-svg-root' );
		if ( ! $root ) {
			libxml_clear_errors();
			return $content;
		}
		$output = '';
		foreach ( $root->childNodes as $child ) {
			$output .= $dom->saveHTML( $child );
		}
		libxml_clear_errors();
		return $output;
	}

	private function replace_dynamic_data( $content, $attributes ) {
		$has_dynamic_url = false !== strpos( $content, 'data-cw-url-source' );
		if ( ( ( empty( $attributes['dynamicData'] ) || ! is_array( $attributes['dynamicData'] ) ) && empty( $attributes['conditions']['sections'] ) && ! $has_dynamic_url ) || ! class_exists( 'DOMDocument' ) ) {
			return $content;
		}
		$map = [
			'eyebrow' => 'cinderwell-eyebrow', 'heading' => 'cinderwell-heading', 'subheading' => 'cinderwell-subheading',
			'caption' => 'cinderwell-caption|cinderwell-atom-image__caption', 'footnote' => 'cinderwell-footnote', 'byline' => 'cinderwell-byline|cinderwell-quote__byline',
			'pullquote' => 'cinderwell-pullquote', 'quote' => 'cinderwell-quote__text', 'attribution' => 'cinderwell-quote__attribution',
			'context' => 'cinderwell-quote__context', 'bodyContent' => 'cinderwell-body__content|cinderwell-cta__body|cinderwell-image-text__body',
			'leftContent' => 'cinderwell-two-column__content', 'rightContent' => 'cinderwell-two-column__content', 'content' => 'wp-block-cinderwell-note|wp-block-cinderwell-heading',
			'text' => 'wp-block-cinderwell-button|wp-block-cinderwell-link',
		];
		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		libxml_use_internal_errors( true );
		$dom->loadHTML( '<meta charset="utf-8"><div id="cw-root">' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		$xpath = new \DOMXPath( $dom );
		foreach ( (array) ( $attributes['dynamicData'] ?? [] ) as $slot => $setting ) {
			if ( empty( $setting['source'] ) || 'static' === $setting['source'] || empty( $map[ $slot ] ) ) {
				continue;
			}
			$value = self::resolve_data_source( '', $setting['source'], [ 'post_id' => get_the_ID(), 'field' => isset( $setting['field'] ) ? $setting['field'] : '' ] );
			$value = '' !== $value ? $value : ( isset( $setting['fallback'] ) ? $setting['fallback'] : '' );
			foreach ( $xpath->query( $this->get_slot_query( $slot, $map[ $slot ] ) ) as $node ) {
				while ( $node->firstChild ) { $node->removeChild( $node->firstChild ); }
				$node->appendChild( $dom->createTextNode( wp_strip_all_tags( $value ) ) );
				$node->setAttribute( 'data-cw-source', sanitize_key( $setting['source'] ) );
				$node->setAttribute( 'data-cw-slot', sanitize_key( $slot ) );
			}
		}
		foreach ( $xpath->query( '//*[@data-cw-url-source]' ) as $node ) {
			$source   = sanitize_key( $node->getAttribute( 'data-cw-url-source' ) );
			$field    = sanitize_key( $node->getAttribute( 'data-cw-url-field' ) );
			$fallback = $node->getAttribute( 'data-cw-url-fallback' );
			$url      = self::resolve_data_source( $fallback, $source, [ 'post_id' => get_the_ID(), 'field' => $field ] );
			$node->setAttribute( 'href', esc_url_raw( $url ) ?: '#' );
		}
		foreach ( (array) ( $attributes['conditions']['sections'] ?? [] ) as $slot => $group ) {
			if ( ! isset( $map[ $slot ] ) || Condition_Evaluator::matches_group( $group ) ) {
				continue;
			}
			foreach ( $xpath->query( $this->get_slot_query( $slot, $map[ $slot ] ) ) as $node ) {
				$node->parentNode->removeChild( $node );
			}
		}
		$root = $dom->getElementById( 'cw-root' );
		if ( ! $root ) { return $content; }
		$output = '';
		foreach ( $root->childNodes as $child ) { $output .= $dom->saveHTML( $child ); }
		libxml_clear_errors();
		return $output;
	}

	private function get_slot_query( $slot, $class_list ) {
		$class_condition = static function ( $class ) {
			return "contains(concat(' ', normalize-space(@class), ' '), ' " . $class . " ')";
		};
		$classes = explode( '|', $class_list );
		$query = implode( ' or ', array_map( $class_condition, $classes ) );
		if ( 'leftContent' === $slot || 'rightContent' === $slot ) {
			$side = 'leftContent' === $slot ? 'cinderwell-two-column__left' : 'cinderwell-two-column__right';
			return '//*[' . $class_condition( $side ) . ']//*[' . $class_condition( 'cinderwell-two-column__content' ) . ']';
		}
		return '//*[@class and (' . $query . ')]';
	}
}
