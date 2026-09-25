<?php
namespace Cinderwell_Portal;

class Template_Loader {
    public function __construct() {
        // Template loader is invoked statically via load()
    }

    public static function load($template, $vars = []) {
        $theme_template = get_stylesheet_directory() . '/cinderwell-portal/' . $template . '.php';
        if (file_exists($theme_template)) {
            $file = $theme_template;
        } else {
            $file = CINDERWELL_PORTAL_PATH . 'templates/' . $template . '.php';
        }

        if (!file_exists($file)) {
            return '';
        }

        extract($vars, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }
}
