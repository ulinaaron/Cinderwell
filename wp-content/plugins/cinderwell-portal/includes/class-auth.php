<?php
namespace Cinderwell_Portal;

class Auth {
    public function __construct() {
        add_action('wp_ajax_nopriv_cinderwell_portal_login', [$this, 'handle_login']);
        add_action('wp_ajax_cinderwell_portal_login', [$this, 'handle_login']);
        add_action('wp_ajax_nopriv_cinderwell_portal_lost_password', [$this, 'handle_lost_password']);
        add_action('wp_ajax_cinderwell_portal_lost_password', [$this, 'handle_lost_password']);
        add_action('wp_ajax_cinderwell_portal_profile_update', [$this, 'handle_profile_update']);
        add_action('wp_ajax_cinderwell_portal_change_password', [$this, 'handle_change_password']);
        add_action('wp_login', [$this, 'track_login'], 10, 2);

        // Member access toggle on user profile screens.
        add_action('edit_user_profile', [$this, 'render_profile_field']);
        add_action('edit_user_profile_update', [$this, 'save_profile_field']);
        add_action('show_user_profile', [$this, 'render_profile_field']);
    }

    public function handle_login() {
        check_ajax_referer('cinderwell_portal_auth', 'nonce');

        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        if (empty($email) || empty($password)) {
            wp_send_json_error(['message' => 'Email and password are required.']);
        }

        $user = wp_authenticate($email, $password);

        if (is_wp_error($user)) {
            wp_send_json_error(['message' => 'Invalid email or password.']);
        }

        if (in_array('cinderwell_pending_member', (array) $user->roles)) {
            $settings = Portal::get_settings();
            $redirect = $settings['pending_redirect_url'] ?: home_url('/pending-approval/');
            wp_send_json_error([
                'message' => 'Your account is pending approval.',
                'redirect' => $redirect,
            ]);
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);

        $settings = Portal::get_settings();
        $redirect = $settings['login_redirect_url'] ?: home_url('/');

        wp_send_json_success(['redirect' => $redirect, 'user' => [
            'id' => $user->ID,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
        ]]);
    }

    public function handle_lost_password() {
        check_ajax_referer('cinderwell_portal_auth', 'nonce');

        $email = sanitize_email($_POST['email'] ?? '');

        if (empty($email)) {
            wp_send_json_error(['message' => 'Email is required.']);
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            wp_send_json_success(['message' => 'If an account exists, a reset link has been sent.']);
            return;
        }

        $key = get_password_reset_key($user);
        if (is_wp_error($key)) {
            wp_send_json_error(['message' => 'Could not generate reset key.']);
        }

        $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');

        $settings = Portal::get_settings();
        Emails::send_template('password_setup', $user->user_email, [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->user_email,
            'setup_url' => $reset_url,
        ]);

        wp_send_json_success(['message' => 'If an account exists, a reset link has been sent.']);
    }

    public function handle_profile_update() {
        check_ajax_referer('cinderwell_portal_profile', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in.']);
        }

        $user = wp_get_current_user();
        $email = sanitize_email($_POST['email'] ?? '');
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');

        if (empty($email) || empty($first_name) || empty($last_name)) {
            wp_send_json_error(['message' => 'All fields are required.']);
        }

        if ($email !== $user->user_email && email_exists($email)) {
            wp_send_json_error(['message' => 'That email is already in use.']);
        }

        $update = wp_update_user([
            'ID' => $user->ID,
            'user_email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim("$first_name $last_name"),
        ]);

        if (is_wp_error($update)) {
            wp_send_json_error(['message' => $update->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Profile updated.']);
    }

    public function handle_change_password() {
        check_ajax_referer('cinderwell_portal_profile', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in.']);
        }

        $user = wp_get_current_user();
        $current = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';

        if (empty($current) || empty($new_pass)) {
            wp_send_json_error(['message' => 'Both password fields are required.']);
        }

        if (strlen($new_pass) < 8) {
            wp_send_json_error(['message' => 'New password must be at least 8 characters.']);
        }

        if (!wp_check_password($current, $user->data->user_pass, $user->ID)) {
            wp_send_json_error(['message' => 'Current password is incorrect.']);
        }

        wp_set_password($new_pass, $user->ID);
        wp_set_auth_cookie($user->ID);

        wp_send_json_success(['message' => 'Password changed.']);
    }

    public function track_login($user_login, $user) {
        if (user_can($user, CINDERWELL_PORTAL_CAP)) {
            update_user_meta($user->ID, 'cinderwell_portal_last_login', current_time('mysql'));
            $count = (int) get_user_meta($user->ID, 'cinderwell_portal_login_count', true);
            update_user_meta($user->ID, 'cinderwell_portal_login_count', $count + 1);
        }
    }

    public function render_profile_field($user) {
        if (!current_user_can('manage_options')) {
            return;
        }
        $has_cap = $user->has_cap(CINDERWELL_PORTAL_CAP);
        $is_pending = in_array('cinderwell_pending_member', (array) $user->roles);
        ?>
        <h2><?php esc_html_e('Members Portal', 'cinderwell-portal'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Member Access', 'cinderwell-portal'); ?></th>
                <td>
                    <label for="cinderwell_portal_member_access">
                        <input type="checkbox" id="cinderwell_portal_member_access" name="cinderwell_portal_member_access" value="1" <?php checked($has_cap); ?> />
                        <?php esc_html_e('Grant access to member-only content', 'cinderwell-portal'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Adds the access_portal_content capability. The user can view all content protected by the Members Portal, regardless of their WordPress role.', 'cinderwell-portal'); ?>
                    </p>
                </td>
            </tr>
            <?php if ($is_pending): ?>
            <tr>
                <th scope="row"><?php esc_html_e('Status', 'cinderwell-portal'); ?></th>
                <td>
                    <span class="cinderwell-portal-status-pending" style="display:inline-block;padding:2px 8px;background:#fef3c7;border-radius:3px;font-size:.8125rem;">
                        <?php esc_html_e('Pending approval', 'cinderwell-portal'); ?>
                    </span>
                    <p class="description">
                        <?php esc_html_e('This user registered and is waiting for approval. Approve from Members Portal → Pending to assign the Member role.', 'cinderwell-portal'); ?>
                    </p>
                </td>
            </tr>
            <?php endif; ?>
            <?php
            $last_login = get_user_meta($user->ID, 'cinderwell_portal_last_login', true);
            $login_count = (int) get_user_meta($user->ID, 'cinderwell_portal_login_count', true);
            if ($last_login || $login_count): ?>
            <tr>
                <th scope="row"><?php esc_html_e('Portal Activity', 'cinderwell-portal'); ?></th>
                <td>
                    <?php
                    $parts = [];
                    if ($last_login) {
                        $parts[] = 'Last login: ' . esc_html(mysql2date('M j, Y g:i a', $last_login));
                    }
                    if ($login_count) {
                        $parts[] = sprintf('%d login%s', $login_count, $login_count === 1 ? '' : 's');
                    }
                    echo implode(' &middot; ', $parts);
                    ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    public function save_profile_field($user_id) {
        if (!current_user_can('manage_options') || !current_user_can('edit_user', $user_id)) {
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $grant = !empty($_POST['cinderwell_portal_member_access']);
        $has_cap = $user->has_cap(CINDERWELL_PORTAL_CAP);

        if ($grant && !$has_cap) {
            $user->add_cap(CINDERWELL_PORTAL_CAP);
        } elseif (!$grant && $has_cap) {
            $user->remove_cap(CINDERWELL_PORTAL_CAP);
        }
    }
}
