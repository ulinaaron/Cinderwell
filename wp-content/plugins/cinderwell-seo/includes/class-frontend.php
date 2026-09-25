<?php
namespace Cinderwell_SEO;

defined( 'ABSPATH' ) || exit;

/** Frontend title, description, canonical, robots, and social metadata. */
class Frontend {
	private $document;

	public function __construct() {
		add_action( 'wp', [ $this, 'prepare' ], 20 );
		add_filter( 'pre_get_document_title', [ $this, 'document_title' ], 20 );
		add_filter( 'wp_robots', [ $this, 'robots' ], 20 );
		add_action( 'wp_head', [ $this, 'output_head' ], 5 );
	}

	public function prepare() {
		$this->document = Document::resolve();
		if ( $this->document && ! empty( $this->document['canonical'] ) ) {
			remove_action( 'wp_head', 'rel_canonical' );
		}
	}

	public function document_title( $title ) {
		if ( $this->document && ! empty( $this->document['custom_title'] ) ) {
			return $this->document['title'];
		}
		return $title;
	}

	public function robots( $robots ) {
		if ( empty( $this->document ) ) {
			return $robots;
		}
		if ( 'noindex' === $this->document['robots_index'] ) {
			$robots['noindex'] = true;
		}
		if ( 'nofollow' === $this->document['robots_follow'] ) {
			$robots['nofollow'] = true;
		}
		return $robots;
	}

	public function output_head() {
		$document = $this->document ?: Document::resolve();
		if ( empty( $document ) ) {
			return;
		}

		if ( ! empty( $document['description'] ) ) {
			printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $document['description'] ) );
		}
		if ( ! empty( $document['canonical'] ) ) {
			printf( "<link rel=\"canonical\" href=\"%s\">\n", esc_url( $document['canonical'] ) );
		}

		if ( empty( $document['suppress_social'] ) ) {
			printf( "<meta property=\"og:title\" content=\"%s\">\n", esc_attr( $document['social_title'] ) );
			printf( "<meta property=\"og:type\" content=\"%s\">\n", esc_attr( $document['og_type'] ) );
			printf( "<meta property=\"og:url\" content=\"%s\">\n", esc_url( $document['canonical'] ) );
			if ( ! empty( $document['social_description'] ) ) {
				printf( "<meta property=\"og:description\" content=\"%s\">\n", esc_attr( $document['social_description'] ) );
				printf( "<meta name=\"twitter:description\" content=\"%s\">\n", esc_attr( $document['social_description'] ) );
			}
			printf( "<meta name=\"twitter:card\" content=\"%s\">\n", $document['social_image'] ? 'summary_large_image' : 'summary' );
			printf( "<meta name=\"twitter:title\" content=\"%s\">\n", esc_attr( $document['social_title'] ) );
			if ( ! empty( $document['social_image'] ) ) {
				printf( "<meta property=\"og:image\" content=\"%s\">\n", esc_url( $document['social_image'] ) );
				printf( "<meta name=\"twitter:image\" content=\"%s\">\n", esc_url( $document['social_image'] ) );
			}
		}

		if ( is_front_page() ) {
			$settings = Settings::get();
			if ( $settings['google_verification'] ) {
				printf( "<meta name=\"google-site-verification\" content=\"%s\">\n", esc_attr( $settings['google_verification'] ) );
			}
			if ( $settings['bing_verification'] ) {
				printf( "<meta name=\"msvalidate.01\" content=\"%s\">\n", esc_attr( $settings['bing_verification'] ) );
			}
		}
	}
}
