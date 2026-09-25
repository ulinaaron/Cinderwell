<?php
namespace Cinderwell_Portal;

class Admin_Page {
    public function __construct() {
        add_filter('cinderwell_settings_tabs', [$this, 'register_tab']);
    }

    public function register_tab($tabs) {
        $tabs['portal'] = [
            'label'    => 'Members Portal',
            'group'    => 'extensions',
            'callback' => [$this, 'render_tab'],
        ];
        return $tabs;
    }

    public function render_tab() {
        $settings = Portal::get_settings();
        $active_subtab = sanitize_key($_GET['subtab'] ?? 'general');
        ?>
        <div class="cinderwell-portal-admin">
            <nav class="nav-tab-wrapper cinderwell-portal-subtabs">
                <?php
                $subtabs = [
                    'general' => 'General',
                    'registration' => 'Registration',
                    'emails' => 'Email Templates',
                    'access' => 'Access Control',
                    'pending' => 'Pending',
                    'members' => 'Members',
                ];
                foreach ($subtabs as $key => $label) {
                    $active = ($active_subtab === $key) ? ' nav-tab-active' : '';
                    $url = admin_url('admin.php?page=cinderwell&tab=portal&subtab=' . $key);
                    echo '<a href="' . esc_url($url) . '" class="nav-tab' . $active . '">' . esc_html($label) . '</a>';
                }
                ?>
            </nav>

            <div class="cinderwell-portal-subtab-content" style="margin-top: 1rem;">
                <?php
                switch ($active_subtab) {
                    case 'registration': $this->render_registration($settings); break;
                    case 'emails': $this->render_emails($settings); break;
                    case 'access': $this->render_access(); break;
                    case 'pending': $this->render_pending(); break;
                    case 'members': $this->render_members(); break;
                    default: $this->render_general($settings);
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_general($settings) {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('cinderwell_portal_settings_group'); ?>
            <h2>General Settings</h2>
            <p class="description">Enable or disable the portal from <a href="<?php echo esc_url(admin_url('admin.php?page=cinderwell&tab=addons')); ?>">Cinderwell &rarr; Add-Ons</a>.</p>
            <table class="form-table">
                <tr>
                    <th scope="row">Registration Mode</th>
                    <td>
                        <select name="cinderwell_portal_settings[registration_mode]">
                            <option value="closed" <?php selected($settings['registration_mode'], 'closed'); ?>>Closed (no new registrations)</option>
                            <option value="moderated" <?php selected($settings['registration_mode'], 'moderated'); ?>>Open sign-up with moderation</option>
                            <option value="admin_issued" <?php selected($settings['registration_mode'], 'admin_issued'); ?>>Admin-issued logins only</option>
                            <option value="both" <?php selected($settings['registration_mode'], 'both'); ?>>Both (moderated + admin-issued)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Login Redirect URL</th>
                    <td>
                        <input type="url" name="cinderwell_portal_settings[login_redirect_url]" value="<?php echo esc_attr($settings['login_redirect_url']); ?>" class="regular-text" placeholder="Leave empty for site homepage" />
                        <p class="description">Where members go after successful login.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Logout Redirect URL</th>
                    <td>
                        <input type="url" name="cinderwell_portal_settings[logout_redirect_url]" value="<?php echo esc_attr($settings['logout_redirect_url']); ?>" class="regular-text" placeholder="Leave empty for site homepage" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Pending Redirect URL</th>
                    <td>
                        <input type="url" name="cinderwell_portal_settings[pending_redirect_url]" value="<?php echo esc_attr($settings['pending_redirect_url']); ?>" class="regular-text" placeholder="Leave empty for /pending-approval/" />
                        <p class="description">Where pending members are sent.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">From Email</th>
                    <td>
                        <input type="email" name="cinderwell_portal_settings[from_email]" value="<?php echo esc_attr($settings['from_email']); ?>" class="regular-text" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">From Name</th>
                    <td>
                        <input type="text" name="cinderwell_portal_settings[from_name]" value="<?php echo esc_attr($settings['from_name']); ?>" class="regular-text" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>" />
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
        <?php
    }

    private function render_registration($settings) {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('cinderwell_portal_settings_group'); ?>
            <h2>Registration Form</h2>
            <p>Fields are fixed in v0.1: Email, First Name, Last Name.</p>
            <table class="form-table">
                <tr>
                    <th scope="row">Show Terms Checkbox</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cinderwell_portal_settings[show_terms_checkbox]" value="1" <?php checked(!empty($settings['show_terms_checkbox'])); ?> />
                            Require users to accept terms
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Terms Text</th>
                    <td>
                        <textarea name="cinderwell_portal_settings[terms_text]" rows="3" class="large-text"><?php echo esc_textarea($settings['terms_text']); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Show Privacy Checkbox</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cinderwell_portal_settings[show_privacy_checkbox]" value="1" <?php checked(!empty($settings['show_privacy_checkbox'])); ?> />
                            Require users to accept privacy policy
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Privacy Text</th>
                    <td>
                        <textarea name="cinderwell_portal_settings[privacy_text]" rows="3" class="large-text"><?php echo esc_textarea($settings['privacy_text']); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Submit Button Text</th>
                    <td>
                        <input type="text" name="cinderwell_portal_settings[submit_button_text]" value="<?php echo esc_attr($settings['submit_button_text']); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
        <?php
    }

    private function render_emails($settings) {
        $templates = $settings['email_templates'];
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('cinderwell_portal_settings_group'); ?>
            <h2>Email Templates</h2>
            <p>Use placeholders like <code>{first_name}</code>, <code>{email}</code>, <code>{login_url}</code>, <code>{setup_url}</code>, <code>{approval_url}</code>, <code>{site_name}</code>.</p>
            <?php foreach ($templates as $key => $tpl): ?>
                <h3 style="margin-top: 1.5rem;"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Subject</th>
                        <td>
                            <input type="text" name="cinderwell_portal_settings[email_templates][<?php echo esc_attr($key); ?>][subject]" value="<?php echo esc_attr($tpl['subject'] ?? ''); ?>" class="large-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Body</th>
                        <td>
                            <textarea name="cinderwell_portal_settings[email_templates][<?php echo esc_attr($key); ?>][body]" rows="8" class="large-text"><?php echo esc_textarea($tpl['body'] ?? ''); ?></textarea>
                        </td>
                    </tr>
                    <?php if ($key === 'rejection'): ?>
                    <tr>
                        <th scope="row">Enabled</th>
                        <td>
                            <label>
                                <input type="checkbox" name="cinderwell_portal_settings[email_templates][rejection][enabled]" value="1" <?php checked(!empty($tpl['enabled'])); ?> />
                                Send rejection emails
                            </label>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th scope="row">Send Test</th>
                        <td>
                            <button type="button" class="button cinderwell-send-test" data-template="<?php echo esc_attr($key); ?>">Send test to <?php echo esc_html(get_option('admin_email')); ?></button>
                            <span class="cinderwell-test-status" data-template="<?php echo esc_attr($key); ?>"></span>
                        </td>
                    </tr>
                </table>
            <?php endforeach; ?>
            <?php submit_button('Save Templates'); ?>
        </form>
        <?php
    }

    private function render_access() {
        ?>
        <h2>Access Control</h2>
        <p>To protect a post or page, edit it and check <strong>"Members only"</strong> in the right sidebar meta box.</p>
        <p>To protect part of a page, use the <strong>Member Only</strong> block in the editor and wrap the content you want gated.</p>
        <h3>How It Works</h3>
        <ul style="list-style: disc; padding-left: 1.5rem;">
            <li>Non-members see a teaser + login link (page-level gating)</li>
            <li>Pending members see a "pending approval" message</li>
            <li>Approved members and admins see full content</li>
        </ul>
        <h3>Theme Overrides</h3>
        <p>Place template overrides in <code>your-theme/cinderwell-portal/login-form.php</code> etc.</p>
        <?php
    }

    private function render_pending() {
        $table = new Pending_List_Table();
        $table->prepare_items();
        ?>
        <h2>Pending Members</h2>
        <form method="post">
            <?php
            $table->search_box('Search Pending', 'cinderwell-pending-search');
            $table->display();
            ?>
        </form>
        <?php
    }

    private function render_members() {
        $table = new Member_List_Table();
        $table->prepare_items();
        ?>
        <h2>Approved Members</h2>
        <form method="post">
            <?php
            $table->search_box('Search Members', 'cinderwell-member-search');
            $table->display();
            ?>
        </form>

        <h3 style="margin-top: 2rem;">Add New Member</h3>
        <form id="cinderwell-admin-create-member">
            <?php wp_nonce_field('cinderwell_portal_admin', 'nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cw-new-email">Email</label></th>
                    <td><input type="email" id="cw-new-email" name="email" required class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="cw-new-first">First Name</label></th>
                    <td><input type="text" id="cw-new-first" name="first_name" required class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="cw-new-last">Last Name</label></th>
                    <td><input type="text" id="cw-new-last" name="last_name" required class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Welcome Email</th>
                    <td>
                        <label>
                            <input type="checkbox" name="send_welcome" value="1" checked />
                            Send a setup link so the user can set their password
                        </label>
                    </td>
                </tr>
            </table>
            <button type="submit" class="button button-primary">Create Member</button>
            <span class="cinderwell-admin-message" style="margin-left: 1rem;"></span>
        </form>
        <?php
    }
}
