<?php
namespace Cinderwell_Portal;

class Emails {
    public function __construct() {
        add_filter('wp_mail_content_type', function ($content_type) {
            return 'text/html';
        });
    }

    public static function send_template($template, $to, $vars = []) {
        $settings = Portal::get_settings();
        $templates = $settings['email_templates'];

        if (!isset($templates[$template])) {
            return false;
        }

        $tpl = $templates[$template];

        if ($template === 'rejection' && empty($tpl['enabled'])) {
            return false;
        }

        $subject = self::replace_placeholders($tpl['subject'] ?? '', $vars);
        $body = self::replace_placeholders($tpl['body'] ?? '', $vars);
        $body = nl2br(esc_html($body));
        $body = self::wrap_html($body, $subject);

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        if (!empty($settings['from_email'])) {
            $from_name = $settings['from_name'] ?: get_bloginfo('name');
            $headers[] = 'From: ' . $from_name . ' <' . $settings['from_email'] . '>';
        }

        return wp_mail($to, $subject, $body, $headers);
    }

    public static function replace_placeholders($text, $vars) {
        $defaults = [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'site_name' => get_bloginfo('name'),
            'login_url' => wp_login_url(),
            'approval_url' => admin_url('admin.php?page=cinderwell&tab=portal&subtab=pending'),
            'setup_url' => wp_login_url(),
        ];
        $vars = wp_parse_args($vars, $defaults);
        $vars = apply_filters('cinderwell_portal_email_vars', $vars);
        foreach ($vars as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }

    public static function wrap_html($body, $subject) {
        $site_name = esc_html(get_bloginfo('name'));
        return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>' . esc_html($subject) . '</title></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, sans-serif; line-height: 1.6; color: #1a1a1a; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="border-bottom: 2px solid #b84c00; padding-bottom: 12px; margin-bottom: 24px;">
        <h1 style="margin: 0; font-size: 1.25rem;">' . $site_name . '</h1>
    </div>
    <div style="font-size: 1rem;">' . $body . '</div>
    <div style="border-top: 1px solid #e5e7eb; padding-top: 12px; margin-top: 24px; font-size: 0.875rem; color: #6b7280;">
        This email was sent by ' . $site_name . '.
    </div>
</body>
</html>';
    }
}
