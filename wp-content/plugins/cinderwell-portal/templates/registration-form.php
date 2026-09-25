<?php
$settings = \Cinderwell_Portal\Portal::get_settings();
?>
<form class="cinderwell-portal-form cinderwell-portal-register">
    <?php wp_nonce_field('cinderwell_portal_registration', 'nonce'); ?>
    <div class="cinderwell-portal-field">
        <label for="cinderwell-register-email"><?php esc_html_e('Email', 'cinderwell-portal'); ?></label>
        <input type="email" id="cinderwell-register-email" name="email" required autocomplete="email" />
    </div>
    <div class="cinderwell-portal-field">
        <label for="cinderwell-register-first"><?php esc_html_e('First Name', 'cinderwell-portal'); ?></label>
        <input type="text" id="cinderwell-register-first" name="first_name" required autocomplete="given-name" />
    </div>
    <div class="cinderwell-portal-field">
        <label for="cinderwell-register-last"><?php esc_html_e('Last Name', 'cinderwell-portal'); ?></label>
        <input type="text" id="cinderwell-register-last" name="last_name" required autocomplete="family-name" />
    </div>
    <?php if (!empty($settings['show_terms_checkbox'])): ?>
    <div class="cinderwell-portal-field cinderwell-portal-field--inline">
        <label>
            <input type="checkbox" name="terms" value="1" required />
            <?php echo esc_html($settings['terms_text']); ?>
        </label>
    </div>
    <?php endif; ?>
    <?php if (!empty($settings['show_privacy_checkbox'])): ?>
    <div class="cinderwell-portal-field cinderwell-portal-field--inline">
        <label>
            <input type="checkbox" name="privacy" value="1" required />
            <?php echo esc_html($settings['privacy_text']); ?>
        </label>
    </div>
    <?php endif; ?>
    <button type="submit" class="cinderwell-portal-btn cinderwell-portal-btn--primary"><?php echo esc_html($settings['submit_button_text'] ?: 'Register'); ?></button>
    <p class="cinderwell-portal-message"></p>
</form>
