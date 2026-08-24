<?php
/**
 * Comunas — hero (mockup .cmn-hero).
 * Stats are computed from the real rows, never authored.
 * @package Apollo\Groups
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cmn_all     = $cmn_list;
$cmn_members = array_sum( wp_list_pluck( $cmn_all, 'members' ) );
$cmn_mine    = count( array_filter( $cmn_all, static fn( $c ) => '' !== $c['role'] ) );
$fmt = static fn( $n ) => $n >= 1000 ? number_format_i18n( $n / 1000, 1 ) . 'k' : number_format_i18n( $n );
?>
<section class="cmn-hero">
    <div class="cmn-hero-bg" aria-hidden="true">Comuna</div>
    <div class="cmn-hero-inner">
        <div class="tref-sec-lbl"><?php esc_html_e( 'Apollo::Rio · Comunidade aberta', 'apollo-groups' ); ?></div>
        <h1 class="display-text">Comunas<br><span class="thin">&amp; Comunidade</span></h1>
        <p class="cmn-hero-desc"><?php esc_html_e( 'Comuna = comunidade pública. Qualquer pessoa pode entrar e criar a sua.', 'apollo-groups' ); ?></p>
        <div class="cmn-pill-row">
            <span class="cmn-pill"><i class="ri-earth-line"></i> <?php esc_html_e( 'Aberta', 'apollo-groups' ); ?></span>
            <span class="cmn-pill"><i class="ri-user-add-line"></i> <?php esc_html_e( 'Entre livre', 'apollo-groups' ); ?></span>
            <span class="cmn-pill"><i class="ri-add-circle-line"></i> <?php esc_html_e( 'Crie a sua', 'apollo-groups' ); ?></span>
        </div>
        <div class="cmn-stats">
            <div><span class="cmn-stat-num"><?php echo esc_html( (string) count( $cmn_all ) ); ?></span><span class="cmn-stat-lbl"><?php esc_html_e( 'Comunas', 'apollo-groups' ); ?></span></div>
            <div><span class="cmn-stat-num"><?php echo esc_html( $fmt( $cmn_members ) ); ?></span><span class="cmn-stat-lbl"><?php esc_html_e( 'Membros', 'apollo-groups' ); ?></span></div>
            <div><span class="cmn-stat-num"><?php echo esc_html( (string) $cmn_mine ); ?></span><span class="cmn-stat-lbl"><?php esc_html_e( 'As suas', 'apollo-groups' ); ?></span></div>
        </div>
    </div>
</section>
