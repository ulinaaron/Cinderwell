<?php
/**
 * Role-based block availability and editor guardrails.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Editor_Access {

    const OPTION = 'cinderwell_block_permissions';

    public function __construct() {
        add_action( 'admin_post_cinderwell_save_editor_access', [ $this, 'save' ] );
        add_filter( 'allowed_block_types_all', [ $this, 'filter_allowed_blocks' ], 20, 2 );
        add_filter( 'block_editor_settings_all', [ $this, 'filter_editor_settings' ], 20, 2 );
    }

    public static function get_control_groups() {
        return (array) apply_filters( 'cinderwell_editor_access_control_groups', [
            'content'    => __( 'Content', 'cinderwell' ),
            'links'      => __( 'Links and actions', 'cinderwell' ),
            'media'      => __( 'Media', 'cinderwell' ),
            'appearance' => __( 'Appearance', 'cinderwell' ),
            'spacing'    => __( 'Spacing', 'cinderwell' ),
            'layout'     => __( 'Layout and structure', 'cinderwell' ),
            'advanced'   => __( 'Advanced', 'cinderwell' ),
        ] );
    }

    public static function get_presets() {
        return (array) apply_filters( 'cinderwell_editor_access_presets', [
            'full' => [
                'label'       => __( 'Full design', 'cinderwell' ),
                'description' => __( 'May edit content and every Cinderwell design or layout control.', 'cinderwell' ),
                'controls'    => array_keys( self::get_control_groups() ),
            ],
            'content' => [
                'label'       => __( 'Content editing', 'cinderwell' ),
                'description' => __( 'May edit text, links, actions, and media while design controls remain fixed.', 'cinderwell' ),
                'controls'    => [ 'content', 'links', 'media' ],
            ],
            'text' => [
                'label'       => __( 'Text only', 'cinderwell' ),
                'description' => __( 'May edit text while links, media, appearance, spacing, and layout remain fixed.', 'cinderwell' ),
                'controls'    => [ 'content' ],
            ],
            'custom' => [
                'label'       => __( 'Custom', 'cinderwell' ),
                'description' => __( 'Choose the available control groups for this role.', 'cinderwell' ),
                'controls'    => [],
            ],
        ] );
    }

    public static function get_blocks() {
        $blocks = [];
        foreach ( \WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $block ) {
            if ( 0 !== strpos( $name, 'cinderwell/' ) || false === ( $block->supports['inserter'] ?? true ) || ! empty( $block->parent ) ) {
                continue;
            }
            $blocks[ $name ] = $block->title ?: $name;
        }
        natcasesort( $blocks );

        return (array) apply_filters( 'cinderwell_editor_access_blocks', $blocks );
    }

    public static function get_roles() {
        $roles = [];
        foreach ( wp_roles()->roles as $slug => $role ) {
            if ( empty( $role['capabilities']['edit_posts'] ) && 'administrator' !== $slug ) {
                continue;
            }
            $roles[ $slug ] = translate_user_role( $role['name'] );
        }

        return $roles;
    }

    public static function get_settings() {
        $stored   = get_option( self::OPTION, [] );
        $stored   = is_array( $stored ) ? $stored : [];
        $settings = [ 'version' => 1, 'roles' => [] ];
        $blocks   = array_keys( self::get_blocks() );
        $presets  = self::get_presets();
        $groups   = array_keys( self::get_control_groups() );

        foreach ( self::get_roles() as $role => $label ) {
            $role_settings = isset( $stored['roles'][ $role ] ) && is_array( $stored['roles'][ $role ] ) ? $stored['roles'][ $role ] : [];
            $preset        = isset( $presets[ $role_settings['preset'] ?? '' ] ) ? $role_settings['preset'] : 'full';
            $controls      = 'custom' === $preset ? array_values( array_intersect( $groups, (array) ( $role_settings['controls'] ?? [] ) ) ) : $presets[ $preset ]['controls'];
            $disabled      = array_values( array_intersect( $blocks, (array) ( $role_settings['disabled_blocks'] ?? [] ) ) );

            if ( 'administrator' === $role ) {
                $preset   = 'full';
                $controls = $groups;
                $disabled = [];
            }

            $settings['roles'][ $role ] = [
                'preset'          => $preset,
                'controls'        => $controls,
                'disabled_blocks' => $disabled,
            ];
        }

        return $settings;
    }

    /**
     * Resolve multiple roles permissively: access granted by any role wins.
     */
    public static function get_current_policy() {
        $user = wp_get_current_user();
        if ( ! $user->exists() || user_can( $user, 'manage_options' ) ) {
            return self::full_policy();
        }

        $settings = self::get_settings();
        $policies = [];
        foreach ( (array) $user->roles as $role ) {
            if ( isset( $settings['roles'][ $role ] ) ) {
                $policies[] = $settings['roles'][ $role ];
            }
        }
        if ( ! $policies ) {
            return self::full_policy();
        }

        $controls = [];
        $disabled = null;
        foreach ( $policies as $policy ) {
            $controls = array_merge( $controls, $policy['controls'] );
            $disabled = null === $disabled ? $policy['disabled_blocks'] : array_values( array_intersect( $disabled, $policy['disabled_blocks'] ) );
        }
        $controls = array_values( array_unique( $controls ) );
        $policy   = [
            'preset'         => 1 === count( array_unique( array_column( $policies, 'preset' ) ) ) ? $policies[0]['preset'] : 'custom',
            'controls'       => $controls,
            'disabledBlocks' => $disabled ?: [],
            'isRestricted'   => count( $controls ) < count( self::get_control_groups() ) || ! empty( $disabled ),
        ];

        return (array) apply_filters( 'cinderwell_editor_access_policy', $policy, $user );
    }

    private static function full_policy() {
        return [
            'preset'         => 'full',
            'controls'       => array_keys( self::get_control_groups() ),
            'disabledBlocks' => [],
            'isRestricted'   => false,
        ];
    }

    public function filter_allowed_blocks( $allowed, $context ) {
        $disabled = self::get_current_policy()['disabledBlocks'] ?? [];
        if ( ! $disabled ) {
            return $allowed;
        }
        if ( true === $allowed ) {
            $allowed = array_keys( \WP_Block_Type_Registry::get_instance()->get_all_registered() );
        }

        return is_array( $allowed ) ? array_values( array_diff( $allowed, $disabled ) ) : $allowed;
    }

    public function filter_editor_settings( $settings, $context ) {
        $policy = self::get_current_policy();
        if ( empty( $policy['isRestricted'] ) ) {
            return $settings;
        }

        // The raw markup editor would bypass block-level control policies.
        $settings['codeEditingEnabled'] = false;
        if ( ! in_array( 'layout', $policy['controls'], true ) ) {
            $settings['canLockBlocks'] = false;
            $settings['templateLock']  = 'all';
        }

        return $settings;
    }

    public function save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage editor access.', 'cinderwell' ) );
        }
        check_admin_referer( 'cinderwell_save_editor_access' );

        $submitted_root = isset( $_POST['editor_access'] ) && is_array( $_POST['editor_access'] ) ? (array) wp_unslash( $_POST['editor_access'] ) : [];
        $submitted      = isset( $submitted_root['roles'] ) && is_array( $submitted_root['roles'] ) ? $submitted_root['roles'] : [];
        $presets   = self::get_presets();
        $groups    = array_keys( self::get_control_groups() );
        $blocks    = array_keys( self::get_blocks() );
        $roles     = [];

        foreach ( self::get_roles() as $role => $label ) {
            if ( 'administrator' === $role ) {
                $roles[ $role ] = [ 'preset' => 'full', 'controls' => $groups, 'disabled_blocks' => [] ];
                continue;
            }
            $values   = isset( $submitted[ $role ] ) && is_array( $submitted[ $role ] ) ? $submitted[ $role ] : [];
            $preset   = sanitize_key( $values['preset'] ?? 'full' );
            $preset   = isset( $presets[ $preset ] ) ? $preset : 'full';
            $controls = 'custom' === $preset ? array_values( array_intersect( $groups, array_map( 'sanitize_key', (array) ( $values['controls'] ?? [] ) ) ) ) : $presets[ $preset ]['controls'];
            $enabled  = array_values( array_intersect( $blocks, array_map( 'sanitize_text_field', (array) ( $values['blocks'] ?? [] ) ) ) );

            $roles[ $role ] = [
                'preset'          => $preset,
                'controls'        => $controls,
                'disabled_blocks' => array_values( array_diff( $blocks, $enabled ) ),
            ];
        }

        update_option( self::OPTION, [ 'version' => 1, 'roles' => $roles ] );
        wp_safe_redirect( add_query_arg( [ 'page' => 'cinderwell', 'tab' => 'editor-access', 'updated' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function render_settings() {
        $settings = self::get_settings();
        $presets  = self::get_presets();
        $groups   = self::get_control_groups();
        $blocks   = self::get_blocks();

        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Editor access settings saved.', 'cinderwell' ) . '</p></div>';
        }
        ?>
        <p><?php esc_html_e( 'Set the Cinderwell blocks and editing controls available to each WordPress role. Existing blocks continue to render even when they are hidden from the inserter.', 'cinderwell' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="cinderwell_save_editor_access">
            <?php wp_nonce_field( 'cinderwell_save_editor_access' ); ?>
            <div class="cw-editor-access-roles">
                <?php foreach ( self::get_roles() as $role => $label ) : ?>
                    <?php
                    $role_settings = $settings['roles'][ $role ];
                    $is_admin      = 'administrator' === $role;
                    $enabled_count = count( $blocks ) - count( $role_settings['disabled_blocks'] );
                    ?>
                    <section class="card cw-settings-card cw-editor-access-role" data-cw-editor-access-role>
                        <div class="cw-editor-access-role__heading">
                            <div><h2><?php echo esc_html( $label ); ?></h2><code><?php echo esc_html( $role ); ?></code></div>
                            <?php if ( $is_admin ) : ?><span class="cw-editor-access-role__fixed"><?php esc_html_e( 'Always full access', 'cinderwell' ); ?></span><?php endif; ?>
                        </div>
                        <label class="cw-editor-access-field">
                            <span><?php esc_html_e( 'Access level', 'cinderwell' ); ?></span>
                            <select name="editor_access[roles][<?php echo esc_attr( $role ); ?>][preset]" data-cw-editor-access-preset <?php disabled( $is_admin ); ?>>
                                <?php foreach ( $presets as $preset => $definition ) : ?>
                                    <option value="<?php echo esc_attr( $preset ); ?>" data-description="<?php echo esc_attr( $definition['description'] ); ?>" <?php selected( $role_settings['preset'], $preset ); ?>><?php echo esc_html( $definition['label'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <p class="description" data-cw-editor-access-description><?php echo esc_html( $presets[ $role_settings['preset'] ]['description'] ); ?></p>
                        <fieldset class="cw-editor-access-controls" data-cw-editor-access-custom <?php echo 'custom' === $role_settings['preset'] && ! $is_admin ? '' : 'hidden'; ?>>
                            <legend><?php esc_html_e( 'Editable controls', 'cinderwell' ); ?></legend>
                            <?php foreach ( $groups as $group => $group_label ) : ?>
                                <label><input type="checkbox" name="editor_access[roles][<?php echo esc_attr( $role ); ?>][controls][]" value="<?php echo esc_attr( $group ); ?>" <?php checked( in_array( $group, $role_settings['controls'], true ) ); ?>> <?php echo esc_html( $group_label ); ?></label>
                            <?php endforeach; ?>
                        </fieldset>
                        <details class="cw-editor-access-blocks">
                            <summary><?php printf( esc_html__( 'Block availability: %1$d of %2$d enabled', 'cinderwell' ), esc_html( $enabled_count ), esc_html( count( $blocks ) ) ); ?></summary>
                            <div class="cw-editor-access-block-grid">
                                <?php foreach ( $blocks as $block_name => $block_label ) : ?>
                                    <label><input type="checkbox" name="editor_access[roles][<?php echo esc_attr( $role ); ?>][blocks][]" value="<?php echo esc_attr( $block_name ); ?>" <?php checked( ! in_array( $block_name, $role_settings['disabled_blocks'], true ) ); ?> <?php disabled( $is_admin ); ?>> <?php echo esc_html( $block_label ); ?></label>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    </section>
                <?php endforeach; ?>
            </div>
            <?php submit_button( __( 'Save Editor Access', 'cinderwell' ) ); ?>
        </form>
        <?php
    }
}
