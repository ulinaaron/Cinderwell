<?php
namespace Cinderwell_Performance;

class Performance {
    public function __construct() {
        add_action('template_redirect', [$this, 'start_buffer']);
    }

    public function start_buffer() {
        if (is_admin()) return;
        if (defined('DOING_AJAX') && DOING_AJAX) return;

        $settings = Settings::get_settings();
        if (empty($settings['performance']['enabled'])) return;

        ob_start([$this, 'process_output']);
    }

    public function process_output($html) {
        if (empty($html)) return $html;

        $settings = Settings::get_settings();
        $perf = $settings['performance'];

        try {
            if (!empty($perf['html_cleanup'])) {
                $html = $this->cleanup_html($html);
            }

            if (!empty($perf['image_async_decoding']) || !empty($perf['image_lazy_loading']) || !empty($perf['image_fetchpriority'])) {
                $html = $this->process_images($html, $perf);
            }

            if (!empty($perf['image_width_height'])) {
                $html = $this->inject_dimensions($html);
            }
        } catch (\Exception $e) {
            error_log('Cinderwell Performance error: ' . $e->getMessage());
            return $html;
        }

        return $html;
    }

    private function cleanup_html($html) {
        $html = preg_replace('/\s+type=[\'"]text\/javascript[\'"]/i', '', $html);
        $html = preg_replace('/\s+type=[\'"]text\/css[\'"]/i', '', $html);
        $html = preg_replace('/<p>\s*<\/p>/i', '', $html);
        $html = preg_replace('/<p>\s*(<img[^>]*>)\s*<\/p>/i', '$1', $html);
        $html = preg_replace('/<p>\s*(<figure[^>]*>.*?<\/figure>)\s*<\/p>/is', '$1', $html);
        $html = preg_replace('/[ \t]+(\r\n|\n|\r)/', '$1', $html);
        return $html;
    }

    private function process_images($html, $perf) {
        $lcp_image = $this->detect_lcp_image($html);

        return preg_replace_callback('/<img\s+([^>]*)>/i', function ($matches) use ($perf, $lcp_image) {
            $attrs = $matches[1];
            $src_match = [];
            preg_match('/src=[\'"]([^\'"]+)[\'"]/i', $attrs, $src_match);
            $src = $src_match[1] ?? '';

            if (empty($src)) return $matches[0];

            $is_lcp = ($src === $lcp_image);

            if (!empty($perf['image_async_decoding']) && !preg_match('/decoding=/i', $attrs)) {
                $attrs .= ' decoding="async"';
            }

            if (!empty($perf['image_lazy_loading']) && !$is_lcp && !preg_match('/loading=/i', $attrs)) {
                $attrs .= ' loading="lazy"';
            }

            if (!empty($perf['image_fetchpriority']) && $is_lcp && !preg_match('/fetchpriority=/i', $attrs)) {
                $attrs .= ' fetchpriority="high"';
            }

            return '<img ' . trim($attrs) . '>';
        }, $html);
    }

    private function detect_lcp_image($html) {
        preg_match_all('/<img\s+[^>]*src=[\'"]([^\'"]+)[\'"][^>]*>/i', $html, $matches);
        if (empty($matches[1])) return '';

        foreach ($matches[1] as $i => $src) {
            $img_tag = $matches[0][$i];
            if (preg_match('/\bwidth=[\'"](\d+)[\'"]/i', $img_tag, $w) && $w[1] < 100) {
                continue;
            }
            if (preg_match('/\bheight=[\'"](\d+)[\'"]/i', $img_tag, $h) && $h[1] < 100) {
                continue;
            }
            return $src;
        }

        return '';
    }

    private function inject_dimensions($html) {
        return preg_replace_callback('/<img\s+([^>]*?)>/i', function ($matches) {
            $attrs = $matches[1];

            if (preg_match('/\bwidth=/i', $attrs) && preg_match('/\bheight=/i', $attrs)) {
                return $matches[0];
            }

            $src_match = [];
            if (!preg_match('/src=[\'"]([^\'"]+)[\'"]/i', $attrs, $src_match)) {
                return $matches[0];
            }

            $src = $src_match[1];
            $site_url = home_url();

            if (!empty($src) && strpos($src, $site_url) === false && !preg_match('/^\//i', $src)) {
                return $matches[0];
            }

            $attachment_id = attachment_url_to_postid($src);
            if ($attachment_id) {
                $metadata = wp_get_attachment_metadata($attachment_id);
                if (!empty($metadata['width']) && !empty($metadata['height'])) {
                    if (!preg_match('/\bwidth=/i', $attrs)) {
                        $attrs .= ' width="' . intval($metadata['width']) . '"';
                    }
                    if (!preg_match('/\bheight=/i', $attrs)) {
                        $attrs .= ' height="' . intval($metadata['height']) . '"';
                    }
                }
            }

            return '<img ' . trim($attrs) . '>';
        }, $html);
    }
}
