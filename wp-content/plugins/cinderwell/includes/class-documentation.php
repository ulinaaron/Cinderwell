<?php
/**
 * Package-owned documentation registry and Markdown renderer.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

/**
 * Collect documentation from active Cinderwell packages without coupling it to
 * a particular interface such as the Cinderwell Help add-on.
 */
class Documentation {

	/** @var Documentation|null */
	private static $instance = null;

	/** @var array<string,array> */
	private $sources = [];

	/** @var array<string,array>|null */
	private $sections = null;

	/** @var array<string,array>|null */
	private $topics = null;

	/** @var bool */
	private $discovering = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->register_directory( 'cinderwell', CINDERWELL_DIR . 'help' );
		add_action( 'after_setup_theme', [ $this, 'reset_index' ], PHP_INT_MAX );
	}

	/** Rebuild after the client theme has had an opportunity to register docs. */
	public function reset_index() {
		$this->sections = null;
		$this->topics   = null;
	}

	/**
	 * Register one package's documentation directory.
	 *
	 * The directory must contain a manifest.php file returning package version,
	 * section definitions, and topic definitions. Topic bodies remain separate
	 * Markdown files and are read only when a consumer requests content.
	 */
	public function register_directory( $package, $directory ) {
		$package   = sanitize_key( $package );
		$directory = untrailingslashit( (string) $directory );

		if ( ! $package || ! is_readable( $directory . '/manifest.php' ) ) {
			return false;
		}

		$this->sources[ $package ] = [
			'package'   => $package,
			'directory' => $directory,
		];
		$this->sections = null;
		$this->topics   = null;

		return true;
	}

	/** Give active add-ons and the client theme one lazy registration point. */
	private function discover() {
		if ( $this->discovering ) {
			return;
		}

		$this->discovering = true;
		/**
		 * Register package-owned documentation sources.
		 *
		 * @param Documentation $registry Documentation registry instance.
		 */
		do_action( 'cinderwell_register_documentation', $this );
		$this->discovering = false;
	}

	/** @return array<string,array> */
	public function get_sections() {
		$this->build_index();
		return $this->sections;
	}

	/**
	 * Return topic metadata, optionally with rendered HTML content.
	 *
	 * @param bool $with_content Whether to read and render Markdown bodies.
	 * @return array<string,array>
	 */
	public function get_topics( $with_content = false ) {
		$this->build_index();
		$topics = $this->topics;

		if ( $with_content ) {
			foreach ( $topics as &$topic ) {
				$topic['content'] = $this->render_topic( $topic );
			}
			unset( $topic );
		}

		return $topics;
	}

	/** Return one normalized topic or null. */
	public function get_topic( $topic_id, $with_content = true ) {
		$topics   = $this->get_topics( false );
		$topic_id = sanitize_key( $topic_id );
		if ( ! isset( $topics[ $topic_id ] ) ) {
			return null;
		}

		$topic = $topics[ $topic_id ];
		if ( $with_content ) {
			$topic['content'] = $this->render_topic( $topic );
		}
		return $topic;
	}

	/**
	 * Provide a transport-neutral bundle for release tooling and future consumers.
	 *
	 * @param string $visibility Visibility to export, normally public or internal.
	 * @return array
	 */
	public function get_export_data( $visibility = 'public' ) {
		$visibility = sanitize_key( $visibility ) ?: 'public';
		$topics     = [];

		foreach ( $this->get_topics( false ) as $id => $topic ) {
			if ( 'all' !== $visibility && $visibility !== $topic['visibility'] ) {
				continue;
			}

			$export             = $topic;
			$export['markdown'] = $this->read_topic( $topic );
			unset( $export['content_path'] );
			$topics[ $id ] = $export;
		}

		return [
			'generated_at' => gmdate( 'c' ),
			'sections'     => $this->get_sections(),
			'topics'       => $topics,
		];
	}

	private function build_index() {
		if ( null !== $this->sections && null !== $this->topics ) {
			return;
		}

		$this->discover();
		$sections = [];
		$topics   = [];

		foreach ( $this->sources as $source ) {
			$manifest = include $source['directory'] . '/manifest.php';
			if ( ! is_array( $manifest ) ) {
				continue;
			}

			$package_version = isset( $manifest['version'] ) ? (string) $manifest['version'] : '';
			foreach ( (array) ( $manifest['sections'] ?? [] ) as $key => $section ) {
				$key = sanitize_key( $key );
				if ( ! $key || ! is_array( $section ) || empty( $section['title'] ) ) {
					continue;
				}
				$sections[ $key ] = [
					'title'       => (string) $section['title'],
					'description' => (string) ( $section['description'] ?? '' ),
					'audience'    => 'development' === sanitize_key( $section['audience'] ?? '' ) ? 'development' : 'user',
					'order'       => isset( $section['order'] ) ? (int) $section['order'] : 100,
					'package'     => $source['package'],
				];
			}

			foreach ( (array) ( $manifest['topics'] ?? [] ) as $key => $topic ) {
				$key = sanitize_key( $key );
				if ( ! $key || ! is_array( $topic ) || empty( $topic['title'] ) || empty( $topic['section'] ) || empty( $topic['file'] ) ) {
					continue;
				}

				$path = $this->resolve_localized_topic_path( $source['directory'], $topic['file'] );
				if ( ! $path ) {
					continue;
				}

				$topics[ $key ] = [
					'id'              => $key,
					'section'         => sanitize_key( $topic['section'] ),
					'title'           => (string) $topic['title'],
					'summary'         => (string) ( $topic['summary'] ?? '' ),
					'icon'            => sanitize_html_class( $topic['icon'] ?? 'dashicons-editor-help' ),
					'order'           => isset( $topic['order'] ) ? (int) $topic['order'] : 100,
					'capability'      => sanitize_key( $topic['capability'] ?? '' ),
					'visibility'      => 'internal' === sanitize_key( $topic['visibility'] ?? '' ) ? 'internal' : 'public',
					'tags'            => array_values( array_filter( array_map( 'sanitize_key', (array) ( $topic['tags'] ?? [] ) ) ) ),
					'package'         => $source['package'],
					'package_version' => $package_version,
					'content_path'    => $path,
				];
			}
		}

		/** Filter all registered documentation sections. */
		$sections = (array) apply_filters( 'cinderwell_documentation_sections', $sections, $this );
		/** Filter all registered documentation topic metadata. */
		$topics = (array) apply_filters( 'cinderwell_documentation_topics', $topics, $sections, $this );

		foreach ( $topics as $key => $topic ) {
			if ( ! is_array( $topic ) || empty( $topic['section'] ) || ! isset( $sections[ $topic['section'] ] ) ) {
				unset( $topics[ $key ] );
			}
		}

		uasort( $sections, static function ( $a, $b ) {
			return ( $a['order'] ?? 100 ) <=> ( $b['order'] ?? 100 );
		} );
		uasort( $topics, static function ( $a, $b ) use ( $sections ) {
			$section_order = ( $sections[ $a['section'] ]['order'] ?? 100 ) <=> ( $sections[ $b['section'] ]['order'] ?? 100 );
			if ( 0 !== $section_order ) {
				return $section_order;
			}
			$topic_order = ( $a['order'] ?? 100 ) <=> ( $b['order'] ?? 100 );
			return 0 !== $topic_order ? $topic_order : strcasecmp( $a['title'] ?? '', $b['title'] ?? '' );
		} );

		$this->sections = $sections;
		$this->topics   = $topics;
	}

	/** Keep topic paths inside their registered package directory. */
	private function resolve_topic_path( $directory, $relative_path ) {
		$base = realpath( $directory );
		$path = realpath( trailingslashit( $directory ) . ltrim( (string) $relative_path, '/\\' ) );
		if ( ! $base || ! $path || ! is_readable( $path ) || 0 !== strpos( $path, trailingslashit( $base ) ) ) {
			return '';
		}
		return $path;
	}

	/** Prefer an explicitly shipped locale variant, then fall back to source. */
	private function resolve_localized_topic_path( $directory, $relative_path ) {
		$relative_path = (string) $relative_path;
		$locale        = sanitize_file_name( determine_locale() );
		if ( $locale && preg_match( '/\.md$/i', $relative_path ) ) {
			$localized = preg_replace( '/\.md$/i', '.' . $locale . '.md', $relative_path );
			$path      = $this->resolve_topic_path( $directory, $localized );
			if ( $path ) {
				return $path;
			}
		}

		return $this->resolve_topic_path( $directory, $relative_path );
	}

	private function read_topic( $topic ) {
		$path = $topic['content_path'] ?? '';
		if ( ! $path || ! is_readable( $path ) ) {
			return '';
		}
		$content = file_get_contents( $path );
		return false === $content ? '' : (string) $content;
	}

	private function render_topic( $topic ) {
		$markdown = $this->read_topic( $topic );
		$html     = self::render_markdown( $markdown );

		/** Filter rendered documentation HTML for one topic. */
		return (string) apply_filters( 'cinderwell_documentation_topic_html', $html, $topic, $markdown );
	}

	/**
	 * Render the intentionally small CommonMark subset used by Cinderwell docs.
	 * Source is escaped before markup is introduced and the result is sanitized.
	 */
	public static function render_markdown( $markdown ) {
		$lines  = preg_split( '/\R/', trim( (string) $markdown ) );
		$html   = [];
		$count  = count( $lines );
		$index  = 0;

		while ( $index < $count ) {
			$line = rtrim( $lines[ $index ] );
			if ( '' === trim( $line ) ) {
				$index++;
				continue;
			}

			if ( preg_match( '/^```([a-zA-Z0-9_-]*)\s*$/', $line, $match ) ) {
				$language = sanitize_html_class( $match[1] ?? '' );
				$code     = [];
				$index++;
				while ( $index < $count && ! preg_match( '/^```\s*$/', rtrim( $lines[ $index ] ) ) ) {
					$code[] = $lines[ $index ];
					$index++;
				}
				$index++;
				$class  = $language ? ' class="language-' . esc_attr( $language ) . '"' : '';
				$html[] = '<pre><code' . $class . '>' . esc_html( implode( "\n", $code ) ) . '</code></pre>';
				continue;
			}

			if ( preg_match( '/^(#{2,4})\s+(.+)$/', $line, $match ) ) {
				$level  = strlen( $match[1] );
				$html[] = '<h' . $level . '>' . self::render_inline_markdown( $match[2] ) . '</h' . $level . '>';
				$index++;
				continue;
			}

			if ( preg_match( '/^[-*]\s+(.+)$/', $line ) ) {
				$items = [];
				while ( $index < $count && preg_match( '/^[-*]\s+(.+)$/', rtrim( $lines[ $index ] ), $match ) ) {
					$items[] = '<li>' . self::render_inline_markdown( $match[1] ) . '</li>';
					$index++;
				}
				$html[] = '<ul>' . implode( '', $items ) . '</ul>';
				continue;
			}

			if ( preg_match( '/^\d+\.\s+(.+)$/', $line ) ) {
				$items = [];
				while ( $index < $count && preg_match( '/^\d+\.\s+(.+)$/', rtrim( $lines[ $index ] ), $match ) ) {
					$items[] = '<li>' . self::render_inline_markdown( $match[1] ) . '</li>';
					$index++;
				}
				$html[] = '<ol>' . implode( '', $items ) . '</ol>';
				continue;
			}

			$paragraph = [ trim( $line ) ];
			$index++;
			while ( $index < $count && '' !== trim( $lines[ $index ] ) && ! preg_match( '/^(?:```|#{2,4}\s|[-*]\s|\d+\.\s)/', rtrim( $lines[ $index ] ) ) ) {
				$paragraph[] = trim( $lines[ $index ] );
				$index++;
			}
			$html[] = '<p>' . self::render_inline_markdown( implode( ' ', $paragraph ) ) . '</p>';
		}

		return wp_kses_post( implode( "\n", $html ) );
	}

	private static function render_inline_markdown( $text ) {
		$pattern = '/(`[^`]+`|\*\*[^*]+\*\*|\*[^*]+\*|\[[^\]]+\]\([^)]+\))/';
		$parts   = preg_split( $pattern, (string) $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		$html    = '';

		foreach ( $parts as $part ) {
			if ( preg_match( '/^`([^`]+)`$/s', $part, $match ) ) {
				$html .= '<code>' . esc_html( $match[1] ) . '</code>';
			} elseif ( preg_match( '/^\*\*([^*]+)\*\*$/s', $part, $match ) ) {
				$html .= '<strong>' . esc_html( $match[1] ) . '</strong>';
			} elseif ( preg_match( '/^\*([^*]+)\*$/s', $part, $match ) ) {
				$html .= '<em>' . esc_html( $match[1] ) . '</em>';
			} elseif ( preg_match( '/^\[([^\]]+)\]\(([^)]+)\)$/s', $part, $match ) ) {
				$url = esc_url( $match[2] );
				$html .= $url ? '<a href="' . $url . '">' . esc_html( $match[1] ) . '</a>' : esc_html( $match[1] );
			} else {
				$html .= esc_html( $part );
			}
		}

		return $html;
	}
}
