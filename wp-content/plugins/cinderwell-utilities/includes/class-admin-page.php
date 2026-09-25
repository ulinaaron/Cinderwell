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
        $labels = Module_Registry::get_modules();
        $groups = apply_filters('cinderwell_utilities_groups', [
            'content' => [
                'label'       => 'Content',
                'description' => 'Tools for managing, duplicating, and organizing site content.',
            ],
            'media'   => [
                'label'       => 'Media',
                'description' => 'Safer workflows for the files that power the site.',
            ],
            'admin'   => [
                'label'       => 'Admin Experience',
                'description' => 'Small improvements for administrators and client handoff.',
            ],
            'communication' => [
                'label'       => 'Communication',
                'description' => 'Reliable delivery and diagnostics for messages sent by the site.',
            ],
            'disable' => [
                'label'       => 'Cleanup & Performance',
                'description' => 'Turn off WordPress features the site does not need.',
            ],
        ]);
        foreach ($labels as &$module_meta) {
            if (!isset($groups[$module_meta['group']])) {
                $module_meta['group'] = 'admin';
            }
        }
        unset($module_meta);
        $enabled_count = count(array_filter($settings, static function ($module) {
            return !empty($module['enabled']);
        }));
        $module_count = count($labels);

        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success inline"><p>Settings saved.</p></div>';
        }
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cinderwell-utilities-form">
            <input type="hidden" name="action" value="cinderwell_utilities_save">
            <?php wp_nonce_field('cinderwell_utilities_save'); ?>

            <section class="cinderwell-utilities-overview" aria-labelledby="cinderwell-utilities-title">
                <div class="cinderwell-utilities-overview__copy">
                    <span class="cinderwell-utilities-eyebrow">Site Utilities</span>
                    <h2 id="cinderwell-utilities-title">Use only what this site needs.</h2>
                    <p>Each utility is independent and disabled by default. Enable a tool to reveal its options, then save once when you are finished.</p>
                </div>
                <div class="cinderwell-utilities-overview__stats" aria-label="Utility status">
                    <div><strong data-cinderwell-enabled-count><?php echo esc_html($enabled_count); ?></strong><span>Enabled</span></div>
                    <div><strong><?php echo esc_html($module_count); ?></strong><span>Available</span></div>
                </div>
            </section>

            <?php foreach ($groups as $group_key => $group): ?>
                <?php
                $group_modules = array_filter($labels, static function ($meta) use ($group_key) {
                    return $meta['group'] === $group_key;
                });
                $group_enabled = count(array_filter(array_keys($group_modules), static function ($module_key) use ($settings) {
                    return !empty($settings[$module_key]['enabled']);
                }));
                ?>
                <div class="cinderwell-utilities-group">
                    <div class="cinderwell-utilities-group__header">
                        <div>
                            <h2 class="cinderwell-utilities-group__title"><?php echo esc_html($group['label']); ?></h2>
                            <p><?php echo esc_html($group['description']); ?></p>
                        </div>
                        <span class="cinderwell-utilities-group__count" data-cinderwell-group-count data-group="<?php echo esc_attr($group_key); ?>">
                            <?php echo esc_html(sprintf('%d of %d enabled', $group_enabled, count($group_modules))); ?>
                        </span>
                    </div>
                    <div class="cinderwell-utilities-module-list">
                    <?php foreach ($group_modules as $module_key => $meta): ?>
                        <?php $mod = $settings[$module_key] ?? []; ?>
                        <?php $enabled = !empty($mod['enabled']); ?>
                        <section class="cinderwell-utilities-module<?php echo $enabled ? ' is-enabled' : ''; ?>" data-module="<?php echo esc_attr($module_key); ?>" data-group="<?php echo esc_attr($group_key); ?>">
                            <label class="cinderwell-utilities-module__header">
                                <span class="cinderwell-utilities-module__identity">
                                    <span class="cinderwell-utilities-module__icon dashicons <?php echo esc_attr($this->get_module_icon($module_key)); ?>" aria-hidden="true"></span>
                                    <span class="cinderwell-utilities-module__copy">
                                        <strong><?php echo esc_html($meta['label']); ?></strong>
                                        <span class="cinderwell-utilities-module__desc"><?php echo esc_html($meta['description']); ?></span>
                                    </span>
                                </span>
                                <span class="cinderwell-utilities-module__control">
                                    <span class="cinderwell-utilities-module__status" data-cinderwell-module-status><?php echo $enabled ? 'Enabled' : 'Disabled'; ?></span>
                                    <input type="checkbox"
                                           name="cinderwell_utilities[<?php echo esc_attr($module_key); ?>][enabled]"
                                           value="1"
                                           <?php checked($enabled); ?>
                                           class="cinderwell-utilities-module-toggle"
                                           aria-controls="cinderwell-utilities-<?php echo esc_attr($module_key); ?>"
                                           aria-expanded="<?php echo $enabled ? 'true' : 'false'; ?>" />
                                    <span class="cinderwell-utilities-switch" aria-hidden="true"><span></span></span>
                                </span>
                            </label>
                            <div class="cinderwell-utilities-module__body" id="cinderwell-utilities-<?php echo esc_attr($module_key); ?>"<?php echo $enabled ? '' : ' hidden'; ?>>
                                <?php if (!empty($meta['warning'])): ?>
                                    <p class="notice notice-warning inline"><strong><?php echo esc_html($meta['warning']); ?></strong></p>
                                <?php endif; ?>
                                <?php $this->render_module_settings($module_key, $mod); ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="cinderwell-utilities-save-bar">
                <p><strong>Site Utilities</strong><span data-cinderwell-save-status>No unsaved changes</span></p>
                <?php submit_button('Save Utilities', 'primary', 'submit', false); ?>
            </div>
        </form>
        <?php
    }

    private function get_module_icon($key) {
        $module = Module_Registry::get_module($key);
        return $module['icon'] ?? 'dashicons-admin-generic';
    }

    private function render_module_settings($key, $mod) {
        $module = Module_Registry::get_module($key);
        if ($module && !empty($module['render_callback']) && is_callable($module['render_callback'])) {
            call_user_func($module['render_callback'], $mod, $module);
        } else {
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
                case 'login_branding':
                    $this->render_login_branding();
                    break;
                case 'search_visibility_status':
                    $this->render_search_visibility_status();
                    break;
                case 'plugin_update_control':
                    $this->render_plugin_update_control($mod);
                    break;
                case 'mail_delivery':
                    $this->render_mail_delivery($mod);
                    break;
                default:
                    do_action('cinderwell_utilities_render_module_settings', $key, $mod, $module);
                    break;
            }
        }

        if ($module && !empty($module['settings_url'])) {
            $settings_url = is_callable($module['settings_url']) ? call_user_func($module['settings_url'], $mod, $module) : $module['settings_url'];
            if ($settings_url) {
                echo '<div class="cinderwell-utilities-module__actions"><a class="button" href="' . esc_url($settings_url) . '">' . esc_html__('Configure', 'cinderwell-utilities') . '</a></div>';
            }
        }
    }

    private function render_checkbox_list($name, $options, $selected) {
        echo '<div class="cinderwell-utilities-option-grid">';
        foreach ($options as $value => $label) {
            echo '<label>';
            echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($value) . '" ' . checked(in_array($value, (array) $selected, true), true, false) . ' /> ';
            echo esc_html($label) . '</label>';
        }
        echo '</div>';
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
        echo '</select></td></tr><tr><th>Title Prefix</th><td>';
        echo '<input type="text" name="cinderwell_utilities[content_duplication][title_suffix]" value="' . esc_attr($mod['title_suffix'] ?? 'Copy of') . '" class="regular-text" />';
        echo '<p class="description">Added before the duplicated post title. Leave empty for no prefix.</p>';
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

    private function render_login_branding() {
        $company_enabled = class_exists('Cinderwell\\Addons') && \Cinderwell\Addons::is_enabled('company-details');
        $company = $company_enabled && class_exists('Cinderwell\\Company_Details')
            ? \Cinderwell\Company_Details::get_settings()
            : [];
        $logo_id = absint($company['logo_id'] ?? 0);
        $logo_mime = $logo_id ? (string) get_post_mime_type($logo_id) : '';

        if ($logo_id && 0 === strpos($logo_mime, 'image/')) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'medium');
            if (!$logo_url) {
                $logo_url = wp_get_attachment_url($logo_id);
            }
            echo '<div class="cinderwell-utilities-login-preview">';
            echo '<img src="' . esc_url($logo_url) . '" alt="" />';
            echo '<p class="description">The login screen will use this Company Details logo and link it to the site homepage.</p>';
            echo '</div>';
            return;
        }

        $company_url = admin_url('admin.php?page=cinderwell&tab=company-details');
        $addons_url = admin_url('admin.php?page=cinderwell&tab=addons');
        if (!$company_enabled) {
            echo '<p class="description">Enable Company Details first, then add a logo. Until then, WordPress keeps its standard login logo.</p>';
            echo '<p><a class="button" href="' . esc_url($addons_url) . '">Open Add-Ons</a></p>';
            return;
        }

        echo '<p class="description">No Company Details logo is set. WordPress will keep its standard login logo.</p>';
        echo '<p><a class="button" href="' . esc_url($company_url) . '">Add Company Logo</a></p>';
    }

    private function render_search_visibility_status() {
        $discouraged = 0 === (int) get_option('blog_public', 1);
        $class = $discouraged ? 'notice-warning' : 'notice-success';
        $message = $discouraged
            ? 'Search engines are currently discouraged from indexing this site.'
            : 'Search engines are currently allowed to index this site.';

        echo '<div class="notice inline ' . esc_attr($class) . '"><p><strong>' . esc_html($message) . '</strong></p></div>';
        echo '<p><a class="button" href="' . esc_url(admin_url('options-reading.php')) . '">Open Reading Settings</a></p>';
        echo '<p class="description">This utility reports the WordPress setting only. It never changes search visibility automatically.</p>';
    }

    private function render_plugin_update_control($mod) {
        $locked = Utilities::sanitize_plugin_basenames($mod['locked_plugins'] ?? ($mod['frozen_plugins'] ?? []));

        foreach ($locked as $plugin) {
            echo '<input type="hidden" name="cinderwell_utilities[plugin_update_control][locked_plugins][]" value="' . esc_attr($plugin) . '" />';
        }

        $locked_count = count($locked);
        $locked_label = sprintf(
            _n('%d plugin locked', '%d plugins locked', $locked_count, 'cinderwell-utilities'),
            $locked_count
        );

        echo '<div class="cinderwell-plugin-update-control">';
        echo '<p class="cinderwell-plugin-update-control__status">';
        echo '<span class="dashicons dashicons-lock" aria-hidden="true"></span>';
        echo '<span><strong>Automatic plugin updates are off.</strong><span class="cinderwell-plugin-update-control__count">' . esc_html($locked_label) . '</span></span>';
        echo '</p>';
        echo '<button class="button button-primary" type="submit" name="cinderwell_utilities_redirect" value="plugins">Manage plugin locks</button>';
        echo '</div>';
    }

    private function render_mail_delivery($mod) {
        $defaults = Mail_Manager::get_sender_defaults();
        $from_name = !empty($mod['from_name']) ? $mod['from_name'] : $defaults['name'];
        $from_email = !empty($mod['from_email']) ? $mod['from_email'] : $defaults['email'];
        $sending_domain = !empty($mod['sending_domain']) ? Mail_Manager::sanitize_sending_domain($mod['sending_domain']) : Mail_Manager::get_email_domain($from_email);
        $key_constant = Mail_Manager::api_key_is_constant();
        $key_configured = Mail_Manager::api_key_is_configured();

        echo '<table class="form-table cinderwell-mail-settings">';
        echo '<tr><th><label for="cinderwell-mail-provider">Provider</label></th><td>';
        echo '<select id="cinderwell-mail-provider" name="cinderwell_utilities[mail_delivery][provider]"><option value="sendgrid" selected>SendGrid</option></select>';
        echo '<p class="description">Provider-ready internally; SendGrid is the first available adapter.</p></td></tr>';
        echo '<tr><th><label for="cinderwell-mail-api-key">SendGrid API Key</label></th><td>';
        if ($key_constant) {
            echo '<span class="cinderwell-mail-key-state is-configured">Configured with <code>' . esc_html(Mail_Manager::API_KEY_CONSTANT) . '</code></span>';
            echo '<p class="description">The wp-config constant takes priority. Credential editing is disabled here.</p>';
        } else {
            echo '<input type="password" id="cinderwell-mail-api-key" name="cinderwell_mail_api_key" value="" class="regular-text" autocomplete="new-password" placeholder="' . ($key_configured ? esc_attr__('API key configured — enter a new key to replace it', 'cinderwell-utilities') : esc_attr__('SG.…', 'cinderwell-utilities')) . '" />';
            echo '<p class="description">Write-only and stored separately with autoload disabled. Use a key limited to Mail Send permission.</p>';
            if ($key_configured) {
                echo '<label class="cinderwell-mail-clear-key"><input type="checkbox" name="cinderwell_mail_clear_api_key" value="1" /> Clear the stored API key when saving</label>';
            }
        }
        echo '</td></tr>';
        echo '<tr><th><label for="cinderwell-mail-sending-domain">Sending Domain</label></th><td><input type="text" id="cinderwell-mail-sending-domain" name="cinderwell_utilities[mail_delivery][sending_domain]" value="' . esc_attr($sending_domain) . '" class="regular-text" placeholder="example.com" autocapitalize="none" spellcheck="false" />';
        echo '<p class="description">Enter the domain authenticated in SendGrid, without <code>https://</code> or an email address. The From address must use this domain or one of its subdomains.</p></td></tr>';
        echo '<tr><th><label for="cinderwell-mail-from-name">From Name</label></th><td><input type="text" id="cinderwell-mail-from-name" name="cinderwell_utilities[mail_delivery][from_name]" value="' . esc_attr($from_name) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="cinderwell-mail-from-email">From Email</label></th><td><input type="email" id="cinderwell-mail-from-email" name="cinderwell_utilities[mail_delivery][from_email]" value="' . esc_attr($from_email) . '" class="regular-text" />';
        echo '<p class="description">This address must be a verified sender or belong to an authenticated SendGrid domain.</p></td></tr>';
        echo '<tr><th>Sender Policy</th><td><input type="hidden" name="cinderwell_utilities[mail_delivery][force_from]" value="1" /><strong>Force the verified sender for every WordPress email</strong>';
        echo '<p class="description">An original From address is preserved as Reply-To when the message does not already provide one.</p></td></tr>';
        echo '<tr><th><label for="cinderwell-mail-retention">Log Retention</label></th><td><select id="cinderwell-mail-retention" name="cinderwell_utilities[mail_delivery][retention_days]">';
        foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days'] as $days => $label) {
            echo '<option value="' . esc_attr($days) . '"' . selected(absint($mod['retention_days'] ?? 30), $days, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select><p class="description">Logs contain recipients and subjects, but never message bodies or attachment contents.</p></td></tr>';
        echo '</table>';

        if (Utilities::module_enabled('mail_delivery')) {
            echo '<div class="cinderwell-utilities-module__actions"><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=cinderwell-mail')) . '">Open Mail Tests &amp; Log</a></div>';
        } else {
            echo '<p class="description">Enable and save Mail Delivery to open its test and log screen.</p>';
        }
    }
}
