<?php
$settings = \Cinderwell_Portal\Portal::get_settings();
if (!in_array($settings['registration_mode'] ?? '', ['moderated', 'both'])) {
    if (current_user_can('manage_options')) {
        echo '<p class="cinderwell-portal-notice">' . esc_html__('Registration is currently closed. This block is only visible to admins in the editor.', 'cinderwell-portal') . '</p>';
    }
    return;
}
echo \Cinderwell_Portal\Template_Loader::load('registration-form');
