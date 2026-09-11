<?php
/**
 * Safely enables SVG files in the WordPress Media Library.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Safe_SVG_Uploads {

	public function __construct() {
		add_filter( 'upload_mimes', [ $this, 'allow_svg_mime' ] );
		add_filter( 'wp_check_filetype_and_ext', [ $this, 'check_svg_filetype' ], 10, 5 );
		add_filter( 'wp_handle_upload_prefilter', [ $this, 'sanitize_svg_upload' ] );
	}

	public static function get_allowed_tags() {
		return [ 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon' ];
	}

	public static function get_allowed_attributes() {
		return [ 'd', 'points', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'width', 'height', 'rx', 'ry', 'cx', 'cy', 'r', 'transform', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'fill-rule', 'clip-rule', 'opacity' ];
	}

	public function allow_svg_mime( $mimes ) {
		if ( current_user_can( 'upload_files' ) ) {
			$mimes['svg'] = 'image/svg+xml';
		}
		return $mimes;
	}

	public function check_svg_filetype( $data, $file, $filename, $mimes, $real_mime = '' ) {
		if ( current_user_can( 'upload_files' ) && 'svg' === strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
			$data['ext'] = 'svg';
			$data['type'] = 'image/svg+xml';
			$data['proper_filename'] = false;
		}
		return $data;
	}

	public function sanitize_svg_upload( $file ) {
		if ( empty( $file['name'] ) || 'svg' !== strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) ) {
			return $file;
		}
		if ( ! current_user_can( 'upload_files' ) || empty( $file['tmp_name'] ) || ! is_readable( $file['tmp_name'] ) ) {
			$file['error'] = __( 'You cannot upload this SVG.', 'cinderwell' );
			return $file;
		}
		$svg = file_get_contents( $file['tmp_name'] );
		$sanitized = self::sanitize_svg( $svg );
		if ( '' === $sanitized ) {
			$file['error'] = __( 'The SVG is invalid or contains no supported shapes.', 'cinderwell' );
			return $file;
		}
		if ( false === file_put_contents( $file['tmp_name'], $sanitized ) ) {
			$file['error'] = __( 'The SVG could not be sanitized.', 'cinderwell' );
		}
		return $file;
	}

	public static function sanitize_svg( $svg ) {
		if ( ! is_string( $svg ) || false !== stripos( $svg, '<!doctype' ) || false !== stripos( $svg, '<!entity' ) || ! class_exists( 'DOMDocument' ) ) {
			return '';
		}
		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		libxml_use_internal_errors( true );
		$loaded = $dom->loadXML( $svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_clear_errors();
		if ( ! $loaded || ! $dom->documentElement || 'svg' !== strtolower( $dom->documentElement->localName ) ) {
			return '';
		}
		$root = $dom->documentElement;
		$root_attributes = [ 'xmlns', 'viewbox', 'width', 'height', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'aria-hidden', 'focusable' ];
		self::sanitize_attributes( $root, $root_attributes );
		$elements = [];
		foreach ( $root->getElementsByTagName( '*' ) as $element ) {
			$elements[] = $element;
		}
		foreach ( array_reverse( $elements ) as $element ) {
			if ( ! in_array( strtolower( $element->localName ), self::get_allowed_tags(), true ) ) {
				$element->parentNode->removeChild( $element );
				continue;
			}
			self::sanitize_attributes( $element, self::get_allowed_attributes() );
		}
		foreach ( iterator_to_array( ( new \DOMXPath( $dom ) )->query( '//comment()|//processing-instruction()' ) ) as $node ) {
			$node->parentNode->removeChild( $node );
		}
		return $root->hasChildNodes() ? $dom->saveXML( $root ) : '';
	}

	private static function sanitize_attributes( $element, $allowed ) {
		for ( $index = $element->attributes->length - 1; $index >= 0; $index-- ) {
			$attribute = $element->attributes->item( $index );
			$name = strtolower( $attribute->name );
			if ( ! in_array( $name, $allowed, true ) || false !== stripos( $attribute->value, 'url(' ) ) {
				$element->removeAttribute( $attribute->name );
			}
		}
	}
}
