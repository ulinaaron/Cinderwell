<?php
namespace Cinderwell_Utilities;

class Rest_Api {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('admin_post_cinderwell_utilities_save', [$this, 'handle_save']);
    }

    public function register_routes() {
        $ns = 'cinder-utilities/v1';

        register_rest_route($ns, '/modules', [
            'methods' => 'GET',
            'callback' => [$this, 'get_modules'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
        register_rest_route($ns, '/modules/(?P<module>[a-z_]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_module'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
        register_rest_route($ns, '/modules/(?P<module>[a-z_]+)', [
            'methods' => 'PATCH',
            'callback' => [$this, 'update_module'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
        register_rest_route($ns, '/content-order/(?P<post_type>[a-z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_content_order'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
        register_rest_route($ns, '/content-order/(?P<post_type>[a-z0-9_-]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'update_content_order'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
        register_rest_route($ns, '/terms-order/(?P<taxonomy>[a-z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_terms_order'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
        register_rest_route($ns, '/terms-order/(?P<taxonomy>[a-z0-9_-]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'update_terms_order'],
            'permission_callback' => [$this, 'admin_only'],
        ]);
    }

    public function admin_only() {
        return current_user_can('manage_options');
    }

    public function get_modules() {
        $settings = Utilities::get_settings();
        $labels = Utilities::get_module_labels();
        $data = [];
        foreach ($labels as $key => $meta) {
            $data[$key] = array_merge($meta, [
                'key' => $key,
                'enabled' => !empty($settings[$key]['enabled']),
                'settings' => $settings[$key] ?? [],
            ]);
        }
        return rest_ensure_response($data);
    }

    public function get_module($request) {
        $key = $request['module'];
        $settings = Utilities::get_settings();
        $labels = Utilities::get_module_labels();
        if (!isset($labels[$key])) {
            return new \WP_Error('not_found', 'Module not found', ['status' => 404]);
        }
        return rest_ensure_response(array_merge($labels[$key], [
            'key' => $key,
            'enabled' => !empty($settings[$key]['enabled']),
            'settings' => $settings[$key] ?? [],
        ]));
    }

    public function update_module($request) {
        $key = $request['module'];
        $all = Utilities::get_settings();
        if (!isset($all[$key])) {
            return new \WP_Error('not_found', 'Module not found', ['status' => 404]);
        }
        $body = $request->get_json_params();
        if (isset($body['enabled'])) {
            $all[$key]['enabled'] = (bool) $body['enabled'];
        }
        if (isset($body['settings']) && is_array($body['settings'])) {
            $defaults = Utilities::get_defaults()[$key];
            foreach ($body['settings'] as $setting => $value) {
                if (!array_key_exists($setting, $defaults) || 'enabled' === $setting) {
                    continue;
                }

                if (is_bool($defaults[$setting])) {
                    $all[$key][$setting] = (bool) $value;
                } elseif (is_array($defaults[$setting])) {
                    $all[$key][$setting] = array_values(array_filter(array_map('sanitize_key', (array) $value)));
                } else {
                    $all[$key][$setting] = sanitize_text_field($value);
                }
            }
        }
        Utilities::update_settings($all);
        return rest_ensure_response(['success' => true, 'settings' => $all[$key]]);
    }

    public function get_content_order($request) {
        $post_type = sanitize_key($request['post_type']);
        if (!$this->content_order_type_allowed($post_type)) {
            return new \WP_Error('invalid_post_type', 'This post type is not enabled for content ordering.', ['status' => 400]);
        }
        $posts = get_posts([
            'post_type' => $post_type,
            'post_status' => 'any',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ]);
        $data = array_map(function ($p) {
            return ['id' => $p->ID, 'title' => $p->post_title, 'menu_order' => $p->menu_order, 'parent' => $p->post_parent, 'status' => $p->post_status];
        }, $posts);
        return rest_ensure_response($data);
    }

    public function update_content_order($request) {
        $post_type = sanitize_key($request['post_type']);
        if (!$this->content_order_type_allowed($post_type)) {
            return new \WP_Error('invalid_post_type', 'This post type is not enabled for content ordering.', ['status' => 400]);
        }
        $body = $request->get_json_params();
        if (empty($body['order']) || !is_array($body['order'])) {
            return new \WP_Error('invalid', 'Order array required', ['status' => 400]);
        }
        foreach ($body['order'] as $i => $post_id) {
            $post_id = absint($post_id);
            if ($post_id && $post_type === get_post_type($post_id)) {
                wp_update_post(['ID' => $post_id, 'menu_order' => intval($i)]);
            }
        }
        return rest_ensure_response(['success' => true]);
    }

    public function get_terms_order($request) {
        $taxonomy = sanitize_key($request['taxonomy']);
        if (!$this->terms_order_taxonomy_allowed($taxonomy)) {
            return new \WP_Error('invalid_taxonomy', 'This taxonomy is not enabled for term ordering.', ['status' => 400]);
        }
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
        if (is_wp_error($terms)) {
            return $terms;
        }
        usort($terms, static function ($a, $b) {
            $a_order = (int) get_term_meta($a->term_id, 'term_order', true);
            $b_order = (int) get_term_meta($b->term_id, 'term_order', true);
            return $a_order <=> $b_order ?: strcasecmp($a->name, $b->name);
        });
        $data = array_map(function ($t) {
            return ['id' => $t->term_id, 'name' => $t->name, 'parent' => $t->parent, 'term_order' => (int) get_term_meta($t->term_id, 'term_order', true)];
        }, $terms);
        return rest_ensure_response($data);
    }

    public function update_terms_order($request) {
        $taxonomy = sanitize_key($request['taxonomy']);
        if (!$this->terms_order_taxonomy_allowed($taxonomy)) {
            return new \WP_Error('invalid_taxonomy', 'This taxonomy is not enabled for term ordering.', ['status' => 400]);
        }
        $body = $request->get_json_params();
        if (empty($body['order']) || !is_array($body['order'])) {
            return new \WP_Error('invalid', 'Order array required', ['status' => 400]);
        }
        foreach ($body['order'] as $i => $term_id) {
            $term_id = absint($term_id);
            if ($term_id && term_exists($term_id, $taxonomy)) {
                update_term_meta($term_id, 'term_order', intval($i));
            }
        }
        return rest_ensure_response(['success' => true]);
    }

    private function content_order_type_allowed($post_type) {
        $settings = Utilities::module_settings('content_order');
        return !empty($settings['enabled']) && in_array($post_type, (array) $settings['post_types'], true);
    }

    private function terms_order_taxonomy_allowed($taxonomy) {
        $settings = Utilities::module_settings('terms_order');
        return !empty($settings['enabled']) && in_array($taxonomy, (array) $settings['taxonomies'], true);
    }

    public function handle_save() {
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied.');
        }
        check_admin_referer('cinderwell_utilities_save');

        $defaults = Utilities::get_defaults();
        $incoming = isset($_POST['cinderwell_utilities']) ? wp_unslash($_POST['cinderwell_utilities']) : [];
        $settings = Utilities::get_settings();

        foreach ($defaults as $module => $module_defaults) {
            $settings[$module]['enabled'] = !empty($incoming[$module]['enabled']);

            // Merge module-specific fields
            foreach ($module_defaults as $key => $default) {
                if ($key === 'enabled') continue;

                if (is_array($default)) {
                    // Checkbox list
                    $settings[$module][$key] = isset($incoming[$module][$key]) ? array_map('sanitize_text_field', (array) $incoming[$module][$key]) : [];
                } elseif (is_bool($default) || $default === true || $default === false) {
                    $settings[$module][$key] = !empty($incoming[$module][$key]);
                } else {
                    $settings[$module][$key] = sanitize_text_field($incoming[$module][$key] ?? $default);
                }
            }
        }

        Utilities::update_settings($settings);
        wp_safe_redirect(add_query_arg(['page' => 'cinderwell', 'tab' => 'utilities', 'saved' => '1'], admin_url('admin.php')));
        exit;
    }
}
