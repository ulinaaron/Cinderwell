<?php
/**
 * Company-branded WordPress login screen.
 *
 * @package Cinderwell_Utilities
 */

namespace Cinderwell_Utilities\Modules;

defined('ABSPATH') || exit;

class Login_Branding {
    private $company = null;
    private $logo_url = null;

    public function __construct($settings = []) {
        add_action('login_enqueue_scripts', [$this, 'print_logo_styles']);
        add_filter('login_headerurl', [$this, 'get_logo_link']);
        add_filter('login_headertext', [$this, 'get_logo_text']);
    }

    public function print_logo_styles() {
        $logo_url = $this->get_logo_url();
        if (!$logo_url) {
            return;
        }
        ?>
        <style id="cinderwell-login-branding">
            body.login #login h1 a {
                width: min(100%, 280px);
                height: 96px;
                background-image: url("<?php echo esc_url($logo_url); ?>") !important;
                background-position: center;
                background-repeat: no-repeat;
                background-size: contain;
            }
        </style>
        <?php
    }

    public function get_logo_link($url) {
        return $this->get_logo_url() ? home_url('/') : $url;
    }

    public function get_logo_text($text) {
        if (!$this->get_logo_url()) {
            return $text;
        }

        $company = $this->get_company();
        return !empty($company['name']) ? $company['name'] : get_bloginfo('name');
    }

    private function get_logo_url() {
        if (null !== $this->logo_url) {
            return $this->logo_url;
        }

        $company = $this->get_company();
        $logo_id = absint($company['logo_id'] ?? 0);
        $mime_type = $logo_id ? (string) get_post_mime_type($logo_id) : '';
        if (!$logo_id || 0 !== strpos($mime_type, 'image/')) {
            $this->logo_url = '';
            return $this->logo_url;
        }

        $this->logo_url = wp_get_attachment_image_url($logo_id, 'full');
        if (!$this->logo_url) {
            // WordPress does not generate image-size metadata for SVG files.
            $this->logo_url = wp_get_attachment_url($logo_id) ?: '';
        }

        return $this->logo_url;
    }

    private function get_company() {
        if (null !== $this->company) {
            return $this->company;
        }

        $enabled = class_exists('Cinderwell\\Addons')
            && \Cinderwell\Addons::is_enabled('company-details')
            && class_exists('Cinderwell\\Company_Details');

        $this->company = $enabled ? \Cinderwell\Company_Details::get_settings() : [];
        return $this->company;
    }
}
