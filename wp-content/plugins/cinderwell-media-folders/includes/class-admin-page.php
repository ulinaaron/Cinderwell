<?php
namespace Cinderwell_Media_Folders;

class Admin_Page {
    public function __construct() {
        add_filter('cinderwell_settings_tabs', [$this, 'register_tab']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings() {
        register_setting('cinderwell_media_folders_settings_group', CINDERWELL_MEDIA_FOLDERS_OPTION, [
            'type'              => 'object',
            'default'           => self::get_defaults(),
            'sanitize_callback' => function ($settings) {
                $settings = is_array($settings) ? $settings : [];
                $default_folder = absint($settings['default_folder'] ?? 0);
                if ($default_folder && !term_exists($default_folder, Folder_Taxonomy::TAXONOMY)) {
                    $default_folder = 0;
                }
                return [
                    'multi_folder'   => !empty($settings['multi_folder']),
                    'default_folder' => $default_folder,
                ];
            },
        ]);
    }

    public function register_tab($tabs) {
        $tabs['media-folders'] = [
            'label'    => __('Media Folders', 'cinderwell-media-folders'),
            'group'    => 'extensions',
            'callback' => [$this, 'render'],
        ];
        return $tabs;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'cinderwell') === false) return;
        wp_enqueue_style('cinderwell-mf-admin', CINDERWELL_MEDIA_FOLDERS_URL . 'assets/css/admin.css', [], CINDERWELL_MEDIA_FOLDERS_VERSION);
        wp_enqueue_script('cinderwell-mf-import', CINDERWELL_MEDIA_FOLDERS_URL . 'assets/js/import-wizard.js', ['jquery'], CINDERWELL_MEDIA_FOLDERS_VERSION, true);
        wp_localize_script('cinderwell-mf-import', 'cinderwellMFImport', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('cinderwell_media_folders'),
        ]);
    }

    public static function get_defaults() {
        return [
            'multi_folder'   => false,
            'default_folder' => 0,
        ];
    }

    public function render() {
        $active = sanitize_key($_GET['subtab'] ?? 'folders');
        if (!in_array($active, ['folders', 'import', 'settings'], true)) {
            $active = 'folders';
        }

        $tabs = [
            'folders'  => __('Folders', 'cinderwell-media-folders'),
            'import'   => __('Import', 'cinderwell-media-folders'),
            'settings' => __('Settings', 'cinderwell-media-folders'),
        ];
        ?>
        <div class="cinderwell-mf-admin">
            <nav class="nav-tab-wrapper cinderwell-mf-subtabs" aria-label="<?php esc_attr_e('Media Folders settings', 'cinderwell-media-folders'); ?>">
                <?php foreach ($tabs as $slug => $label) : ?>
                    <a href="<?php echo esc_url(add_query_arg(['page' => 'cinderwell', 'tab' => 'media-folders', 'subtab' => $slug], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active === $slug ? 'nav-tab-active' : ''; ?>" <?php echo $active === $slug ? 'aria-current="page"' : ''; ?>><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="cinderwell-mf-content">
                <?php
                switch ($active) {
                    case 'import':  $this->render_import(); break;
                    case 'settings': $this->render_settings(); break;
                    default:         $this->render_folders();
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_folders() {
        $folder_url = admin_url('edit-tags.php?taxonomy=cwmf_folder&post_type=attachment');
        $library_url = admin_url('upload.php');
        $terms = get_terms(['taxonomy' => Folder_Taxonomy::TAXONOMY, 'hide_empty' => false, 'orderby' => 'name']);
        $folder_count = is_wp_error($terms) ? 0 : count($terms);
        $assigned_query = new \WP_Query([
            'post_type'              => 'attachment',
            'post_status'            => 'inherit',
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'no_found_rows'          => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'tax_query'              => [['taxonomy' => Folder_Taxonomy::TAXONOMY, 'operator' => 'EXISTS']],
        ]);
        $unfiled_query = new \WP_Query([
            'post_type'              => 'attachment',
            'post_status'            => 'inherit',
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'no_found_rows'          => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'tax_query'              => [['taxonomy' => Folder_Taxonomy::TAXONOMY, 'operator' => 'NOT EXISTS']],
        ]);
        $assigned = (int) $assigned_query->found_posts;
        $unfiled = (int) $unfiled_query->found_posts;
        ?>
        <section class="card cw-settings-card cinderwell-mf-intro">
            <div>
                <span class="cinderwell-mf-eyebrow"><?php esc_html_e('Media organization', 'cinderwell-media-folders'); ?></span>
                <h2><?php esc_html_e('Organize media without changing its URLs.', 'cinderwell-media-folders'); ?></h2>
                <p><?php esc_html_e('Create nested folders, search the tree, and drag one or many files between folders directly in the Media Library.', 'cinderwell-media-folders'); ?></p>
            </div>
            <a href="<?php echo esc_url($library_url); ?>" class="button button-primary"><?php esc_html_e('Open Media Library', 'cinderwell-media-folders'); ?></a>
        </section>

        <div class="cinderwell-mf-stats" aria-label="<?php esc_attr_e('Folder summary', 'cinderwell-media-folders'); ?>">
            <div><strong><?php echo esc_html(number_format_i18n($folder_count)); ?></strong><span><?php esc_html_e('Folders', 'cinderwell-media-folders'); ?></span></div>
            <div><strong><?php echo esc_html(number_format_i18n($assigned)); ?></strong><span><?php esc_html_e('Filed media', 'cinderwell-media-folders'); ?></span></div>
            <div><strong><?php echo esc_html(number_format_i18n($unfiled)); ?></strong><span><?php esc_html_e('Unfiled media', 'cinderwell-media-folders'); ?></span></div>
        </div>

        <section class="card cw-settings-card cinderwell-mf-folder-card">
            <div class="cinderwell-mf-card-heading">
                <div><h2><?php esc_html_e('Folder overview', 'cinderwell-media-folders'); ?></h2><p><?php esc_html_e('Colors and nesting are reflected in the Media Library folder tree.', 'cinderwell-media-folders'); ?></p></div>
                <a href="<?php echo esc_url($folder_url); ?>" class="button"><?php esc_html_e('Advanced folder management', 'cinderwell-media-folders'); ?></a>
            </div>
        <table class="wp-list-table widefat fixed striped cinderwell-mf-table">
            <thead>
                <tr><th><?php esc_html_e('Folder', 'cinderwell-media-folders'); ?></th><th><?php esc_html_e('Parent', 'cinderwell-media-folders'); ?></th><th><?php esc_html_e('Media', 'cinderwell-media-folders'); ?></th><th><?php esc_html_e('Color', 'cinderwell-media-folders'); ?></th></tr>
            </thead>
            <tbody>
                <?php
                if (is_wp_error($terms) || empty($terms)) {
                    echo '<tr><td colspan="4" class="cinderwell-mf-empty">' . wp_kses_post(sprintf(__('No folders yet. <a href="%s">Open the Media Library</a> and select New folder to begin.', 'cinderwell-media-folders'), esc_url($library_url))) . '</td></tr>';
                } else {
                    foreach ($terms as $term) {
                        $color = get_term_meta($term->term_id, '_cwmf_folder_color', true);
                        $parent = $term->parent ? get_term($term->parent) : null;
                        echo '<tr>';
                        echo '<td><a href="' . esc_url(add_query_arg('cwmf_folder', $term->term_id, $library_url)) . '"><strong>' . esc_html($term->name) . '</strong></a></td>';
                        echo '<td>' . ($parent ? esc_html($parent->name) : '—') . '</td>';
                        echo '<td>' . intval($term->count) . '</td>';
                        echo '<td>' . ($color ? '<span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:' . esc_attr($color) . ';vertical-align:middle;"></span> ' . esc_html($color) : '—') . '</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
        </section>
        <?php
    }

    private function render_import() {
        $hf  = new Import_HappyFiles();
        $wpmf = new Import_WPMF();
        ?>
        <section class="card cw-settings-card cinderwell-mf-intro">
            <div><span class="cinderwell-mf-eyebrow"><?php esc_html_e('Migration', 'cinderwell-media-folders'); ?></span><h2><?php esc_html_e('Bring existing folders with you.', 'cinderwell-media-folders'); ?></h2><p><?php esc_html_e('Preview the migration first. Imports preserve nested folders, reuse previously imported items, and never change attachment URLs.', 'cinderwell-media-folders'); ?></p></div>
        </section>

        <?php if (!$hf->is_active() && !$wpmf->is_active()): ?>
            <div class="cinderwell-mf-empty-state"><span class="dashicons dashicons-migrate"></span><h3><?php esc_html_e('No supported folder plugin detected', 'cinderwell-media-folders'); ?></h3><p><?php esc_html_e('Activate HappyFiles or WP Media Folder, then return here to preview an import.', 'cinderwell-media-folders'); ?></p></div>
        <?php endif; ?>

        <div class="cinderwell-mf-source-grid">
        <?php if ($hf->is_active()): ?>
            <?php $stats = $hf->count_items(); ?>
            <?php $this->render_import_source('HappyFiles', 'happyfiles', $stats); ?>
        <?php endif; ?>

        <?php if ($wpmf->is_active()): ?>
            <?php $stats = $wpmf->count_items(); ?>
            <?php $this->render_import_source('WP Media Folder', 'wpmf', $stats); ?>
        <?php endif; ?>
        </div>

        <div id="cinderwell-mf-import-result" class="cinderwell-mf-import-result" aria-live="polite"></div>
        <?php
    }

    private function render_import_source($name, $slug, $stats) {
        ?>
        <section class="card cw-settings-card cinderwell-mf-source-card">
            <span class="dashicons dashicons-category" aria-hidden="true"></span>
            <h3><?php echo esc_html($name); ?></h3>
            <p><?php echo esc_html(sprintf(__('%1$s folders and %2$s media assignments detected.', 'cinderwell-media-folders'), number_format_i18n((int) $stats['terms']), number_format_i18n((int) $stats['attachments']))); ?></p>
            <div class="cinderwell-mf-actions">
                <button type="button" class="button" data-cwmf-dry-run="<?php echo esc_attr($slug); ?>"><?php esc_html_e('Preview import', 'cinderwell-media-folders'); ?></button>
                <button type="button" class="button button-primary" data-cwmf-import="<?php echo esc_attr($slug); ?>"><?php esc_html_e('Run import', 'cinderwell-media-folders'); ?></button>
            </div>
        </section>
        <?php
    }

    private function render_settings() {
        $settings = wp_parse_args(get_option(CINDERWELL_MEDIA_FOLDERS_OPTION, []), self::get_defaults());
        ?>
        <form method="post" action="options.php" class="cw-settings-form cw-settings-card cinderwell-mf-settings-form">
            <?php settings_fields('cinderwell_media_folders_settings_group'); ?>
            <span class="cinderwell-mf-eyebrow"><?php esc_html_e('Behavior', 'cinderwell-media-folders'); ?></span>
            <h2><?php esc_html_e('Media folder defaults', 'cinderwell-media-folders'); ?></h2>
            <p><?php esc_html_e('Choose how new uploads and folder assignments behave for editors.', 'cinderwell-media-folders'); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Folder assignment', 'cinderwell-media-folders'); ?></th>
                    <td>
                        <div class="cw-admin-segmented">
                            <label><input class="screen-reader-text" type="radio" name="cinderwell_media_folders_settings[multi_folder]" value="0" <?php checked(empty($settings['multi_folder'])); ?>><span class="cw-admin-segmented__option"><?php esc_html_e('One folder', 'cinderwell-media-folders'); ?></span></label>
                            <label><input class="screen-reader-text" type="radio" name="cinderwell_media_folders_settings[multi_folder]" value="1" <?php checked(!empty($settings['multi_folder'])); ?>><span class="cw-admin-segmented__option"><?php esc_html_e('Multiple folders', 'cinderwell-media-folders'); ?></span></label>
                        </div>
                        <p class="description"><?php esc_html_e('One folder keeps filing predictable. Multiple folders lets the same media item appear in several folders without duplication.', 'cinderwell-media-folders'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cinderwell-mf-default-folder"><?php esc_html_e('Default folder', 'cinderwell-media-folders'); ?></label></th>
                    <td>
                        <?php
                        wp_dropdown_categories([
                            'taxonomy'         => Folder_Taxonomy::TAXONOMY,
                            'id'               => 'cinderwell-mf-default-folder',
                            'name'             => 'cinderwell_media_folders_settings[default_folder]',
                            'selected'         => $settings['default_folder'] ?? 0,
                            'show_option_none' => __('No default folder', 'cinderwell-media-folders'),
                            'option_none_value'=> '0',
                            'hierarchical'     => true,
                            'hide_empty'       => false,
                        ]);
                        ?>
                        <p class="description"><?php esc_html_e('Used only when an upload is not already targeted at the folder currently open in the Media Library.', 'cinderwell-media-folders'); ?></p>
                    </td>
                </tr>
            </table>
            <div class="cw-settings-save-bar"><span class="cw-settings-save-status"><?php esc_html_e('Changes apply to future folder actions and uploads.', 'cinderwell-media-folders'); ?></span><?php submit_button(__('Save settings', 'cinderwell-media-folders'), 'primary', 'submit', false); ?></div>
        </form>
        <?php
    }
}
