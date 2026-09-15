<?php
/**
 * Import and export Cinderwell configuration without moving site content.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Settings_Transfer {

    public function __construct() {
        add_filter( 'cinderwell_settings_tabs', [ $this, 'add_settings_tab' ], 40 );
        add_action( 'admin_post_cinderwell_export_settings', [ $this, 'export' ] );
        add_action( 'admin_post_cinderwell_import_settings', [ $this, 'import' ] );
    }

    public function add_settings_tab( $tabs ) {
        $tabs['settings-transfer'] = [
            'label'    => __( 'Import / Export', 'cinderwell' ),
            'group'    => 'maintenance',
            'callback' => [ $this, 'render' ],
        ];
        return $tabs;
    }

    public static function get_option_names() {
        return array_values( array_unique( array_map( 'sanitize_key', (array) apply_filters( 'cinderwell_settings_export_options', [
            Addons::OPTION,
            'cinderwell_design_tokens',
            'cinderwell_block_permissions',
            Teams::OPTION,
            Portfolio::OPTION,
            Company_Details::OPTION,
            Locations::OPTION,
            Animations::OPTION,
			Page_Header::OPTION,
            'cinderwell_cookie_consent_settings',
        ] ) ) ) );
    }

    public function render() {
        if ( isset( $_GET['imported'] ) ) {
            echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Cinderwell settings imported.', 'cinderwell' ) . '</p></div>';
        }
        if ( isset( $_GET['import_error'] ) ) {
            echo '<div class="notice notice-error inline"><p>' . esc_html__( 'The settings file could not be imported. Use an unmodified Cinderwell JSON export under 1 MB.', 'cinderwell' ) . '</p></div>';
        }
        $export_url = wp_nonce_url( add_query_arg( 'action', 'cinderwell_export_settings', admin_url( 'admin-post.php' ) ), 'cinderwell_export_settings' );
        ?>
        <div class="cw-settings-card-grid">
            <section class="card cw-settings-card">
                <h2><?php esc_html_e( 'Export settings', 'cinderwell' ); ?></h2>
                <p><?php esc_html_e( 'Download enabled add-ons, design tokens, and module configuration as a portable JSON file.', 'cinderwell' ); ?></p>
                <p><a class="button button-primary" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Download settings', 'cinderwell' ); ?></a></p>
            </section>
            <section class="card cw-settings-card">
                <h2><?php esc_html_e( 'Import settings', 'cinderwell' ); ?></h2>
                <p><?php esc_html_e( 'Importing replaces the matching settings on this site. Posts, people, projects, locations, templates, and media are not included.', 'cinderwell' ); ?></p>
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="cinderwell_import_settings">
                    <?php wp_nonce_field( 'cinderwell_import_settings' ); ?>
                    <input type="file" name="cinderwell_settings_file" accept="application/json,.json" required>
                    <?php submit_button( __( 'Import settings', 'cinderwell' ), 'secondary', 'submit', false, [ 'data-cw-confirm' => __( 'Replace this site’s matching Cinderwell settings?', 'cinderwell' ) ] ); ?>
                </form>
            </section>
        </div>
        <?php
    }

    public function export() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to export Cinderwell settings.', 'cinderwell' ) );
        }
        check_admin_referer( 'cinderwell_export_settings' );
        $options = [];
        foreach ( self::get_option_names() as $option_name ) {
            $value = get_option( $option_name, null );
            if ( null !== $value ) {
                $options[ $option_name ] = $value;
            }
        }
        $payload = apply_filters( 'cinderwell_settings_export', [
            'format'       => 'cinderwell-settings',
            'version'      => 1,
            'generated_at' => gmdate( 'c' ),
            'site'         => home_url( '/' ),
            'options'      => $options,
        ] );
        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="cinderwell-settings-' . gmdate( 'Y-m-d' ) . '.json"' );
        echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        exit;
    }

    public function import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to import Cinderwell settings.', 'cinderwell' ) );
        }
        check_admin_referer( 'cinderwell_import_settings' );
        $file = $_FILES['cinderwell_settings_file'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( ! is_array( $file ) || UPLOAD_ERR_OK !== ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) || ( $file['size'] ?? 0 ) > MB_IN_BYTES ) {
            $this->redirect( false );
        }
        $payload = json_decode( (string) file_get_contents( $file['tmp_name'] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        if ( ! is_array( $payload ) || 'cinderwell-settings' !== ( $payload['format'] ?? '' ) || 1 !== absint( $payload['version'] ?? 0 ) || ! is_array( $payload['options'] ?? null ) ) {
            $this->redirect( false );
        }
        $allowed = array_flip( self::get_option_names() );
        foreach ( array_intersect_key( $payload['options'], $allowed ) as $option_name => $value ) {
            update_option( $option_name, $this->sanitize_option( $option_name, $value ) );
        }
        update_option( Addons::OPTION, Addons::resolve_dependencies( get_option( Addons::OPTION, [] ) ) );
        update_option( 'cinderwell_flush_rewrite_rules', 1, false );
        do_action( 'cinderwell_settings_imported', $payload );
        $this->redirect( true );
    }

    private function sanitize_option( $option_name, $value ) {
        if ( Addons::OPTION === $option_name ) {
            return Addons::resolve_dependencies( $value );
        }
        if ( Company_Details::OPTION === $option_name ) {
            return Admin_Fields::sanitize_values( Company_Details::get_field_definitions(), $value );
        }
        if ( Locations::OPTION === $option_name ) {
            $value = (array) $value;
            return [
                'public_locations' => ! empty( $value['public_locations'] ),
                'location_slug'    => sanitize_title( $value['location_slug'] ?? 'locations' ) ?: 'locations',
            ];
        }
        if ( Animations::OPTION === $option_name ) {
            return Animations::sanitize_settings( $value );
        }
		if ( Page_Header::OPTION === $option_name ) {
			return Page_Header::sanitize_settings( $value );
		}
        if ( 'cinderwell_design_tokens' === $option_name ) {
            $value  = (array) $value;
            $tokens = [];
            foreach ( Design_Tokens::get_manifest() as $key => $token ) {
                if ( isset( $value[ $key ] ) ) {
                    $tokens[ $key ] = Design_Tokens::sanitize_token_value( $key, $value[ $key ], $token );
                }
            }
            return $tokens;
        }
        return apply_filters( 'cinderwell_import_setting', $this->sanitize_recursive( $value ), $option_name, $value );
    }

    private function sanitize_recursive( $value ) {
        if ( is_array( $value ) ) {
            $clean = [];
            foreach ( $value as $key => $item ) {
                $clean[ is_int( $key ) ? $key : sanitize_key( $key ) ] = $this->sanitize_recursive( $item );
            }
            return $clean;
        }
        if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
            return $value;
        }
        return sanitize_text_field( (string) $value );
    }

    private function redirect( $success ) {
        wp_safe_redirect( add_query_arg( [
            'page' => 'cinderwell',
            'tab'  => 'settings-transfer',
            $success ? 'imported' : 'import_error' => '1',
        ], admin_url( 'admin.php' ) ) );
        exit;
    }
}
