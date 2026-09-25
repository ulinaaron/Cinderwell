<?php
$settings = \Cinderwell_Portal\Portal::get_settings();
$redirect = $redirect ?? ($settings['login_redirect_url'] ?? '');
?>
<form class="cinderwell-portal-form cinderwell-portal-login" data-redirect="<?php echo esc_attr($redirect); ?>">
    <?php wp_nonce_field('cinderwell_portal_auth', 'nonce'); ?>
    <div class="cinderwell-portal-field">
        <label for="cinderwell-login-email"><?php esc_html_e('Email', 'cinderwell-portal'); ?></label>
        <input type="email" id="cinderwell-login-email" name="email" required autocomplete="email" />
    </div>
    <div class="cinderwell-portal-field">
        <label for="cinderwell-login-password"><?php esc_html_e('Password', 'cinderwell-portal'); ?></label>
        <input type="password" id="cinderwell-login-password" name="password" required autocomplete="current-password" />
    </div>
    <div class="cinderwell-portal-field cinderwell-portal-field--inline">
        <label>
            <input type="checkbox" name="remember" value="1" />
            <?php esc_html_e('Remember me', 'cinderwell-portal'); ?>
        </label>
    </div>
    <button type="submit" class="cinderwell-portal-btn cinderwell-portal-btn--primary"><?php esc_html_e('Log in', 'cinderwell-portal'); ?></button>
    <p class="cinderwell-portal-message"></p>
    <p class="cinderwell-portal-links">
        <a href="#" class="cinderwell-portal-lost-password-link"><?php esc_html_e('Lost your password?', 'cinderwell-portal'); ?></a>
    </p>
    <?php if (in_array($settings['registration_mode'] ?? '', ['moderated', 'both'])): ?>
    <p class="cinderwell-portal-links">
        <?php esc_html_e("Don't have an account?", 'cinderwell-portal'); ?>
        <a href="#" class="cinderwell-portal-show-register"><?php esc_html_e('Register', 'cinderwell-portal'); ?></a>
    </p>
    <?php endif; ?>
</form>

<div class="cinderwell-portal-lost-password-form" style="display: none;">
    <h3><?php esc_html_e('Reset Password', 'cinderwell-portal'); ?></h3>
    <form class="cinderwell-portal-form cinderwell-portal-lost-pw-form">
        <?php wp_nonce_field('cinderwell_portal_auth', 'nonce'); ?>
        <div class="cinderwell-portal-field">
            <label for="cinderwell-lost-email"><?php esc_html_e('Email', 'cinderwell-portal'); ?></label>
            <input type="email" id="cinderwell-lost-email" name="email" required />
        </div>
        <button type="submit" class="cinderwell-portal-btn cinderwell-portal-btn--primary"><?php esc_html_e('Send Reset Link', 'cinderwell-portal'); ?></button>
        <p class="cinderwell-portal-message"></p>
        <p class="cinderwell-portal-links">
            <a href="#" class="cinderwell-portal-back-to-login"><?php esc_html_e('Back to login', 'cinderwell-portal'); ?></a>
        </p>
    </form>
</div>
