<?php
namespace Cinderwell_SEO;

defined( 'ABSPATH' ) || exit;

/** Detect competing SEO providers and place Cinderwell SEO in safe standby. */
class Compatibility {
	private $conflict;

	public function __construct() {
		$this->conflict = $this->detect();
	}

	public function has_conflict() {
		return '' !== $this->conflict;
	}

	public function get_conflict() {
		return $this->conflict;
	}

	private function detect() {
		$providers = [
			'wordpress-seo/wp-seo.php'              => __( 'Yoast SEO', 'cinderwell-seo' ),
			'seo-by-rank-math/rank-math.php'         => __( 'Rank Math', 'cinderwell-seo' ),
			'all-in-one-seo-pack/all_in_one_seo_pack.php' => __( 'All in One SEO', 'cinderwell-seo' ),
			'wp-seopress/seopress.php'               => __( 'SEOPress', 'cinderwell-seo' ),
		];

		/**
		 * Register active SEO providers that should place Cinderwell SEO in standby.
		 *
		 * Keys are plugin basenames and values are human-readable provider names.
		 */
		$providers = (array) apply_filters( 'cinderwell_seo_conflicting_providers', $providers );

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( $providers as $plugin => $label ) {
			if ( is_plugin_active( $plugin ) || ( is_multisite() && is_plugin_active_for_network( $plugin ) ) ) {
				return sanitize_text_field( $label );
			}
		}

		return '';
	}
}
