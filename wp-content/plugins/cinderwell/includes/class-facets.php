<?php
/**
 * Shared, progressively enhanced facet controls for Cinderwell result blocks.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Facets {

    const QUERY_VAR = 'cw_filter';
    const PAGE_VAR  = 'cw_page';

    public function __construct() {
        add_action( 'init', [ $this, 'register_assets' ], 5 );
    }

    public function register_assets() {
        wp_register_style(
            'cinderwell-facets',
            CINDERWELL_BUILD_URL . 'shared/facets.css',
            [ 'cinderwell-base', 'cinderwell-forms' ],
            CINDERWELL_VERSION
        );
        wp_register_script(
            'cinderwell-facets',
            CINDERWELL_BUILD_URL . 'shared/facets.js',
            [],
            CINDERWELL_VERSION,
            true
        );
    }

    public static function normalize_id( $value ) {
        return sanitize_key( (string) $value );
    }

    /**
     * Read and sanitize the namespaced state for one result block.
     */
    public static function get_state( $facet_id ) {
        $facet_id = self::normalize_id( $facet_id );
        if ( ! $facet_id || empty( $_GET[ self::QUERY_VAR ] ) || ! is_array( $_GET[ self::QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only filters.
            return self::empty_state();
        }

        $all = wp_unslash( $_GET[ self::QUERY_VAR ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only filters.
        $raw = isset( $all[ $facet_id ] ) && is_array( $all[ $facet_id ] ) ? $all[ $facet_id ] : [];
        $tax = [];
        foreach ( (array) ( $raw['tax'] ?? [] ) as $taxonomy => $term_ids ) {
            $taxonomy = sanitize_key( $taxonomy );
            if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
                continue;
            }
            $tax[ $taxonomy ] = array_values( array_unique( array_filter( array_map( 'absint', (array) $term_ids ) ) ) );
        }

        return [
            'search' => sanitize_text_field( (string) ( $raw['search'] ?? '' ) ),
            'tax'    => $tax,
            'after'  => self::sanitize_date( $raw['after'] ?? '' ),
            'before' => self::sanitize_date( $raw['before'] ?? '' ),
            'sort'   => sanitize_key( (string) ( $raw['sort'] ?? '' ) ),
        ];
    }

    public static function get_page( $facet_id ) {
        $facet_id = self::normalize_id( $facet_id );
        if ( ! $facet_id || empty( $_GET[ self::PAGE_VAR ] ) || ! is_array( $_GET[ self::PAGE_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public pagination state.
            return 1;
        }
        $pages = wp_unslash( $_GET[ self::PAGE_VAR ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public pagination state.
        return max( 1, absint( $pages[ $facet_id ] ?? 1 ) );
    }

    public static function query_args( $facet_id, array $state, $page = null ) {
        $facet_id = self::normalize_id( $facet_id );
        $state    = self::compact_state( $state );
        $args     = [];
        if ( $facet_id && $state ) {
            $args[ self::QUERY_VAR ] = [ $facet_id => $state ];
        }
        if ( $facet_id && null !== $page ) {
            $args[ self::PAGE_VAR ] = [ $facet_id => max( 1, absint( $page ) ) ];
        }
        return $args;
    }

    /**
     * Render a provider-neutral filter form.
     *
     * Fields are registered as search, taxonomy, date, or sort definitions.
     */
    public static function render( array $args ) {
        $facet_id = self::normalize_id( $args['id'] ?? '' );
        $fields   = is_array( $args['fields'] ?? null ) ? $args['fields'] : [];
        if ( ! $facet_id || ! $fields ) {
            return '';
        }

        $fields = (array) apply_filters( 'cinderwell_facets_fields', $fields, $args );
        $state  = is_array( $args['state'] ?? null ) ? $args['state'] : self::get_state( $facet_id );
        $live   = ! empty( $args['live'] );
        $total  = isset( $args['total'] ) ? absint( $args['total'] ) : null;
        $results_id = 'cinderwell-facet-results-' . $facet_id;

        wp_enqueue_style( 'cinderwell-facets' );
        if ( $live && ! is_admin() ) {
            wp_enqueue_script( 'cinderwell-facets' );
        }

        ob_start();
        ?>
        <form class="cinderwell-facets cinderwell-form" method="get" aria-label="<?php esc_attr_e( 'Filter results', 'cinderwell' ); ?>" aria-controls="<?php echo esc_attr( $results_id ); ?>" data-cw-facet-form="<?php echo esc_attr( $facet_id ); ?>" data-cw-facet-updating="<?php esc_attr_e( 'Updating results…', 'cinderwell' ); ?>"<?php echo $live ? ' data-cw-facet-live="true"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attribute. ?>>
            <div class="cinderwell-facets__fields">
                <?php foreach ( $fields as $field ) : ?>
                    <?php self::render_field( $facet_id, $field, $state ); ?>
                <?php endforeach; ?>
                <div class="cinderwell-facets__actions">
                    <button class="cinderwell-facets__submit" type="submit"><?php esc_html_e( 'Apply filters', 'cinderwell' ); ?></button>
                    <?php if ( self::compact_state( $state ) ) : ?>
                        <a class="cinderwell-facets__clear" href="<?php echo esc_url( self::clear_url( $facet_id ) ); ?>"><?php esc_html_e( 'Clear filters', 'cinderwell' ); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php self::render_preserved_query_fields( $facet_id ); ?>
            <p class="cinderwell-facets__summary" role="status" aria-live="polite" aria-atomic="true" data-cw-facet-status>
                <?php if ( null !== $total ) : ?>
                    <?php echo esc_html( sprintf( _n( '%s result', '%s results', $total, 'cinderwell' ), number_format_i18n( $total ) ) ); ?>
                <?php endif; ?>
            </p>
        </form>
        <?php
        return (string) ob_get_clean();
    }

    private static function render_field( $facet_id, array $field, array $state ) {
        $type  = sanitize_key( $field['type'] ?? '' );
        $label = sanitize_text_field( $field['label'] ?? '' );
        if ( 'search' === $type ) {
            ?>
            <label class="cinderwell-facets__field cinderwell-facets__field--search">
                <span><?php echo esc_html( $label ?: __( 'Search', 'cinderwell' ) ); ?></span>
                <input type="search" name="<?php echo esc_attr( self::name( $facet_id, 'search' ) ); ?>" value="<?php echo esc_attr( $state['search'] ?? '' ); ?>">
            </label>
            <?php
            return;
        }

        if ( 'taxonomy' === $type ) {
            self::render_taxonomy_field( $facet_id, $field, $state );
            return;
        }

        if ( 'date' === $type ) {
            ?>
            <fieldset class="cinderwell-facets__field cinderwell-facets__field--date">
                <legend><?php echo esc_html( $label ?: __( 'Date', 'cinderwell' ) ); ?></legend>
                <div class="cinderwell-facets__date-range">
                    <label><span><?php esc_html_e( 'From', 'cinderwell' ); ?></span><input type="date" name="<?php echo esc_attr( self::name( $facet_id, 'after' ) ); ?>" value="<?php echo esc_attr( $state['after'] ?? '' ); ?>"></label>
                    <label><span><?php esc_html_e( 'Through', 'cinderwell' ); ?></span><input type="date" name="<?php echo esc_attr( self::name( $facet_id, 'before' ) ); ?>" value="<?php echo esc_attr( $state['before'] ?? '' ); ?>"></label>
                </div>
            </fieldset>
            <?php
            return;
        }

        if ( 'sort' === $type ) {
            $options = is_array( $field['options'] ?? null ) ? $field['options'] : [];
            ?>
            <label class="cinderwell-facets__field cinderwell-facets__field--sort">
                <span><?php echo esc_html( $label ?: __( 'Sort', 'cinderwell' ) ); ?></span>
                <select name="<?php echo esc_attr( self::name( $facet_id, 'sort' ) ); ?>">
                    <?php foreach ( $options as $value => $option_label ) : ?>
                        <option value="<?php echo esc_attr( sanitize_key( $value ) ); ?>" <?php selected( $state['sort'] ?? '', $value ); ?>><?php echo esc_html( $option_label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php
        }
    }

    private static function render_taxonomy_field( $facet_id, array $field, array $state ) {
        $taxonomy = sanitize_key( $field['taxonomy'] ?? '' );
        if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
            return;
        }
        $taxonomy_object = get_taxonomy( $taxonomy );
        $label            = sanitize_text_field( $field['label'] ?? '' ) ?: $taxonomy_object->labels->name;
        $display          = in_array( $field['display'] ?? '', [ 'select', 'pills', 'checkboxes' ], true ) ? $field['display'] : 'select';
        $selected         = array_map( 'absint', (array) ( $state['tax'][ $taxonomy ] ?? [] ) );
        $term_args        = [
            'taxonomy'   => $taxonomy,
            'hide_empty' => ! isset( $field['hideEmpty'] ) || (bool) $field['hideEmpty'],
            'orderby'    => in_array( $field['orderby'] ?? '', [ 'name', 'count', 'term_order' ], true ) ? $field['orderby'] : 'name',
            'order'      => 'DESC' === strtoupper( $field['order'] ?? '' ) ? 'DESC' : 'ASC',
        ];
        if ( ! empty( $field['include'] ) ) {
            $term_args['include'] = array_map( 'absint', (array) $field['include'] );
        }
        if ( ! empty( $field['exclude'] ) ) {
            $term_args['exclude'] = array_map( 'absint', (array) $field['exclude'] );
        }
        $terms = get_terms( $term_args );
        if ( is_wp_error( $terms ) || ! $terms ) {
            return;
        }

        if ( 'select' === $display ) {
            ?>
            <label class="cinderwell-facets__field cinderwell-facets__field--taxonomy">
                <span><?php echo esc_html( $label ); ?></span>
                <select name="<?php echo esc_attr( self::name( $facet_id, 'tax][' . $taxonomy ) ); ?>">
                    <option value=""><?php echo esc_html( sprintf( __( 'All %s', 'cinderwell' ), strtolower( $label ) ) ); ?></option>
                    <?php foreach ( $terms as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( in_array( $term->term_id, $selected, true ) ); ?>><?php echo esc_html( $term->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php
            return;
        }
        ?>
        <fieldset class="cinderwell-facets__field cinderwell-facets__field--taxonomy cinderwell-facets__field--<?php echo esc_attr( $display ); ?>">
            <legend><?php echo esc_html( $label ); ?></legend>
            <div class="cinderwell-facets__choices">
                <?php foreach ( $terms as $term ) : ?>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( self::name( $facet_id, 'tax][' . $taxonomy ) . '[]' ); ?>" value="<?php echo esc_attr( $term->term_id ); ?>" <?php checked( in_array( $term->term_id, $selected, true ) ); ?>>
                        <span><?php echo esc_html( $term->name ); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <?php
    }

    private static function render_preserved_query_fields( $facet_id ) {
        $query = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve public query state.
        unset( $query[ self::PAGE_VAR ] );
        if ( isset( $query[ self::QUERY_VAR ][ $facet_id ] ) ) {
            unset( $query[ self::QUERY_VAR ][ $facet_id ] );
        }
        foreach ( self::flatten_query( $query ) as $name => $value ) {
            printf( '<input type="hidden" name="%s" value="%s">', esc_attr( $name ), esc_attr( $value ) );
        }
    }

    private static function flatten_query( array $values, $prefix = '' ) {
        $flat = [];
        foreach ( $values as $key => $value ) {
            $key  = sanitize_key( $key );
            $name = $prefix ? $prefix . '[' . $key . ']' : $key;
            if ( is_array( $value ) ) {
                $flat += self::flatten_query( $value, $name );
            } elseif ( is_scalar( $value ) ) {
                $flat[ $name ] = sanitize_text_field( (string) $value );
            }
        }
        return $flat;
    }

    private static function clear_url( $facet_id ) {
        $query = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public navigation state.
        unset( $query[ self::PAGE_VAR ] );
        if ( isset( $query[ self::QUERY_VAR ][ $facet_id ] ) ) {
            unset( $query[ self::QUERY_VAR ][ $facet_id ] );
        }
        $path = strtok( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), '?' );
        return add_query_arg( $query, home_url( $path ) );
    }

    private static function compact_state( array $state ) {
        $compact = [];
        foreach ( [ 'search', 'after', 'before', 'sort' ] as $key ) {
            if ( ! empty( $state[ $key ] ) ) {
                $compact[ $key ] = $state[ $key ];
            }
        }
        foreach ( (array) ( $state['tax'] ?? [] ) as $taxonomy => $term_ids ) {
            $term_ids = array_values( array_filter( array_map( 'absint', (array) $term_ids ) ) );
            if ( $term_ids ) {
                $compact['tax'][ sanitize_key( $taxonomy ) ] = $term_ids;
            }
        }
        return $compact;
    }

    private static function empty_state() {
        return [ 'search' => '', 'tax' => [], 'after' => '', 'before' => '', 'sort' => '' ];
    }

    private static function sanitize_date( $value ) {
        $value = sanitize_text_field( (string) $value );
        $date  = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
        return $date && $date->format( 'Y-m-d' ) === $value ? $value : '';
    }

    private static function name( $facet_id, $key ) {
        return self::QUERY_VAR . '[' . $facet_id . '][' . $key . ']';
    }
}
