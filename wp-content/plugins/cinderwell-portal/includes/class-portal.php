<?php
namespace Cinderwell_Portal;

class Portal {
    private static $instance;

    const OPTION = 'cinderwell_portal_settings';

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init_hooks();
        $this->load_components();
    }

    private function init_hooks() {
        add_action('init', [$this, 'register_blocks']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('init', [$this, 'register_settings']);
    }

    private function load_components() {
        new Auth();
        new Registration();
        new Admin_Issued();
        new Access_Control();
        new Emails();
        new Template_Loader();

        if (is_admin()) {
            new Admin_Page();
        }

        new Rest_Api();
    }

    /**
     * Whether the current user can access member content.
     * Checks the `access_portal_content` capability, which the Member role has
     * by default. Admins always pass. Other roles can be granted the capability
     * via Users → Roles or a role editor plugin.
     */
    public static function is_member() {
        if (!is_user_logged_in()) {
            return false;
        }
        return current_user_can('manage_options') || current_user_can(CINDERWELL_PORTAL_CAP);
    }

    public function register_blocks() {
        $blocks = ['login', 'register', 'member-profile', 'member-only'];
        foreach ($blocks as $block) {
            $block_path = CINDERWELL_PORTAL_PATH . 'build/blocks/' . $block;
            if (file_exists($block_path . '/block.json')) {
                register_block_type($block_path);
            }
        }
    }

    public function register_settings() {
        register_setting('cinderwell_portal_settings_group', self::OPTION, [
            'type' => 'object',
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);
    }

    public function sanitize_settings($settings) {
        $settings['registration_mode'] = in_array($settings['registration_mode'] ?? '', ['closed', 'moderated', 'admin_issued', 'both']) ? $settings['registration_mode'] : 'moderated';
        $settings['login_redirect_url'] = esc_url_raw($settings['login_redirect_url'] ?? '');
        $settings['logout_redirect_url'] = esc_url_raw($settings['logout_redirect_url'] ?? '');
        $settings['pending_redirect_url'] = esc_url_raw($settings['pending_redirect_url'] ?? '');
        $settings['from_email'] = sanitize_email($settings['from_email'] ?? '');
        $settings['from_name'] = sanitize_text_field($settings['from_name'] ?? '');
        $settings['show_terms_checkbox'] = !empty($settings['show_terms_checkbox']);
        $settings['terms_text'] = wp_kses_post($settings['terms_text'] ?? '');
        $settings['show_privacy_checkbox'] = !empty($settings['show_privacy_checkbox']);
        $settings['privacy_text'] = wp_kses_post($settings['privacy_text'] ?? '');
        $settings['submit_button_text'] = sanitize_text_field($settings['submit_button_text'] ?? 'Register');
        if (isset($settings['email_templates'])) {
            foreach ($settings['email_templates'] as $key => &$tpl) {
                $tpl['subject'] = sanitize_text_field($tpl['subject'] ?? '');
                $tpl['body'] = wp_kses_post($tpl['body'] ?? '');
                if ($key === 'rejection') {
                    $tpl['enabled'] = !empty($tpl['enabled']);
                }
            }
        }
        return $settings;
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style('cinderwell-portal-style', CINDERWELL_PORTAL_URL . 'build/style-frontend-style.css', [], CINDERWELL_PORTAL_VERSION);
        wp_enqueue_script('cinderwell-portal-frontend', CINDERWELL_PORTAL_URL . 'build/frontend.js', [], CINDERWELL_PORTAL_VERSION, true);

        wp_localize_script('cinderwell-portal-frontend', 'cinderwell_portal', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('cinder-portal/v1/'),
            'nonce' => wp_create_nonce('cinderwell_portal_auth'),
            'is_logged_in' => is_user_logged_in(),
            'is_member' => self::is_member(),
            'login_url' => wp_login_url(),
        ]);
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'cinderwell') === false && $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        wp_enqueue_script('cinderwell-portal-admin', CINDERWELL_PORTAL_URL . 'build/admin.js', [], CINDERWELL_PORTAL_VERSION, true);
        wp_enqueue_script('cinderwell-portal-admin-email', CINDERWELL_PORTAL_URL . 'build/admin-email.js', [], CINDERWELL_PORTAL_VERSION, true);
        wp_enqueue_style('cinderwell-portal-admin-style', CINDERWELL_PORTAL_URL . 'build/admin-style.css', [], CINDERWELL_PORTAL_VERSION);

        wp_localize_script('cinderwell-portal-admin', 'cinderwell_portal_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cinderwell_portal_admin'),
        ]);
    }

    /**
     * Default settings — used by the Add-Ons reset flow.
     */
    public static function get_defaults() {
        return [
            'registration_mode' => 'moderated',
            'login_redirect_url' => '',
            'logout_redirect_url' => '',
            'pending_redirect_url' => '',
            'show_terms_checkbox' => false,
            'terms_text' => '',
            'show_privacy_checkbox' => false,
            'privacy_text' => '',
            'submit_button_text' => 'Register',
            'from_email' => '',
            'from_name' => '',
            'email_templates' => [
                'welcome' => [
                    'subject' => 'Welcome to {site_name}',
                    'body' => "Hi {first_name},\n\nYour account has been approved. You can now log in at {login_url}.\n\nYour email: {email}\n\nThanks,\n{site_name}",
                ],
                'approval' => [
                    'subject' => 'Your account has been approved',
                    'body' => "Hi {first_name},\n\nGreat news — your registration has been approved. You can now log in at {login_url}.\n\nThanks,\n{site_name}",
                ],
                'rejection' => [
                    'subject' => 'Your registration was not approved',
                    'body' => "Hi {first_name},\n\nUnfortunately your registration was not approved. If you think this is a mistake, contact us.\n\n{site_name}",
                    'enabled' => true,
                ],
                'admin_notification' => [
                    'subject' => 'New pending member registration',
                    'body' => "A new registration is awaiting approval:\n\nName: {first_name} {last_name}\nEmail: {email}\n\nReview: {approval_url}",
                ],
                'password_setup' => [
                    'subject' => 'Set up your {site_name} account',
                    'body' => "Hi {first_name},\n\nAn account has been created for you. Set your password here: {setup_url}\n\nThanks,\n{site_name}",
                ],
            ],
        ];
    }

    /**
     * Health check — shown on the Add-Ons card when enabled.
     */
    public static function get_health() {
        $settings = self::get_settings();
        $members = count(get_users(['role' => 'cinderwell_member', 'fields' => 'ID']));
        $pending = count(get_users(['role' => 'cinderwell_pending_member', 'fields' => 'ID']));
        $mode = $settings['registration_mode'] ?? 'moderated';

        $mode_labels = [
            'closed' => 'Closed',
            'moderated' => 'Moderated sign-up',
            'admin_issued' => 'Admin-issued only',
            'both' => 'Moderated + admin-issued',
        ];

        return [
            'status'  => 'good',
            'message' => sprintf(
                '%s · %d member%s, %d pending',
                $mode_labels[$mode] ?? $mode,
                $members,
                $members === 1 ? '' : 's',
                $pending
            ),
        ];
    }

    public static function get_settings() {
        return wp_parse_args(get_option(self::OPTION, []), self::get_defaults());
    }

    public static function update_settings($settings) {
        update_option(self::OPTION, $settings);
    }
}
