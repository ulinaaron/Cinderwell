<?php
/**
 * Authenticated REST API for mail tests and logs.
 *
 * @package Cinderwell_Utilities
 */

namespace Cinderwell_Utilities;

defined('ABSPATH') || exit;

class Mail_Rest_Api {
    private $settings;

    public function __construct(array $settings) {
        $this->settings = $settings;
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('cinder-utilities/v1', '/mail/log', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_log'],
                'permission_callback' => [$this, 'can_manage'],
                'args'                => [
                    'page'     => ['sanitize_callback' => 'absint', 'default' => 1],
                    'per_page' => ['sanitize_callback' => 'absint', 'default' => 20],
                    'status'   => ['sanitize_callback' => 'sanitize_key', 'default' => ''],
                    'search'   => ['sanitize_callback' => 'sanitize_text_field', 'default' => ''],
                ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [$this, 'clear_log'],
                'permission_callback' => [$this, 'can_manage'],
            ],
        ]);
        register_rest_route('cinder-utilities/v1', '/mail/test', [
            'methods'             => 'POST',
            'callback'            => [$this, 'test_mail'],
            'permission_callback' => [$this, 'can_manage'],
            'args'                => [
                'mode' => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => static function ($value) {
                        return in_array($value, ['validate', 'send'], true);
                    },
                ],
                'recipient' => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => static function ($value) {
                        return (bool) is_email($value);
                    },
                ],
            ],
        ]);
    }

    public function can_manage() {
        return current_user_can('manage_options');
    }

    public function get_log(\WP_REST_Request $request) {
        return rest_ensure_response(Mail_Log::query([
            'page'     => $request->get_param('page'),
            'per_page' => $request->get_param('per_page'),
            'status'   => $request->get_param('status'),
            'search'   => $request->get_param('search'),
        ]));
    }

    public function test_mail(\WP_REST_Request $request) {
        $manager = new Mail_Manager($this->settings);
        $result = $manager->send_test($request->get_param('recipient'), 'validate' === $request->get_param('mode'));
        $status = $result['success'] ? 200 : 400;
        return new \WP_REST_Response($result, $status);
    }

    public function clear_log() {
        if (!Mail_Log::clear()) {
            return new \WP_Error('clear_failed', __('The mail log could not be cleared.', 'cinderwell-utilities'), ['status' => 500]);
        }
        return rest_ensure_response(['success' => true]);
    }
}
