<?php
/**
 * Shared schema-driven admin form fields.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Admin_Fields {

    /**
     * Render a WordPress form table from field definitions.
     *
     * @param array  $fields      Keyed field definitions.
     * @param array  $values      Current values.
     * @param string $name_prefix Form name prefix.
     * @param string $id_prefix   HTML id prefix.
     */
    public static function render_table( $fields, $values, $name_prefix, $id_prefix = 'cinderwell' ) {
        echo '<table class="form-table" role="presentation">';
        foreach ( self::normalize_fields( $fields ) as $key => $field ) {
            self::render_row( $key, $field, $values[ $key ] ?? $field['default'], $name_prefix, $id_prefix );
        }
        echo '</table>';
    }

    /**
     * Sanitize submitted values using the same field schema used to render them.
     */
    public static function sanitize_values( $fields, $submitted ) {
        $submitted = is_array( $submitted ) ? $submitted : [];
        $values    = [];

        foreach ( self::normalize_fields( $fields ) as $key => $field ) {
            $value = $submitted[ $key ] ?? ( in_array( $field['type'], [ 'checkbox', 'checkboxes' ], true ) ? [] : '' );

            if ( is_callable( $field['sanitize_callback'] ) ) {
                $values[ $key ] = call_user_func( $field['sanitize_callback'], $value, $field );
                continue;
            }

            switch ( $field['type'] ) {
                case 'checkbox':
                    $values[ $key ] = ! empty( $value );
                    break;
                case 'checkboxes':
                    $allowed        = array_map( 'strval', array_keys( $field['options'] ) );
                    $selected       = array_map( 'strval', (array) $value );
                    $values[ $key ] = array_values( array_intersect( $allowed, $selected ) );
                    break;
                case 'select':
                case 'segmented':
                case 'color_chips':
                    $value          = sanitize_key( $value );
                    $values[ $key ] = array_key_exists( $value, $field['options'] ) ? $value : (string) $field['default'];
                    break;
                case 'email':
                    $values[ $key ] = sanitize_email( $value );
                    break;
                case 'url':
                    $values[ $key ] = esc_url_raw( $value );
                    break;
                case 'slug':
                    $values[ $key ] = sanitize_title( $value );
                    break;
                case 'textarea':
                    $values[ $key ] = sanitize_textarea_field( $value );
                    break;
                case 'media':
                    $values[ $key ] = absint( $value );
                    break;
                case 'repeater':
                    $rows = [];
                    foreach ( array_slice( (array) $value, 0, $field['max_items'] ) as $row ) {
                        $row = self::sanitize_values( $field['fields'], is_array( $row ) ? $row : [] );
                        if ( array_filter( $row, static function ( $item ) {
                            return ! in_array( $item, [ '', [], false, 0 ], true );
                        } ) ) {
                            $rows[] = $row;
                        }
                    }
                    $values[ $key ] = $rows;
                    break;
                default:
                    $values[ $key ] = sanitize_text_field( $value );
                    break;
            }
        }

        return $values;
    }

    public static function normalize_fields( $fields ) {
        $normalized = [];
        foreach ( (array) $fields as $key => $field ) {
            $key = sanitize_key( $key );
            if ( ! $key || ! is_array( $field ) || empty( $field['label'] ) ) {
                continue;
            }
            $normalized[ $key ] = wp_parse_args( $field, [
                'type'              => 'text',
                'label'             => $key,
                'description'       => '',
                'options'           => [],
                'default'           => '',
                'class'             => 'regular-text',
                'sanitize_callback' => null,
                'fields'            => [],
                'max_items'         => 20,
                'dynamic'           => true,
            ] );
            $normalized[ $key ]['max_items'] = max( 1, absint( $normalized[ $key ]['max_items'] ) );
            if ( 'repeater' === $normalized[ $key ]['type'] ) {
                $normalized[ $key ]['fields'] = self::normalize_fields( $normalized[ $key ]['fields'] );
            }
        }
        return $normalized;
    }

    private static function render_row( $key, $field, $value, $name_prefix, $id_prefix ) {
        $id   = sanitize_html_class( $id_prefix . '-' . $key );
        $name = $name_prefix . '[' . $key . ']';
        echo '<tr>';
        echo '<th scope="row">';
        if ( ! in_array( $field['type'], [ 'checkbox', 'checkboxes', 'segmented', 'color_chips' ], true ) ) {
            echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';
        } else {
            echo esc_html( $field['label'] );
        }
        echo '</th><td>';

        if ( 'checkbox' === $field['type'] ) {
            echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( ! empty( $value ), true, false ) . '> ' . esc_html( $field['checkbox_label'] ?? $field['label'] ) . '</label>';
        } elseif ( 'checkboxes' === $field['type'] ) {
            foreach ( $field['options'] as $option_value => $option_label ) {
                echo '<label style="display:block;margin-bottom:8px;"><input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $option_value ) . '" ' . checked( in_array( (string) $option_value, array_map( 'strval', (array) $value ), true ), true, false ) . '> ' . esc_html( $option_label ) . '</label>';
            }
        } elseif ( 'select' === $field['type'] ) {
            echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
            foreach ( $field['options'] as $option_value => $option_label ) {
                echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( (string) $value, (string) $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
            }
            echo '</select>';
        } elseif ( 'segmented' === $field['type'] ) {
            self::render_segmented_field( $id, $name, $field, $value );
        } elseif ( 'color_chips' === $field['type'] ) {
            self::render_color_chips_field( $id, $name, $field, $value );
        } elseif ( 'textarea' === $field['type'] ) {
            echo '<textarea class="' . esc_attr( $field['class'] ) . '" rows="5" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
        } elseif ( 'media' === $field['type'] ) {
            self::render_media_field( $id, $name, absint( $value ) );
        } elseif ( 'repeater' === $field['type'] ) {
            self::render_repeater_field( $id, $name, $field, (array) $value );
        } else {
            $input_type = in_array( $field['type'], [ 'email', 'url', 'tel', 'date', 'number' ], true ) ? $field['type'] : 'text';
            echo '<input class="' . esc_attr( $field['class'] ) . '" type="' . esc_attr( $input_type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
        }

        if ( $field['description'] ) {
            echo '<p class="description">' . wp_kses_post( $field['description'] ) . '</p>';
        }
        echo '</td></tr>';
    }

    private static function render_segmented_field( $id, $name, $field, $value ) {
        echo '<fieldset class="cw-admin-segmented" id="' . esc_attr( $id ) . '">';
        echo '<legend class="screen-reader-text">' . esc_html( $field['label'] ) . '</legend>';
        foreach ( $field['options'] as $option_value => $option ) {
            $label = is_array( $option ) ? ( $option['label'] ?? $option_value ) : $option;
            echo '<label><input class="screen-reader-text" type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $option_value ) . '" ' . checked( (string) $value, (string) $option_value, false ) . '><span class="cw-admin-segmented__option">' . esc_html( $label ) . '</span></label>';
        }
        echo '</fieldset>';
    }

    private static function render_color_chips_field( $id, $name, $field, $value ) {
        echo '<fieldset class="cw-admin-color-chips" id="' . esc_attr( $id ) . '">';
        echo '<legend class="screen-reader-text">' . esc_html( $field['label'] ) . '</legend>';
        foreach ( $field['options'] as $option_value => $option ) {
            $label = is_array( $option ) ? ( $option['label'] ?? $option_value ) : $option;
            $color = is_array( $option ) ? ( $option['color'] ?? '#ffffff' ) : '#ffffff';
            echo '<label title="' . esc_attr( $label ) . '">';
            echo '<input class="screen-reader-text" type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $option_value ) . '" ' . checked( (string) $value, (string) $option_value, false ) . '>';
            echo '<span class="cw-admin-color-chip" style="--cw-admin-chip-color:' . esc_attr( $color ) . '"><span class="screen-reader-text">' . esc_html( $label ) . '</span></span>';
            echo '</label>';
        }
        echo '</fieldset>';
    }

    private static function render_media_field( $id, $name, $attachment_id ) {
        $image = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
        echo '<div class="cw-admin-media" data-cw-media>';
        echo '<input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $attachment_id ) . '" data-cw-media-input>';
        echo '<div class="cw-admin-media__preview" data-cw-media-preview>';
        if ( $image ) {
            echo '<img src="' . esc_url( $image ) . '" alt="">';
        }
        echo '</div><div class="cw-admin-media__actions">';
        echo '<button type="button" class="button" data-cw-media-select>' . esc_html__( 'Choose image', 'cinderwell' ) . '</button> ';
        echo '<button type="button" class="button-link-delete" data-cw-media-remove' . ( $attachment_id ? '' : ' hidden' ) . '>' . esc_html__( 'Remove', 'cinderwell' ) . '</button>';
        echo '</div></div>';
    }

    private static function render_repeater_field( $id, $name, $field, $rows ) {
        echo '<div class="cw-admin-repeater" id="' . esc_attr( $id ) . '" data-cw-repeater data-max-items="' . esc_attr( $field['max_items'] ) . '">';
        echo '<div data-cw-repeater-rows>';
        foreach ( array_values( $rows ) as $index => $row ) {
            self::render_repeater_row( $name, $field['fields'], $index, $row );
        }
        echo '</div>';
        echo '<template data-cw-repeater-template>';
        self::render_repeater_row( $name, $field['fields'], '__index__', [] );
        echo '</template>';
        echo '<button type="button" class="button" data-cw-repeater-add>' . esc_html__( 'Add item', 'cinderwell' ) . '</button>';
        echo '</div>';
    }

    private static function render_repeater_row( $name, $fields, $index, $values ) {
        echo '<div class="cw-admin-repeater__row" data-cw-repeater-row>';
        foreach ( $fields as $key => $field ) {
            $field_name = $name . '[' . $index . '][' . $key . ']';
            $field_id   = sanitize_html_class( $name . '-' . $index . '-' . $key );
            $value      = $values[ $key ] ?? $field['default'];
            $input_type = in_array( $field['type'], [ 'email', 'url', 'tel', 'number' ], true ) ? $field['type'] : 'text';
            echo '<label><span>' . esc_html( $field['label'] ) . '</span><input class="regular-text" type="' . esc_attr( $input_type ) . '" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '"></label>';
        }
        echo '<button type="button" class="button-link-delete" data-cw-repeater-remove>' . esc_html__( 'Remove', 'cinderwell' ) . '</button>';
        echo '</div>';
    }
}
