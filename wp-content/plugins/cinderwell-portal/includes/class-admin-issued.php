<?php
namespace Cinderwell_Portal;

class Admin_Issued {
    public function __construct() {
        add_action('wp_ajax_cinderwell_portal_admin_create_member', [$this, 'handle_admin_create']);
        add_action('wp_ajax_cinderwell_portal_admin_update_member', [$this, 'handle_admin_update']);
        add_action('wp_ajax_cinderwell_portal_admin_delete_member', [$this, 'handle_admin_delete']);
        add_action('wp_ajax_cinderwell_portal_admin_approve_pending', [$this, 'handle_approve']);
        add_action('wp_ajax_cinderwell_portal_admin_reject_pending', [$this, 'handle_reject']);
        add_action('wp_ajax_cinderwell_portal_admin_send_test_email', [$this, 'handle_test_email']);
    }

    public function handle_admin_create() {
        check_ajax_referer('cinderwell_portal_admin', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        $email = sanitize_email($_POST['email'] ?? '');
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $send_welcome = !empty($_POST['send_welcome']);

        if (empty($email) || empty($first_name) || empty($last_name)) {
            wp_send_json_error(['message' => 'All fields are required.']);
        }

        if (email_exists($email)) {
            wp_send_json_error(['message' => 'An account with this email already exists.']);
        }

        $username = sanitize_user(explode('@', $email)[0], true);
        if (empty($username)) {
            $username = 'member';
        }
        $i = 1;
        while (username_exists($username)) {
            $username = sanitize_user(explode('@', $email)[0], true) . $i;
            $i++;
        }

        $password = wp_generate_password(20, true, true);
        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim("$first_name $last_name"),
            'role' => 'cinderwell_member',
        ]);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }

        update_user_meta($user_id, 'cinderwell_portal_approved_by', get_current_user_id());
        update_user_meta($user_id, 'cinderwell_portal_approved_at', current_time('mysql'));
        update_user_meta($user_id, 'cinderwell_portal_invited_by', get_current_user_id());

        if ($send_welcome) {
            $key = get_password_reset_key(get_user_by('id', $user_id));
            if (!is_wp_error($key)) {
                $setup_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($username), 'login');
                Emails::send_template('password_setup', $email, [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'setup_url' => $setup_url,
                ]);
            }
        }

        wp_send_json_success(['message' => 'Member created.', 'user_id' => $user_id]);
    }

    public function handle_admin_update() {
        check_ajax_referer('cinderwell_portal_admin', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        $user_id = intval($_POST['user_id'] ?? 0);
        $email = sanitize_email($_POST['email'] ?? '');
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');

        if (!$user_id || !get_userdata($user_id)) {
            wp_send_json_error(['message' => 'User not found.']);
        }

        $update = ['ID' => $user_id];
        if (!empty($email)) $update['user_email'] = $email;
        if (!empty($first_name)) $update['first_name'] = $first_name;
        if (!empty($last_name)) $update['last_name'] = $last_name;
        if (!empty($first_name) && !empty($last_name)) {
            $update['display_name'] = trim("$first_name $last_name");
        }

        $result = wp_update_user($update);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Member updated.']);
    }

    public function handle_admin_delete() {
        check_ajax_referer('cinderwell_portal_admin', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        $user_id = intval($_POST['user_id'] ?? 0);
        if (!$user_id) {
            wp_send_json_error(['message' => 'User ID required.']);
        }

        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($user_id);

        wp_send_json_success(['message' => 'Member deleted.']);
    }

    public function handle_approve() {
        check_ajax_referer('cinderwell_portal_admin', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        $user_id = intval($_POST['user_id'] ?? 0);
        $send_email = !empty($_POST['send_email']);
        $user = get_user_by('id', $user_id);

        if (!$user) {
            wp_send_json_error(['message' => 'User not found.']);
        }

        $user->set_role('cinderwell_member');
        update_user_meta($user_id, 'cinderwell_portal_approved_by', get_current_user_id());
        update_user_meta($user_id, 'cinderwell_portal_approved_at', current_time('mysql'));

        if ($send_email) {
            $login_url = wp_login_url();
            Emails::send_template('approval', $user->user_email, [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->user_email,
                'login_url' => $login_url,
            ]);
        }

        wp_send_json_success(['message' => 'Member approved.']);
    }

    public function handle_reject() {
        check_ajax_referer('cinderwell_portal_admin', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        $user_id = intval($_POST['user_id'] ?? 0);
        $send_email = !empty($_POST['send_email']);
        $user = get_user_by('id', $user_id);

        if (!$user) {
            wp_send_json_error(['message' => 'User not found.']);
        }

        $settings = Portal::get_settings();
        if ($send_email && !empty($settings['email_templates']['rejection']['enabled'])) {
            Emails::send_template('rejection', $user->user_email, [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->user_email,
            ]);
        }

        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($user_id);

        wp_send_json_success(['message' => 'Registration rejected.']);
    }

    public function handle_test_email() {
        check_ajax_referer('cinderwell_portal_admin', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        $template = sanitize_key($_POST['template'] ?? '');
        $to = sanitize_email($_POST['to'] ?? get_option('admin_email'));

        $valid = ['welcome', 'approval', 'rejection', 'admin_notification', 'password_setup'];
        if (!in_array($template, $valid)) {
            wp_send_json_error(['message' => 'Invalid template.']);
        }

        Emails::send_template($template, $to, [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $to,
            'login_url' => wp_login_url(),
            'setup_url' => wp_login_url(),
            'approval_url' => admin_url('admin.php?page=cinderwell&tab=portal&subtab=pending'),
        ]);

        wp_send_json_success(['message' => 'Test email sent to ' . $to]);
    }
}
