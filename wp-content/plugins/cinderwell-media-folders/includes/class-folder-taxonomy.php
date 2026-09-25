<?php
namespace Cinderwell_Media_Folders;

class Folder_Taxonomy {
    const TAXONOMY = 'cwmf_folder';

    public function __construct() {
        add_action('init', [$this, 'register_taxonomy']);
        add_action('cwmf_folder_add_form_fields', [$this, 'add_term_fields']);
        add_action('cwmf_folder_edit_form_fields', [$this, 'edit_term_fields']);
        add_action('created_cwmf_folder', [$this, 'save_term_fields']);
        add_action('edited_cwmf_folder', [$this, 'save_term_fields']);
    }

    public function register_taxonomy() {
        register_taxonomy(self::TAXONOMY, 'attachment', [
            'labels' => [
                'name'              => __('Folders', 'cinderwell-media-folders'),
                'singular_name'     => __('Folder', 'cinderwell-media-folders'),
                'search_items'      => __('Search Folders', 'cinderwell-media-folders'),
                'all_items'         => __('All Folders', 'cinderwell-media-folders'),
                'parent_item'       => __('Parent Folder', 'cinderwell-media-folders'),
                'parent_item_colon' => __('Parent Folder:', 'cinderwell-media-folders'),
                'edit_item'         => __('Edit Folder', 'cinderwell-media-folders'),
                'update_item'       => __('Update Folder', 'cinderwell-media-folders'),
                'add_new_item'      => __('Add New Folder', 'cinderwell-media-folders'),
                'new_item_name'     => __('New Folder Name', 'cinderwell-media-folders'),
                'menu_name'         => __('Folders', 'cinderwell-media-folders'),
            ],
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'show_in_nav_menus' => false,
            'show_tagcloud'     => false,
            'query_var'         => false,
            'rewrite'           => false,
            // Attachments normally use the "inherit" post status. The default
            // taxonomy counter only counts published posts, so use the generic
            // callback to keep folder badges accurate after media is moved.
            'update_count_callback' => '_update_generic_term_count',
        ]);

        register_term_meta(self::TAXONOMY, '_cwmf_folder_color', [
            'type'         => 'string',
            'description'  => __('Folder color tag', 'cinderwell-media-folders'),
            'single'       => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_hex_color',
            'auth_callback' => function () {
                return current_user_can('manage_categories');
            },
        ]);
    }

    public function add_term_fields() {
        ?>
        <div class="form-field">
            <label for="cwmf_folder_color"><?php esc_html_e('Color', 'cinderwell-media-folders'); ?></label>
            <input type="color" name="cwmf_folder_color" id="cwmf_folder_color" value="#cc5500" />
            <p><?php esc_html_e('Optional color tag for visual identification.', 'cinderwell-media-folders'); ?></p>
        </div>
        <?php
    }

    public function edit_term_fields($term) {
        $color = get_term_meta($term->term_id, '_cwmf_folder_color', true) ?: '#cc5500';
        ?>
        <tr class="form-field">
            <th scope="row"><label for="cwmf_folder_color"><?php esc_html_e('Color', 'cinderwell-media-folders'); ?></label></th>
            <td>
                <input type="color" name="cwmf_folder_color" id="cwmf_folder_color" value="<?php echo esc_attr($color); ?>" />
            </td>
        </tr>
        <?php
    }

    public function save_term_fields($term_id) {
        if (!current_user_can('manage_categories') || !isset($_POST['cwmf_folder_color'])) {
            return;
        }

        $color = sanitize_hex_color(wp_unslash($_POST['cwmf_folder_color']));
        if ($color) {
            update_term_meta($term_id, '_cwmf_folder_color', $color);
        } else {
            delete_term_meta($term_id, '_cwmf_folder_color');
        }
    }

    public static function get_health() {
        $count = wp_count_terms(self::TAXONOMY, ['hide_empty' => false]);
        $count = is_wp_error($count) ? 0 : intval($count);
        return [
            'status'  => 'good',
            'message' => sprintf('%d folder%s', $count, $count === 1 ? '' : 's'),
        ];
    }
}
