<?php
namespace Cinderwell_Performance;

class Rest_Api {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        $ns = 'cinder-performance/v1';

        register_rest_route($ns, '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_settings'],
            'permission_callback' => [$this, 'admin_only'],
        ]);

        register_rest_route($ns, '/settings', [
            'methods' => 'PATCH',
            'callback' => [$this, 'update_settings'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
    }

    public function admin_only() {
        return current_user_can('manage_options');
    }

    public function get_settings() {
        return rest_ensure_response(Settings::get_settings());
    }

    public function update_settings($request) {
        $params = $request->get_json_params();
        $current = Settings::get_settings();

        foreach ($params as $section => $values) {
            if (isset($current[$section]) && is_array($values)) {
                $current[$section] = array_merge($current[$section], $values);
            }
        }

        Settings::update_settings($current);
        return rest_ensure_response(['success' => true, 'settings' => Settings::get_settings()]);
    }
}
