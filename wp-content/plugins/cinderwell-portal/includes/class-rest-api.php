<?php
namespace Cinderwell_Portal;

class Rest_Api {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('admin_init', [$this, 'handle_bulk_actions']);
    }

    public function register_routes() {
        $ns = 'cinder-portal/v1';

        register_rest_route($ns, '/login', [
            'methods' => 'POST',
            'callback' => [$this, 'login'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route($ns, '/logout', [
            'methods' => 'POST',
            'callback' => [$this, 'logout'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route($ns, '/register', [
            'methods' => 'POST',
            'callback' => [$this, 'register'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route($ns, '/password-reset/request', [
            'methods' => 'POST',
            'callback' => [$this, 'password_reset_request'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route($ns, '/me', [
            'methods' => 'GET',
            'callback' => [$this, 'me'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route($ns, '/can-access/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'can_access'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route($ns, '/pending', [
            'methods' => 'GET',
            'callback' => [$this, 'list_pending'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
        register_rest_route($ns, '/pending/(?P<id>\d+)/approve', [
            'methods' => 'POST',
            'callback' => [$this, 'approve'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
        register_rest_route($ns, '/pending/(?P<id>\d+)/reject', [
            'methods' => 'POST',
            'callback' => [$this, 'reject'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
        register_rest_route($ns, '/members', [
            'methods' => 'GET',
            'callback' => [$this, 'list_members'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
        register_rest_route($ns, '/members', [
            'methods' => 'POST',
            'callback' => [$this, 'create_member'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
        register_rest_route($ns, '/members/(?P<id>\d+)', [
            'methods' => 'PATCH',
            'callback' => [$this, 'update_member'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
        register_rest_route($ns, '/members/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_member'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
    }

    public function admin_only() {
        return current_user_can('manage_options');
    }

    public function login($request) {
        $email = sanitize_email($request->get_param('email'));
        $password = $request->get_param('password');
        $remember = (bool) $request->get_param('remember');

        if (empty($email) || empty($password)) {
            return new \WP_Error('missing_fields', 'Email and password required', ['status' => 400]);
        }

        $user = wp_authenticate($email, $password);
        if (is_wp_error($user)) {
            return new \WP_Error('invalid', 'Invalid credentials', ['status' => 401]);
        }

        if (in_array('cinderwell_pending_member', (array) $user->roles)) {
            return new \WP_Error('pending', 'Account pending approval', ['status' => 403]);
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);

        return rest_ensure_response(['success' => true, 'user' => [
            'id' => $user->ID,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
        ]]);
    }

    public function logout() {
        wp_destroy_current_session();
        wp_clear_auth_cookie();
        return rest_ensure_response(['success' => true]);
    }

    public function register($request) {
        $settings = Portal::get_settings();
        if (!in_array($settings['registration_mode'], ['moderated', 'both'])) {
            return new \WP_Error('disabled', 'Registration not enabled', ['status' => 403]);
        }

        $email = sanitize_email($request->get_param('email'));
        $first_name = sanitize_text_field($request->get_param('first_name'));
        $last_name = sanitize_text_field($request->get_param('last_name'));

        if (empty($email) || empty($first_name) || empty($last_name)) {
            return new \WP_Error('missing_fields', 'All fields required', ['status' => 400]);
        }

        if (!is_email($email)) {
            return new \WP_Error('invalid_email', 'Invalid email', ['status' => 400]);
        }

        if (email_exists($email)) {
            return new \WP_Error('email_taken', 'Email already registered', ['status' => 400]);
        }

        $username = sanitize_user(explode('@', $email)[0], true);
        if (empty($username)) $username = 'member';
        $i = 1;
        while (username_exists($username)) {
            $username = sanitize_user(explode('@', $email)[0], true) . $i;
            $i++;
        }

        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => wp_generate_password(20, true, true),
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim("$first_name $last_name"),
            'role' => 'cinderwell_pending_member',
        ]);

        if (is_wp_error($user_id)) {
            return new \WP_Error('create_failed', $user_id->get_error_message(), ['status' => 500]);
        }

        do_action('cinderwell_portal_register_user', $user_id, $request->get_params());

        $admin_email = get_option('admin_email');
        $approval_url = admin_url('admin.php?page=cinderwell&tab=portal&subtab=pending');
        Emails::send_template('admin_notification', $admin_email, [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'approval_url' => $approval_url,
        ]);

        return rest_ensure_response(['success' => true, 'message' => 'Registration pending approval']);
    }

    public function password_reset_request($request) {
        $email = sanitize_email($request->get_param('email'));
        if (empty($email)) {
            return new \WP_Error('missing_email', 'Email required', ['status' => 400]);
        }

        $user = get_user_by('email', $email);
        if ($user) {
            $key = get_password_reset_key($user);
            if (!is_wp_error($key)) {
                $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');
                Emails::send_template('password_setup', $user->user_email, [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->user_email,
                    'setup_url' => $reset_url,
                ]);
            }
        }

        return rest_ensure_response(['success' => true, 'message' => 'If an account exists, a reset link has been sent.']);
    }

    public function me() {
        $user = wp_get_current_user();
        if (!$user->exists()) {
            return new \WP_Error('not_logged_in', 'Not logged in', ['status' => 401]);
        }
        return rest_ensure_response([
            'id' => $user->ID,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'roles' => $user->roles,
            'is_member' => Portal::is_member(),
            'is_pending' => in_array('cinderwell_pending_member', (array) $user->roles),
        ]);
    }

    public function can_access($request) {
        $post_id = intval($request['id']);
        $restricted = get_post_meta($post_id, '_cinderwell_portal_restricted', true) === '1';
        if (!$restricted) {
            return rest_ensure_response(['restricted' => false, 'can_access' => true]);
        }
        $can = current_user_can('manage_options') || current_user_can(CINDERWELL_PORTAL_CAP);
        return rest_ensure_response(['restricted' => true, 'can_access' => $can]);
    }

    public function list_pending() {
        $users = get_users(['role' => 'cinderwell_pending_member', 'orderby' => 'registered', 'order' => 'DESC']);
        $data = array_map(function ($u) {
            return [
                'id' => $u->ID,
                'email' => $u->user_email,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'registered' => $u->user_registered,
            ];
        }, $users);
        return rest_ensure_response($data);
    }

    public function approve($request) {
        $user_id = intval($request['id']);
        $send_email = (bool) $request->get_param('send_email');
        $user = get_user_by('id', $user_id);
        if (!$user) return new \WP_Error('not_found', 'User not found', ['status' => 404]);

        $user->set_role('cinderwell_member');
        update_user_meta($user_id, 'cinderwell_portal_approved_by', get_current_user_id());
        update_user_meta($user_id, 'cinderwell_portal_approved_at', current_time('mysql'));

        if ($send_email) {
            Emails::send_template('approval', $user->user_email, [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->user_email,
                'login_url' => wp_login_url(),
            ]);
        }

        return rest_ensure_response(['success' => true]);
    }

    public function reject($request) {
        $user_id = intval($request['id']);
        $send_email = (bool) $request->get_param('send_email');
        $user = get_user_by('id', $user_id);
        if (!$user) return new \WP_Error('not_found', 'User not found', ['status' => 404]);

        if ($send_email) {
            Emails::send_template('rejection', $user->user_email, [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->user_email,
            ]);
        }

        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($user_id);

        return rest_ensure_response(['success' => true]);
    }

    public function list_members() {
        $users = get_users(['role' => 'cinderwell_member', 'orderby' => 'registered', 'order' => 'DESC']);
        $data = array_map(function ($u) {
            return [
                'id' => $u->ID,
                'email' => $u->user_email,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'registered' => $u->user_registered,
                'last_login' => get_user_meta($u->ID, 'cinderwell_portal_last_login', true),
                'login_count' => (int) get_user_meta($u->ID, 'cinderwell_portal_login_count', true),
            ];
        }, $users);
        return rest_ensure_response($data);
    }

    public function create_member($request) {
        $email = sanitize_email($request->get_param('email'));
        $first_name = sanitize_text_field($request->get_param('first_name'));
        $last_name = sanitize_text_field($request->get_param('last_name'));
        $send_welcome = (bool) $request->get_param('send_welcome');

        if (empty($email) || empty($first_name) || empty($last_name)) {
            return new \WP_Error('missing_fields', 'All fields required', ['status' => 400]);
        }

        if (email_exists($email)) {
            return new \WP_Error('email_taken', 'Email exists', ['status' => 400]);
        }

        $username = sanitize_user(explode('@', $email)[0], true);
        if (empty($username)) $username = 'member';
        $i = 1;
        while (username_exists($username)) {
            $username = sanitize_user(explode('@', $email)[0], true) . $i;
            $i++;
        }

        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => wp_generate_password(20, true, true),
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim("$first_name $last_name"),
            'role' => 'cinderwell_member',
        ]);

        if (is_wp_error($user_id)) {
            return new \WP_Error('create_failed', $user_id->get_error_message(), ['status' => 500]);
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

        return rest_ensure_response(['success' => true, 'user_id' => $user_id]);
    }

    public function update_member($request) {
        $user_id = intval($request['id']);
        $user = get_user_by('id', $user_id);
        if (!$user) return new \WP_Error('not_found', 'User not found', ['status' => 404]);

        $update = ['ID' => $user_id];
        if ($request->get_param('email')) $update['user_email'] = sanitize_email($request->get_param('email'));
        if ($request->get_param('first_name')) $update['first_name'] = sanitize_text_field($request->get_param('first_name'));
        if ($request->get_param('last_name')) $update['last_name'] = sanitize_text_field($request->get_param('last_name'));

        $fn = $update['first_name'] ?? $user->first_name;
        $ln = $update['last_name'] ?? $user->last_name;
        $update['display_name'] = trim("$fn $ln");

        $result = wp_update_user($update);
        if (is_wp_error($result)) {
            return new \WP_Error('update_failed', $result->get_error_message(), ['status' => 500]);
        }

        return rest_ensure_response(['success' => true]);
    }

    public function delete_member($request) {
        $user_id = intval($request['id']);
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($user_id);
        return rest_ensure_response(['success' => true]);
    }

    /**
     * Handle approve/reject from admin list table links
     */
    public function handle_bulk_actions() {
        if (!is_admin() || !current_user_can('manage_options')) return;

        $action = sanitize_key($_GET['cinderwell_action'] ?? '');
        $user_id = intval($_GET['user_id'] ?? 0);

        if (empty($action) || empty($user_id)) return;

        if ($action === 'approve') {
            if (!wp_verify_nonce($_GET['_wp_nonce'] ?? '', 'cinderwell_approve_' . $user_id)) {
                wp_die('Security check failed.');
            }
            $user = get_user_by('id', $user_id);
            if ($user) {
                $user->set_role('cinderwell_member');
                update_user_meta($user_id, 'cinderwell_portal_approved_by', get_current_user_id());
                update_user_meta($user_id, 'cinderwell_portal_approved_at', current_time('mysql'));
                Emails::send_template('approval', $user->user_email, [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->user_email,
                    'login_url' => wp_login_url(),
                ]);
            }
            wp_safe_redirect(admin_url('admin.php?page=cinderwell&tab=portal&subtab=pending&approved=1'));
            exit;
        }

        if ($action === 'reject') {
            if (!wp_verify_nonce($_GET['_wp_nonce'] ?? '', 'cinderwell_reject_' . $user_id)) {
                wp_die('Security check failed.');
            }
            $user = get_user_by('id', $user_id);
            if ($user) {
                Emails::send_template('rejection', $user->user_email, [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->user_email,
                ]);
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                wp_delete_user($user_id);
            }
            wp_safe_redirect(admin_url('admin.php?page=cinderwell&tab=portal&subtab=pending&rejected=1'));
            exit;
        }
    }
}
