<?php
namespace Cinderwell_SEO;

defined( 'ABSPATH' ) || exit;

/** Derive local search visibility without making external indexing claims. */
class Visibility {
	public static function resolve( $object_id, $object_type = 'post' ) {
		if ( Plugin::instance()->compatibility()->has_conflict() ) {
			return self::status( 'managed', __( 'Managed elsewhere', 'cinderwell-seo' ), Plugin::instance()->compatibility()->get_conflict() );
		}
		if ( ! get_option( 'blog_public' ) ) {
			return self::status( 'site-hidden', __( 'Site hidden', 'cinderwell-seo' ), __( 'WordPress discourages search engines from indexing this site.', 'cinderwell-seo' ) );
		}

		if ( 'term' === $object_type ) {
			$term = get_term( $object_id );
			if ( ! $term || is_wp_error( $term ) || ! is_taxonomy_viewable( $term->taxonomy ) ) {
				return self::status( 'not-public', __( 'Not public', 'cinderwell-seo' ), '' );
			}
			$meta = Meta::values( $object_id, 'term' );
			$url  = get_term_link( $term );
		} else {
			$post = get_post( $object_id );
			if ( ! $post || 'publish' !== $post->post_status || ! is_post_type_viewable( $post->post_type ) ) {
				return self::status( 'not-public', __( 'Draft/private', 'cinderwell-seo' ), __( 'Only published public content can be indexed.', 'cinderwell-seo' ) );
			}
			$meta = Meta::values( $object_id );
			$url  = get_permalink( $post );
			$portal_policy = function_exists( 'cinderwell_portal_get_search_policy' ) ? (array) cinderwell_portal_get_search_policy( $object_id ) : [];
			if ( ! empty( $portal_policy['noindex'] ) ) {
				return self::status(
					sanitize_key( $portal_policy['code'] ?? 'portal-protected' ),
					sanitize_text_field( $portal_policy['label'] ?? __( 'Members only', 'cinderwell-seo' ) ),
					sanitize_text_field( $portal_policy['description'] ?? __( 'Members Portal prevents search engines from indexing this page.', 'cinderwell-seo' ) )
				);
			}
		}

		if ( 'noindex' === $meta['robots_index'] ) {
			return self::status( 'noindex', __( 'Noindex', 'cinderwell-seo' ), __( 'This item asks search engines not to index it.', 'cinderwell-seo' ) );
		}

		if ( ! empty( $meta['canonical'] ) && self::normalize_url( $meta['canonical'] ) !== self::normalize_url( is_wp_error( $url ) ? '' : $url ) ) {
			return self::status( 'canonical-elsewhere', __( 'Canonical elsewhere', 'cinderwell-seo' ), __( 'Search engines are directed to treat another URL as canonical.', 'cinderwell-seo' ) );
		}

		return self::status( 'indexable', __( 'Indexable', 'cinderwell-seo' ), __( 'Local settings allow search engines to index this item.', 'cinderwell-seo' ) );
	}

	private static function normalize_url( $url ) {
		return untrailingslashit( strtolower( (string) $url ) );
	}

	private static function status( $code, $label, $description ) {
		return [ 'code' => $code, 'label' => $label, 'description' => $description ];
	}
}
