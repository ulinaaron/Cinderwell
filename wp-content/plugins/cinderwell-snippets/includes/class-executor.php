<?php
/**
 * Runtime snippet dispatcher and fatal guard.
 *
 * @package Cinderwell_Snippets
 */

namespace Cinderwell_Snippets;

defined( 'ABSPATH' ) || exit;

class Executor {
	private static $current_snippet = 0;
	private static $memory_reserve;
	private $manifest = [];

	public function __construct() {
		add_shortcode( 'cinderwell_snippet', [ $this, 'shortcode' ] );
		if ( Repository::safe_mode_enabled() ) {
			return;
		}

		$this->manifest = Repository::get_manifest();
		self::$memory_reserve = str_repeat( 'x', 262144 );
		add_filter( 'wp_php_error_args', [ $this, 'handle_wordpress_fatal' ], 1, 2 );
		register_shutdown_function( [ $this, 'handle_shutdown' ] );
		$this->register_snippets();
	}

	private function register_snippets() {
		foreach ( $this->manifest as $snippet ) {
			if ( empty( $snippet['id'] ) || empty( $snippet['type'] ) || empty( $snippet['location'] ) ) {
				continue;
			}
			$priority = Repository::sanitize_priority( $snippet['priority'] ?? 10 );
			$type     = $snippet['type'];
			$location = $snippet['location'];

			if ( 'php' === $type && 'shortcode' !== $location ) {
				$hooks = [ 'init' => 'init', 'frontend' => 'wp', 'admin' => 'admin_init', 'login' => 'login_init' ];
				if ( isset( $hooks[ $location ] ) ) {
					add_action( $hooks[ $location ], function () use ( $snippet ) { $this->run_php( $snippet ); }, $priority );
				}
				continue;
			}

			if ( 'js' === $type ) {
				$hooks = [
					'frontend_head' => 'wp_head', 'frontend_footer' => 'wp_footer',
					'admin_head' => 'admin_head', 'admin_footer' => 'admin_footer',
					'login_head' => 'login_head', 'login_footer' => 'login_footer',
				];
				if ( isset( $hooks[ $location ] ) ) add_action( $hooks[ $location ], function () use ( $snippet ) { $this->render_script( $snippet ); }, $priority );
				continue;
			}

			if ( 'css' === $type ) {
				$hooks = [ 'frontend' => 'wp_head', 'admin' => 'admin_head', 'login' => 'login_head' ];
				if ( isset( $hooks[ $location ] ) ) add_action( $hooks[ $location ], function () use ( $snippet ) { $this->render_style( $snippet ); }, $priority );
				continue;
			}

			if ( 'html' === $type && 'shortcode' !== $location ) {
				$hooks = [ 'frontend_head' => 'wp_head', 'body_open' => 'wp_body_open', 'frontend_footer' => 'wp_footer' ];
				if ( isset( $hooks[ $location ] ) ) {
					add_action( $hooks[ $location ], function () use ( $snippet ) { $this->render_html( $snippet ); }, $priority );
				} elseif ( in_array( $location, [ 'before_content', 'after_content' ], true ) ) {
					add_filter( 'the_content', function ( $content ) use ( $snippet, $location ) {
						if ( Repository::safe_mode_enabled() || ! is_singular() || ! in_the_loop() || ! is_main_query() || ! Condition_Evaluator::matches( $snippet ) ) return $content;
						return 'before_content' === $location ? $snippet['code'] . $content : $content . $snippet['code'];
					}, $priority );
				}
			}
		}
	}

	public function shortcode( $atts, $content = '', $tag = '' ) {
		if ( Repository::safe_mode_enabled() ) return '';
		$atts = is_array( $atts ) ? $atts : [];
		$slug = sanitize_title( $atts['id'] ?? '' );
		if ( ! $slug ) return '';

		foreach ( $this->manifest as $snippet ) {
			if ( $slug !== $snippet['slug'] || 'shortcode' !== $snippet['location'] || ! in_array( $snippet['type'], [ 'php', 'html' ], true ) ) continue;
			if ( ! Condition_Evaluator::matches( $snippet ) ) return '';
			if ( 'html' === $snippet['type'] ) return $snippet['code'];

			return $this->run_php( $snippet, [ 'atts' => $atts, 'content' => $content, 'tag' => $tag ], true );
		}
		return '';
	}

	private function run_php( array $snippet, array $context = [], $return_output = false ) {
		if ( Repository::safe_mode_enabled() ) return $return_output ? '' : null;
		if ( ! Condition_Evaluator::matches( $snippet ) ) return $return_output ? '' : null;

		self::$current_snippet = absint( $snippet['id'] );
		$buffer_level = ob_get_level();
		ob_start();
		try {
			$code = Repository::prepare_php_for_execution( $snippet['code'] );
			if ( is_wp_error( $code ) ) {
				throw new \RuntimeException( $code->get_error_message() );
			}
			$result = cinderwell_snippets_execute_php_code( $code, $context );
			$output = (string) ob_get_clean();
			self::$current_snippet = 0;
			if ( $return_output ) {
				return $output . ( is_scalar( $result ) ? (string) $result : '' );
			}
			return $result;
		} catch ( \Throwable $error ) {
			while ( ob_get_level() > $buffer_level ) ob_end_clean();
			self::$current_snippet = 0;
			Repository::disable_with_error( $snippet['id'], [
				'type'    => get_class( $error ),
				'message' => $error->getMessage(),
				'file'    => $error->getFile(),
				'line'    => $error->getLine(),
			] );
			return $return_output ? '' : null;
		}
	}

	private function render_script( array $snippet ) {
		if ( Repository::safe_mode_enabled() ) return;
		if ( ! Condition_Evaluator::matches( $snippet ) ) return;
		$code = Repository::prepare_javascript_for_output( $snippet['code'] );
		$code = str_ireplace( '</script', '<\\/script', $code );
		echo "\n<script id=\"cinderwell-snippet-" . absint( $snippet['id'] ) . "\">\n" . $code . "\n</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted administrator-authored JavaScript.
	}

	private function render_style( array $snippet ) {
		if ( Repository::safe_mode_enabled() ) return;
		if ( ! Condition_Evaluator::matches( $snippet ) ) return;
		$code = str_ireplace( '</style', '<\\/style', $snippet['code'] );
		echo "\n<style id=\"cinderwell-snippet-" . absint( $snippet['id'] ) . "\">\n" . $code . "\n</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted administrator-authored CSS.
	}

	private function render_html( array $snippet ) {
		if ( ! Repository::safe_mode_enabled() && Condition_Evaluator::matches( $snippet ) ) {
			echo $snippet['code']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted administrator-authored HTML.
		}
	}

	public function handle_shutdown() {
		self::$memory_reserve = null;
		if ( ! self::$current_snippet ) return;
		$error = error_get_last();
		if ( $this->is_fatal( $error ) ) $this->disable_current_fatal( $error );
	}

	/**
	 * Disable the active snippet before WordPress renders its fatal error page.
	 * WordPress's shutdown callback is registered before plugins and may exit,
	 * so this filter is the reliable point at which to persist recovery state.
	 */
	public function handle_wordpress_fatal( $args, $error ) {
		self::$memory_reserve = null;
		if ( self::$current_snippet && $this->is_fatal( $error ) ) $this->disable_current_fatal( $error );
		return $args;
	}

	private function is_fatal( $error ) {
		$fatal = [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ];
		return is_array( $error ) && in_array( $error['type'] ?? 0, $fatal, true );
	}

	private function disable_current_fatal( array $error ) {
		$post_id = self::$current_snippet;
		self::$current_snippet = 0;
		Repository::disable_with_error( $post_id, [
			'type'    => 'PHP fatal ' . absint( $error['type'] ?? 0 ),
			'message' => $error['message'] ?? '',
			'file'    => $error['file'] ?? '',
			'line'    => $error['line'] ?? 0,
		] );
	}
}
