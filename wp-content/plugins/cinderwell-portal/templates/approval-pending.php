<?php
/**
 * Approval pending full page template
 */
?>
<div class="cinderwell-portal-page cinderwell-portal-approval-pending">
    <div class="cinderwell-portal-card">
        <h1><?php esc_html_e('Pending Approval', 'cinderwell-portal'); ?></h1>
        <p><?php esc_html_e('Your account is waiting for administrator approval. You will receive an email when your account is activated.', 'cinderwell-portal'); ?></p>
        <p><a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="cinderwell-portal-btn cinderwell-portal-btn--ghost"><?php esc_html_e('Log out', 'cinderwell-portal'); ?></a></p>
    </div>
</div>
