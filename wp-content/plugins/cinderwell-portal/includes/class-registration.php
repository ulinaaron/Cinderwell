<?php
namespace Cinderwell_Portal;

class Registration {
    public function __construct() {
        add_action('wp_ajax_nopriv_cinderwell_portal_register', [$this, 'handle_registration']);
    }

    public function handle_registration() {
        check_ajax_referer('cinderwell_portal_registration', 'nonce');

        $settings = Portal::get_settings();

        if (!in_array($settings['registration_mode'], ['moderated', 'both'])) {
            wp_send_json_error(['message' => 'Public registration is not enabled.']);
        }

        $email = sanitize_email($_POST['email'] ?? '');
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $terms = !empty($_POST['terms']);
        $privacy = !empty($_POST['privacy']);

        if (empty($email) || empty($first_name) || empty($last_name)) {
            wp_send_json_error(['message' => 'All fields are required.']);
        }

        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Please enter a valid email address.']);
        }

        if (email_exists($email)) {
            wp_send_json_error(['message' => 'An account with this email already exists.']);
        }

        if ($settings['show_terms_checkbox'] && !$terms) {
            wp_send_json_error(['message' => 'You must accept the terms.']);
        }

        if ($settings['show_privacy_checkbox'] && !$privacy) {
            wp_send_json_error(['message' => 'You must accept the privacy policy.']);
        }

        $username = $this->generate_username($email);
        $password = wp_generate_password(20, true, true);

        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim("$first_name $last_name"),
            'role' => 'cinderwell_pending_member',
        ]);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => 'Could not create account. Please try again.']);
        }

        do_action('cinderwell_portal_register_user', $user_id, $_POST);

        $admin_email = get_option('admin_email');
        $approval_url = admin_url('admin.php?page=cinderwell&tab=portal&subtab=pending');
        Emails::send_template('admin_notification', $admin_email, [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'approval_url' => $approval_url,
        ]);

        wp_send_json_success([
            'message' => 'Your registration is pending approval. You will receive an email once your account is approved.',
        ]);
    }

    private function generate_username($email) {
        $base = sanitize_user(explode('@', $email)[0], true);
        if (empty($base)) {
            $base = 'member';
        }
        $username = $base;
        $i = 1;
        while (username_exists($username)) {
            $username = $base . $i;
            $i++;
        }
        return $username;
    }
}
