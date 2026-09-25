<?php
/**
 * Block loader — auto-discovers and registers all blocks from build directory.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Block_Loader {

    public function __construct() {
        add_filter( 'block_categories_all', [ $this, 'register_category' ], 100 );
        add_filter( 'block_type_metadata', [ $this, 'normalize_block_category' ] );
        add_filter( 'block_type_metadata', [ $this, 'allow_mega_menu_in_navigation' ] );
        add_filter( 'block_type_metadata_settings', [ $this, 'add_responsive_visibility_attribute' ], 20, 2 );
        add_action( 'init', [ $this, 'register_blocks' ] );
    }

    /**
     * Organize core and add-on blocks into Cinderwell's functional groups.
     *
     * @param array $metadata Block metadata.
     * @return array
     */
    public function normalize_block_category( $metadata ) {
        $group = Block_Library::group_for_block( $metadata['name'] ?? '' );
        if ( $group ) {
            $metadata['category'] = $group;
        }

        return $metadata;
    }

    /**
     * Give every Cinderwell block the shared responsive visibility contract.
     *
     * @param array $settings Registered block settings.
     * @param array $metadata Block metadata.
     * @return array
     */
    public function add_responsive_visibility_attribute( $settings, $metadata ) {
        if ( empty( $metadata['name'] ) || 0 !== strpos( $metadata['name'], 'cinderwell/' ) ) {
            return $settings;
        }

        $settings['attributes']['responsiveVisibility'] = [
            'type'    => 'object',
            'default' => [],
        ];

        return $settings;
    }

    /**
     * Expose the Cinderwell mega menu as a native Navigation child.
     *
     * @param array $metadata Block metadata.
     * @return array
     */
    public function allow_mega_menu_in_navigation( $metadata ) {
        if ( 'core/navigation' !== ( $metadata['name'] ?? '' ) ) {
            return $metadata;
        }

        $allowed_blocks = $metadata['allowedBlocks'] ?? [];
        if ( ! in_array( 'cinderwell/mega-menu', $allowed_blocks, true ) ) {
            $allowed_blocks[] = 'cinderwell/mega-menu';
        }

        $metadata['allowedBlocks'] = $allowed_blocks;
        return $metadata;
    }

    /**
     * Register Cinderwell's functional inserter groups.
     */
    public function register_category( $categories ) {
        $groups = Block_Library::get_groups();
        $categories = array_values(
            array_filter(
                $categories,
                static function ( $category ) use ( $groups ) {
                    $slug = $category['slug'] ?? '';
                    return 'cinderwell' !== $slug && ! isset( $groups[ $slug ] );
                }
            )
        );

        $functional = [];
        foreach ( $groups as $slug => $group ) {
            $functional[] = [
                'slug'  => $slug,
                'title' => $group['title'],
                'icon'  => $group['icon'] ?? null,
            ];
        }

        return array_merge( $functional, $categories );
    }

    public function register_blocks() {
        // Register blocks from build directory.
        $blocks_dir = CINDERWELL_BUILD_DIR . 'blocks/';
        if ( is_dir( $blocks_dir ) ) {
            $blocks        = scandir( $blocks_dir );
            $module_blocks = (array) apply_filters( 'cinderwell_module_blocks', [
                'company-details' => 'company-details',
                'utility-bar'     => 'company-details',
            ] );
            foreach ( $blocks as $block ) {
                if ( '.' === $block || '..' === $block ) {
                    continue;
                }
                if ( isset( $module_blocks[ $block ] ) && ! Addons::is_enabled( $module_blocks[ $block ] ) ) {
                    continue;
                }
                $block_path = $blocks_dir . $block;
                if ( ! is_dir( $block_path ) ) {
                    continue;
                }
                $metadata_file = $block_path . '/block.json';
                if ( file_exists( $metadata_file ) ) {
                    register_block_type( $block_path );
                }
            }
        }

        // Register atoms from build directory.
        $atoms_dir = CINDERWELL_BUILD_DIR . 'atoms/';
        if ( is_dir( $atoms_dir ) ) {
            $atoms = scandir( $atoms_dir );
            foreach ( $atoms as $atom ) {
                if ( '.' === $atom || '..' === $atom ) {
                    continue;
                }
                $atom_path = $atoms_dir . $atom;
                if ( ! is_dir( $atom_path ) ) {
                    continue;
                }
                $metadata_file = $atom_path . '/block.json';
                if ( file_exists( $metadata_file ) ) {
                    register_block_type( $atom_path );
                }
            }
        }

        /**
         * Fires after Cinderwell blocks are registered.
         */
        do_action( 'cinderwell_register_blocks' );
    }
}
