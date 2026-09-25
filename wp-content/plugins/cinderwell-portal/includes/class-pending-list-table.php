<?php
namespace Cinderwell_Portal;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Pending_List_Table extends \WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => 'pending_member',
            'plural' => 'pending_members',
            'ajax' => false,
        ]);
    }

    public function get_columns() {
        return [
            'cb' => '<input type="checkbox" />',
            'name' => 'Name',
            'email' => 'Email',
            'submitted' => 'Submitted',
            'actions' => 'Actions',
        ];
    }

    public function prepare_items() {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $this->_column_headers = [$this->get_columns(), [], []];

        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $args = [
            'role' => 'cinderwell_pending_member',
            'orderby' => 'registered',
            'order' => 'DESC',
            'number' => $per_page,
            'offset' => ($current_page - 1) * $per_page,
        ];
        if (!empty($search)) {
            $args['search'] = '*' . $search . '*';
        }

        $users = get_users($args);

        $count_args = ['role' => 'cinderwell_pending_member', 'count_total' => true, 'fields' => 'ID'];
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
                'submitted' => $user->user_registered,
            ];
        }, $users);
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="user_ids[]" value="%d" />', $item['ID']);
    }

    public function column_name($item) {
        return esc_html($item['name']);
    }

    public function column_email($item) {
        return esc_html($item['email']);
    }

    public function column_submitted($item) {
        return esc_html(mysql2date('Y-m-d H:i', $item['submitted']));
    }

    public function column_actions($item) {
        $approve_url = wp_nonce_url(
            add_query_arg(['cinderwell_action' => 'approve', 'user_id' => $item['ID']]),
            'cinderwell_approve_' . $item['ID']
        );
        $reject_url = wp_nonce_url(
            add_query_arg(['cinderwell_action' => 'reject', 'user_id' => $item['ID']]),
            'cinderwell_reject_' . $item['ID']
        );
        return sprintf(
            '<a href="%s" class="button button-primary button-small">Approve</a> <a href="%s" class="button button-small" onclick="return confirm(\'Reject this registration?\')">Reject</a>',
            esc_url($approve_url),
            esc_url($reject_url)
        );
    }

    public function column_default($item, $column_name) {
        return $item[$column_name] ?? '';
    }
}
