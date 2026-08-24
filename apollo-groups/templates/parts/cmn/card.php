<?php
/**
 * Comunas — one community card.
 *
 * Uses the Design System's .gallery-card component (ds-components.css), the
 * same one the mockup uses here — not a bespoke card.
 *
 * Expects: $c (row from apollo_cmn_list()).
 * @package Apollo\Groups
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$joined = '' !== $c['role'];
?>
<div class="gallery-card" data-cm="<?php echo esc_attr( (string) $c['id'] ); ?>">
    <?php if ( $c['cover'] ) : ?>
        <img loading="lazy" decoding="async" src="<?php echo esc_url( $c['cover'] ); ?>" alt="<?php echo esc_attr( $c['name'] ); ?>">
    <?php endif; ?>
    <div class="gallery-card-body">
        <div class="flex-row" style="gap:6px;margin-bottom:6px;">
            <span class="tag tag-success"><?php esc_html_e( 'Pública', 'apollo-groups' ); ?></span>
            <?php if ( $joined ) : ?><span class="tag tag-accent"><?php echo esc_html( $c['role'] ); ?></span><?php endif; ?>
        </div>
        <h4><?php echo esc_html( $c['name'] ); ?></h4>
        <p><?php echo esc_html( wp_trim_words( $c['desc'], 18, '…' ) ); ?></p>
        <div class="flex-between" style="margin-top:10px;gap:8px;">
            <span class="txt-mono" style="font-size:calc(var(--fs-u, 1) * 11px);color:var(--muted);">
                <i class="ri-user-3-line"></i> <?php echo esc_html( number_format_i18n( $c['members'] ) ); ?>
            </span>
            <?php if ( $joined ) : ?>
                <a class="btn btn-sm btn-secondary" href="<?php echo esc_url( $c['url'] ); ?>"><?php esc_html_e( 'Abrir', 'apollo-groups' ); ?></a>
            <?php elseif ( is_user_logged_in() ) : ?>
                <button type="button" class="btn btn-sm btn-primary" data-cmn-join="<?php echo esc_attr( (string) $c['id'] ); ?>"><?php esc_html_e( 'Entrar', 'apollo-groups' ); ?></button>
            <?php else : ?>
                <a class="btn btn-sm btn-primary" href="<?php echo esc_url( home_url( '/acesso?redirect=' . rawurlencode( $c['url'] ) ) ); ?>"><?php esc_html_e( 'Entrar', 'apollo-groups' ); ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>
