<?php

/**
 * Meus Anúncios — screen layout (PHASE 008).
 *
 * Structure matches the mockup's #view-anuncios-meus intent (hero + status
 * KPIs + manage table) but sourced entirely from
 * apollo_adverts_get_user_listings() — the same real author+status query the
 * working BuddyPress "Meus Anúncios" tab already uses. Table actions reuse
 * the real, existing renew/delete admin-post handlers plus a new
 * toggle-status handler (includes/buddypress.php), not invented endpoints.
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}

$mna_user_id = get_current_user_id();
$mna_paged   = max(1, (int) get_query_var('paged', 1));
if (isset($_GET['paged'])) {
    $mna_paged = max(1, absint($_GET['paged']));
}

$mna = apollo_adverts_get_user_listings($mna_user_id, $mna_paged);

$mna_status_labels = array(
    'publish' => __('Ativo', 'apollo-adverts'),
    'pending' => __('Pendente', 'apollo-adverts'),
    'draft'   => __('Rascunho', 'apollo-adverts'),
    'expired' => __('Expirado', 'apollo-adverts'),
);
$mna_status_colors = array(
    'publish' => 'var(--accent)',
    'pending' => '#e0a83a',
    'draft'   => 'var(--muted)',
    'expired' => 'var(--accent-sunset-red)',
);

/**
 * @param string $action
 * @param int    $post_id
 * @param string $nonce_key
 * @param array<string,mixed> $extra_args
 * @return string
 */
$mna_action_url = static function (string $action, int $post_id, string $nonce_key, array $extra_args = array()): string {
    $args = array_merge(
        array(
            'action'  => $action,
            'post_id' => $post_id,
        ),
        $extra_args
    );
    return wp_nonce_url(add_query_arg($args, admin_url('admin-post.php')), $nonce_key . $post_id);
};
?>
<div class="mna-screen">

    <div class="mna-hero">
        <p class="mna-kicker"><?php esc_html_e('Marketplace', 'apollo-adverts'); ?></p>
        <h1><?php esc_html_e('Meus Anúncios', 'apollo-adverts'); ?></h1>
        <div class="mna-hero-kpis">
            <div class="mna-hero-kpi"><strong><?php echo esc_html((string) $mna['total']); ?></strong><span><?php esc_html_e('total', 'apollo-adverts'); ?></span></div>
            <div class="mna-hero-kpi"><strong><?php echo esc_html((string) $mna['counts']['publish']); ?></strong><span><?php esc_html_e('ativos', 'apollo-adverts'); ?></span></div>
            <div class="mna-hero-kpi"><strong><?php echo esc_html((string) $mna['counts']['pending']); ?></strong><span><?php esc_html_e('pendentes', 'apollo-adverts'); ?></span></div>
            <div class="mna-hero-kpi"><strong><?php echo esc_html((string) $mna['counts']['draft']); ?></strong><span><?php esc_html_e('rascunhos', 'apollo-adverts'); ?></span></div>
            <div class="mna-hero-kpi"><strong><?php echo esc_html((string) $mna['counts']['expired']); ?></strong><span><?php esc_html_e('expirados', 'apollo-adverts'); ?></span></div>
        </div>
        <div class="mna-hero-actions">
            <a class="btn btn-primary" href="<?php echo esc_url(home_url('/novo-anuncio')); ?>"><i class="ri-add-line"></i> <?php esc_html_e('Criar Anúncio', 'apollo-adverts'); ?></a>
        </div>
        <p class="mna-notice">
            <?php esc_html_e('Apollo conecta pessoas — não processamos pagamentos nem intermediamos entregas. Dúvidas sobre o marketplace, fale com o', 'apollo-adverts'); ?>
            <button type="button" class="mna-notice-link" data-apollo-suporte><?php esc_html_e('Suporte', 'apollo-adverts'); ?></button>.
        </p>
    </div>

    <div class="mna-card">
        <?php if (0 === $mna['total']) : ?>
            <div class="mna-empty">
                <p><?php esc_html_e('Você ainda não possui anúncios.', 'apollo-adverts'); ?></p>
                <a class="btn btn-primary" href="<?php echo esc_url(home_url('/novo-anuncio')); ?>"><?php esc_html_e('Criar primeiro anúncio', 'apollo-adverts'); ?></a>
            </div>
        <?php else : ?>
            <table class="mna-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Imagem', 'apollo-adverts'); ?></th>
                        <th><?php esc_html_e('Título', 'apollo-adverts'); ?></th>
                        <th><?php esc_html_e('Valor Ref.', 'apollo-adverts'); ?></th>
                        <th><?php esc_html_e('Status', 'apollo-adverts'); ?></th>
                        <th><?php esc_html_e('Views', 'apollo-adverts'); ?></th>
                        <th><?php esc_html_e('Ações', 'apollo-adverts'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mna['items'] as $mna_post) :
                        $mna_id      = (int) $mna_post->ID;
                        $mna_status  = (string) $mna_post->post_status;
                        $mna_img     = apollo_adverts_get_main_image($mna_id, 'classified-thumb');
                        $mna_price   = apollo_adverts_get_the_price($mna_id);
                        $mna_views   = (int) get_post_meta($mna_id, '_classified_views', true);
                        $mna_expired = apollo_adverts_is_expired($mna_id);
                        ?>
                        <tr>
                            <td>
                                <?php if ($mna_img) : ?>
                                    <img class="mna-thumb" src="<?php echo esc_url($mna_img); ?>" alt="" />
                                <?php else : ?>
                                    <span class="mna-no-thumb"><i class="ri-image-line"></i></span>
                                <?php endif; ?>
                            </td>
                            <td class="mna-title-cell">
                                <a href="<?php echo esc_url(get_permalink($mna_id)); ?>"><?php echo esc_html(get_the_title($mna_id)); ?></a>
                                <span class="mna-row-date"><?php echo esc_html(get_the_date('d/m/Y', $mna_id)); ?></span>
                            </td>
                            <td><?php echo $mna_price ? esc_html($mna_price) : '—'; ?></td>
                            <td><span class="mna-tag" style="background:<?php echo esc_attr($mna_status_colors[$mna_status] ?? 'var(--muted)'); ?>"><?php echo esc_html($mna_status_labels[$mna_status] ?? ucfirst($mna_status)); ?></span></td>
                            <td><?php echo esc_html((string) $mna_views); ?></td>
                            <td>
                                <div class="mna-actions">
                                    <a class="mna-btn" href="<?php echo esc_url(add_query_arg('edit', $mna_id, home_url('/novo-anuncio'))); ?>" title="<?php esc_attr_e('Editar', 'apollo-adverts'); ?>"><i class="ri-edit-line"></i></a>

                                    <?php if ('publish' === $mna_status) : ?>
                                        <a class="mna-btn" href="<?php echo esc_url($mna_action_url('apollo_toggle_ad_status', $mna_id, 'apollo_toggle_status_', array('to' => 'draft'))); ?>" title="<?php esc_attr_e('Pausar', 'apollo-adverts'); ?>"><i class="ri-eye-off-line"></i></a>
                                    <?php elseif ('draft' === $mna_status) : ?>
                                        <a class="mna-btn" href="<?php echo esc_url($mna_action_url('apollo_toggle_ad_status', $mna_id, 'apollo_toggle_status_', array('to' => 'publish'))); ?>" title="<?php esc_attr_e('Ativar', 'apollo-adverts'); ?>"><i class="ri-eye-line"></i></a>
                                    <?php endif; ?>

                                    <?php if ($mna_expired) : ?>
                                        <a class="mna-btn" href="<?php echo esc_url($mna_action_url('apollo_renew_ad', $mna_id, 'apollo_renew_')); ?>" title="<?php esc_attr_e('Renovar', 'apollo-adverts'); ?>"><i class="ri-refresh-line"></i></a>
                                    <?php endif; ?>

                                    <a class="mna-btn mna-btn-danger" href="<?php echo esc_url($mna_action_url('apollo_delete_ad', $mna_id, 'apollo_delete_')); ?>" title="<?php esc_attr_e('Excluir', 'apollo-adverts'); ?>" onclick="return confirm('<?php echo esc_js(__('Tem certeza que deseja excluir este anúncio?', 'apollo-adverts')); ?>');"><i class="ri-delete-bin-line"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($mna['total_pages'] > 1) : ?>
                <div class="mna-pagination">
                    <?php
                    echo wp_kses_post(
                        paginate_links(
                            array(
                                'base'      => add_query_arg('paged', '%#%'),
                                'format'    => '',
                                'current'   => $mna['paged'],
                                'total'     => $mna['total_pages'],
                                'prev_text' => '‹',
                                'next_text' => '›',
                            )
                        )
                    );
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</div>
