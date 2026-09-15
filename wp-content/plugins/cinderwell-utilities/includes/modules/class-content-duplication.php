<?php
namespace Cinderwell_Utilities\Modules;

class Content_Duplication {
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;
        add_filter('post_row_actions', [$this, 'row_action'], 10, 2);
        add_filter('page_row_actions', [$this, 'row_action'], 10, 2);
        add_action('post_submitbox_misc_actions', [$this, 'edit_screen_action']);
        add_action('admin_bar_menu', [$this, 'admin_bar_action'], 80);
        add_action('admin_action_cinderwell_duplicate', [$this, 'handle_duplicate']);
    }

    private function user_can($post_id = 0) {
        $user = wp_get_current_user();
        $allowed = $this->settings['roles'] ?? ['administrator', 'editor'];
        if (empty(array_intersect($allowed, (array) $user->roles))) {
            return false;
        }
        return !$post_id || current_user_can('edit_post', $post_id);
    }

    private function post_type_enabled($post_type) {
        return in_array($post_type, $this->settings['post_types'] ?? [], true);
    }

    public function row_action($actions, $post) {
        if (!$this->user_can($post->ID) || !$this->post_type_enabled($post->post_type)) {
            return $actions;
        }
        if (!in_array('list', $this->settings['show_in'] ?? [], true)) {
            return $actions;
        }
        $url = wp_nonce_url(
            add_query_arg(['action' => 'cinderwell_duplicate', 'post_id' => $post->ID], admin_url('admin.php')),
            'cinderwell_duplicate_' . $post->ID
        );
        $actions['cinderwell_duplicate'] = '<a href="' . esc_url($url) . '">Duplicate</a>';
        return $actions;
    }

    public function edit_screen_action($post) {
        if (!$this->user_can($post->ID) || !$this->post_type_enabled($post->post_type)) {
            return;
        }
        if (!in_array('edit', $this->settings['show_in'] ?? [], true)) {
            return;
        }
        $url = wp_nonce_url(
            add_query_arg(['action' => 'cinderwell_duplicate', 'post_id' => $post->ID], admin_url('admin.php')),
            'cinderwell_duplicate_' . $post->ID
        );
        echo '<div class="misc-pub-section"><a href="' . esc_url($url) . '" class="button">Duplicate this post</a></div>';
    }

    public function admin_bar_action($wp_admin_bar) {
        if (!$this->user_can()) {
            return;
        }
        if (!in_array('admin_bar', $this->settings['show_in'] ?? [], true)) {
            return;
        }
        global $post;
        if (!$post || !$this->user_can($post->ID) || !$this->post_type_enabled($post->post_type)) {
            return;
        }
        $url = wp_nonce_url(
            add_query_arg(['action' => 'cinderwell_duplicate', 'post_id' => $post->ID], admin_url('admin.php')),
            'cinderwell_duplicate_' . $post->ID
        );
        $wp_admin_bar->add_node([
            'id' => 'cinderwell-duplicate',
            'title' => 'Duplicate',
            'href' => $url,
            'parent' => 'edit',
        ]);
    }

    public function handle_duplicate() {
        $post_id = intval($_GET['post_id'] ?? 0);
        check_admin_referer('cinderwell_duplicate_' . $post_id);

        $original = get_post($post_id);
        if (!$original || !$this->post_type_enabled($original->post_type) || !$this->user_can($post_id)) {
            wp_die('Post not found.');
        }

        $suffix = $this->settings['title_suffix'] ?? 'Copy of ';
        $new_status = $this->settings['new_status'] ?? 'draft';
        $status = ($new_status === 'same') ? $original->post_status : $new_status;
        $post_type_object = get_post_type_object($original->post_type);
        if ('publish' === $status && (!$post_type_object || !current_user_can($post_type_object->cap->publish_posts))) {
            $status = 'draft';
        }

        $new_id = wp_insert_post([
            'post_title'   => $suffix . $original->post_title,
            'post_content' => $original->post_content,
            'post_excerpt' => $original->post_excerpt,
            'post_status'  => $status,
            'post_type'    => $original->post_type,
            'post_author'  => get_current_user_id(),
            'menu_order'   => $original->menu_order,
        ]);

        if (is_wp_error($new_id)) {
            wp_die('Could not duplicate: ' . $new_id->get_error_message());
        }

        // Copy meta
        $meta = get_post_meta($post_id);
        $skip = ['_edit_lock', '_edit_last', '_wp_old_slug', '_wp_trash_meta_time', '_wp_trash_meta_status'];
        foreach ($meta as $key => $values) {
            if (in_array($key, $skip, true)) continue;
            foreach ($values as $value) {
                add_post_meta($new_id, $key, maybe_unserialize($value));
            }
        }

        // Copy featured image
        $thumb = get_post_thumbnail_id($post_id);
        if ($thumb) {
            set_post_thumbnail($new_id, $thumb);
        }

        // Copy taxonomy terms
        $taxonomies = get_object_taxonomies($original->post_type);
        foreach ($taxonomies as $tax) {
            $terms = wp_get_post_terms($post_id, $tax, ['fields' => 'ids']);
            if (!is_wp_error($terms)) {
                wp_set_object_terms($new_id, $terms, $tax);
            }
        }

        // Redirect to edit screen
        wp_safe_redirect(get_edit_post_link($new_id, 'raw'));
        exit;
    }
}
