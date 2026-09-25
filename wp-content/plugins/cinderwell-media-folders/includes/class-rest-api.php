<?php
namespace Cinderwell_Media_Folders;

class Rest_Api {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        $ns = 'cinder-media-folders/v1';

        register_rest_route($ns, '/folders', [
            'methods'  => 'GET',
            'callback' => [$this, 'list_folders'],
            'permission_callback' => [$this, 'read_access'],
        ]);
        register_rest_route($ns, '/folders/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_folder'],
            'permission_callback' => [$this, 'read_access'],
        ]);
        register_rest_route($ns, '/folders/(?P<id>\d+)/media', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_folder_media'],
            'permission_callback' => [$this, 'read_access'],
        ]);
        register_rest_route($ns, '/folders', [
            'methods'  => 'POST',
            'callback' => [$this, 'create_folder'],
            'permission_callback' => [$this, 'manage_access'],
            'args' => [
                'name'   => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'parent' => ['type' => 'integer', 'default' => 0],
                'color'  => ['type' => 'string', 'sanitize_callback' => 'sanitize_hex_color'],
            ],
        ]);
        register_rest_route($ns, '/folders/(?P<id>\d+)', [
            'methods'  => 'PATCH',
            'callback' => [$this, 'update_folder'],
            'permission_callback' => [$this, 'manage_access'],
        ]);
        register_rest_route($ns, '/folders/(?P<id>\d+)', [
            'methods'  => 'DELETE',
            'callback' => [$this, 'delete_folder'],
            'permission_callback' => [$this, 'manage_access'],
        ]);

        register_rest_route($ns, '/folders/(?P<id>-?\d+)/media', [
            'methods'  => 'POST',
            'callback' => [$this, 'move_to_folder'],
            'permission_callback' => [$this, 'move_access'],
            'args' => [
                'attachment_ids' => ['required' => true, 'type' => 'array', 'items' => ['type' => 'integer']],
                'replace'        => ['type' => 'boolean', 'default' => true],
            ],
        ]);
    }

    public function read_access() {
        return current_user_can('upload_files');
    }

    public function manage_access() {
        return current_user_can('upload_files') && current_user_can('manage_categories');
    }

    public function move_access($request) {
        if (!current_user_can('upload_files')) return false;
        $attachment_ids = $request->get_param('attachment_ids');
        if (!is_array($attachment_ids) || empty($attachment_ids)) return false;
        foreach ($attachment_ids as $attachment_id) {
            if (get_post_type($attachment_id) !== 'attachment' || !current_user_can('edit_post', $attachment_id)) {
                return false;
            }
        }
        return true;
    }

    public function list_folders() {
        $terms = get_terms(['taxonomy' => Folder_Taxonomy::TAXONOMY, 'hide_empty' => false, 'orderby' => 'name']);
        if (is_wp_error($terms)) return rest_ensure_response([]);

        return rest_ensure_response(array_map(function ($t) {
            return [
                'id'     => $t->term_id,
                'name'   => $t->name,
                'slug'   => $t->slug,
                'parent' => $t->parent,
                'count'  => $t->count,
                'color'  => get_term_meta($t->term_id, '_cwmf_folder_color', true),
            ];
        }, $terms));
    }

    public function get_folder($request) {
        $term = get_term(intval($request['id']), Folder_Taxonomy::TAXONOMY);
        if (is_wp_error($term) || !$term) {
            return new \WP_Error('not_found', 'Folder not found', ['status' => 404]);
        }
        return rest_ensure_response([
            'id'     => $term->term_id,
            'name'   => $term->name,
            'slug'   => $term->slug,
            'parent' => $term->parent,
            'count'  => $term->count,
            'color'  => get_term_meta($term->term_id, '_cwmf_folder_color', true),
        ]);
    }

    public function get_folder_media($request) {
        $query = new \WP_Query([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 50,
            'tax_query'      => [[
                'taxonomy' => Folder_Taxonomy::TAXONOMY,
                'field'    => 'term_id',
                'terms'    => intval($request['id']),
            ]],
        ]);

        return rest_ensure_response(array_map(function ($p) {
            return [
                'id'        => $p->ID,
                'title'     => $p->post_title,
                'url'       => wp_get_attachment_url($p->ID),
                'mime_type' => $p->post_mime_type,
                'date'      => $p->post_date,
            ];
        }, $query->posts));
    }

    public function create_folder($request) {
        $name   = sanitize_text_field($request->get_param('name'));
        $parent = intval($request->get_param('parent') ?: 0);
        $color  = sanitize_hex_color($request->get_param('color'));

        if (empty($name)) {
            return new \WP_Error('missing_name', 'Folder name required', ['status' => 400]);
        }

        if ($parent && !term_exists($parent, Folder_Taxonomy::TAXONOMY)) {
            return new \WP_Error('invalid_parent', 'Parent folder not found', ['status' => 400]);
        }

        $args = ['parent' => $parent];
        $result = wp_insert_term($name, Folder_Taxonomy::TAXONOMY, $args);
        if (is_wp_error($result)) return $result;

        if ($color) {
            update_term_meta($result['term_id'], '_cwmf_folder_color', $color);
        }

        return rest_ensure_response([
            'id'     => $result['term_id'],
            'name'   => $name,
            'parent' => $parent,
        ]);
    }

    public function update_folder($request) {
        $id   = intval($request['id']);
        $term = get_term($id, Folder_Taxonomy::TAXONOMY);
        if (is_wp_error($term) || !$term) {
            return new \WP_Error('not_found', 'Folder not found', ['status' => 404]);
        }
        $args = [];
        if ($request->get_param('name'))   $args['name'] = sanitize_text_field($request->get_param('name'));
        if ($request->get_param('parent') !== null) {
            $parent = intval($request->get_param('parent'));
            if ($parent === $id || ($parent && !term_exists($parent, Folder_Taxonomy::TAXONOMY))) {
                return new \WP_Error('invalid_parent', 'Parent folder not found', ['status' => 400]);
            }
            if ($parent && term_is_ancestor_of($id, $parent, Folder_Taxonomy::TAXONOMY)) {
                return new \WP_Error('invalid_parent', 'A folder cannot be moved inside one of its descendants', ['status' => 400]);
            }
            $args['parent'] = $parent;
        }

        if (!empty($args)) {
            $result = wp_update_term($id, Folder_Taxonomy::TAXONOMY, $args);
            if (is_wp_error($result)) return $result;
        }

        if ($request->has_param('color')) {
            $color = sanitize_hex_color($request->get_param('color'));
            if ($color) update_term_meta($id, '_cwmf_folder_color', $color);
            else delete_term_meta($id, '_cwmf_folder_color');
        }

        return rest_ensure_response(['success' => true]);
    }

    public function delete_folder($request) {
        $id = intval($request['id']);
        $term = get_term($id, Folder_Taxonomy::TAXONOMY);
        if (is_wp_error($term) || !$term) {
            return new \WP_Error('not_found', 'Folder not found', ['status' => 404]);
        }
        $attachments = get_objects_in_term($id, Folder_Taxonomy::TAXONOMY);
        foreach ($attachments as $aid) {
            wp_remove_object_terms($aid, $id, Folder_Taxonomy::TAXONOMY);
        }

        $result = wp_delete_term($id, Folder_Taxonomy::TAXONOMY);
        if (is_wp_error($result)) return $result;

        return rest_ensure_response(['success' => true]);
    }

    public function move_to_folder($request) {
        $folder_id = intval($request['id']);
        $attachment_ids = $request->get_param('attachment_ids');

        if (empty($attachment_ids) || !is_array($attachment_ids)) {
            return new \WP_Error('missing_ids', 'attachment_ids array required', ['status' => 400]);
        }

        if ($folder_id > 0) {
            $folder = get_term($folder_id, Folder_Taxonomy::TAXONOMY);
            if (is_wp_error($folder) || !$folder) {
                return new \WP_Error('not_found', 'Folder not found', ['status' => 404]);
            }
        }

        $replace = $request->get_param('replace') !== false;
        $settings = wp_parse_args(get_option(CINDERWELL_MEDIA_FOLDERS_OPTION, []), Admin_Page::get_defaults());
        if (empty($settings['multi_folder'])) {
            $replace = true;
        }
        $moved = 0;

        foreach ($attachment_ids as $aid) {
            $aid = intval($aid);
            if (!$aid) continue;

            if ($folder_id < 1) {
                $result = wp_set_object_terms($aid, [], Folder_Taxonomy::TAXONOMY);
            } elseif ($replace) {
                $result = wp_set_object_terms($aid, [$folder_id], Folder_Taxonomy::TAXONOMY);
            } else {
                $existing = wp_get_object_terms($aid, Folder_Taxonomy::TAXONOMY, ['fields' => 'ids']);
                if (!is_array($existing)) $existing = [];
                if (!in_array($folder_id, $existing)) {
                    $existing[] = $folder_id;
                }
                $result = wp_set_object_terms($aid, $existing, Folder_Taxonomy::TAXONOMY);
            }

            if (!is_wp_error($result)) $moved++;
        }

        // Return updated folder counts
        $terms = get_terms(['taxonomy' => Folder_Taxonomy::TAXONOMY, 'hide_empty' => false]);
        $counts = [];
        if (!is_wp_error($terms)) {
            foreach ($terms as $t) {
                $counts[$t->term_id] = $t->count;
            }
        }

        return rest_ensure_response([
            'success' => true,
            'moved'   => $moved,
            'counts'  => $counts,
        ]);
    }
}
