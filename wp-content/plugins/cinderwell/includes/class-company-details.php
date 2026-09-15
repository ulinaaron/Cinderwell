<?php
/**
 * Opt-in company details module.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Company_Details {

    const OPTION = 'cinderwell_company_details';

    public function __construct() {
        add_filter( 'cinderwell_help_sections', [ $this, 'add_help_section' ] );
        add_filter( 'cinderwell_help_topics', [ $this, 'add_help_topics' ] );
        add_filter( 'cinderwell_settings_tabs', [ $this, 'add_settings_tab' ], 8 );
        add_action( 'admin_post_cinderwell_save_company_details', [ $this, 'save_settings' ] );
        add_filter( 'cinderwell_data_sources', [ $this, 'register_data_sources' ] );
        add_filter( 'cinderwell_resolve_data_source', [ $this, 'resolve_data_source' ], 10, 3 );
        add_filter( 'cinderwell_editor_preview_values', [ $this, 'add_editor_preview_values' ] );
        add_action( 'wp_head', [ $this, 'output_schema' ], 2 );
    }

    public function add_help_section( $sections ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return $sections;
        }
        $sections['company-details'] = [
            'title'       => __( 'Company details', 'cinderwell' ),
            'description' => __( 'Maintain shared organization and contact information.', 'cinderwell' ),
            'order'       => 90,
        ];
        return $sections;
    }

    public function add_help_topics( $topics ) {
        $topics['company-details-manage'] = [
            'section'    => 'company-details',
            'title'      => __( 'Update shared company information', 'cinderwell' ),
            'summary'    => __( 'Change contact details once and reuse them throughout the site.', 'cinderwell' ),
            'icon'       => 'dashicons-building',
            'order'      => 10,
            'capability' => 'manage_options',
            'content'    => sprintf(
                wp_kses_post( __( '<p>Open <a href="%s"><strong>Cinderwell → Company Details</strong></a> to maintain the organization name, contact information, address, hours, logos, and social profiles.</p><p>Blocks using dynamic company data update automatically when these values change. Avoid typing the same details directly into several pages.</p>', 'cinderwell' ) ),
                esc_url( admin_url( 'admin.php?page=cinderwell&tab=company-details' ) )
            ),
        ];
        $topics['company-details-schema'] = [
            'section'    => 'company-details',
            'title'      => __( 'Manage organization schema', 'cinderwell' ),
            'summary'    => __( 'Avoid duplicate structured organization data.', 'cinderwell' ),
            'icon'       => 'dashicons-media-code',
            'order'      => 20,
            'capability' => 'manage_options',
            'content'    => __( '<p>Enable Organization schema only when Cinderwell should own that structured data. Leave it disabled when an SEO plugin already outputs organization schema. Keep the selected organization type, name, URL, logo, address, and contact information accurate.</p>', 'cinderwell' ),
        ];
        return $topics;
    }

    public static function get_defaults() {
        $defaults = [];
        foreach ( self::get_field_definitions() as $key => $field ) {
            $defaults[ $key ] = $field['default'];
        }
        return $defaults;
    }

    /**
     * Company fields are filterable so client implementations can extend the
     * defaults without creating another settings system.
     */
    public static function get_field_definitions() {
        $fields = [
            'name' => [
                'label'       => __( 'Company name', 'cinderwell' ),
                'default'     => get_bloginfo( 'name' ),
                'description' => __( 'The public-facing organization name.', 'cinderwell' ),
            ],
            'legal_name' => [
                'label'       => __( 'Legal name', 'cinderwell' ),
                'description' => __( 'Use only when it differs from the public company name.', 'cinderwell' ),
            ],
            'description' => [
                'label' => __( 'Short description', 'cinderwell' ),
                'type'  => 'textarea',
            ],
            'logo_id' => [
                'label'   => __( 'Logo', 'cinderwell' ),
                'type'    => 'media',
                'dynamic' => false,
            ],
            'alternate_logo_id' => [
                'label'       => __( 'Alternate logo', 'cinderwell' ),
                'type'        => 'media',
                'dynamic'     => false,
                'description' => __( 'Useful for dark or contrasting backgrounds.', 'cinderwell' ),
            ],
            'organization_type' => [
                'label'   => __( 'Organization type', 'cinderwell' ),
                'type'    => 'select',
                'default' => 'Organization',
                'dynamic' => false,
                'options' => [
                    'Organization'            => __( 'Organization', 'cinderwell' ),
                    'Corporation'             => __( 'Corporation', 'cinderwell' ),
                    'LocalBusiness'           => __( 'Local business', 'cinderwell' ),
                    'ProfessionalService'     => __( 'Professional service', 'cinderwell' ),
                    'MedicalOrganization'     => __( 'Medical organization', 'cinderwell' ),
                    'NGO'                     => __( 'Nonprofit / NGO', 'cinderwell' ),
                    'EducationalOrganization' => __( 'Educational organization', 'cinderwell' ),
                ],
                'sanitize_callback' => static function ( $value, $field ) {
                    return array_key_exists( $value, $field['options'] ) ? $value : 'Organization';
                },
            ],
            'schema_enabled' => [
                'label'          => __( 'Organization schema', 'cinderwell' ),
                'type'           => 'checkbox',
                'default'        => false,
                'dynamic'        => false,
                'checkbox_label' => __( 'Output Organization JSON-LD', 'cinderwell' ),
                'description'    => __( 'Leave disabled when an SEO plugin already owns organization schema.', 'cinderwell' ),
            ],
            'phone' => [
                'label' => __( 'Main phone', 'cinderwell' ),
                'type'  => 'tel',
            ],
            'email' => [
                'label' => __( 'Main email', 'cinderwell' ),
                'type'  => 'email',
            ],
            'website' => [
                'label'   => __( 'Website', 'cinderwell' ),
                'type'    => 'url',
                'default' => home_url( '/' ),
            ],
            'contact_url' => [
                'label'       => __( 'Contact page URL', 'cinderwell' ),
                'type'        => 'url',
                'description' => __( 'Used by buttons and contact calls to action.', 'cinderwell' ),
            ],
            'directions_url' => [
                'label'       => __( 'Directions URL', 'cinderwell' ),
                'type'        => 'url',
                'description' => __( 'A map or directions link for the primary location.', 'cinderwell' ),
            ],
            'address_line_1' => [
                'label' => __( 'Street address', 'cinderwell' ),
            ],
            'address_line_2' => [
                'label' => __( 'Address line 2', 'cinderwell' ),
            ],
            'locality' => [
                'label' => __( 'City / locality', 'cinderwell' ),
            ],
            'region' => [
                'label' => __( 'State / region', 'cinderwell' ),
            ],
            'postal_code' => [
                'label' => __( 'Postal code', 'cinderwell' ),
            ],
            'country' => [
                'label' => __( 'Country', 'cinderwell' ),
            ],
            'hours' => [
                'label'       => __( 'Business hours', 'cinderwell' ),
                'type'        => 'textarea',
                'description' => __( 'Use one line per day or group of days.', 'cinderwell' ),
            ],
            'facebook' => [
                'label' => __( 'Facebook URL', 'cinderwell' ),
                'type'  => 'url',
            ],
            'instagram' => [
                'label' => __( 'Instagram URL', 'cinderwell' ),
                'type'  => 'url',
            ],
            'linkedin' => [
                'label' => __( 'LinkedIn URL', 'cinderwell' ),
                'type'  => 'url',
            ],
            'youtube' => [
                'label' => __( 'YouTube URL', 'cinderwell' ),
                'type'  => 'url',
            ],
            'x' => [
                'label' => __( 'X URL', 'cinderwell' ),
                'type'  => 'url',
            ],
            'social_profiles' => [
                'label'       => __( 'Other social profiles', 'cinderwell' ),
                'type'        => 'repeater',
                'dynamic'     => false,
                'max_items'   => 12,
                'description' => __( 'Add networks without waiting for a named field to be added to Cinderwell.', 'cinderwell' ),
                'fields'      => [
                    'label' => [ 'label' => __( 'Network', 'cinderwell' ) ],
                    'url'   => [ 'label' => __( 'Profile URL', 'cinderwell' ), 'type' => 'url' ],
                ],
            ],
        ];

        return Admin_Fields::normalize_fields( apply_filters( 'cinderwell_company_details_fields', $fields ) );
    }

    public static function get_settings() {
        return wp_parse_args( (array) get_option( self::OPTION, [] ), self::get_defaults() );
    }

    /**
     * Values keyed exactly as the dynamic-data registry expects them.
     */
    public static function get_data_source_values() {
        $settings = self::get_settings();
        $values   = [];

        foreach ( self::get_field_definitions() as $key => $field ) {
            if ( ! empty( $field['dynamic'] ) && ! in_array( $field['type'], [ 'media', 'repeater' ], true ) ) {
                $values[ 'company_' . $key ] = $settings[ $key ] ?? '';
            }
        }

        $address = array_filter( [
            trim( ( $settings['address_line_1'] ?? '' ) . ( ! empty( $settings['address_line_2'] ) ? ', ' . $settings['address_line_2'] : '' ) ),
            trim( ( $settings['locality'] ?? '' ) . ( ! empty( $settings['region'] ) ? ', ' . $settings['region'] : '' ) . ( ! empty( $settings['postal_code'] ) ? ' ' . $settings['postal_code'] : '' ) ),
            $settings['country'] ?? '',
        ] );
        $values['company_address'] = implode( ', ', $address );
        $values['company_phone_url'] = ! empty( $settings['phone'] ) ? 'tel:' . preg_replace( '/[^0-9+]/', '', $settings['phone'] ) : '';
        $values['company_email_url'] = ! empty( $settings['email'] ) ? 'mailto:' . $settings['email'] : '';
        $values['company_logo_url'] = ! empty( $settings['logo_id'] ) ? wp_get_attachment_image_url( $settings['logo_id'], 'full' ) : '';
        $values['company_alternate_logo_url'] = ! empty( $settings['alternate_logo_id'] ) ? wp_get_attachment_image_url( $settings['alternate_logo_id'], 'full' ) : '';
        $values['company_copyright'] = sprintf(
            /* translators: 1: current year, 2: company name. */
            __( '© %1$s %2$s. All rights reserved.', 'cinderwell' ),
            wp_date( 'Y' ),
            $settings['name'] ?? get_bloginfo( 'name' )
        );

        return $values;
    }

    public function register_data_sources( $sources ) {
        foreach ( self::get_field_definitions() as $key => $field ) {
            if ( empty( $field['dynamic'] ) || in_array( $field['type'], [ 'media', 'repeater' ], true ) ) {
                continue;
            }
            $sources[ 'company_' . $key ] = [
                'label'   => $field['label'],
                'group'   => 'company',
                'context' => 'site',
            ];
        }
        $sources['company_address'] = [
            'label'   => __( 'Formatted address', 'cinderwell' ),
            'group'   => 'company',
            'context' => 'site',
        ];
        foreach ( [
            'company_phone_url'          => __( 'Phone link', 'cinderwell' ),
            'company_email_url'          => __( 'Email link', 'cinderwell' ),
            'company_logo_url'           => __( 'Logo URL', 'cinderwell' ),
            'company_alternate_logo_url' => __( 'Alternate logo URL', 'cinderwell' ),
            'company_copyright'          => __( 'Copyright line', 'cinderwell' ),
        ] as $key => $label ) {
            $sources[ $key ] = [ 'label' => $label, 'group' => 'company', 'context' => 'site' ];
        }

        return $sources;
    }

    public function resolve_data_source( $fallback, $source, $context ) {
        $values = self::get_data_source_values();
        return array_key_exists( $source, $values ) ? $values[ $source ] : $fallback;
    }

    public function add_editor_preview_values( $values ) {
        return array_merge( $values, self::get_data_source_values() );
    }

    public static function get_health() {
        $settings = self::get_settings();
        if ( empty( $settings['name'] ) ) {
            return [ 'status' => 'error', 'message' => __( 'Company name is missing.', 'cinderwell' ) ];
        }
        if ( empty( $settings['phone'] ) && empty( $settings['email'] ) && empty( $settings['address_line_1'] ) ) {
            return [ 'status' => 'warning', 'message' => __( 'Add a contact method or address.', 'cinderwell' ) ];
        }
        return [ 'status' => 'good', 'message' => __( 'Core company details are ready.', 'cinderwell' ) ];
    }

    public function output_schema() {
        $settings = self::get_settings();
        if ( empty( $settings['schema_enabled'] ) || empty( $settings['name'] ) ) {
            return;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => $settings['organization_type'] ?? 'Organization',
            '@id'      => home_url( '/#organization' ),
            'name'     => $settings['name'],
            'url'      => ! empty( $settings['website'] ) ? $settings['website'] : home_url( '/' ),
        ];
        foreach ( [ 'legalName' => 'legal_name', 'description' => 'description', 'email' => 'email', 'telephone' => 'phone' ] as $schema_key => $setting_key ) {
            if ( ! empty( $settings[ $setting_key ] ) ) {
                $schema[ $schema_key ] = $settings[ $setting_key ];
            }
        }
        if ( ! empty( $settings['logo_id'] ) ) {
            $schema['logo'] = wp_get_attachment_image_url( $settings['logo_id'], 'full' );
        }
        if ( ! empty( $settings['address_line_1'] ) ) {
            $schema['address'] = array_filter( [
                '@type'           => 'PostalAddress',
                'streetAddress'   => trim( $settings['address_line_1'] . ( ! empty( $settings['address_line_2'] ) ? "\n" . $settings['address_line_2'] : '' ) ),
                'addressLocality' => $settings['locality'] ?? '',
                'addressRegion'   => $settings['region'] ?? '',
                'postalCode'      => $settings['postal_code'] ?? '',
                'addressCountry'  => $settings['country'] ?? '',
            ] );
        }
        $same_as = array_filter( [ $settings['facebook'] ?? '', $settings['instagram'] ?? '', $settings['linkedin'] ?? '', $settings['youtube'] ?? '', $settings['x'] ?? '' ] );
        foreach ( (array) ( $settings['social_profiles'] ?? [] ) as $profile ) {
            if ( ! empty( $profile['url'] ) ) {
                $same_as[] = $profile['url'];
            }
        }
        if ( $same_as ) {
            $schema['sameAs'] = array_values( array_unique( $same_as ) );
        }

        echo '<script type="application/ld+json">' . wp_json_encode( apply_filters( 'cinderwell_company_schema', $schema ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>' . "\n";
    }

    public function add_settings_tab( $tabs ) {
        $tab = [
            'company-details' => [
                'label'    => __( 'Company Details', 'cinderwell' ),
                'group'    => 'content',
                'callback' => [ $this, 'render_settings' ],
            ],
        ];
        $keys     = array_keys( $tabs );
        $position = array_search( 'addons', $keys, true );
        $position = false === $position ? 1 : $position + 1;

        return array_slice( $tabs, 0, $position, true ) + $tab + array_slice( $tabs, $position, null, true );
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to manage company details.', 'cinderwell' ) );
        }

        check_admin_referer( 'cinderwell_save_company_details' );
        $submitted = isset( $_POST['company_details'] ) ? (array) wp_unslash( $_POST['company_details'] ) : [];
        $settings  = Admin_Fields::sanitize_values( self::get_field_definitions(), $submitted );
        update_option( self::OPTION, $settings );

        wp_safe_redirect( add_query_arg( [
            'page'    => 'cinderwell',
            'tab'     => 'company-details',
            'updated' => '1',
        ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function render_settings() {
        $fields   = self::get_field_definitions();
        $settings = self::get_settings();
        $sections = [
            'identity' => [
                'title'       => __( 'Identity', 'cinderwell' ),
                'description' => __( 'Reusable organization information for headings, footers, contact areas, and other Cinderwell content.', 'cinderwell' ),
                'fields'      => [ 'name', 'legal_name', 'description', 'logo_id', 'alternate_logo_id', 'organization_type', 'schema_enabled' ],
            ],
            'contact' => [
                'title'  => __( 'Contact', 'cinderwell' ),
                'fields' => [ 'phone', 'email', 'website', 'contact_url', 'directions_url', 'hours' ],
            ],
            'address' => [
                'title'  => __( 'Address', 'cinderwell' ),
                'fields' => [ 'address_line_1', 'address_line_2', 'locality', 'region', 'postal_code', 'country' ],
            ],
            'social' => [
                'title'  => __( 'Social profiles', 'cinderwell' ),
                'fields' => [ 'facebook', 'instagram', 'linkedin', 'youtube', 'x', 'social_profiles' ],
            ],
        ];

        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Company details saved.', 'cinderwell' ) . '</p></div>';
        }
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="cinderwell_save_company_details">
            <?php wp_nonce_field( 'cinderwell_save_company_details' ); ?>
            <div class="cw-settings-card-grid">
                <?php
                $rendered = [];
                foreach ( $sections as $section ) :
                    $section_fields = array_intersect_key( $fields, array_flip( $section['fields'] ) );
                    if ( ! $section_fields ) {
                        continue;
                    }
                    $rendered = array_merge( $rendered, array_keys( $section_fields ) );
                    ?>
                    <section class="card cw-settings-card">
                        <h2><?php echo esc_html( $section['title'] ); ?></h2>
                        <?php if ( ! empty( $section['description'] ) ) : ?>
                            <p><?php echo esc_html( $section['description'] ); ?></p>
                        <?php endif; ?>
                        <?php Admin_Fields::render_table( $section_fields, $settings, 'company_details', 'cw-company' ); ?>
                    </section>
                <?php endforeach; ?>
                <?php $additional = array_diff_key( $fields, array_flip( $rendered ) ); ?>
                <?php if ( $additional ) : ?>
                    <section class="card cw-settings-card">
                        <h2><?php esc_html_e( 'Additional details', 'cinderwell' ); ?></h2>
                        <?php Admin_Fields::render_table( $additional, $settings, 'company_details', 'cw-company' ); ?>
                    </section>
                <?php endif; ?>
            </div>
            <?php submit_button( __( 'Save Company Details', 'cinderwell' ) ); ?>
        </form>
        <?php
    }
}
