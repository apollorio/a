<?php

namespace Apollo\Remind\Channels;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Email channel — delegates to apollo-email if available, falls back to wp_mail().
 */
class EmailChannel {

    public static function send( object $reminder ): array {
        $user = get_userdata( (int) $reminder->user_id );
        if ( ! $user || empty( $user->user_email ) ) {
            return [ 'success' => false, 'response' => 'No email' ];
        }

        $subject = $reminder->title ?: __( 'Lembrete Apollo::Rio', 'apollo-remind' );
        $body    = $reminder->message;

        if ( ! empty( $reminder->ref_id ) && $reminder->ref_id > 0 ) {
            $url = get_permalink( (int) $reminder->ref_id );
            if ( $url ) {
                $body .= "\n\n" . $url;
            }
        }

        // Check user email preferences before sending
        if ( function_exists( 'apollo_user_channel_allowed' )
             && ! apollo_user_channel_allowed( (int) $reminder->user_id, 'email', 'reminder' ) ) {
            return [ 'success' => true, 'response' => 'Email blocked by user preferences' ];
        }

        // Try apollo-email integration (apollo_send_email: $to, $subject, $template, $data)
        if ( function_exists( 'apollo_send_email' ) ) {
            $result = apollo_send_email(
                $user->user_email,
                $subject,
                'notification',
                [
                    'user_id'     => (int) $reminder->user_id,
                    'user_name'   => $user->display_name,
                    'heading'     => '🔔 ' . $subject,
                    'content'     => wpautop( $body ),
                    'action_url'  => $reminder->ref_id > 0 ? get_permalink( (int) $reminder->ref_id ) : '',
                    'action_text' => __( 'Ver mais', 'apollo-remind' ),
                    'site_name'   => get_bloginfo( 'name' ),
                ]
            );
            return [ 'success' => (bool) $result, 'response' => $result ? 'Sent via apollo-email' : 'apollo-email send failed' ];
        }

        // Fallback: wp_mail
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        $html    = '<div style="font-family:sans-serif;max-width:600px;margin:0 auto">'
                 . '<h2 style="color:#FF8C00">🔔 ' . esc_html( $subject ) . '</h2>'
                 . wpautop( esc_html( $body ) )
                 . '</div>';

        $sent = wp_mail( $user->user_email, $subject, $html, $headers );
        return [ 'success' => $sent, 'response' => $sent ? 'wp_mail sent' : 'wp_mail failed' ];
    }
}

// NotifChannel is in its own file: src/Channels/NotifChannel.php (PSR-4)
