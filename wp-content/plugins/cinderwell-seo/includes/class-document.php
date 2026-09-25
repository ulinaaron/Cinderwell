<?php
namespace Cinderwell_SEO;

defined( 'ABSPATH' ) || exit;

/** Resolve inherited and explicit metadata into one frontend/editor contract. */
class Document {
	public static function resolve( array $context = [] ) {
		$post_id = absint( $context['post_id'] ?? 0 );
		$term_id = absint( $context['term_id'] ?? 0 );

		if ( ! $post_id && ! $term_id && is_singular() ) {
			$post_id = get_queried_object_id();
		}
		if ( ! $term_id && ! $post_id && ( is_category() || is_tag() || is_tax() ) ) {
			$term_id = get_queried_object_id();
		}

		if ( $post_id ) {
			return self::post( $post_id );
		}
		if ( $term_id ) {
			return self::term( $term_id );
		}

		return self::site_context();
	}

	private static function post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return [];
		}
		$meta        = Meta::values( $post_id );
		$settings    = Settings::get();
		$is_front    = absint( get_option( 'page_on_front' ) ) === $post_id;
		$search_policy = function_exists( 'cinderwell_portal_get_search_policy' ) ? (array) cinderwell_portal_get_search_policy( $post_id ) : [];
		$description = '';
		if ( empty( $search_policy['suppress_description'] ) ) {
			$description = trim( (string) $meta['description'] );
			if ( '' === $description && $is_front ) {
				$description = trim( (string) $settings['home_description'] );
			}
			if ( '' === $description ) {
				$description = trim( wp_strip_all_tags( (string) $post->post_excerpt ) );
			}
			if ( '' === $description ) {
				$description = self::content_summary( $post->post_content );
			}
		}
		$title       = '' !== trim( (string) $meta['title'] ) ? $meta['title'] : ( $is_front && $settings['home_title'] ? $settings['home_title'] : get_the_title( $post ) );
		$canonical   = '' !== $meta['canonical'] ? $meta['canonical'] : wp_get_canonical_url( $post );
		$image_id    = absint( $meta['social_image_id'] ) ?: get_post_thumbnail_id( $post );
		$image_id    = $image_id ?: absint( $settings['default_social_image_id'] );

		$document = [
			'object_type'        => 'post',
			'object_id'          => $post_id,
			'post_type'          => $post->post_type,
			'custom_title'       => '' !== trim( (string) $meta['title'] ) || ( $is_front && '' !== trim( (string) $settings['home_title'] ) ),
			'title'              => $title,
			'description'        => $description,
			'canonical'          => $canonical,
			'canonical_custom'   => '' !== $meta['canonical'],
			'robots_index'       => ! empty( $search_policy['noindex'] ) ? 'noindex' : $meta['robots_index'],
			'robots_follow'      => $meta['robots_follow'],
			'social_title'       => '' !== trim( (string) $meta['social_title'] ) ? $meta['social_title'] : $title,
			'social_description' => ! empty( $search_policy['suppress_description'] ) ? '' : ( '' !== trim( (string) $meta['social_description'] ) ? $meta['social_description'] : $description ),
			'social_image_id'    => $image_id,
			'social_image'       => $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '',
			'og_type'            => 'post' === $post->post_type ? 'article' : 'website',
			'suppress_social'     => ! empty( $search_policy['suppress_social'] ),
			'suppress_schema'     => ! empty( $search_policy['suppress_schema'] ),
			'search_policy'       => $search_policy,
		];

		return (array) apply_filters( 'cinderwell_seo_document', $document, [ 'post_id' => $post_id ] );
	}

	private static function term( $term_id ) {
		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return [];
		}
		$meta        = Meta::values( $term_id, 'term' );
		$title       = '' !== trim( (string) $meta['title'] ) ? $meta['title'] : $term->name;
		$description = '' !== trim( (string) $meta['description'] ) ? $meta['description'] : wp_strip_all_tags( term_description( $term ) );
		$url         = get_term_link( $term );
		$settings    = Settings::get();
		$image_id    = absint( $meta['social_image_id'] ) ?: absint( $settings['default_social_image_id'] );

		$document = [
			'object_type'        => 'term',
			'object_id'          => $term_id,
			'taxonomy'           => $term->taxonomy,
			'custom_title'       => '' !== trim( (string) $meta['title'] ),
			'title'              => $title,
			'description'        => $description,
			'canonical'          => '' !== $meta['canonical'] ? $meta['canonical'] : ( is_wp_error( $url ) ? '' : $url ),
			'canonical_custom'   => '' !== $meta['canonical'],
			'robots_index'       => $meta['robots_index'],
			'robots_follow'      => $meta['robots_follow'],
			'social_title'       => '' !== trim( (string) $meta['social_title'] ) ? $meta['social_title'] : $title,
			'social_description' => '' !== trim( (string) $meta['social_description'] ) ? $meta['social_description'] : $description,
			'social_image_id'    => $image_id,
			'social_image'       => $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '',
			'og_type'            => 'website',
		];

		return (array) apply_filters( 'cinderwell_seo_document', $document, [ 'term_id' => $term_id ] );
	}

	private static function site_context() {
		$settings = Settings::get();
		$title       = get_bloginfo( 'name' );
		$description = get_bloginfo( 'description' );
		$canonical   = home_url( '/' );

		$custom_title = false;
		if ( is_home() || is_front_page() ) {
			$title       = $settings['home_title'] ?: $title;
			$description = $settings['home_description'] ?: $description;
			$custom_title = '' !== trim( (string) $settings['home_title'] );
		} elseif ( is_post_type_archive() ) {
			$post_type   = get_query_var( 'post_type' );
			$post_type   = is_array( $post_type ) ? reset( $post_type ) : $post_type;
			$object      = get_post_type_object( $post_type );
			$archive     = $settings['archives'][ $post_type ] ?? [];
			$title       = ! empty( $archive['title'] ) ? $archive['title'] : ( $object ? $object->labels->name : $title );
			$description = ! empty( $archive['description'] ) ? $archive['description'] : ( $object ? $object->description : '' );
			$canonical   = get_post_type_archive_link( $post_type ) ?: $canonical;
			$custom_title = ! empty( $archive['title'] );
		} else {
			return [];
		}

		$image_id = absint( $settings['default_social_image_id'] );
		$document = [
			'object_type'        => 'site',
			'object_id'          => 0,
			'custom_title'       => $custom_title,
			'title'              => $title,
			'description'        => $description,
			'canonical'          => $canonical,
			'canonical_custom'   => false,
			'robots_index'       => '',
			'robots_follow'      => '',
			'social_title'       => $title,
			'social_description' => $description,
			'social_image_id'    => $image_id,
			'social_image'       => $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '',
			'og_type'            => 'website',
		];

		return (array) apply_filters( 'cinderwell_seo_document', $document, [ 'site' => true ] );
	}

	public static function content_summary( $content ) {
		$content = preg_replace( '/<!--.*?-->/s', ' ', (string) $content );
		$content = wp_strip_all_tags( strip_shortcodes( $content ) );
		$content = preg_replace( '/\s+/u', ' ', $content );
		return wp_trim_words( trim( $content ), 32, '…' );
	}
}
