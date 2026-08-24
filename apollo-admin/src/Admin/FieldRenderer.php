<?php
/**
 * Field Renderer — renders individual form fields by type.
 *
 * Supported types: text, email, number, color, toggle, select, textarea.
 *
 * @package Apollo\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class FieldRenderer {

    /**
     * Render a single form field based on its type.
     */
    public static function render( string $id, string $key, array $field, mixed $value ): void {
        $name = 'apollo_settings[' . esc_attr( $key ) . ']';

        switch ( $field['type'] ) {

            case 'text':
            case 'email':
                printf(
                    '<input type="%s" id="%s" name="%s" value="%s" class="regular-text apollo-field" />',
                    esc_attr( $field['type'] ),
                    esc_attr( $id ),
                    esc_attr( $name ),
                    esc_attr( (string) $value )
                );
                break;

            case 'number':
                printf(
                    '<input type="number" id="%s" name="%s" value="%s" class="small-text apollo-field" />',
                    esc_attr( $id ),
                    esc_attr( $name ),
                    esc_attr( (string) $value )
                );
                break;

            case 'color':
                printf(
                    '<input type="color" id="%s" name="%s" value="%s" class="apollo-color-field" />',
                    esc_attr( $id ),
                    esc_attr( $name ),
                    esc_attr( (string) $value )
                );
                break;

            case 'toggle':
                $checked = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
                printf(
                    '<label class="apollo-toggle">
                        <input type="hidden" name="%s" value="0" />
                        <input type="checkbox" id="%s" name="%s" value="1" %s />
                        <span class="apollo-toggle-slider"></span>
                    </label>',
                    esc_attr( $name ),
                    esc_attr( $id ),
                    esc_attr( $name ),
                    checked( $checked, true, false )
                );
                break;

            case 'select':
                printf( '<select id="%s" name="%s" class="apollo-field">', esc_attr( $id ), esc_attr( $name ) );
                foreach ( ( $field['options'] ?? array() ) as $opt_val => $opt_label ) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr( $opt_val ),
                        selected( $value, $opt_val, false ),
                        esc_html( $opt_label )
                    );
                }
                echo '</select>';
                break;

            case 'textarea':
                printf(
                    '<textarea id="%s" name="%s" rows="5" class="large-text apollo-field">%s</textarea>',
                    esc_attr( $id ),
                    esc_attr( $name ),
                    esc_textarea( (string) $value )
                );
                break;
        }
    }
}
