<?php
/**
 * Update mechanism — GitHub Releases as update server.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Update_Mechanism {

    private $update_url = 'https://updates.stevensinc.com/cinderwell/info.json';

    public function __construct() {
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_updates' ] );
        add_filter( 'plugins_api', [ $this, 'plugin_info' ], 10, 3 );
    }

    /**
     * Check for plugin updates.
     */
    public function check_for_updates( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $data = $this->fetch_update_info();
        if ( ! $data ) {
            return $transient;
        }

        $plugin_slug = 'cinderwell/cinderwell.php';
        if ( isset( $transient->checked[ $plugin_slug ] ) && version_compare( $data->version, $transient->checked[ $plugin_slug ], '>' ) ) {
            $transient->response[ $plugin_slug ] = (object) [
                'slug'        => 'cinderwell',
                'new_version' => $data->version,
                'url'         => $data->homepage,
                'package'     => $data->download_url,
                'requires'    => $data->requires,
                'tested'      => $data->tested,
            ];
        }

        return $transient;
    }

    /**
     * Plugin info popup.
     */
    public function plugin_info( $response, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $response;
        }
        if ( ! isset( $args->slug ) || 'cinderwell' !== $args->slug ) {
            return $response;
        }

        $data = $this->fetch_update_info();
        if ( ! $data ) {
            return $response;
        }

        return (object) [
            'name'           => $data->name,
            'slug'           => $data->slug,
            'version'        => $data->version,
            'homepage'       => $data->homepage,
            'requires'       => $data->requires,
            'tested'         => $data->tested,
            'sections'       => (array) $data->sections,
            'download_link'  => $data->download_url,
        ];
    }

    private function fetch_update_info() {
        $response = wp_remote_get( $this->update_url, [
            'timeout' => 10,
        ] );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        return $data ?: null;
    }
}
