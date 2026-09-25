<?php
defined( 'ABSPATH' ) || exit;

$scope       = 'global' === ( $attributes['scope'] ?? '' ) ? 'global' : 'current';
$context_id  = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : 0;
$event_id    = 'current' === $scope ? ( $context_id ?: absint( get_queried_object_id() ) ) : 0;
$event_id    = \Cinderwell_Events\Event_Post_Type::POST_TYPE === get_post_type( $event_id ) ? $event_id : 0;
$category_id = absint( $attributes['categoryId'] ?? 0 );
if ( ! $category_id && is_tax( \Cinderwell_Events\Event_Post_Type::TAXONOMY ) ) {
	$term        = get_queried_object();
	$category_id = $term instanceof WP_Term ? $term->term_id : 0;
}
$facets_enabled = 'global' === $scope && ! empty( $attributes['filtersEnabled'] ) && class_exists( '\\Cinderwell\\Facets' );
$facet_id       = $facets_enabled ? \Cinderwell\Facets::normalize_id( $attributes['facetId'] ?? '' ) : '';
$facets_enabled = $facets_enabled && (bool) $facet_id;
$facet_config   = $facets_enabled && is_array( $attributes['facets'] ?? null ) ? $attributes['facets'] : [];
$facet_state    = $facets_enabled ? \Cinderwell\Facets::get_state( $facet_id ) : [];
$per_page       = min( 24, max( 1, absint( $attributes['perPage'] ?? 10 ) ) );
$show_pagination = ! empty( $attributes['showPagination'] );
$page            = $show_pagination
	? ( $facets_enabled ? \Cinderwell\Facets::get_page( $facet_id ) : max( 1, absint( get_query_var( 'paged' ) ), absint( get_query_var( 'page' ) ) ) )
	: 1;
$query_args      = [
	'event_id'    => $event_id,
	'category_id' => $category_id,
	'per_page'    => $per_page,
	'page'        => $page,
];
if ( $facets_enabled ) {
	if ( ! empty( $facet_config['search']['enabled'] ) ) {
		$query_args['search'] = $facet_state['search'] ?? '';
	}
	if ( ! $category_id && ! empty( $facet_config['category']['enabled'] ) ) {
		$query_args['category_ids'] = $facet_state['tax'][ \Cinderwell_Events\Event_Post_Type::TAXONOMY ] ?? [];
	}
	if ( ! empty( $facet_config['date']['enabled'] ) ) {
		$query_args['after']  = ! empty( $facet_state['after'] ) ? $facet_state['after'] . ' 00:00:00' : '';
		$query_args['before'] = ! empty( $facet_state['before'] ) ? $facet_state['before'] . ' 23:59:59' : '';
	}
	if ( ! empty( $facet_config['sort']['enabled'] ) && 'latest' === ( $facet_state['sort'] ?? '' ) ) {
		$query_args['order'] = 'DESC';
	}
}
$result          = 'current' === $scope && ! $event_id
	? [ 'items' => [], 'total' => 0, 'total_pages' => 0 ]
	: cinderwell_events_get_sessions( $query_args );
$event_mode      = $event_id ? \Cinderwell_Events\Event_Post_Type::schedule_mode( $event_id ) : 'sessions';
$heading         = trim( (string) ( $attributes['heading'] ?? '' ) );
$empty_message   = (string) ( $attributes['emptyMessage'] ?? __( 'No upcoming sessions are scheduled.', 'cinderwell-events' ) );
if ( 'current' === $scope && 'single' === $event_mode ) {
	if ( '' === $heading || __( 'Upcoming Sessions', 'cinderwell-events' ) === $heading ) {
		$heading = __( 'Date and time', 'cinderwell-events' );
	}
	if ( __( 'No upcoming sessions are scheduled.', 'cinderwell-events' ) === $empty_message ) {
		$empty_message = __( 'The Event date and time will be announced soon.', 'cinderwell-events' );
	}
}

$background = sanitize_key( $attributes['background'] ?? 'white' );
$allowed_backgrounds = \Cinderwell\Design_Tokens::get_color_slugs( 'background' );
$background = in_array( $background, $allowed_backgrounds, true ) ? $background : 'white';
$width      = in_array( $attributes['width'] ?? '', [ 'narrow', 'standard', 'wide' ], true ) ? $attributes['width'] : 'wide';
$spacing    = in_array( $attributes['spacing'] ?? '', [ 'none', 'sm', 'md', 'lg' ], true ) ? $attributes['spacing'] : 'md';
$classes    = "cinderwell-upcoming-sessions cinderwell-upcoming-sessions--bg-{$background} cinderwell-upcoming-sessions--spacing-{$spacing}";
$wrapper_options = [ 'class' => $classes ];
if ( $facets_enabled ) {
	$wrapper_options['data-cw-facet-region'] = $facet_id;
}
$wrapper = get_block_wrapper_attributes( $wrapper_options );
$facet_fields = [];
if ( $facets_enabled ) {
	if ( ! empty( $facet_config['search']['enabled'] ) ) {
		$facet_fields[] = [ 'type' => 'search', 'label' => sanitize_text_field( $facet_config['search']['label'] ?? '' ) ?: __( 'Search events', 'cinderwell-events' ) ];
	}
	if ( ! $category_id && ! empty( $facet_config['category']['enabled'] ) ) {
		$facet_fields[] = [
			'type'      => 'taxonomy',
			'taxonomy'  => \Cinderwell_Events\Event_Post_Type::TAXONOMY,
			'label'     => sanitize_text_field( $facet_config['category']['label'] ?? '' ) ?: __( 'Event type', 'cinderwell-events' ),
			'display'   => sanitize_key( $facet_config['category']['display'] ?? 'pills' ),
			'hideEmpty' => true,
		];
	}
	if ( ! empty( $facet_config['date']['enabled'] ) ) {
		$facet_fields[] = [ 'type' => 'date', 'label' => sanitize_text_field( $facet_config['date']['label'] ?? '' ) ?: __( 'Session date', 'cinderwell-events' ) ];
	}
	if ( ! empty( $facet_config['sort']['enabled'] ) ) {
		$facet_fields[] = [
			'type'    => 'sort',
			'label'   => sanitize_text_field( $facet_config['sort']['label'] ?? '' ) ?: __( 'Sort by', 'cinderwell-events' ),
			'options' => [
				''       => __( 'Soonest first', 'cinderwell-events' ),
				'latest' => __( 'Latest first', 'cinderwell-events' ),
			],
		];
	}
}
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="cinderwell-upcoming-sessions__inner" style="max-width:var(--cw-width-<?php echo esc_attr( $width ); ?>)">
		<?php if ( ! empty( $attributes['showHeading'] ) && $heading ) : ?>
			<h2 class="cinderwell-upcoming-sessions__heading"><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>
		<?php if ( $facets_enabled && $facet_fields ) : ?>
			<?php echo \Cinderwell\Facets::render( [
				'id'         => $facet_id,
				'fields'     => $facet_fields,
				'state'      => $facet_state,
				'total'      => $result['total'],
				'live'       => ! empty( $attributes['liveFiltering'] ),
				'provider'   => 'events',
				'attributes' => $attributes,
			] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by renderer. ?>
		<?php endif; ?>
		<div class="cinderwell-upcoming-sessions__results"<?php if ( $facets_enabled ) : ?> id="<?php echo esc_attr( 'cinderwell-facet-results-' . $facet_id ); ?>" data-cw-facet-results aria-busy="false"<?php endif; ?>>
		<?php if ( $result['items'] ) : ?>
			<ol class="cinderwell-upcoming-sessions__list">
				<?php foreach ( $result['items'] as $session ) : ?>
					<?php
					$event_id      = absint( $session['event_id'] );
					$has_thumbnail = has_post_thumbnail( $event_id );
					$item_classes  = 'cinderwell-upcoming-sessions__item cinderwell-upcoming-sessions__item--' . sanitize_html_class( $session['state'] );
					$item_classes .= $has_thumbnail ? ' has-image' : '';
					?>
					<li class="<?php echo esc_attr( $item_classes ); ?>">
						<div class="cinderwell-upcoming-sessions__when">
							<time datetime="<?php echo esc_attr( $session['start'] ); ?>"><?php echo esc_html( $session['display']['date'] ); ?></time>
							<span><?php echo esc_html( $session['display']['time'] ); ?></span>
						</div>
						<div class="cinderwell-upcoming-sessions__event">
							<h3><a href="<?php echo esc_url( $session['event']['permalink'] ); ?>"><?php echo esc_html( $session['event']['title'] ); ?></a></h3>
							<?php $excerpt = trim( (string) get_the_excerpt( $event_id ) ); ?>
							<?php if ( $excerpt ) : ?><p class="cinderwell-upcoming-sessions__excerpt"><?php echo esc_html( $excerpt ); ?></p><?php endif; ?>
							<?php $terms = get_the_terms( $event_id, \Cinderwell_Events\Event_Post_Type::TAXONOMY ); ?>
							<?php if ( is_array( $terms ) && $terms ) : ?>
								<div class="cinderwell-upcoming-sessions__terms">
									<?php foreach ( $terms as $term ) : ?><a href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a><?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
						<?php if ( $has_thumbnail ) : ?>
							<a class="cinderwell-upcoming-sessions__image" href="<?php echo esc_url( $session['event']['permalink'] ); ?>" tabindex="-1" aria-hidden="true">
								<?php echo get_the_post_thumbnail( $event_id, 'medium_large', [ 'loading' => 'lazy', 'decoding' => 'async' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core image markup. ?>
							</a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
			<?php if ( $show_pagination && $result['total_pages'] > 1 ) : ?>
				<nav class="cinderwell-upcoming-sessions__pagination" aria-label="<?php esc_attr_e( 'Session pagination', 'cinderwell-events' ); ?>">
					<?php
					$pagination_args = [ 'current' => $page, 'total' => $result['total_pages'], 'type' => 'list', 'prev_text' => __( 'Previous', 'cinderwell-events' ), 'next_text' => __( 'Next', 'cinderwell-events' ) ];
					if ( $facets_enabled ) {
						$path = strtok( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), '?' );
						$big  = 999999999;
						$pagination_args['base'] = str_replace(
							(string) $big,
							'%#%',
							add_query_arg( \Cinderwell\Facets::query_args( $facet_id, $facet_state, $big ), home_url( $path ) )
						);
						$pagination_args['format'] = '';
					}
					echo wp_kses_post( paginate_links( $pagination_args ) );
					?>
				</nav>
			<?php endif; ?>
		<?php else : ?>
			<p class="cinderwell-upcoming-sessions__empty"><?php echo esc_html( $empty_message ); ?></p>
		<?php endif; ?>
		</div>
	</div>
</section>
