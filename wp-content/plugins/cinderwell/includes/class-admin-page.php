<?php
/**
 * Admin page — Settings → Cinderwell.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Admin_Page {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
    }

    public function add_menu() {
        add_options_page(
            __( 'Cinderwell Settings', 'cinderwell' ),
            __( 'Cinderwell', 'cinderwell' ),
            'manage_options',
            'cinderwell',
            [ $this, 'render_page' ]
        );
    }

    public function render_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Cinderwell Settings', 'cinderwell' ); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=cinderwell&tab=general" class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'General', 'cinderwell' ); ?>
                </a>
                <a href="?page=cinderwell&tab=permissions" class="nav-tab <?php echo 'permissions' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Permissions', 'cinderwell' ); ?>
                </a>
                <a href="?page=cinderwell&tab=tokens" class="nav-tab <?php echo 'tokens' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Design Tokens', 'cinderwell' ); ?>
                </a>
            </nav>

            <div class="tab-content" style="margin-top: 20px;">
                <?php
                switch ( $active_tab ) {
                    case 'permissions':
                        $this->render_permissions_tab();
                        break;
                    case 'tokens':
                        $this->render_tokens_tab();
                        break;
                    default:
                        $this->render_general_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_general_tab() {
        $blocks    = $this->get_registered_blocks();
        $patterns  = $this->get_registered_patterns();
        $theme     = wp_get_theme();
        $override  = file_exists( get_template_directory() . '/cinderwell/' );
        ?>
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2><?php esc_html_e( 'General Information', 'cinderwell' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Version', 'cinderwell' ); ?></th>
                    <td><code><?php echo esc_html( CINDERWELL_VERSION ); ?></code></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Blocks Registered', 'cinderwell' ); ?></th>
                    <td><?php echo esc_html( count( $blocks ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Patterns Registered', 'cinderwell' ); ?></th>
                    <td><?php echo esc_html( count( $patterns ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Active Theme', 'cinderwell' ); ?></th>
                    <td><?php echo esc_html( $theme->get( 'Name' ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Override Directory', 'cinderwell' ); ?></th>
                    <td>
                        <?php if ( $override ) : ?>
                            <span style="color: green;">&#10003; <?php esc_html_e( 'Found', 'cinderwell' ); ?></span>
                        <?php else : ?>
                            <span style="color: #999;">&#9888; <?php esc_html_e( 'Not found', 'cinderwell' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'WordPress Version', 'cinderwell' ); ?></th>
                    <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'PHP Version', 'cinderwell' ); ?></th>
                    <td><?php echo esc_html( PHP_VERSION ); ?></td>
                </tr>
            </table>
        </div>

        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2><?php esc_html_e( 'Registered Blocks', 'cinderwell' ); ?></h2>
            <ul style="columns: 2; -webkit-columns: 2;">
                <?php foreach ( $blocks as $block ) : ?>
                    <li><code><?php echo esc_html( $block ); ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    private function render_permissions_tab() {
        echo '<div class="card" style="max-width: 600px; margin-top: 20px;">';
        echo '<h2>' . esc_html__( 'Block Permissions', 'cinderwell' ) . '</h2>';
        echo '<p>' . esc_html__( 'Block permissions by role will be available in v0.2.', 'cinderwell' ) . '</p>';
        echo '</div>';
    }

    private function render_tokens_tab() {
        $manifest = Design_Tokens::get_manifest();
        $saved    = get_option( 'cinderwell_design_tokens', [] );

        // Handle form submission.
        if ( isset( $_POST['cinderwell_save_tokens'] ) && check_admin_referer( 'cinderwell_tokens_nonce' ) ) {
            $new_tokens = [];
            foreach ( $manifest as $key => $token ) {
                $val = wp_unslash( $_POST[ 'token_' . $key ] ?? '' );
                $new_tokens[ $key ] = Design_Tokens::sanitize_token_value( $key, $val, $token );
            }
            update_option( 'cinderwell_design_tokens', $new_tokens );
            $saved = $new_tokens;
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Tokens saved.', 'cinderwell' ) . '</p></div>';
        }

        echo '<div class="card" style="max-width: 700px; margin-top: 20px;">';
        echo '<h2>' . esc_html__( 'Design Tokens', 'cinderwell' ) . '</h2>';
        echo '<p>' . esc_html__( 'Override default design tokens. Leave empty to use defaults.', 'cinderwell' ) . '</p>';
        echo '<form method="post">';
        wp_nonce_field( 'cinderwell_tokens_nonce' );
        echo '<table class="form-table">';

        foreach ( $manifest as $key => $token ) {
            $value = $saved[ $key ] ?? '';
            $default = $token['default'];
            printf(
                '<tr><th><label for="token_%s">%s</label><br><small><code>--%s</code></small></th><td>',
                esc_attr( $key ),
                esc_html( $token['label'] ),
                esc_attr( $key )
            );

            if ( 'color' === $token['type'] ) {
                printf(
                    '<input type="color" id="token_%s" name="token_%s" value="%s" style="width: 60px; height: 36px;"> <span style="color: #999;">%s: %s</span>',
                    esc_attr( $key ),
                    esc_attr( $key ),
                    esc_attr( $value ?: $default ),
                    esc_html__( 'Default', 'cinderwell' ),
                    esc_html( $default )
                );
            } elseif ( 'choice' === $token['type'] ) {
                printf( '<select id="token_%s" name="token_%s">', esc_attr( $key ), esc_attr( $key ) );
                foreach ( $token['options'] ?? [] as $option ) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr( $option['value'] ),
                        selected( $value ?: $default, $option['value'], false ),
                        esc_html( $option['label'] )
                    );
                }
                echo '</select>';
            } else {
                printf(
                    '<input type="text" id="token_%s" name="token_%s" value="%s" class="regular-text" placeholder="%s"> <span style="color: #999;">%s: %s</span>',
                    esc_attr( $key ),
                    esc_attr( $key ),
                    esc_attr( $value ),
                    esc_attr( $default ),
                    esc_html__( 'Default', 'cinderwell' ),
                    esc_html( $default )
                );
            }

            echo '</td></tr>';
        }

        echo '</table>';
        submit_button( __( 'Save Tokens', 'cinderwell' ), 'primary', 'cinderwell_save_tokens' );
        echo '</form></div>';
    }

    private function get_registered_blocks() {
        $blocks = [];
        $all    = \WP_Block_Type_Registry::get_instance()->get_all_registered();
        foreach ( $all as $name => $block ) {
            if ( str_starts_with( $name, 'cinderwell/' ) ) {
                $blocks[] = $name;
            }
        }
        sort( $blocks );
        return $blocks;
    }

    private function get_registered_patterns() {
        $patterns = [];
        if ( class_exists( 'WP_Block_Patterns_Registry' ) ) {
            $all = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();
            foreach ( $all as $name => $pattern ) {
                if ( str_starts_with( $name, 'cinderwell/' ) ) {
                    $patterns[] = $name;
                }
            }
        }
        sort( $patterns );
        return $patterns;
    }
}
