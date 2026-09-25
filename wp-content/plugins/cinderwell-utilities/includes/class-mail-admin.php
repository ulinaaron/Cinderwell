<?php
/**
 * Cinderwell Mail operations screen.
 *
 * @package Cinderwell_Utilities
 */

namespace Cinderwell_Utilities;

defined('ABSPATH') || exit;

class Mail_Admin {
    private $settings;

    public function __construct(array $settings) {
        $this->settings = $settings;
        // Register after Cinderwell has created its top-level menu container.
        add_action('admin_menu', [$this, 'add_menu'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_notices', [$this, 'configuration_notice']);
        add_filter('parent_file', [$this, 'highlight_parent_menu']);
    }

    public function add_menu() {
        add_submenu_page(
            'cinderwell',
            __('Mail', 'cinderwell-utilities'),
            __('Mail', 'cinderwell-utilities'),
            'manage_options',
            'cinderwell-mail',
            [$this, 'render']
        );
    }

    public function enqueue_assets($hook) {
        if ('cinderwell_page_cinderwell-mail' !== $hook) {
            return;
        }
        wp_enqueue_style('cinderwell-utilities-admin', CINDERWELL_UTILITIES_URL . 'assets/css/admin.css', [], CINDERWELL_UTILITIES_VERSION);
        wp_enqueue_script('cinderwell-utilities-mail', CINDERWELL_UTILITIES_URL . 'assets/js/mail.js', [], CINDERWELL_UTILITIES_VERSION, true);
        wp_localize_script('cinderwell-utilities-mail', 'cinderwellMail', [
            'restUrl'      => rest_url('cinder-utilities/v1/mail/'),
            'nonce'        => wp_create_nonce('wp_rest'),
            'defaultEmail' => sanitize_email(wp_get_current_user()->user_email),
            'dateFormat'   => get_option('date_format') . ' ' . get_option('time_format'),
            'strings'      => [
                'loading'      => __('Loading mail log…', 'cinderwell-utilities'),
                'empty'        => __('No mail has been logged yet.', 'cinderwell-utilities'),
                'requestError' => __('The request could not be completed.', 'cinderwell-utilities'),
                'confirmClear' => __('Clear every record from the mail log? This cannot be undone.', 'cinderwell-utilities'),
            ],
        ]);
    }

    public function configuration_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ('cinderwell-help' === $page) {
            return;
        }
        $manager = new Mail_Manager($this->settings);
        $valid = $manager->validate_configuration();
        if (!is_wp_error($valid)) {
            return;
        }
        $url = admin_url('admin.php?page=cinderwell&tab=utilities');
        echo '<div class="notice notice-error"><p><strong>' . esc_html__('Cinderwell Mail Delivery is not ready.', 'cinderwell-utilities') . '</strong> ';
        echo esc_html($valid->get_error_message()) . ' ';
        echo '<a href="' . esc_url($url) . '">' . esc_html__('Review Site Utilities', 'cinderwell-utilities') . '</a></p></div>';
    }

    public function highlight_parent_menu($parent_file) {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        return 'cinderwell-mail' === $page ? 'cinderwell' : $parent_file;
    }

    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to manage mail delivery.', 'cinderwell-utilities'));
        }
        $sender = Mail_Manager::get_sender_defaults();
        $from_name = $this->settings['from_name'] ?: $sender['name'];
        $from_email = $this->settings['from_email'] ?: $sender['email'];
        $sending_domain = Mail_Manager::sanitize_sending_domain($this->settings['sending_domain'] ?? '');
        ?>
        <div class="wrap cinderwell-mail-page">
            <div class="cinderwell-mail-hero">
                <div>
                    <span class="cinderwell-utilities-eyebrow"><?php esc_html_e('Site Utilities', 'cinderwell-utilities'); ?></span>
                    <h1><?php esc_html_e('Mail', 'cinderwell-utilities'); ?></h1>
                    <p><?php esc_html_e('Test SendGrid and inspect submission outcomes. Accepted means SendGrid queued the message; it does not confirm delivery.', 'cinderwell-utilities'); ?></p>
                </div>
                <div class="cinderwell-mail-sender">
                    <span><?php esc_html_e('Sending as', 'cinderwell-utilities'); ?></span>
                    <strong><?php echo esc_html($from_name); ?></strong>
                    <code><?php echo esc_html($from_email); ?></code>
                </div>
            </div>

            <div class="cinderwell-mail-test-grid">
                <section class="cinderwell-mail-card">
                    <h2><?php esc_html_e('Test configuration', 'cinderwell-utilities'); ?></h2>
                    <p><?php esc_html_e('Validate the API key, sender, recipient, and request format in SendGrid sandbox mode. Nothing is delivered.', 'cinderwell-utilities'); ?></p>
                    <label for="cinderwell-mail-recipient"><?php esc_html_e('Test recipient', 'cinderwell-utilities'); ?></label>
                    <input type="email" id="cinderwell-mail-recipient" class="regular-text" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" />
                    <div class="cinderwell-mail-test-actions">
                        <button type="button" class="button" data-mail-test="validate"><?php esc_html_e('Validate configuration', 'cinderwell-utilities'); ?></button>
                        <button type="button" class="button button-primary" data-mail-test="send"><?php esc_html_e('Send test email', 'cinderwell-utilities'); ?></button>
                    </div>
                    <div class="cinderwell-mail-result" data-mail-test-result role="status" aria-live="polite"></div>
                </section>
                <section class="cinderwell-mail-card cinderwell-mail-card--status">
                    <h2><?php esc_html_e('Configuration', 'cinderwell-utilities'); ?></h2>
                    <dl>
                        <div><dt><?php esc_html_e('Provider', 'cinderwell-utilities'); ?></dt><dd>SendGrid</dd></div>
                        <div><dt><?php esc_html_e('Sending domain', 'cinderwell-utilities'); ?></dt><dd><code><?php echo esc_html($sending_domain ?: __('Missing', 'cinderwell-utilities')); ?></code></dd></div>
                        <div><dt><?php esc_html_e('API key', 'cinderwell-utilities'); ?></dt><dd><?php echo Mail_Manager::api_key_is_configured() ? esc_html__('Configured', 'cinderwell-utilities') : esc_html__('Missing', 'cinderwell-utilities'); ?></dd></div>
                        <div><dt><?php esc_html_e('Retention', 'cinderwell-utilities'); ?></dt><dd><?php echo esc_html(sprintf(__('%d days', 'cinderwell-utilities'), absint($this->settings['retention_days'] ?? 30))); ?></dd></div>
                        <div><dt><?php esc_html_e('Fallback', 'cinderwell-utilities'); ?></dt><dd><?php esc_html_e('Disabled', 'cinderwell-utilities'); ?></dd></div>
                    </dl>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=cinderwell&tab=utilities')); ?>" class="button"><?php esc_html_e('Edit mail settings', 'cinderwell-utilities'); ?></a>
                </section>
            </div>

            <section class="cinderwell-mail-log" aria-labelledby="cinderwell-mail-log-title">
                <div class="cinderwell-mail-log__header">
                    <div>
                        <h2 id="cinderwell-mail-log-title"><?php esc_html_e('Mail log', 'cinderwell-utilities'); ?></h2>
                        <p><?php esc_html_e('Metadata only. Message bodies and attachment contents are never stored.', 'cinderwell-utilities'); ?></p>
                    </div>
                    <button type="button" class="button button-link-delete" data-mail-clear><?php esc_html_e('Clear log', 'cinderwell-utilities'); ?></button>
                </div>
                <form class="cinderwell-mail-filters" data-mail-filters>
                    <label class="screen-reader-text" for="cinderwell-mail-search"><?php esc_html_e('Search mail log', 'cinderwell-utilities'); ?></label>
                    <input type="search" id="cinderwell-mail-search" placeholder="<?php esc_attr_e('Search recipient, subject, or message ID', 'cinderwell-utilities'); ?>" />
                    <label class="screen-reader-text" for="cinderwell-mail-status"><?php esc_html_e('Filter by status', 'cinderwell-utilities'); ?></label>
                    <select id="cinderwell-mail-status">
                        <option value=""><?php esc_html_e('All statuses', 'cinderwell-utilities'); ?></option>
                        <option value="accepted"><?php esc_html_e('Accepted', 'cinderwell-utilities'); ?></option>
                        <option value="validated"><?php esc_html_e('Validated', 'cinderwell-utilities'); ?></option>
                        <option value="failed"><?php esc_html_e('Failed', 'cinderwell-utilities'); ?></option>
                    </select>
                    <button type="submit" class="button"><?php esc_html_e('Filter', 'cinderwell-utilities'); ?></button>
                </form>
                <div class="cinderwell-mail-table-wrap">
                    <table class="widefat striped cinderwell-mail-table">
                        <thead><tr>
                            <th><?php esc_html_e('Date', 'cinderwell-utilities'); ?></th>
                            <th><?php esc_html_e('Status', 'cinderwell-utilities'); ?></th>
                            <th><?php esc_html_e('Recipient', 'cinderwell-utilities'); ?></th>
                            <th><?php esc_html_e('Subject', 'cinderwell-utilities'); ?></th>
                            <th><?php esc_html_e('Source', 'cinderwell-utilities'); ?></th>
                            <th><?php esc_html_e('Provider response', 'cinderwell-utilities'); ?></th>
                        </tr></thead>
                        <tbody data-mail-log-body><tr><td colspan="6"><?php esc_html_e('Loading mail log…', 'cinderwell-utilities'); ?></td></tr></tbody>
                    </table>
                </div>
                <div class="cinderwell-mail-pagination" data-mail-pagination></div>
            </section>
        </div>
        <?php
    }
}
