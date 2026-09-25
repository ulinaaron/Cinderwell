<?php
namespace Cinderwell_Portal;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Member_List_Table extends \WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => 'member',
            'plural' => 'members',
            'ajax' => false,
        ]);
    }

    public function get_columns() {
        return [
            'cb' => '<input type="checkbox" />',
            'name' => 'Name',
            'email' => 'Email',
            'member_since' => 'Member Since',
            'last_login' => 'Last Login',
            'login_count' => 'Logins',
        ];
    }

    public function prepare_items() {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $this->_column_headers = [$this->get_columns(), [], []];

        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $args = [
            'role' => 'cinderwell_member',
            'orderby' => 'registered',
            'order' => 'DESC',
            'number' => $per_page,
            'offset' => ($current_page - 1) * $per_page,
        ];
        if (!empty($search)) {
            $args['search'] = '*' . $search . '*';
        }

        $users = get_users($args);

        $count_args = ['role' => 'cinderwell_member', 'count_total' => true, 'fields' => 'ID'];
        if (!empty($search)) {
            $count_args['search'] = '*' . $search . '*';
        }
        $total = count(get_users($count_args));

        $this->set_pagination_args([
            'total_items' => $total,
            'per_page' => $per_page,
        ]);

        $this->items = array_map(function ($user) {
            return [
                'ID' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'member_since' => get_user_meta($user->ID, 'cinderwell_portal_approved_at', true) ?: $user->user_registered,
                'last_login' => get_user_meta($user->ID, 'cinderwell_portal_last_login', true) ?: '',
                'login_count' => get_user_meta($user->ID, 'cinderwell_portal_login_count', true) ?: 0,
            ];
        }, $users);
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="user_ids[]" value="%d" />', $item['ID']);
    }

    public function column_name($item) {
        $edit_url = get_edit_user_link($item['ID']);
        return sprintf('<strong><a href="%s">%s</a></strong>', esc_url($edit_url), esc_html($item['name']));
    }

    public function column_email($item) {
        return esc_html($item['email']);
    }

    public function column_member_since($item) {
        return $item['member_since'] ? esc_html(mysql2date('Y-m-d', $item['member_since'])) : '&mdash;';
    }

    public function column_last_login($item) {
        return $item['last_login'] ? esc_html(mysql2date('Y-m-d H:i', $item['last_login'])) : 'Never';
    }

    public function column_login_count($item) {
        return intval($item['login_count']);
    }

    public function column_default($item, $column_name) {
        return $item[$column_name] ?? '';
    }
}
