<?php
/**
 * Register module-owned block templates with theme-first precedence.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Module_Templates {

    private $plugin;
    private $templates;

    public function __construct( $plugin, $templates ) {
        $this->plugin    = sanitize_key( $plugin );
        $this->templates = $templates;

        add_action( 'init', [ $this, 'register' ], 20 );

        // WordPress 6.3–6.6 compatibility. Modern WordPress uses the registry.
        if ( ! function_exists( 'register_block_template' ) ) {
            add_filter( 'get_block_file_template', [ $this, 'provide_legacy_template' ], 10, 3 );
        }
    }

    public function register() {
        if ( ! function_exists( 'register_block_template' ) ) {
            return;
        }

        foreach ( $this->templates as $slug => $template ) {
            $content = $this->get_content( $template );
            if ( '' === $content ) {
                continue;
            }
            register_block_template( $this->plugin . '//' . sanitize_key( $slug ), [
                'title'       => $template['title'] ?? $slug,
                'description' => $template['description'] ?? '',
                'content'     => $content,
                'post_types'  => $template['post_types'] ?? [],
                'plugin'      => $this->plugin,
            ] );
        }
    }

    public function provide_legacy_template( $block_template, $id, $template_type ) {
        if ( $block_template || 'wp_template' !== $template_type ) {
            return $block_template;
        }
        $parts = explode( '//', $id, 2 );
        $slug  = $parts[1] ?? '';
        if ( empty( $this->templates[ $slug ] ) ) {
            return $block_template;
        }
        $template = $this->templates[ $slug ];
        $content  = $this->get_content( $template );
        if ( '' === $content ) {
            return $block_template;
        }

        $result              = new \WP_Block_Template();
        $result->id          = get_stylesheet() . '//' . $slug;
        $result->theme       = get_stylesheet();
        $result->slug        = $slug;
        $result->type        = 'wp_template';
        $result->source      = 'plugin';
        $result->origin      = 'plugin';
        $result->plugin      = $this->plugin;
        $result->title       = $template['title'] ?? $slug;
        $result->description = $template['description'] ?? '';
        $result->content     = $content;
        $result->status      = 'publish';
        $result->has_theme_file = false;
        $result->is_custom      = false;

        return $result;
    }

    private function get_content( $template ) {
        $path = $template['path'] ?? '';
        return $path && is_readable( $path ) ? (string) file_get_contents( $path ) : '';
    }
}
