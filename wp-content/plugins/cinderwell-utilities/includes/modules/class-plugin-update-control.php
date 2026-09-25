<?php
namespace Cinderwell_Utilities\Modules;

use Cinderwell_Utilities\Utilities;

class Plugin_Update_Control {
    private $settings;

    public function __construct($settings = []) {
        $this->settings = wp_parse_args($settings, [
            'locked_plugins' => $settings['frozen_plugins'] ?? [],
        ]);

        // Disable the feature UI and enforce the final automatic-update decision.
        add_filter('plugins_auto_update_enabled', '__return_false', PHP_INT_MAX);
        add_filter('auto_update_plugin', '__return_false', PHP_INT_MAX);

        // Block updates at the upgrader layer so admin, AJAX, cron, and WP-CLI agree.
        add_filter('upgrader_pre_install', [$this, 'guard_update'], 1, 2);
        add_filter('upgrader_source_selection', [$this, 'guard_uploaded_package'], PHP_INT_MAX, 4);

        add_action('deleted_plugin', [$this, 'remove_deleted_plugin'], 10, 2);

        if (is_admin()) {
            add_filter('plugin_action_links', [$this, 'add_row_action'], 20, 4);
            add_filter('network_admin_plugin_action_links', [$this, 'add_row_action'], 20, 4);
            add_filter('plugin_row_meta', [$this, 'add_locked_meta'], 20, 2);
            add_filter('bulk_actions-plugins', [$this, 'add_bulk_actions']);
            add_filter('bulk_actions-plugins-network', [$this, 'add_bulk_actions']);
            add_filter('handle_bulk_actions-plugins', [$this, 'handle_bulk_action'], 10, 3);
            add_filter('handle_bulk_actions-plugins-network', [$this, 'handle_bulk_action'], 10, 3);
            add_action('admin_post_cinderwell_toggle_plugin_lock', [$this, 'handle_row_action']);
            add_action('load-plugins.php', [$this, 'replace_update_rows'], 30);
            add_action('admin_notices', [$this, 'render_admin_notice']);
            add_action('network_admin_notices', [$this, 'render_admin_notice']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        }
    }

    public function guard_update($response, $hook_extra) {
        if (is_wp_error($response)) {
            return $response;
        }

        $plugin = isset($hook_extra['plugin']) ? $this->normalize_plugin($hook_extra['plugin']) : '';
        if ($plugin && $this->is_locked($plugin)) {
            return $this->locked_error($plugin);
        }

        return $response;
    }

    /**
     * Prevent an uploaded ZIP from replacing a locked plugin. This runs after
     * WordPress has identified the package but before it clears the destination.
     */
    public function guard_uploaded_package($source, $remote_source, $upgrader, $hook_extra) {
        if (is_wp_error($source) || !is_string($source)) {
            return $source;
        }

        if (
            !($upgrader instanceof \Plugin_Upgrader) ||
            'plugin' !== ($hook_extra['type'] ?? '') ||
            'install' !== ($hook_extra['action'] ?? '')
        ) {
            return $source;
        }

        $directory = basename(untrailingslashit(wp_normalize_path($source)));
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $php_files = glob(trailingslashit($source) . '*.php');
        $main_files = [];

        foreach ((array) $php_files as $file) {
            $data = get_plugin_data($file, false, false);
            if (!empty($data['Name'])) {
                $main_files[] = basename($file);
            }
        }

        foreach ($this->get_locked_plugins() as $plugin) {
            $plugin_directory = dirname($plugin);
            $matches_directory = '.' !== $plugin_directory && $plugin_directory === $directory;
            $matches_file = '.' === $plugin_directory && in_array(basename($plugin), $main_files, true);

            if ($matches_directory || $matches_file) {
                return $this->locked_error($plugin);
            }
        }

        return $source;
    }

    public function add_row_action($actions, $plugin_file, $plugin_data = [], $context = '') {
        if (!$this->can_manage()) {
            return $actions;
        }

        $plugin_file = $this->normalize_plugin($plugin_file);
        if (!$plugin_file) {
            return $actions;
        }

        $locked = $this->is_locked($plugin_file);
        $operation = $locked ? 'unlock' : 'lock';
        $label = $locked ? __('Unlock updates', 'cinderwell-utilities') : __('Lock updates', 'cinderwell-utilities');
        $url = wp_nonce_url(
            add_query_arg([
                'action'    => 'cinderwell_toggle_plugin_lock',
                'plugin'    => $plugin_file,
                'operation' => $operation,
            ], admin_url('admin-post.php')),
            'cinderwell_plugin_lock_' . $plugin_file
        );

        $actions['cinderwell-' . $operation] = sprintf(
            '<a href="%1$s" class="cinderwell-plugin-lock-action">%2$s</a>',
            esc_url($url),
            esc_html($label)
        );

        return $actions;
    }

    public function add_locked_meta($meta, $plugin_file) {
        if ($this->is_locked($plugin_file)) {
            $meta[] = '<span class="cinderwell-plugin-lock-badge"><span class="dashicons dashicons-lock" aria-hidden="true"></span>' . esc_html__('Updates locked', 'cinderwell-utilities') . '</span>';
        }
        return $meta;
    }

    public function add_bulk_actions($actions) {
        if ($this->can_manage()) {
            $actions['cinderwell-lock-updates'] = __('Lock updates', 'cinderwell-utilities');
            $actions['cinderwell-unlock-updates'] = __('Unlock updates', 'cinderwell-utilities');
        }
        return $actions;
    }

    public function handle_bulk_action($redirect, $action, $plugins) {
        if (!in_array($action, ['cinderwell-lock-updates', 'cinderwell-unlock-updates'], true)) {
            return $redirect;
        }

        if (!$this->can_manage()) {
            return add_query_arg('cinderwell_plugin_lock_error', 'permission', $redirect);
        }

        $plugins = Utilities::sanitize_plugin_basenames($plugins);
        $lock = 'cinderwell-lock-updates' === $action;
        $changed = $this->set_locked_state($plugins, $lock);

        return add_query_arg(
            $lock ? 'cinderwell_plugins_locked' : 'cinderwell_plugins_unlocked',
            $changed,
            $redirect
        );
    }

    public function handle_row_action() {
        if (!$this->can_manage()) {
            wp_die(esc_html__('You are not allowed to manage plugin locks.', 'cinderwell-utilities'));
        }

        $plugin = isset($_GET['plugin']) ? $this->normalize_plugin(wp_unslash($_GET['plugin'])) : '';
        if (!$plugin || !in_array($plugin, Utilities::sanitize_plugin_basenames([$plugin]), true)) {
            wp_die(esc_html__('The requested plugin is not installed.', 'cinderwell-utilities'));
        }

        check_admin_referer('cinderwell_plugin_lock_' . $plugin);

        $operation = isset($_GET['operation']) ? sanitize_key(wp_unslash($_GET['operation'])) : '';
        if (!in_array($operation, ['lock', 'unlock'], true)) {
            wp_die(esc_html__('Invalid plugin lock action.', 'cinderwell-utilities'));
        }

        $changed = $this->set_locked_state([$plugin], 'lock' === $operation);
        $query_key = 'lock' === $operation ? 'cinderwell_plugins_locked' : 'cinderwell_plugins_unlocked';
        $redirect = wp_get_referer() ?: admin_url('plugins.php');
        wp_safe_redirect(add_query_arg($query_key, $changed, $redirect));
        exit;
    }

    public function remove_deleted_plugin($plugin_file, $deleted) {
        if ($deleted && $this->is_locked($plugin_file)) {
            $this->set_locked_state([$plugin_file], false);
        }
    }

    public function replace_update_rows() {
        foreach ($this->get_locked_plugins() as $plugin) {
            remove_action("after_plugin_row_{$plugin}", 'wp_plugin_update_row', 10);
            add_action("after_plugin_row_{$plugin}", [$this, 'render_locked_update_row'], 10, 2);
        }
    }

    public function render_locked_update_row($plugin_file, $plugin_data) {
        $updates = get_site_transient('update_plugins');
        if (empty($updates->response[$plugin_file])) {
            return;
        }

        $update = $updates->response[$plugin_file];
        $plugin_name = $plugin_data['Name'] ?? $plugin_file;
        $new_version = $update->new_version ?? '';
        $details_url = '';

        if (!empty($update->slug)) {
            $details_url = self_admin_url('plugin-install.php?tab=plugin-information&plugin=' . rawurlencode($update->slug) . '&section=changelog&TB_iframe=true&width=600&height=800');
        } elseif (!empty($update->url)) {
            $details_url = $update->url;
        }

        $list_table = _get_list_table('WP_Plugins_List_Table', ['screen' => get_current_screen()]);
        $active_class = is_plugin_active($plugin_file) ? ' active' : '';
        $slug = !empty($update->slug) ? $update->slug : dirname($plugin_file);

        echo '<tr class="plugin-update-tr' . esc_attr($active_class) . ' cinderwell-plugin-update-locked" id="' . esc_attr($slug . '-update') . '" data-plugin="' . esc_attr($plugin_file) . '">';
        echo '<td colspan="' . esc_attr($list_table->get_column_count()) . '" class="plugin-update colspanchange">';
        echo '<div class="update-message notice inline notice-warning notice-alt"><p>';
        echo esc_html(sprintf(__('Version %1$s of %2$s is available.', 'cinderwell-utilities'), $new_version, $plugin_name));
        if ($details_url) {
            echo ' <a href="' . esc_url($details_url) . '" class="thickbox open-plugin-details-modal">' . esc_html__('View version details', 'cinderwell-utilities') . '</a>.';
        }
        echo ' <strong><span class="dashicons dashicons-lock" aria-hidden="true"></span>' . esc_html__('Updates are locked. Unlock this plugin to update it.', 'cinderwell-utilities') . '</strong>';
        echo '</p></div></td></tr>';
    }

    public function render_admin_notice() {
        if (!$this->can_manage()) {
            return;
        }

        if (isset($_GET['cinderwell_plugins_locked'])) {
            $count = absint($_GET['cinderwell_plugins_locked']);
            $message = sprintf(_n('%d plugin was locked.', '%d plugins were locked.', $count, 'cinderwell-utilities'), $count);
        } elseif (isset($_GET['cinderwell_plugins_unlocked'])) {
            $count = absint($_GET['cinderwell_plugins_unlocked']);
            $message = sprintf(_n('%d plugin was unlocked.', '%d plugins were unlocked.', $count, 'cinderwell-utilities'), $count);
        } elseif (isset($_GET['cinderwell_plugin_lock_error'])) {
            $message = __('Plugin lock settings could not be changed.', 'cinderwell-utilities');
        } elseif (isset($_GET['cinderwell_plugin_control_saved'])) {
            $message = __('Plugin Update Control was saved. You can now lock or unlock plugin updates.', 'cinderwell-utilities');
        } else {
            return;
        }

        $notice_class = isset($_GET['cinderwell_plugin_lock_error']) ? 'notice-error' : 'notice-success';
        echo '<div class="notice ' . esc_attr($notice_class) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }

    public function enqueue_assets($hook_suffix) {
        if (!in_array($hook_suffix, ['plugins.php', 'plugins-network.php', 'update-core.php'], true)) {
            return;
        }

        wp_enqueue_style(
            'cinderwell-plugin-update-control',
            CINDERWELL_UTILITIES_URL . 'assets/css/plugin-update-control.css',
            [],
            CINDERWELL_UTILITIES_VERSION
        );

        if ('update-core.php' === $hook_suffix) {
            wp_enqueue_script(
                'cinderwell-plugin-update-control',
                CINDERWELL_UTILITIES_URL . 'assets/js/plugin-update-control.js',
                [],
                CINDERWELL_UTILITIES_VERSION,
                true
            );
            wp_localize_script('cinderwell-plugin-update-control', 'cinderwellPluginUpdateControl', [
                'lockedPlugins' => $this->get_locked_plugins(),
                'label'         => __('Updates locked', 'cinderwell-utilities'),
            ]);
        }
    }

    private function set_locked_state($plugins, $lock) {
        $settings = Utilities::get_settings();
        $current = Utilities::sanitize_plugin_basenames($settings['plugin_update_control']['locked_plugins'] ?? [], false);
        $plugins = Utilities::sanitize_plugin_basenames($plugins, $lock);

        if ($lock) {
            $next = array_values(array_unique(array_merge($current, $plugins)));
        } else {
            $next = array_values(array_diff($current, $plugins));
        }

        $changed = count(array_diff($current, $next)) + count(array_diff($next, $current));
        $settings['plugin_update_control']['locked_plugins'] = $next;
        Utilities::update_settings($settings);
        $this->settings['locked_plugins'] = $next;

        return $changed;
    }

    private function get_locked_plugins() {
        return Utilities::sanitize_plugin_basenames($this->settings['locked_plugins'] ?? [], false);
    }

    private function is_locked($plugin) {
        $plugin = $this->normalize_plugin($plugin);
        return $plugin && in_array($plugin, $this->get_locked_plugins(), true);
    }

    private function normalize_plugin($plugin) {
        $plugins = Utilities::sanitize_plugin_basenames([$plugin], false);
        return $plugins[0] ?? '';
    }

    private function can_manage() {
        return current_user_can('manage_options') && current_user_can('update_plugins');
    }

    private function locked_error($plugin) {
        return new \WP_Error(
            'cinderwell_plugin_locked',
            sprintf(__('Updates for %s are locked. Unlock the plugin from the Plugins screen before updating it.', 'cinderwell-utilities'), $plugin)
        );
    }
}
