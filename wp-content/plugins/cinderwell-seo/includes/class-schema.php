<?php
namespace Cinderwell_SEO;

defined( 'ABSPATH' ) || exit;

/** Minimal extensible site/document JSON-LD graph. */
class Schema {
	public function __construct() {
		add_action( 'wp_head', [ $this, 'output' ], 6 );
	}

	public function output() {
		$document = Document::resolve();
		if ( empty( $document ) ) {
			return;
		}

		$website_id = home_url( '/#website' );
		$graph      = [
			[
				'@type' => 'WebSite',
				'@id'   => $website_id,
				'url'   => home_url( '/' ),
				'name'  => get_bloginfo( 'name' ),
			],
		];

		if ( ! empty( $document['canonical'] ) && empty( $document['suppress_schema'] ) ) {
			$type = ( 'post' === ( $document['post_type'] ?? '' ) ) ? 'Article' : 'WebPage';
			$page = [
				'@type'       => $type,
				'@id'         => trailingslashit( $document['canonical'] ) . '#webpage',
				'url'         => $document['canonical'],
				'name'        => $document['title'],
				'description' => $document['description'],
				'isPartOf'    => [ '@id' => $website_id ],
			];
			if ( ! empty( $document['social_image'] ) ) {
				$page['primaryImageOfPage'] = [ '@type' => 'ImageObject', 'url' => $document['social_image'] ];
			}

			if ( class_exists( '\\Cinderwell\\Company_Details' ) && method_exists( '\\Cinderwell\\Company_Details', 'get_settings' ) ) {
				$company = \Cinderwell\Company_Details::get_settings();
				if ( ! empty( $company['schema_enabled'] ) && ! empty( $company['name'] ) ) {
					$graph[0]['publisher'] = [ '@id' => home_url( '/#organization' ) ];
					$page['publisher']     = [ '@id' => home_url( '/#organization' ) ];
				}
			}
			$graph[] = array_filter( $page );
		}

		$graph = (array) apply_filters( 'cinderwell_seo_schema_graph', $graph, $document );
		if ( ! $graph ) {
			return;
		}
		echo '<script type="application/ld+json">' . wp_json_encode(
			[ '@context' => 'https://schema.org', '@graph' => array_values( $graph ) ],
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		) . '</script>' . "\n";
	}
}
