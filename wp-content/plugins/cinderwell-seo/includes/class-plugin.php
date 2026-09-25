<?php
namespace Cinderwell_SEO;

defined( 'ABSPATH' ) || exit;

/** Main add-on coordinator. */
class Plugin {
	private static $instance;
	private $compatibility;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->compatibility = new Compatibility();

		new Meta();
		new Admin( $this->compatibility );
		new Rest_Controller( $this->compatibility );

		if ( ! $this->compatibility->has_conflict() ) {
			new Frontend();
			new Sitemaps();
			new Schema();
		}
	}

	public function compatibility() {
		return $this->compatibility;
	}

	public static function get_health() {
		$conflict = self::instance()->compatibility()->get_conflict();
		if ( $conflict ) {
			return [
				'status'  => 'warning',
				'message' => sprintf(
					/* translators: %s: SEO provider name. */
					__( 'Standing by while %s manages search metadata.', 'cinderwell-seo' ),
					$conflict
				),
			];
		}

		if ( ! get_option( 'blog_public' ) ) {
			return [ 'status' => 'warning', 'message' => __( 'Search engines are discouraged from indexing this site.', 'cinderwell-seo' ) ];
		}

		return [ 'status' => 'good', 'message' => __( 'Cinderwell owns search metadata and the site is visible to search engines.', 'cinderwell-seo' ) ];
	}
}
