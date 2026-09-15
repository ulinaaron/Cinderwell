<?php
namespace Cinderwell_Utilities;

class Admin_Page {
    public function __construct() {
        add_filter('cinderwell_settings_tabs', [$this, 'register_tab']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_tab($tabs) {
        $tabs['utilities'] = [
            'label'    => 'Site Utilities',
            'group'    => 'extensions',
            'callback' => [$this, 'render'],
        ];
        return $tabs;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'cinderwell') === false) {
            return;
        }
        wp_enqueue_style('cinderwell-utilities-admin', CINDERWELL_UTILITIES_URL . 'assets/css/admin.css', [], CINDERWELL_UTILITIES_VERSION);
        wp_enqueue_script('cinderwell-utilities-admin', CINDERWELL_UTILITIES_URL . 'assets/js/modules.js', [], CINDERWELL_UTILITIES_VERSION, true);
        wp_localize_script('cinderwell-utilities-admin', 'cinderwell_utilities', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('cinder-utilities/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    public function render() {
        $settings = Utilities::get_settings();
        $labels = Utilities::get_module_labels();
        $groups = [
            'content' => 'Content',
            'media'   => 'Media',
            'disable' => 'Disable',
        ];

        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success inline"><p>Settings saved.</p></div>';
        }
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="cinderwell_utilities_save">
            <?php wp_nonce_field('cinderwell_utilities_save'); ?>

            <?php foreach ($groups as $group_key => $group_label): ?>
                <div class="cinderwell-utilities-group">
                    <h2 class="cinderwell-utilities-group__title"><?php echo esc_html($group_label); ?></h2>
                    <?php foreach ($labels as $module_key => $meta): ?>
                        <?php if ($meta['group'] !== $group_key) continue; ?>
                        <?php $mod = $settings[$module_key] ?? []; ?>
                        <div class="cinderwell-utilities-module card" data-module="<?php echo esc_attr($module_key); ?>">
                            <div class="cinderwell-utilities-module__header">
                                <label class="cinderwell-utilities-module__toggle">
                                    <input type="checkbox"
                                           name="cinderwell_utilities[<?php echo esc_attr($module_key); ?>][enabled]"
                                           value="1"
                                           <?php checked(!empty($mod['enabled'])); ?>
                                           class="cinderwell-utilities-module-toggle" />
                                    <strong><?php echo esc_html($meta['label']); ?></strong>
                                </label>
                                <span class="cinderwell-utilities-module__desc"><?php echo esc_html($meta['description']); ?></span>
                            </div>
                            <div class="cinderwell-utilities-module__body" style="<?php echo empty($mod['enabled']) ? 'display:none;' : ''; ?>">
                                <?php $this->render_module_settings($module_key, $mod); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <?php submit_button('Save Settings'); ?>
        </form>
        <?php
    }

    private function render_module_settings($key, $mod) {
        switch ($key) {
            case 'content_duplication':
                $this->render_duplication($mod);
                break;
            case 'content_order':
                $this->render_content_order($mod);
                break;
            case 'terms_order':
                $this->render_terms_order($mod);
                break;
            case 'media_replacement':
                $this->render_media_replacement($mod);
                break;
            case 'allow_svgs':
                $this->render_allow_svgs($mod);
                break;
            case 'disable_comments':
                $this->render_disable_comments($mod);
                break;
            case 'disable_feeds':
                $this->render_disable_feeds($mod);
                break;
            case 'disable_smaller':
                $this->render_disable_smaller($mod);
                break;
        }
    }

    private function render_checkbox_list($name, $options, $selected) {
        foreach ($options as $value => $label) {
            echo '<label style="display:block;margin:2px 0;">';
            echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($value) . '" ' . checked(in_array($value, (array) $selected, true), true, false) . ' /> ';
            echo esc_html($label) . '</label>';
        }
    }

    private function get_public_post_types() {
        $types = [];
        foreach (get_post_types(['public' => true], 'objects') as $pt) {
            $types[$pt->name] = $pt->label;
        }
        return $types;
    }

    private function get_hierarchical_post_types() {
        $types = [];
        foreach (get_post_types(['public' => true], 'objects') as $pt) {
            if ($pt->hierarchical || post_type_supports($pt->name, 'page-attributes')) {
                $types[$pt->name] = $pt->label;
            }
        }
        return $types;
    }

    private function get_hierarchical_taxonomies() {
        $taxonomies = [];
        foreach (get_taxonomies(['public' => true, 'hierarchical' => true], 'objects') as $tax) {
            $taxonomies[$tax->name] = $tax->label;
        }
        return $taxonomies;
    }

    private function get_roles() {
        $roles = [];
        foreach (wp_roles()->get_names() as $slug => $name) {
            $roles[$slug] = $name;
        }
        return $roles;
    }

    private function render_duplication($mod) {
        echo '<table class="form-table"><tr><th>Post Types</th><td>';
        $this->render_checkbox_list(
            'cinderwell_utilities[content_duplication][post_types]',
            $this->get_public_post_types(),
            $mod['post_types'] ?? []
        );
        echo '</td></tr><tr><th>Allowed Roles</th><td>';
        $this->render_checkbox_list(
            'cinderwell_utilities[content_duplication][roles]',
            $this->get_roles(),
            $mod['roles'] ?? []
        );
        echo '</td></tr><tr><th>Show Link In</th><td>';
        $this->render_checkbox_list(
            'cinderwell_utilities[content_duplication][show_in]',
            ['list' => 'List view', 'edit' => 'Edit screen', 'admin_bar' => 'Admin bar'],
            $mod['show_in'] ?? []
        );
        echo '</td></tr><tr><th>New Post Status</th><td>';
        echo '<select name="cinderwell_utilities[content_duplication][new_status]">';
        foreach (['draft' => 'Draft', 'same' => 'Same as source', 'publish' => 'Publish'] as $v => $l) {
            echo '<option value="' . $v . '"' . selected($mod['new_status'] ?? '', $v, false) . '>' . $l . '</option>';
        }
        echo '</select></td></tr><tr><th>Title Suffix</th><td>';
        echo '<input type="text" name="cinderwell_utilities[content_duplication][title_suffix]" value="' . esc_attr($mod['title_suffix'] ?? 'Copy of ') . '" class="regular-text" />';
        echo '<p class="description">Appended to the duplicated post title. Leave empty for no suffix.</p>';
        echo '</td></tr></table>';
    }

    private function render_content_order($mod) {
        echo '<table class="form-table"><tr><th>Post Types</th><td>';
        $this->render_checkbox_list(
            'cinderwell_utilities[content_order][post_types]',
            $this->get_hierarchical_post_types(),
            $mod['post_types'] ?? []
        );
        echo '</td></tr><tr><th>Apply on Frontend</th><td>';
        echo '<label><input type="checkbox" name="cinderwell_utilities[content_order][apply_frontend]" value="1" ' . checked(!empty($mod['apply_frontend']), true, false) . ' /> Sort frontend queries by menu order</label>';
        echo '</td></tr></table>';
        $links = [];
        foreach ((array) ($mod['post_types'] ?? []) as $post_type) {
            $object = get_post_type_object($post_type);
            if (!$object) {
                continue;
            }
            $links[] = '<a class="button" href="' . esc_url(add_query_arg('page', 'cinderwell-order-' . $post_type, admin_url('admin.php'))) . '">' .
                sprintf(esc_html__('Order %s', 'cinderwell-utilities'), esc_html($object->label)) . '</a>';
        }
        if ($links) {
            echo '<div class="cinderwell-utilities-module__actions">' . implode(' ', $links) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Links are escaped above.
        } else {
            echo '<p class="description">' . esc_html__('Choose a post type and save settings to open its ordering screen.', 'cinderwell-utilities') . '</p>';
        }
    }

    private function render_terms_order($mod) {
        echo '<table class="form-table"><tr><th>Taxonomies</th><td>';
        $this->render_checkbox_list(
            'cinderwell_utilities[terms_order][taxonomies]',
            $this->get_hierarchical_taxonomies(),
            $mod['taxonomies'] ?? []
        );
        echo '</td></tr><tr><th>Apply on Frontend</th><td>';
        echo '<label><input type="checkbox" name="cinderwell_utilities[terms_order][apply_frontend]" value="1" ' . checked(!empty($mod['apply_frontend']), true, false) . ' /> Sort frontend term queries by custom order</label>';
        echo '</td></tr></table>';
        $links = [];
        foreach ((array) ($mod['taxonomies'] ?? []) as $taxonomy) {
            $object = get_taxonomy($taxonomy);
            if (!$object) {
                continue;
            }
            $links[] = '<a class="button" href="' . esc_url(add_query_arg('page', 'cinderwell-terms-' . $taxonomy, admin_url('admin.php'))) . '">' .
                sprintf(esc_html__('Order %s', 'cinderwell-utilities'), esc_html($object->label)) . '</a>';
        }
        if ($links) {
            echo '<div class="cinderwell-utilities-module__actions">' . implode(' ', $links) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Links are escaped above.
        } else {
            echo '<p class="description">' . esc_html__('Choose a taxonomy and save settings to open its ordering screen.', 'cinderwell-utilities') . '</p>';
        }
    }

    private function render_media_replacement($mod) {
        echo '<table class="form-table"><tr><th>Allowed Roles</th><td>';
        $this->render_checkbox_list(
            'cinderwell_utilities[media_replacement][roles]',
            $this->get_roles(),
            $mod['roles'] ?? []
        );
        echo '</td></tr><tr><th>Show Replace In</th><td>';
        echo '<label><input type="checkbox" name="cinderwell_utilities[media_replacement][replace_from_grid]" value="1" ' . checked(!empty($mod['replace_from_grid']), true, false) . ' /> Grid view (media library)</label><br/>';
        echo '<label><input type="checkbox" name="cinderwell_utilities[media_replacement][replace_from_edit]" value="1" ' . checked(!empty($mod['replace_from_edit']), true, false) . ' /> Edit screen (attachment details)</label>';
        echo '</td></tr></table>';
    }

    private function render_allow_svgs($mod) {
        echo '<table class="form-table"><tr><th>Allowed Roles</th><td>';
        $this->render_checkbox_list(
            'cinderwell_utilities[allow_svgs][roles]',
            $this->get_roles(),
            $mod['roles'] ?? []
        );
        echo '<p class="description">SVGs are sanitized on upload. Existing dirty SVGs are not retroactively cleaned.</p>';
        echo '</td></tr></table>';
    }

    private function render_disable_comments($mod) {
        echo '<table class="form-table">';
        echo '<tr><th>Hide Existing Comments</th><td><label><input type="checkbox" name="cinderwell_utilities[disable_comments][hide_existing]" value="1" ' . checked(!empty($mod['hide_existing']), true, false) . ' /> Hide comments from the frontend</label></td></tr>';
        echo '<tr><th>Close Existing Posts</th><td>';
        echo '<label><input type="checkbox" name="cinderwell_utilities[disable_comments][closed_existing]" value="1" ' . checked(!empty($mod['closed_existing']), true, false) . ' /> Close comments and pings on all existing posts</label>';
        echo '<p class="description">This runs once on save. Existing comments are preserved but hidden.</p>';
        echo '</td></tr></table>';
    }

    private function render_disable_feeds($mod) {
        echo '<table class="form-table">';
        echo '<tr><th>Redirect Feeds</th><td><label><input type="checkbox" name="cinderwell_utilities[disable_feeds][redirect_to_home]" value="1" ' . checked(!empty($mod['redirect_to_home']), true, false) . ' /> Redirect feed URLs to homepage (instead of 404)</label></td></tr>';
        echo '</table>';
    }

    private function render_disable_smaller($mod) {
        $toggles = [
            'remove_generator'     => 'Remove generator meta tag',
            'remove_wp_version'    => 'Remove WP version from scripts/styles',
            'remove_wlw'           => 'Remove Windows Live Writer (WLW) manifest',
            'remove_rsd'           => 'Remove Really Simple Discovery (RSD) link',
            'remove_shortlink'     => 'Remove shortlink from head',
            'remove_adjacent'      => 'Remove adjacent posts links',
            'disable_emoji'        => 'Disable emoji scripts and styles',
            'disable_wp_embed'     => 'Disable WP Embed script',
            'disable_block_css'    => 'Disable block library CSS (only if not using blocks)',
            'disable_jquery_migrate' => 'Disable jQuery Migrate',
            'disable_wc_assets'    => 'Disable WooCommerce assets on non-WC pages',
        ];
        echo '<table class="form-table">';
        foreach ($toggles as $key => $label) {
            $checked = isset($mod[$key]) ? !empty($mod[$key]) : !empty(Utilities::get_defaults()['disable_smaller'][$key]);
            echo '<tr><th>' . esc_html($label) . '</th><td>';
            echo '<label><input type="checkbox" name="cinderwell_utilities[disable_smaller][' . $key . ']" value="1" ' . checked($checked, true, false) . ' /></label>';
            echo '</td></tr>';
        }
        echo '</table>';
    }
}
