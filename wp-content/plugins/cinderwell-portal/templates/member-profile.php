<?php
$user = wp_get_current_user();
$is_pending = in_array('cinderwell_pending_member', (array) $user->roles);
$is_member = \Cinderwell_Portal\Portal::is_member();

if (!$user->exists()): ?>
    <div class="cinderwell-portal-profile-login">
        <p><?php esc_html_e('Please log in to view your profile.', 'cinderwell-portal'); ?></p>
        <?php echo \Cinderwell_Portal\Template_Loader::load('login-form'); ?>
    </div>
<?php elseif ($is_pending): ?>
    <div class="cinderwell-portal-pending">
        <h2><?php esc_html_e('Account Pending Approval', 'cinderwell-portal'); ?></h2>
        <p><?php esc_html_e('Your registration is being reviewed. You will receive an email once your account is approved.', 'cinderwell-portal'); ?></p>
        <p><a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="cinderwell-portal-btn cinderwell-portal-btn--ghost"><?php esc_html_e('Log out', 'cinderwell-portal'); ?></a></p>
    </div>
<?php elseif ($is_member): ?>
    <div class="cinderwell-portal-profile">
        <form class="cinderwell-portal-form cinderwell-portal-profile-form" data-user-id="<?php echo esc_attr($user->ID); ?>">
            <?php wp_nonce_field('cinderwell_portal_profile', 'nonce'); ?>
            <h2><?php esc_html_e('Your Profile', 'cinderwell-portal'); ?></h2>
            <div class="cinderwell-portal-field">
                <label for="cinderwell-profile-email"><?php esc_html_e('Email', 'cinderwell-portal'); ?></label>
                <input type="email" id="cinderwell-profile-email" name="email" value="<?php echo esc_attr($user->user_email); ?>" required />
            </div>
            <div class="cinderwell-portal-field">
                <label for="cinderwell-profile-first"><?php esc_html_e('First Name', 'cinderwell-portal'); ?></label>
                <input type="text" id="cinderwell-profile-first" name="first_name" value="<?php echo esc_attr($user->first_name); ?>" required />
            </div>
            <div class="cinderwell-portal-field">
                <label for="cinderwell-profile-last"><?php esc_html_e('Last Name', 'cinderwell-portal'); ?></label>
                <input type="text" id="cinderwell-profile-last" name="last_name" value="<?php echo esc_attr($user->last_name); ?>" required />
            </div>
            <button type="submit" class="cinderwell-portal-btn cinderwell-portal-btn--primary"><?php esc_html_e('Update Profile', 'cinderwell-portal'); ?></button>
            <p class="cinderwell-portal-message"></p>
        </form>

        <form class="cinderwell-portal-form cinderwell-portal-change-password">
            <?php wp_nonce_field('cinderwell_portal_profile', 'nonce'); ?>
            <h3><?php esc_html_e('Change Password', 'cinderwell-portal'); ?></h3>
            <div class="cinderwell-portal-field">
                <label for="cinderwell-profile-current"><?php esc_html_e('Current Password', 'cinderwell-portal'); ?></label>
                <input type="password" id="cinderwell-profile-current" name="current_password" required autocomplete="current-password" />
            </div>
            <div class="cinderwell-portal-field">
                <label for="cinderwell-profile-new"><?php esc_html_e('New Password', 'cinderwell-portal'); ?></label>
                <input type="password" id="cinderwell-profile-new" name="new_password" required autocomplete="new-password" minlength="8" />
            </div>
            <button type="submit" class="cinderwell-portal-btn"><?php esc_html_e('Change Password', 'cinderwell-portal'); ?></button>
            <p class="cinderwell-portal-message"></p>
        </form>

        <p style="margin-top: 2rem;">
            <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="cinderwell-portal-btn cinderwell-portal-btn--ghost"><?php esc_html_e('Log out', 'cinderwell-portal'); ?></a>
        </p>
    </div>
<?php endif; ?>
