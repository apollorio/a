<?php
/**
 * Partial: `.icon-user-access` — Visibility badge for sensitive/restricted tabs.
 *
 * Replaces the old `.lock` span pattern. Renders a small floating eye icon
 * to signal that the tab contains sensitive or restricted content.
 *
 * Usage:
 *   gestor_icon_user_access()                   → defaults (eye-line, visible state)
 *   gestor_icon_user_access('ri-eye-off-line')  → hidden/locked state
 *   gestor_icon_user_access('ri-eye-line', 'Somente Gestor + ADM') → with tooltip
 *
 * The function is defined once (function_exists guard) so this file can be
 * required multiple times without fatal errors.
 *
 * Animation: GSAP morphism reveal — see gestor.animations.js § icon-user-access
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'gestor_icon_user_access' ) ) {
    /**
     * Render the `.icon-user-access` badge.
     *
     * @param string $icon    RemixIcon class. Default 'ri-eye-line'.
     * @param string $tooltip Optional tooltip label.
     * @param string $state   'visible' | 'hidden' | 'restricted'. Default 'visible'.
     */
    function gestor_icon_user_access(
        string $icon    = 'ri-eye-line',
        string $tooltip = '',
        string $state   = 'visible'
    ): void {
        $state_attr  = esc_attr( $state );
        $icon_attr   = esc_attr( $icon );
        $title_attr  = $tooltip ? ' title="' . esc_attr( $tooltip ) . '"' : '';
        $tooltip_attr = $tooltip ? ' data-tooltip="' . esc_attr( $tooltip ) . '"' : '';

        printf(
            '<span class="icon-user-access icon-user-access--%s" data-state="%s"%s%s aria-hidden="true"><i class="%s"></i></span>',
            $state_attr,
            $state_attr,
            $title_attr,
            $tooltip_attr,
            $icon_attr
        );
    }
}

/*
 * When included directly as a template file (not via function call),
 * render in the default "visible" state. The caller can pass context
 * vars via extract() or set_query_var() before including.
 *
 * Supported context vars:
 *   $icon_ua    string   RemixIcon class (default: 'ri-eye-line')
 *   $tooltip_ua string   Tooltip text
 *   $state_ua   string   'visible' | 'hidden' | 'restricted'
 */
if ( isset( $icon_ua ) || isset( $tooltip_ua ) || isset( $state_ua ) ) {
    gestor_icon_user_access(
        $icon_ua    ?? 'ri-eye-line',
        $tooltip_ua ?? '',
        $state_ua   ?? 'visible'
    );
}
