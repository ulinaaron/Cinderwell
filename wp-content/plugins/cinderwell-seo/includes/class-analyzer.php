<?php
namespace Cinderwell_SEO;

defined( 'ABSPATH' ) || exit;

/** Deterministic, explainable SEO readiness analysis. */
class Analyzer {
	public static function analyze( array $input ) {
		$title       = trim( wp_strip_all_tags( (string) ( $input['document']['title'] ?? $input['meta']['title'] ?? $input['title'] ?? '' ) ) );
		$description = trim( wp_strip_all_tags( (string) ( $input['document']['description'] ?? $input['meta']['description'] ?? $input['excerpt'] ?? '' ) ) );
		$content     = (string) ( $input['content'] ?? '' );
		$text        = self::plain_text( $content );
		$focus       = trim( wp_strip_all_tags( (string) ( $input['meta']['focus_phrase'] ?? '' ) ) );
		$url         = (string) ( $input['url'] ?? '' );
		$canonical   = trim( (string) ( $input['meta']['canonical'] ?? '' ) );
		$checks      = [];

		self::check( $checks, 'title-present', __( 'Search title is present', 'cinderwell-seo' ), '' !== $title, 10, __( 'Add a concise title that identifies this page.', 'cinderwell-seo' ) );
		self::check( $checks, 'title-length', __( 'Search title is a useful length', 'cinderwell-seo' ), self::length( $title ) >= 15 && self::length( $title ) <= 65, 5, __( 'Treat 15–65 characters as guidance, not a ranking rule.', 'cinderwell-seo' ) );
		self::check( $checks, 'description-present', __( 'Meta description is present', 'cinderwell-seo' ), '' !== $description, 10, __( 'Add a clear summary for search results and sharing.', 'cinderwell-seo' ) );
		self::check( $checks, 'description-length', __( 'Meta description is a useful length', 'cinderwell-seo' ), self::length( $description ) >= 70 && self::length( $description ) <= 170, 5, __( 'Treat 70–170 characters as flexible preview guidance.', 'cinderwell-seo' ) );
		self::check( $checks, 'canonical', __( 'Canonical URL is valid', 'cinderwell-seo' ), '' === $canonical || (bool) wp_http_validate_url( $canonical ), 5, __( 'Leave this blank to inherit the WordPress permalink, or use a complete HTTP(S) URL.', 'cinderwell-seo' ) );
		self::check( $checks, 'indexability', __( 'The site permits indexing', 'cinderwell-seo' ), (bool) get_option( 'blog_public' ), 10, __( 'Review Settings → Reading before launching.', 'cinderwell-seo' ) );
		self::check( $checks, 'content', __( 'The page has meaningful text', 'cinderwell-seo' ), str_word_count( $text ) >= 50, 10, __( 'Add enough useful text to explain the page to visitors.', 'cinderwell-seo' ) );
		self::check( $checks, 'headings', __( 'Heading levels are ordered', 'cinderwell-seo' ), self::headings_are_ordered( $content ), 10, __( 'Do not skip heading levels when organizing the content.', 'cinderwell-seo' ) );
		self::check( $checks, 'internal-links', __( 'Long-form content includes an internal link', 'cinderwell-seo' ), str_word_count( $text ) < 150 || self::has_internal_link( $content ), 5, __( 'Connect longer content to another useful page on this site.', 'cinderwell-seo' ) );
		self::check( $checks, 'image-alt', __( 'Content images have alternative text', 'cinderwell-seo' ), self::images_have_alt( $content ), 10, __( 'Add meaningful alternative text, or leave alt empty only for decorative images.', 'cinderwell-seo' ) );
		self::check( $checks, 'focus-title', __( 'Focus phrase appears in the title', 'cinderwell-seo' ), self::contains( $title, $focus ), 8, __( 'Choose a focus phrase and use it naturally in the title.', 'cinderwell-seo' ) );
		self::check( $checks, 'focus-description', __( 'Focus phrase appears in the description', 'cinderwell-seo' ), self::contains( $description, $focus ), 5, __( 'Use the phrase naturally in the search description.', 'cinderwell-seo' ) );
		self::check( $checks, 'focus-opening', __( 'Focus phrase appears near the opening', 'cinderwell-seo' ), self::contains( self::substr( $text, 0, 500 ), $focus ), 4, __( 'Help readers confirm the page topic early.', 'cinderwell-seo' ) );
		self::check( $checks, 'focus-heading', __( 'Focus phrase appears in a heading', 'cinderwell-seo' ), self::headings_contain( $content, $focus ), 3, __( 'Use the phrase in a relevant heading when it reads naturally.', 'cinderwell-seo' ) );

		$checks = (array) apply_filters( 'cinderwell_seo_analysis_checks', $checks, $input );
		$score  = 0;
		$total  = 0;
		foreach ( $checks as $check ) {
			$weight = max( 0, absint( $check['weight'] ?? 0 ) );
			$total += $weight;
			if ( 'pass' === ( $check['status'] ?? '' ) ) {
				$score += $weight;
			}
		}
		$score = $total ? (int) round( 100 * $score / $total ) : 0;

		return [
			'score'            => $score,
			'band'             => $score >= 80 ? 'ready' : ( $score >= 50 ? 'progress' : 'needs-work' ),
			'band_label'       => $score >= 80 ? __( 'Ready', 'cinderwell-seo' ) : ( $score >= 50 ? __( 'In progress', 'cinderwell-seo' ) : __( 'Needs work', 'cinderwell-seo' ) ),
			'checks'           => array_values( $checks ),
			'analysis_version' => Meta::ANALYSIS_VERSION,
		];
	}

	private static function check( &$checks, $id, $label, $passed, $weight, $message ) {
		$checks[] = [
			'id'      => $id,
			'label'   => $label,
			'status'  => $passed ? 'pass' : 'recommendation',
			'message' => $passed ? __( 'This check passes.', 'cinderwell-seo' ) : $message,
			'weight'  => $weight,
		];
	}

	private static function plain_text( $content ) {
		$content = preg_replace( '/<!--.*?-->/s', ' ', $content );
		return trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $content ) ) ) );
	}

	private static function length( $text ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
	}

	private static function substr( $text, $start, $length ) {
		return function_exists( 'mb_substr' ) ? mb_substr( $text, $start, $length ) : substr( $text, $start, $length );
	}

	private static function lower( $text ) {
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text ) : strtolower( $text );
	}

	private static function contains( $haystack, $needle ) {
		return '' !== $needle && false !== strpos( self::lower( $haystack ), self::lower( $needle ) );
	}

	private static function headings_are_ordered( $content ) {
		if ( ! preg_match_all( '/<h([1-6])\b/i', $content, $matches ) ) {
			return true;
		}
		$previous = (int) reset( $matches[1] );
		foreach ( array_slice( $matches[1], 1 ) as $level ) {
			$level = (int) $level;
			if ( $level > $previous + 1 ) {
				return false;
			}
			$previous = $level;
		}
		return true;
	}

	private static function headings_contain( $content, $focus ) {
		if ( '' === $focus || ! preg_match_all( '/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is', $content, $matches ) ) {
			return false;
		}
		return self::contains( implode( ' ', array_map( 'wp_strip_all_tags', $matches[1] ) ), $focus );
	}

	private static function has_internal_link( $content ) {
		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		if ( ! preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\']/i', $content, $matches ) ) {
			return false;
		}
		foreach ( $matches[1] as $url ) {
			$url_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
			if ( '' === $url_host || $host === $url_host ) {
				return true;
			}
		}
		return false;
	}

	private static function images_have_alt( $content ) {
		if ( ! preg_match_all( '/<img\b[^>]*>/i', $content, $matches ) ) {
			return true;
		}
		foreach ( $matches[0] as $image ) {
			if ( ! preg_match( '/\balt\s*=\s*(["\']).*?\1/i', $image ) ) {
				return false;
			}
		}
		return true;
	}
}
