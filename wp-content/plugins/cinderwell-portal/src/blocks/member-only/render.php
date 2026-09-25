<?php
$is_member = \Cinderwell_Portal\Portal::is_member();

$fallback_message = $attributes['fallbackMessage'] ?? 'This content is for members only.';
$show_login = $attributes['fallbackShowLoginLink'] ?? true;

if ($is_member) {
    echo '<div class="wp-block-cinderwell-portal-member-only">';
    echo do_blocks($content ?? '');
    echo '</div>';
} else {
    echo '<div class="cinderwell-portal-gate">';
    if (!is_user_logged_in()) {
        echo '<p>' . esc_html($fallback_message) . '</p>';
        if ($show_login) {
            echo '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '" class="cinderwell-portal-btn cinderwell-portal-btn--primary">' . esc_html__('Log in', 'cinderwell-portal') . '</a></p>';
        }
    } else {
        echo '<p>' . esc_html__('This content is for members only.', 'cinderwell-portal') . '</p>';
    }
    echo '</div>';
}
