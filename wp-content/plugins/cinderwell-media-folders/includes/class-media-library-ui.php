<?php
namespace Cinderwell_Media_Folders;

class Media_Library_UI {
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('ajax_query_attachments_args', [$this, 'filter_by_folder_ajax']);
        add_action('pre_get_posts', [$this, 'filter_media_list']);
        add_action('restrict_manage_posts', [$this, 'render_folder_filter']);
        add_filter('bulk_actions-upload', [$this, 'register_move_bulk_action']);
        add_filter('upload_post_params', [$this, 'add_folder_to_upload_params']);
        add_action('add_attachment', [$this, 'assign_default_folder']);
        add_filter('media_view_settings', [$this, 'pass_folder_data_to_modal'], 10, 2);
    }

    public function enqueue_assets($hook) {
        if (!in_array($hook, ['upload.php', 'post.php', 'post-new.php'], true)) return;

        wp_enqueue_style(
            'cinderwell-media-folders',
            CINDERWELL_MEDIA_FOLDERS_URL . 'assets/css/media-library.css',
            [],
            CINDERWELL_MEDIA_FOLDERS_VERSION
        );

        wp_enqueue_script(
            'cinderwell-media-folders',
            CINDERWELL_MEDIA_FOLDERS_URL . 'assets/js/media-library.js',
            ['jquery', 'wp-util', 'jquery-ui-draggable', 'jquery-ui-droppable'],
            CINDERWELL_MEDIA_FOLDERS_VERSION,
            false
        );

        $settings = wp_parse_args(get_option(CINDERWELL_MEDIA_FOLDERS_OPTION, []), Admin_Page::get_defaults());

        wp_localize_script('cinderwell-media-folders', 'cinderwellMediaFolders', [
            'folders'        => $this->get_folder_tree(),
            'currentFolder'  => $this->get_requested_folder(),
            'restUrl'        => rest_url('cinder-media-folders/v1/'),
            'nonce'          => wp_create_nonce('wp_rest'),
            'uploadUrl'      => admin_url('upload.php'),
            'libraryMode'    => $this->get_library_mode(),
            'multiFolder'    => !empty($settings['multi_folder']),
            'canManage'      => current_user_can('manage_categories'),
            'strings'        => [
                'allMedia'      => __('All Media', 'cinderwell-media-folders'),
                'uncategorized' => __('Unfiled', 'cinderwell-media-folders'),
                'folders'       => __('Folders', 'cinderwell-media-folders'),
                'searchFolders' => __('Search folders', 'cinderwell-media-folders'),
                'newFolder'     => __('New Folder', 'cinderwell-media-folders'),
                'createFolder'  => __('Create', 'cinderwell-media-folders'),
                'folderName'    => __('Folder name', 'cinderwell-media-folders'),
                'cancel'        => __('Cancel', 'cinderwell-media-folders'),
                'save'          => __('Save', 'cinderwell-media-folders'),
                'delete'        => __('Delete', 'cinderwell-media-folders'),
                'rename'        => __('Rename', 'cinderwell-media-folders'),
                'newSubfolder'  => __('New subfolder', 'cinderwell-media-folders'),
                'moreActions'   => __('Folder actions', 'cinderwell-media-folders'),
                'confirmDelete' => __('Delete this folder? Its media will become unfiled.', 'cinderwell-media-folders'),
                'moveSuccess'   => __('Media moved.', 'cinderwell-media-folders'),
                'addSuccess'    => __('Media added to folder.', 'cinderwell-media-folders'),
                'requestFailed' => __('That change could not be saved. Please try again.', 'cinderwell-media-folders'),
                'mediaFolders'  => __('Media folders', 'cinderwell-media-folders'),
                'toggleFolders' => __('Toggle subfolders', 'cinderwell-media-folders'),
                'filterByFolder' => __('Filter by folder', 'cinderwell-media-folders'),
                'moveSelectedTo' => __('Move selected media to folder', 'cinderwell-media-folders'),
                'moveToFolder'   => __('Move to folder…', 'cinderwell-media-folders'),
                'chooseFolder'   => __('Choose folder…', 'cinderwell-media-folders'),
                'chooseDestination' => __('Destination folder', 'cinderwell-media-folders'),
                'moveSelected'   => __('Move', 'cinderwell-media-folders'),
                'moving'         => __('Moving…', 'cinderwell-media-folders'),
                'mediaItem'      => __('media item', 'cinderwell-media-folders'),
                'mediaItems'     => __('media items', 'cinderwell-media-folders'),
                'selectMediaFirst' => __('Select at least one media item first.', 'cinderwell-media-folders'),
                'openFolder'     => __('Open folder', 'cinderwell-media-folders'),
            ],
        ]);
    }

    private function get_folder_tree() {
        $terms = get_terms([
            'taxonomy'   => Folder_Taxonomy::TAXONOMY,
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms)) return [];

        return $this->build_tree($terms, 0);
    }

    private function build_tree($terms, $parent_id) {
        $tree = [];
        foreach ($terms as $term) {
            if ($term->parent == $parent_id) {
                $tree[] = [
                    'id'       => $term->term_id,
                    'name'     => $term->name,
                    'slug'     => $term->slug,
                    'parent'   => $term->parent,
                    'count'    => $term->count,
                    'color'    => get_term_meta($term->term_id, '_cwmf_folder_color', true),
                    'children' => $this->build_tree($terms, $term->term_id),
                ];
            }
        }
        return $tree;
    }

    public function filter_by_folder_ajax($query) {
        if (!wp_doing_ajax()) return $query;
        $folder_id = 0;
        if (isset($query['cwmf_folder'])) {
            $folder_id = intval($query['cwmf_folder']);
            unset($query['cwmf_folder']);
        } elseif (isset($_REQUEST['query']['cwmf_folder'])) {
            $folder_id = intval(wp_unslash($_REQUEST['query']['cwmf_folder']));
        }
        if (!$folder_id) return $query;
        return $this->apply_folder_tax_query($query, $folder_id);
    }

    public function filter_media_list($query) {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'attachment') {
            return;
        }

        $folder_id = $this->get_requested_folder();
        if (!$folder_id) return;

        $args = $this->apply_folder_tax_query([], $folder_id);
        if (!empty($args['tax_query'])) {
            $query->set('tax_query', $args['tax_query']);
        }
    }

    public function render_folder_filter($post_type) {
        if ($post_type !== 'attachment') return;

        wp_dropdown_categories([
            'taxonomy'          => Folder_Taxonomy::TAXONOMY,
            'name'              => 'cwmf_folder',
            'id'                => 'cwmf-folder-filter',
            'selected'          => $this->get_requested_folder(),
            'show_option_all'   => __('All folders', 'cinderwell-media-folders'),
            'show_option_none'  => __('Unfiled', 'cinderwell-media-folders'),
            'option_none_value' => '-1',
            'hierarchical'      => true,
            'hide_empty'        => false,
            'value_field'       => 'term_id',
        ]);
    }

    public function register_move_bulk_action($actions) {
        $actions['cinderwell_move_folder'] = __('Move to folder…', 'cinderwell-media-folders');
        return $actions;
    }

    private function apply_folder_tax_query($query, $folder_id) {
        if ($folder_id === -1) {
            $query['tax_query'] = [
                [
                    'taxonomy' => Folder_Taxonomy::TAXONOMY,
                    'operator' => 'NOT EXISTS',
                ],
            ];
        } elseif ($folder_id > 0) {
            $query['tax_query'] = [
                [
                    'taxonomy' => Folder_Taxonomy::TAXONOMY,
                    'field'    => 'term_id',
                    'terms'    => $folder_id,
                ],
            ];
        }
        return $query;
    }

    public function assign_default_folder($attachment_id) {
        $settings = get_option(CINDERWELL_MEDIA_FOLDERS_OPTION, []);
        $folder_id = $this->get_requested_folder();
        if ($folder_id < 1) {
            $folder_id = absint($settings['default_folder'] ?? 0);
        }
        if (!$folder_id || !term_exists($folder_id, Folder_Taxonomy::TAXONOMY)) return;

        wp_set_object_terms($attachment_id, $folder_id, Folder_Taxonomy::TAXONOMY);
    }

    public function add_folder_to_upload_params($params) {
        $folder_id = $this->get_requested_folder();
        if ($folder_id > 0) {
            $params['cwmf_folder'] = $folder_id;
        }
        return $params;
    }

    /**
     * Pass folder data to wp.media Backbone views via the media_view_settings filter.
     * Accessible in JS as wp.media.view.settings.cwmfFolders.
     */
    public function pass_folder_data_to_modal($settings, $post) {
        $folder_settings = wp_parse_args(get_option(CINDERWELL_MEDIA_FOLDERS_OPTION, []), Admin_Page::get_defaults());
        $settings['cwmfFolders'] = [
            'folders'   => $this->get_folder_tree(),
            'restUrl'   => rest_url('cinder-media-folders/v1/'),
            'nonce'     => wp_create_nonce('wp_rest'),
            'multiFolder' => !empty($folder_settings['multi_folder']),
            'canManage' => current_user_can('manage_categories'),
        ];
        return $settings;
    }

    private function get_requested_folder() {
        if (isset($_REQUEST['cwmf_folder'])) {
            return intval(wp_unslash($_REQUEST['cwmf_folder']));
        }
        if (isset($_REQUEST['query']['cwmf_folder'])) {
            return intval(wp_unslash($_REQUEST['query']['cwmf_folder']));
        }
        return 0;
    }

    private function get_library_mode() {
        if (isset($_GET['mode'])) {
            $requested_mode = sanitize_key(wp_unslash($_GET['mode']));
            if (in_array($requested_mode, ['grid', 'list'], true)) return $requested_mode;
        }

        $saved_mode = get_user_option('media_library_mode', get_current_user_id());
        return in_array($saved_mode, ['grid', 'list'], true) ? $saved_mode : 'grid';
    }
}
