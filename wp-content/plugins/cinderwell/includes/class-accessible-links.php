<?php
/**
 * Improve generic Cinderwell link labels without changing saved block markup.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Accessible_Links {

    public function __construct() {
        add_filter( 'render_block_cinderwell/card-grid', [ $this, 'label_card_links' ], 40, 2 );
    }

    /**
     * Give generic card CTAs a card-specific accessible name.
     */
    public function label_card_links( $block_content, $block ) {
        if ( '' === $block_content || ! class_exists( '\\WP_HTML_Tag_Processor' ) ) {
            return $block_content;
        }

        $cards = $block['attrs']['cards'] ?? [];
        if ( empty( $cards ) ) {
            return $block_content;
        }

        $processor = new \WP_HTML_Tag_Processor( $block_content );

        foreach ( $cards as $card ) {
            $button_text = $this->plain_text( $card['buttonText'] ?? '' );
            if ( '' === $button_text ) {
                continue;
            }

            if ( ! $processor->next_tag( 'a' ) ) {
                break;
            }

            if ( $processor->get_attribute( 'aria-label' ) || ! $this->is_generic_label( $button_text ) ) {
                continue;
            }

            $title = $this->plain_text( $card['title'] ?? '' );
            if ( '' !== $title ) {
                $processor->set_attribute(
                    'aria-label',
                    sprintf(
                        /* translators: 1: link label, 2: card title. */
                        __( '%1$s about %2$s', 'cinderwell' ),
                        $button_text,
                        $title
                    )
                );
            }
        }

        return $processor->get_updated_html();
    }

    private function plain_text( $value ) {
        return trim( wp_strip_all_tags( html_entity_decode( (string) $value, ENT_QUOTES, get_bloginfo( 'charset' ) ) ) );
    }

    private function is_generic_label( $label ) {
        $label = strtolower( trim( $label ) );

        return in_array(
            $label,
            [ 'learn more', 'read more', 'view more', 'more', 'details', 'view details' ],
            true
        );
    }
}
