<?php
/**
 * Alert editor controls and list-table details.
 *
 * @package Cinderwell_Alerts
 */

namespace Cinderwell_Alerts;

defined( 'ABSPATH' ) || exit;

class Admin {

    public function __construct() {
        add_action( 'add_meta_boxes_' . Post_Type::POST_TYPE, [ $this, 'add_meta_box' ] );
        add_action( 'save_post_' . Post_Type::POST_TYPE, [ $this, 'save' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_filter( 'manage_' . Post_Type::POST_TYPE . '_posts_columns', [ $this, 'columns' ] );
        add_action( 'manage_' . Post_Type::POST_TYPE . '_posts_custom_column', [ $this, 'column_content' ], 10, 2 );
    }

    public function add_meta_box() {
        add_meta_box(
            'cinderwell-alert-display',
            __( 'Alert Display', 'cinderwell-alerts' ),
            [ $this, 'render_meta_box' ],
            Post_Type::POST_TYPE,
            'side',
            'high'
        );
    }

    public function enqueue_assets( $hook ) {
        $screen = get_current_screen();
        if ( Post_Type::POST_TYPE !== ( $screen->post_type ?? '' ) ) {
            return;
        }

        wp_enqueue_style(
            'cinderwell-alerts-admin',
            CINDERWELL_ALERTS_URL . 'assets/css/admin.css',
            [],
            CINDERWELL_ALERTS_VERSION
        );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'cinderwell_alert_save', 'cinderwell_alert_nonce' );

        $placement    = get_post_meta( $post->ID, '_cw_alert_placement', true ) ?: 'top';
        $tone         = get_post_meta( $post->ID, '_cw_alert_tone', true ) ?: 'info';
        $priority     = (int) ( get_post_meta( $post->ID, '_cw_alert_priority', true ) ?: 10 );
        $audience     = get_post_meta( $post->ID, '_cw_alert_audience', true ) ?: 'all';
        $post_types   = (array) get_post_meta( $post->ID, '_cw_alert_post_types', true );
        $include      = (array) get_post_meta( $post->ID, '_cw_alert_include_paths', true );
        $exclude      = (array) get_post_meta( $post->ID, '_cw_alert_exclude_paths', true );
        $start        = absint( get_post_meta( $post->ID, '_cw_alert_start', true ) );
        $end          = absint( get_post_meta( $post->ID, '_cw_alert_end', true ) );
        $dismissible  = '0' !== (string) get_post_meta( $post->ID, '_cw_alert_dismissible', true );
        $dismiss_days = absint( get_post_meta( $post->ID, '_cw_alert_dismiss_days', true ) ?: 7 );
        $show_title   = '0' !== (string) get_post_meta( $post->ID, '_cw_alert_show_title', true );
        ?>
        <div class="cw-alert-fields">
            <?php $this->select( 'cw_alert_placement', __( 'Placement', 'cinderwell-alerts' ), $placement, [ 'top' => __( 'Top of page', 'cinderwell-alerts' ), 'bottom' => __( 'Sticky bottom', 'cinderwell-alerts' ) ] ); ?>
            <?php $this->select( 'cw_alert_tone', __( 'Tone', 'cinderwell-alerts' ), $tone, [ 'info' => __( 'Information', 'cinderwell-alerts' ), 'brand' => __( 'Brand', 'cinderwell-alerts' ), 'success' => __( 'Success', 'cinderwell-alerts' ), 'warning' => __( 'Warning', 'cinderwell-alerts' ), 'critical' => __( 'Critical', 'cinderwell-alerts' ) ] ); ?>
            <label>
                <span><?php esc_html_e( 'Priority', 'cinderwell-alerts' ); ?></span>
                <input type="number" name="cw_alert_priority" value="<?php echo esc_attr( $priority ); ?>" min="0" max="1000">
                <small><?php esc_html_e( 'Higher numbers win when alerts share a placement.', 'cinderwell-alerts' ); ?></small>
            </label>
            <?php $this->select( 'cw_alert_audience', __( 'Show on', 'cinderwell-alerts' ), $audience, [ 'all' => __( 'Entire site', 'cinderwell-alerts' ), 'front_page' => __( 'Front page only', 'cinderwell-alerts' ), 'singular' => __( 'Selected content types', 'cinderwell-alerts' ), 'archives' => __( 'Archives and search', 'cinderwell-alerts' ), 'paths' => __( 'Included paths only', 'cinderwell-alerts' ) ] ); ?>

            <fieldset>
                <legend><?php esc_html_e( 'Content types', 'cinderwell-alerts' ); ?></legend>
                <?php foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $post_type ) : ?>
                    <?php if ( 'attachment' === $post_type->name ) continue; ?>
                    <label class="cw-alert-check"><input type="checkbox" name="cw_alert_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $post_types, true ) ); ?>> <?php echo esc_html( $post_type->labels->singular_name ); ?></label>
                <?php endforeach; ?>
            </fieldset>

            <label>
                <span><?php esc_html_e( 'Included paths', 'cinderwell-alerts' ); ?></span>
                <textarea name="cw_alert_include_paths" rows="3" placeholder="/services/*"><?php echo esc_textarea( implode( "\n", $include ) ); ?></textarea>
                <small><?php esc_html_e( 'Optional. One path per line; * is supported.', 'cinderwell-alerts' ); ?></small>
            </label>
            <label>
                <span><?php esc_html_e( 'Excluded paths', 'cinderwell-alerts' ); ?></span>
                <textarea name="cw_alert_exclude_paths" rows="3" placeholder="/checkout/*"><?php echo esc_textarea( implode( "\n", $exclude ) ); ?></textarea>
            </label>

            <hr>
            <label><span><?php esc_html_e( 'Starts', 'cinderwell-alerts' ); ?></span><input type="datetime-local" name="cw_alert_start" value="<?php echo esc_attr( $this->format_datetime( $start ) ); ?>"></label>
            <label><span><?php esc_html_e( 'Ends', 'cinderwell-alerts' ); ?></span><input type="datetime-local" name="cw_alert_end" value="<?php echo esc_attr( $this->format_datetime( $end ) ); ?>"></label>
            <small><?php esc_html_e( 'Leave either field empty for no boundary. Times use the site timezone.', 'cinderwell-alerts' ); ?></small>

            <hr>
            <label class="cw-alert-check"><input type="checkbox" name="cw_alert_show_title" value="1" <?php checked( $show_title ); ?>> <?php esc_html_e( 'Show the alert title', 'cinderwell-alerts' ); ?></label>
            <label class="cw-alert-check"><input type="checkbox" name="cw_alert_dismissible" value="1" <?php checked( $dismissible ); ?>> <?php esc_html_e( 'Visitors can dismiss this alert', 'cinderwell-alerts' ); ?></label>
            <label>
                <span><?php esc_html_e( 'Dismiss for', 'cinderwell-alerts' ); ?></span>
                <span class="cw-alert-inline"><input type="number" name="cw_alert_dismiss_days" value="<?php echo esc_attr( $dismiss_days ); ?>" min="1" max="365"> <?php esc_html_e( 'days', 'cinderwell-alerts' ); ?></span>
            </label>
        </div>
        <?php
    }

    public function save( $post_id, $post ) {
        $nonce = isset( $_POST['cinderwell_alert_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['cinderwell_alert_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'cinderwell_alert_save' ) || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }

        $this->save_enum( $post_id, '_cw_alert_placement', 'cw_alert_placement', [ 'top', 'bottom' ], 'top' );
        $this->save_enum( $post_id, '_cw_alert_tone', 'cw_alert_tone', [ 'info', 'brand', 'success', 'warning', 'critical' ], 'info' );
        $this->save_enum( $post_id, '_cw_alert_audience', 'cw_alert_audience', [ 'all', 'front_page', 'singular', 'archives', 'paths' ], 'all' );

        update_post_meta( $post_id, '_cw_alert_priority', min( 1000, max( 0, absint( $_POST['cw_alert_priority'] ?? 10 ) ) ) );
        update_post_meta( $post_id, '_cw_alert_start', $this->parse_datetime( $_POST['cw_alert_start'] ?? '' ) );
        update_post_meta( $post_id, '_cw_alert_end', $this->parse_datetime( $_POST['cw_alert_end'] ?? '' ) );
        update_post_meta( $post_id, '_cw_alert_dismissible', isset( $_POST['cw_alert_dismissible'] ) );
        update_post_meta( $post_id, '_cw_alert_show_title', isset( $_POST['cw_alert_show_title'] ) );
        update_post_meta( $post_id, '_cw_alert_dismiss_days', min( 365, max( 1, absint( $_POST['cw_alert_dismiss_days'] ?? 7 ) ) ) );

        $post_types = isset( $_POST['cw_alert_post_types'] ) && is_array( $_POST['cw_alert_post_types'] )
            ? array_values( array_intersect( array_map( 'sanitize_key', wp_unslash( $_POST['cw_alert_post_types'] ) ), get_post_types( [ 'public' => true ] ) ) )
            : [];
        update_post_meta( $post_id, '_cw_alert_post_types', $post_types );
        update_post_meta( $post_id, '_cw_alert_include_paths', $this->sanitize_paths( $_POST['cw_alert_include_paths'] ?? '' ) );
        update_post_meta( $post_id, '_cw_alert_exclude_paths', $this->sanitize_paths( $_POST['cw_alert_exclude_paths'] ?? '' ) );
    }

    public function columns( $columns ) {
        return array_slice( $columns, 0, 2, true ) + [
            'cw_alert_display'  => __( 'Display', 'cinderwell-alerts' ),
            'cw_alert_schedule' => __( 'Schedule', 'cinderwell-alerts' ),
        ] + array_slice( $columns, 2, null, true );
    }

    public function column_content( $column, $post_id ) {
        if ( 'cw_alert_display' === $column ) {
            $placement = get_post_meta( $post_id, '_cw_alert_placement', true ) ?: 'top';
            $tone      = get_post_meta( $post_id, '_cw_alert_tone', true ) ?: 'info';
            $priority  = (int) ( get_post_meta( $post_id, '_cw_alert_priority', true ) ?: 10 );
            printf( '%s · %s · %s %d', esc_html( ucfirst( $placement ) ), esc_html( ucfirst( $tone ) ), esc_html__( 'Priority', 'cinderwell-alerts' ), $priority );
        }
        if ( 'cw_alert_schedule' === $column ) {
            $start = absint( get_post_meta( $post_id, '_cw_alert_start', true ) );
            $end   = absint( get_post_meta( $post_id, '_cw_alert_end', true ) );
            echo esc_html( $start ? wp_date( get_option( 'date_format' ), $start, wp_timezone() ) : __( 'Now', 'cinderwell-alerts' ) );
            echo ' → ';
            echo esc_html( $end ? wp_date( get_option( 'date_format' ), $end, wp_timezone() ) : __( 'No end', 'cinderwell-alerts' ) );
        }
    }

    private function select( $name, $label, $value, $options ) {
        echo '<label><span>' . esc_html( $label ) . '</span><select name="' . esc_attr( $name ) . '">';
        foreach ( $options as $option_value => $option_label ) {
            echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( $value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
        }
        echo '</select></label>';
    }

    private function save_enum( $post_id, $meta_key, $field, $allowed, $default ) {
        $value = sanitize_key( wp_unslash( $_POST[ $field ] ?? $default ) );
        update_post_meta( $post_id, $meta_key, in_array( $value, $allowed, true ) ? $value : $default );
    }

    private function sanitize_paths( $value ) {
        $lines = preg_split( '/\r\n|\r|\n/', sanitize_textarea_field( wp_unslash( $value ) ) );
        $paths = [];
        foreach ( $lines as $line ) {
            $path = trim( strtok( trim( $line ), '?#' ) ?: '' );
            if ( '' === $path ) continue;
            $path = '/' . ltrim( $path, '/' );
            if ( '/' !== $path && '*' !== substr( $path, -1 ) ) $path = untrailingslashit( $path ) . '/';
            $paths[] = $path;
        }
        return array_values( array_unique( $paths ) );
    }

    private function parse_datetime( $value ) {
        $value = sanitize_text_field( wp_unslash( $value ) );
        if ( ! $value ) return 0;
        $date = \DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $value, wp_timezone() );
        return $date ? $date->getTimestamp() : 0;
    }

    private function format_datetime( $timestamp ) {
        return $timestamp ? wp_date( 'Y-m-d\TH:i', $timestamp, wp_timezone() ) : '';
    }
}
