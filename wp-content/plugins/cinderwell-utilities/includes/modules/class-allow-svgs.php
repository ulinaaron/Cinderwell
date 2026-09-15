<?php
namespace Cinderwell_Utilities\Modules;

class Allow_SVGs {
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;
        add_filter('upload_mimes', [$this, 'allow_svg']);
        add_filter('wp_handle_upload_prefilter', [$this, 'sanitize_svg']);
        add_filter('wp_check_filetype_and_ext', [$this, 'fix_svg_mime'], 10, 4);
    }

    private function user_can() {
        $user = wp_get_current_user();
        $allowed = $this->settings['roles'] ?? ['administrator'];
        return !empty(array_intersect($allowed, (array) $user->roles));
    }

    public function allow_svg($mimes) {
        if ($this->user_can()) {
            $mimes['svg'] = 'image/svg+xml';
        }
        return $mimes;
    }

    public function fix_svg_mime($data, $file, $filename, $mimes) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext === 'svg' && $this->user_can()) {
            $data['ext'] = 'svg';
            $data['type'] = 'image/svg+xml';
        }
        return $data;
    }

    public function sanitize_svg($file) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'svg') {
            return $file;
        }

        if (!$this->user_can()) {
            $file['error'] = 'You are not allowed to upload SVG files.';
            return $file;
        }

        $content = file_get_contents($file['tmp_name']);
        if ($content === false) {
            $file['error'] = 'Could not read SVG file.';
            return $file;
        }

        $sanitized = $this->sanitize_svg_content($content);
        if ($sanitized === false) {
            $file['error'] = 'SVG could not be sanitized.';
            return $file;
        }

        if ($sanitized !== $content) {
            if (false === file_put_contents($file['tmp_name'], $sanitized)) {
                $file['error'] = 'The sanitized SVG could not be saved.';
            }
        }

        return $file;
    }

    private function sanitize_svg_content($content) {
        if (false !== stripos($content, '<!DOCTYPE') || false !== stripos($content, '<!ENTITY')) {
            return false;
        }

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        if (!$doc->loadXML($content, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            libxml_clear_errors();
            return false;
        }
        libxml_clear_errors();

        if (!$doc->documentElement || 'svg' !== strtolower($doc->documentElement->localName)) {
            return false;
        }

        $allowed_elements = [
            'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
            'defs', 'clippath', 'mask', 'lineargradient', 'radialgradient', 'stop', 'use',
            'symbol', 'pattern', 'title', 'desc', 'text', 'tspan',
        ];
        $allowed_attributes = [
            'xmlns', 'xmlns:xlink', 'width', 'height', 'viewbox', 'preserveaspectratio',
            'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-linecap',
            'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset',
            'stroke-opacity', 'opacity', 'transform', 'd', 'points', 'x', 'y', 'x1', 'y1',
            'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'dx', 'dy', 'id', 'class', 'offset',
            'stop-color', 'stop-opacity', 'gradientunits', 'gradienttransform', 'spreadmethod',
            'patternunits', 'patterncontentunits', 'patterntransform', 'clip-path', 'clip-rule',
            'mask', 'href', 'xlink:href', 'role', 'aria-label', 'aria-labelledby', 'focusable',
        ];

        $elements = [];
        foreach ($doc->getElementsByTagName('*') as $element) {
            $elements[] = $element;
        }

        foreach (array_reverse($elements) as $element) {
            if (!in_array(strtolower($element->localName), $allowed_elements, true)) {
                if ($element->parentNode) {
                    $element->parentNode->removeChild($element);
                }
                continue;
            }

            $attributes = [];
            foreach ($element->attributes as $attribute) {
                $attributes[] = $attribute;
            }

            foreach ($attributes as $attribute) {
                $name  = strtolower($attribute->name);
                $value = trim($attribute->value);
                $remove = !in_array($name, $allowed_attributes, true) || 0 === strpos($name, 'on');

                if (in_array($name, ['href', 'xlink:href'], true) && !preg_match('/^#[A-Za-z_][A-Za-z0-9_.:-]*$/', $value)) {
                    $remove = true;
                }

                if (in_array($name, ['fill', 'stroke', 'clip-path', 'mask'], true) && false !== stripos($value, 'url(') && !preg_match('/^url\(#[A-Za-z_][A-Za-z0-9_.:-]*\)$/', $value)) {
                    $remove = true;
                }

                if ($remove) {
                    $element->removeAttributeNode($attribute);
                }
            }
        }

        return $doc->saveXML();
    }
}
