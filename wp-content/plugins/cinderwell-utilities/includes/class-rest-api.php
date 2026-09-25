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
        $data = [];
        foreach (Module_Registry::get_modules() as $key => $module) {
            $data[$key] = array_merge($this->public_module_meta($module), [
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
        $module = Module_Registry::get_module($key);
        if (!$module) {
            return new \WP_Error('not_found', 'Module not found', ['status' => 404]);
        }
        return rest_ensure_response(array_merge($this->public_module_meta($module), [
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
        $next = $all[$key];
        if (isset($body['enabled'])) {
            $next['enabled'] = $body['enabled'];
        }
        if (isset($body['settings']) && is_array($body['settings'])) {
            foreach ($body['settings'] as $setting => $value) {
                if ('enabled' !== $setting) {
                    $next[$setting] = $value;
                }
            }
        }
        $all[$key] = Module_Registry::sanitize_module($key, $next);
        Utilities::update_settings($all);
        if ('mail_delivery' === $key) {
            Mail_Log::sync_schedule(!empty($all[$key]['enabled']));
        }
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

        $incoming = isset($_POST['cinderwell_utilities']) ? wp_unslash($_POST['cinderwell_utilities']) : [];
        $settings = Utilities::get_settings();

        if (!Mail_Manager::api_key_is_constant()) {
            if (!empty($_POST['cinderwell_mail_clear_api_key'])) {
                Mail_Manager::clear_api_key();
            } elseif (isset($_POST['cinderwell_mail_api_key']) && '' !== trim((string) wp_unslash($_POST['cinderwell_mail_api_key']))) {
                Mail_Manager::update_api_key(sanitize_text_field(wp_unslash($_POST['cinderwell_mail_api_key'])));
            }
        }

        foreach (Module_Registry::get_modules() as $module_id => $module) {
            $posted = isset($incoming[$module_id]) && is_array($incoming[$module_id]) ? $incoming[$module_id] : [];
            $next = $settings[$module_id] ?? [];

            foreach ($module['settings'] as $key => $field) {
                $type = $field['type'] ?? 'text';
                if ('boolean' === $type) {
                    $next[$key] = !empty($posted[$key]);
                } elseif (in_array($type, ['key_list', 'plugin_list', 'post_type_list', 'hierarchical_post_type_list', 'hierarchical_taxonomy_list', 'role_list'], true)) {
                    $next[$key] = isset($posted[$key]) ? (array) $posted[$key] : [];
                } elseif (array_key_exists($key, $posted)) {
                    $next[$key] = $posted[$key];
                }
            }
            $settings[$module_id] = Module_Registry::sanitize_module($module_id, $next);
        }

        Utilities::update_settings($settings);
        Mail_Log::sync_schedule(!empty($settings['mail_delivery']['enabled']));

        $redirect_target = isset($_POST['cinderwell_utilities_redirect'])
            ? sanitize_key(wp_unslash($_POST['cinderwell_utilities_redirect']))
            : '';
        if ('plugins' === $redirect_target && !empty($settings['plugin_update_control']['enabled'])) {
            wp_safe_redirect(add_query_arg('cinderwell_plugin_control_saved', '1', admin_url('plugins.php')));
            exit;
        }

        wp_safe_redirect(add_query_arg(['page' => 'cinderwell', 'tab' => 'utilities', 'saved' => '1'], admin_url('admin.php')));
        exit;
    }

    private function public_module_meta($module) {
        return array_intersect_key($module, array_flip(['id', 'label', 'description', 'group', 'icon', 'settings_url', 'warning']));
    }
}
