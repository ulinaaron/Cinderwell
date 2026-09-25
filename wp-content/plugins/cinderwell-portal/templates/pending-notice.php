<?php
/**
 * Pending approval notice template
 */
?>
<div class="cinderwell-portal-pending">
    <h2><?php esc_html_e('Account Pending Approval', 'cinderwell-portal'); ?></h2>
    <p><?php esc_html_e('Your registration is being reviewed. You will receive an email once your account is approved.', 'cinderwell-portal'); ?></p>
    <p><a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="cinderwell-portal-btn cinderwell-portal-btn--ghost"><?php esc_html_e('Log out', 'cinderwell-portal'); ?></a></p>
</div>
