<?php
/**
 * Update mechanism — static Surge manifest and release packages.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Update_Mechanism {

    private $update_url = 'https://cinderwell-updates.surge.sh/info.json';

    private $update_info = null;

    private $update_info_loaded = false;

    public function __construct() {
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_updates' ] );
        add_filter( 'pre_set_site_transient_update_themes', [ $this, 'check_for_theme_updates' ] );
        add_filter( 'plugins_api', [ $this, 'plugin_info' ], 10, 3 );
    }

    /**
     * Check for plugin updates.
     */
    public function check_for_updates( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        foreach ( $this->get_plugin_packages( $this->fetch_update_info() ) as $package ) {
            $data        = $package['data'];
            $plugin_file = $package['plugin_file'];

            if ( ! isset( $transient->checked[ $plugin_file ] ) || ! version_compare( $data->version, $transient->checked[ $plugin_file ], '>' ) ) {
                continue;
            }

            $transient->response[ $plugin_file ] = (object) [
                'slug'         => sanitize_key( $data->slug ),
                'plugin'       => $plugin_file,
                'new_version'  => sanitize_text_field( $data->version ),
                'url'          => esc_url_raw( $data->homepage ?? '' ),
                'package'      => esc_url_raw( $data->download_url ),
                'requires'     => sanitize_text_field( $data->requires ?? '' ),
                'requires_php' => sanitize_text_field( $data->requires_php ?? '' ),
                'tested'       => sanitize_text_field( $data->tested ?? '' ),
            ];
        }

        return $transient;
    }

    /**
     * Make the Cinderwell parent theme updateable without copying changes into
     * every client child theme.
     */
    public function check_for_theme_updates( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $manifest = $this->fetch_update_info();
        $data     = $manifest->theme ?? null;
        $slug     = 'cinderwell-starter';

        if (
            ! $data
            || empty( $data->version )
            || empty( $data->download_url )
            || ! isset( $transient->checked[ $slug ] )
            || ! version_compare( $data->version, $transient->checked[ $slug ], '>' )
        ) {
            return $transient;
        }

        $transient->response[ $slug ] = [
            'theme'        => $slug,
            'new_version'  => sanitize_text_field( $data->version ),
            'url'          => esc_url_raw( $data->homepage ?? '' ),
            'package'      => esc_url_raw( $data->download_url ),
            'requires'     => sanitize_text_field( $data->requires ?? '' ),
            'requires_php' => sanitize_text_field( $data->requires_php ?? '' ),
        ];

        return $transient;
    }

    /**
     * Plugin info popup.
     */
    public function plugin_info( $response, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $response;
        }
        if ( ! isset( $args->slug ) ) {
            return $response;
        }

        foreach ( $this->get_plugin_packages( $this->fetch_update_info() ) as $package ) {
            $data = $package['data'];

            if ( $args->slug !== $data->slug ) {
                continue;
            }

            return (object) [
                'name'          => sanitize_text_field( $data->name ?? $data->slug ),
                'slug'          => sanitize_key( $data->slug ),
                'version'       => sanitize_text_field( $data->version ),
                'homepage'      => esc_url_raw( $data->homepage ?? '' ),
                'requires'      => sanitize_text_field( $data->requires ?? '' ),
                'requires_php'  => sanitize_text_field( $data->requires_php ?? '' ),
                'tested'        => sanitize_text_field( $data->tested ?? '' ),
                'sections'      => (array) ( $data->sections ?? [] ),
                'download_link' => esc_url_raw( $data->download_url ),
            ];
        }

        return $response;
    }

    /**
     * Return the core plugin and any add-ons declared by the manifest.
     *
     * The legacy top-level plugin object remains supported so installations
     * running the first updater can discover the next core release.
     */
    private function get_plugin_packages( $manifest ) {
        if ( ! is_object( $manifest ) ) {
            return [];
        }

        $packages = [];
        $core     = isset( $manifest->plugin ) ? $manifest->plugin : $manifest;

        if ( is_object( $core ) && ! empty( $core->version ) && ! empty( $core->download_url ) ) {
            $core->slug = sanitize_key( $core->slug ?? 'cinderwell' );
            $packages[] = [
                'plugin_file' => sanitize_text_field( $core->plugin_file ?? 'cinderwell/cinderwell.php' ),
                'data'        => $core,
            ];
        }

        if ( empty( $manifest->addons ) || ! is_object( $manifest->addons ) ) {
            return $packages;
        }

        foreach ( get_object_vars( $manifest->addons ) as $manifest_slug => $data ) {
            if ( ! is_object( $data ) || empty( $data->version ) || empty( $data->download_url ) ) {
                continue;
            }

            $data->slug = sanitize_key( $data->slug ?? $manifest_slug );
            if ( ! $data->slug ) {
                continue;
            }

            $packages[] = [
                'plugin_file' => sanitize_text_field( $data->plugin_file ?? $data->slug . '/' . $data->slug . '.php' ),
                'data'        => $data,
            ];
        }

        return $packages;
    }

    private function fetch_update_info() {
        if ( $this->update_info_loaded ) {
            return $this->update_info;
        }

        $this->update_info_loaded = true;
        $update_url = apply_filters( 'cinderwell_update_manifest_url', $this->update_url );
        $response   = wp_remote_get( esc_url_raw( $update_url ), [
            'timeout' => 10,
        ] );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return null;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        $this->update_info = is_object( $data ) ? $data : null;

        return $this->update_info;
    }
}
