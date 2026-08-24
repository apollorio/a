<?php

/**
 * Template: Editor do Hub — /editar-hub
 *
 * Tipo: Blank Canvas (zero theme — padrão apollo-login)
 * Layout: Dual-panel mobile-first (controles ← → preview)
 * Ícones: <i class="ri-*"> — CDN MutationObserver auto-renderiza
 *
 * @package Apollo\Hub
 */

if (! defined('ABSPATH')) {
    exit;
}

// Segurança: só usuário logado
if (! is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/' . APOLLO_HUB_EDIT_SLUG)));
    exit;
}

// Auto-provision hub
$hub_post_id = apollo_hub_ensure_current_user_hub();
if (is_wp_error($hub_post_id)) {
    wp_die(esc_html($hub_post_id->get_error_message()), 'Erro', array('response' => 403));
}

$hub_data     = apollo_hub_get_data($hub_post_id);
$hub_post     = get_post($hub_post_id);
$hub_username = $hub_post->post_name;
$hub_url      = get_permalink($hub_post_id);
$current_user = wp_get_current_user();

wp_enqueue_style('apollo-hub');
wp_enqueue_script('apollo-hub');
wp_enqueue_script('apollo-hub-builder');

$hub_v = defined('APOLLO_HUB_VERSION') ? APOLLO_HUB_VERSION : '1.0.0';

ob_start();
?>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?php echo esc_url(APOLLO_HUB_URL . 'assets/css/hub.css?v=' . $hub_v); ?>">
    <script src="<?php echo esc_url(APOLLO_HUB_URL . 'assets/js/hub.js?v=' . $hub_v); ?>" defer></script>
    <script src="<?php echo esc_url(APOLLO_HUB_URL . 'assets/js/hub-builder.js?v=' . $hub_v); ?>" defer></script>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => __('Editar Hub', 'apollo-hub') . ' — ' . $current_user->display_name,
            'extra_head' => $extra_head,
            'html_class' => 'dark-mode',
            'html_attrs' => 'data-hub-theme="' . esc_attr($hub_data['theme']) . '"',
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="dark-mode" data-hub-theme="<?php echo esc_attr($hub_data['theme']); ?>">
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
?>
</head>

<body class="apollo-hub-builder-page">

    <div class="hub-builder" id="hub-builder"
        data-username="<?php echo esc_attr($hub_username); ?>"
        data-hub-url="<?php echo esc_url($hub_url); ?>"
        data-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>"
        data-rest-url="<?php echo esc_url_raw(rest_url(APOLLO_HUB_REST_NAMESPACE)); ?>">

        <!-- ═══ TOOLBAR ═══ -->
        <header class="hub-builder__toolbar">
            <div class="hub-builder__toolbar-left">
                <a href="<?php echo esc_url(home_url()); ?>" class="hub-builder__back" aria-label="<?php esc_attr_e('Voltar', 'apollo-hub'); ?>">
                    <i class="ri-arrow-left-line" aria-hidden="true"></i>
                </a>
                <span class="hub-builder__title">
                    <i class="ri-link-m" aria-hidden="true"></i>
                    HUB::rio
                </span>
            </div>
            <div class="hub-builder__toolbar-right">
                <!-- Botões de preview (mobile/PC) -->
                <div class="hub-preview-mode-btns">
                    <button class="hub-btn-icon js-panel-toggle" type="button"
                        data-tooltip="<?php esc_attr_e('Pré-visualizar', 'apollo-hub'); ?>">
                        <i class="ri-smartphone-line" aria-hidden="true"></i>
                    </button>
                    <button class="hub-btn-icon js-preview-mobile" type="button"
                        data-tooltip="<?php esc_attr_e('Mobile', 'apollo-hub'); ?>">
                        <i class="ri-phone-line" aria-hidden="true"></i>
                    </button>
                    <button class="hub-btn-icon js-preview-pc" type="button"
                        data-tooltip="<?php esc_attr_e('Computador', 'apollo-hub'); ?>">
                        <i class="ri-computer-line" aria-hidden="true"></i>
                    </button>
                    <a href="<?php echo esc_url($hub_url); ?>" class="hub-btn-icon" target="_blank" rel="noopener noreferrer"
                        data-tooltip="<?php esc_attr_e('Abrir Hub', 'apollo-hub'); ?>">
                        <i class="ri-external-link-line" aria-hidden="true"></i>
                    </a>
                </div>
                <button class="hub-btn-icon hub-btn-icon--save js-hub-save" type="button"
                    data-tooltip="<?php esc_attr_e('Salvar', 'apollo-hub'); ?>">
                    <span class="hub-builder__save-btn-text">
                        <i class="ri-save-line" aria-hidden="true"></i>
                        <?php esc_html_e('Salvar', 'apollo-hub'); ?>
                    </span>
                    <span class="hub-builder__save-btn-loading" aria-hidden="true">
                        <i class="ri-loader-4-line i-spin" aria-hidden="true"></i>
                    </span>
                </button>
            </div>
        </header>

        <!-- ═══ DUAL-PANEL WRAPPER — slider mobile ═══ -->
        <div class="hub-panels" id="hub-panels">

            <!-- ─── PAINEL 1: CONTROLES (landing no mobile) ─── -->
            <section class="hub-panel hub-panel--controls" id="hub-panel-controls">

                <!-- TABS -->
                <nav class="hub-builder__tabs" role="tablist" aria-label="<?php esc_attr_e('Seções do editor', 'apollo-hub'); ?>">
                    <button class="hub-builder__tab is-active" role="tab" data-tab="links" aria-selected="true">
                        <span style="position:relative;display:inline-flex;align-items:center;">
                            <i class="ri-stack-line" aria-hidden="true"></i>
                            <span class="cnt js-link-count-badge"><?php echo esc_html((string) count($hub_data['links'])); ?></span>
                        </span>
                        <?php esc_html_e('Blocos', 'apollo-hub'); ?>
                    </button>
                    <button class="hub-builder__tab" role="tab" data-tab="profile" aria-selected="false">
                        <i class="ri-user-line" aria-hidden="true"></i>
                        <?php esc_html_e('Perfil', 'apollo-hub'); ?>
                    </button>
                    <button class="hub-builder__tab" role="tab" data-tab="socials" aria-selected="false">
                        <i class="ri-global-line" aria-hidden="true"></i>
                        <?php esc_html_e('Redes', 'apollo-hub'); ?>
                    </button>
                    <button class="hub-builder__tab" role="tab" data-tab="appearance" aria-selected="false">
                        <i class="ri-palette-line" aria-hidden="true"></i>
                        <?php esc_html_e('Visual', 'apollo-hub'); ?>
                    </button>
                </nav>

                <!-- TAB: LINKS -->
                <div class="hub-builder__tab-panel is-active" id="tab-links" role="tabpanel">
                    <div class="hub-builder__section-header">
                        <h2><?php esc_html_e('Blocos', 'apollo-hub'); ?></h2>
                        <div class="hub-builder__section-actions">
                            <span class="hub-builder__link-count">
                                <span class="js-link-count"><?php echo esc_html((string) count($hub_data['links'])); ?></span>/<?php echo esc_html((string) APOLLO_HUB_LINKS_MAX); ?>
                            </span>
                            <button type="button"
                                class="hub-btn-add-block js-hub-add-block-trigger"
                                data-tooltip="<?php esc_attr_e('Novo bloco', 'apollo-hub'); ?>"
                                aria-label="<?php esc_attr_e('Adicionar bloco', 'apollo-hub'); ?>">
                                <i class="ri-add-line" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <ul class="hub-builder__links-list js-hub-links-list" aria-label="<?php esc_attr_e('Lista de links', 'apollo-hub'); ?>">
                        <?php foreach ($hub_data['links'] as $idx => $link) : ?>
                            <li class="hub-builder__link-item block-card js-hub-link-item" data-index="<?php echo esc_attr((string) $idx); ?>" draggable="true">
                                <span class="hub-builder__link-item__drag hub-btn-icon"
                                    data-tooltip="<?php esc_attr_e('Arrastar', 'apollo-hub'); ?>">
                                    <i class="ri-drag-move-2-line" aria-hidden="true"></i>
                                </span>
                                <div class="hub-builder__link-item__fields">
                                    <label class="sr-only" for="hub-link-title-<?php echo esc_attr((string) $idx); ?>"><?php esc_html_e('Título', 'apollo-hub'); ?></label>
                                    <input
                                        type="text"
                                        id="hub-link-title-<?php echo esc_attr((string) $idx); ?>" class="apollo-input"
                                        name="hub_links[<?php echo esc_attr((string) $idx); ?>][title]"
                                        class="hub-builder__input js-link-title"
                                        placeholder="<?php esc_attr_e('Título do link', 'apollo-hub'); ?>"
                                        value="<?php echo esc_attr($link['title'] ?? ''); ?>">
                                    <label class="sr-only" for="hub-link-url-<?php echo esc_attr((string) $idx); ?>">URL</label>
                                    <input
                                        type="url"
                                        id="hub-link-url-<?php echo esc_attr((string) $idx); ?>" class="apollo-input"
                                        name="hub_links[<?php echo esc_attr((string) $idx); ?>][url]"
                                        class="hub-builder__input js-link-url"
                                        placeholder="https://"
                                        value="<?php echo esc_url($link['url'] ?? ''); ?>">
                                    <!-- Botão seletor de ícone — abre modal com <i> tags -->
                                    <button type="button"
                                        class="hub-builder__icon-picker-btn js-icon-picker-btn"
                                        data-current-icon="<?php echo esc_attr($link['icon'] ?? 'ri-link-m'); ?>">
                                        <i class="<?php echo esc_attr($link['icon'] ?? 'ri-link-m'); ?>" aria-hidden="true"></i>
                                        <span><?php esc_html_e('Ícone', 'apollo-hub'); ?></span>
                                        <i class="ri-arrow-down-s-line" aria-hidden="true"></i>
                                    </button>
                                    <input type="hidden"
                                        class="js-link-icon"
                                        id="hub-link-icon-<?php echo esc_attr((string) $idx); ?>"
                                        name="hub_links[<?php echo esc_attr((string) $idx); ?>][icon]"
                                        value="<?php echo esc_attr($link['icon'] ?? 'ri-link-m'); ?>">
                                </div>
                                <div class="hub-builder__link-item__actions">
                                    <button class="hub-btn-icon js-hub-link-edit"
                                        type="button"
                                        data-tooltip="<?php esc_attr_e('Editar', 'apollo-hub'); ?>"
                                        aria-label="<?php esc_attr_e('Editar bloco', 'apollo-hub'); ?>">
                                        <i class="ri-edit-line" aria-hidden="true"></i>
                                    </button>
                                    <label class="hub-builder__toggle"
                                        data-tooltip="<?php esc_attr_e('Ativo', 'apollo-hub'); ?>"
                                        title="<?php esc_attr_e('Ativo/Inativo', 'apollo-hub'); ?>">
                                        <span class="sr-only"><?php esc_html_e('Link ativo', 'apollo-hub'); ?></span>
                                        <input
                                            type="checkbox"
                                            id="hub-link-active-<?php echo esc_attr((string) $idx); ?>"
                                            name="hub_links[<?php echo esc_attr((string) $idx); ?>][active]"
                                            class="js-link-active"
                                            <?php checked($link['active'] ?? true); ?>>
                                        <span class="hub-builder__toggle-slider"></span>
                                    </label>
                                    <button class="hub-btn-icon hub-btn-icon--danger js-hub-link-remove"
                                        type="button"
                                        data-tooltip="<?php esc_attr_e('Remover', 'apollo-hub'); ?>"
                                        aria-label="<?php esc_attr_e('Remover', 'apollo-hub'); ?>">
                                        <i class="ri-delete-bin-7-line" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <!-- Botão add via modal (também existe o FAB no header da seção) -->
                </div>

                <!-- TAB: PERFIL -->
                <div class="hub-builder__tab-panel" id="tab-profile" role="tabpanel" aria-hidden="true">
                    <div class="hub-builder__section-header">
                        <h2><?php esc_html_e('Perfil', 'apollo-hub'); ?></h2>
                    </div>

                    <div class="hub-builder__field">
                        <label class="hub-builder__label" for="hub-bio">
                            <?php esc_html_e('Bio', 'apollo-hub'); ?>
                            <span class="hub-builder__char-count">
                                <span class="js-bio-count"><?php echo esc_html((string) mb_strlen($hub_data['bio'])); ?></span>/<?php echo esc_html((string) APOLLO_HUB_BIO_MAX_LEN); ?>
                            </span>
                        </label>
                        <textarea
                            id="hub-bio"
                            class="apollo-textarea hub-builder__textarea js-hub-bio"
                            rows="4"
                            maxlength="<?php echo esc_attr((string) APOLLO_HUB_BIO_MAX_LEN); ?>"
                            placeholder="<?php esc_attr_e('Escreva uma bio curta...', 'apollo-hub'); ?>"><?php echo esc_textarea($hub_data['bio']); ?></textarea>
                    </div>

                    <div class="hub-builder__field">
                        <label class="hub-builder__label" for="hub-avatar-picker-btn"><?php esc_html_e('Avatar', 'apollo-hub'); ?></label>
                        <div class="hub-builder__media-picker js-hub-avatar-picker">
                            <?php
                            if ($hub_data['avatar']) :
                                $av_url = wp_get_attachment_image_url($hub_data['avatar'], 'thumbnail');
                            ?>
                                <img src="<?php echo esc_url($av_url); ?>" class="hub-builder__media-preview js-avatar-preview" alt="Avatar" width="80" height="80">
                            <?php else : ?>
                                <div class="hub-builder__media-placeholder js-avatar-preview">
                                    <i class="ri-user-3-line" aria-hidden="true"></i>
                                </div>
                            <?php endif; ?>
                            <input type="hidden" id="hub-avatar-id" name="hub_avatar_id" class="js-hub-avatar-id" value="<?php echo esc_attr((string) $hub_data['avatar']); ?>">
                            <button type="button" id="hub-avatar-picker-btn" class="hub-builder__btn js-open-media" data-target="avatar" aria-label="<?php esc_attr_e('Escolher avatar', 'apollo-hub'); ?>">
                                <i class="ri-image-add-line" aria-hidden="true"></i>
                                <?php esc_html_e('Escolher imagem', 'apollo-hub'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="hub-builder__field">
                        <label class="hub-builder__label" for="hub-avatar-type"><?php esc_html_e('Tipo de Avatar', 'apollo-hub'); ?></label>
                        <select id="hub-avatar-type" name="hub_avatar_type" class="hub-builder__select js-avatar-type">
                            <option value="normal" <?php selected($hub_data['avatar_type'] ?? 'normal', 'normal'); ?>>
                                <?php esc_html_e('Normal', 'apollo-hub'); ?>
                            </option>
                            <option value="morphism" <?php selected($hub_data['avatar_type'] ?? 'normal', 'morphism'); ?>>
                                <?php esc_html_e('Morphism (Animado)', 'apollo-hub'); ?>
                            </option>
                        </select>
                        <p class="hub-builder__hint"><?php esc_html_e('Morphism: Avatar com rotação e morphing contínuo', 'apollo-hub'); ?></p>
                    </div>

                    <div class="hub-builder__field">
                        <label class="hub-builder__label" for="hub-cover-picker-btn"><?php esc_html_e('Capa de fundo', 'apollo-hub'); ?></label>
                        <div class="hub-builder__media-picker js-hub-cover-picker">
                            <?php
                            if ($hub_data['cover']) :
                                $cv_url = wp_get_attachment_image_url($hub_data['cover'], 'thumbnail');
                            ?>
                                <img src="<?php echo esc_url($cv_url); ?>" class="hub-builder__media-preview js-cover-preview" alt="Capa" width="160" height="60">
                            <?php else : ?>
                                <div class="hub-builder__media-placeholder hub-builder__media-placeholder--wide js-cover-preview">
                                    <i class="ri-image-2-line" aria-hidden="true"></i>
                                </div>
                            <?php endif; ?>
                            <input type="hidden" id="hub-cover-id" name="hub_cover_id" class="js-hub-cover-id" value="<?php echo esc_attr((string) $hub_data['cover']); ?>">
                            <button type="button" id="hub-cover-picker-btn" class="hub-builder__btn js-open-media" data-target="cover" aria-label="<?php esc_attr_e('Escolher capa', 'apollo-hub'); ?>">
                                <i class="ri-image-add-line" aria-hidden="true"></i>
                                <?php esc_html_e('Escolher capa', 'apollo-hub'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TAB: REDES SOCIAIS -->
                <div class="hub-builder__tab-panel" id="tab-socials" role="tabpanel" aria-hidden="true">
                    <div class="hub-builder__section-header">
                        <h2><?php esc_html_e('Redes Sociais', 'apollo-hub'); ?></h2>
                    </div>
                    <ul class="hub-builder__socials-list js-hub-socials-list">
                        <?php
                        $existing_socials = array();
                        foreach ($hub_data['socials'] as $s) {
                            $existing_socials[$s['network']] = $s['url'];
                        }
                        foreach (APOLLO_HUB_SOCIAL_ICONS as $network => $icon_class) :
                        ?>
                            <li class="hub-builder__social-item">
                                <label class="hub-builder__social-label" for="social-<?php echo esc_attr($network); ?>">
                                    <i class="<?php echo esc_attr($icon_class); ?>" aria-hidden="true"></i>
                                    <?php echo esc_html(ucfirst($network)); ?>
                                </label>
                                <input
                                    type="url"
                                    id="social-<?php echo esc_attr($network); ?>" class="apollo-input"
                                    name="hub_socials[<?php echo esc_attr($network); ?>]"
                                    class="hub-builder__input js-social-url"
                                    data-network="<?php echo esc_attr($network); ?>"
                                    placeholder="https://<?php echo esc_attr($network); ?>.com/..."
                                    value="<?php echo esc_url($existing_socials[$network] ?? ''); ?>">
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- TAB: VISUAL / APARÊNCIA -->
                <div class="hub-builder__tab-panel" id="tab-appearance" role="tabpanel" aria-hidden="true">
                    <div class="hub-builder__section-header">
                        <h2><?php esc_html_e('Aparência', 'apollo-hub'); ?></h2>
                    </div>
                    <div class="hub-builder__field">
                        <p class="hub-builder__label" id="hub-theme-label"><?php esc_html_e('Tema', 'apollo-hub'); ?></p>
                        <div class="hub-builder__theme-grid js-hub-theme-picker" role="radiogroup" aria-labelledby="hub-theme-label">
                            <?php foreach (APOLLO_HUB_THEMES as $key => $label) : ?>
                                <button
                                    type="button"
                                    class="hub-builder__theme-btn js-hub-theme-btn <?php echo esc_attr( $hub_data['theme'] === $key ? 'is-active' : '' ); ?>"
                                    data-theme="<?php echo esc_attr($key); ?>"
                                    role="radio"
                                    aria-checked="<?php echo esc_attr( $hub_data['theme'] === $key ? 'true' : 'false' ); ?>"
                                    aria-label="<?php echo esc_attr($label); ?>">
                                    <span class="hub-builder__theme-preview hub-builder__theme-preview--<?php echo esc_attr($key); ?>"></span>
                                    <span><?php echo esc_html($label); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="hub-builder__field" style="margin-top:20px;">
                        <label class="hub-builder__label" for="hub-custom-css">
                            <i class="ri-code-s-slash-line" aria-hidden="true"></i>
                            <?php esc_html_e('CSS Personalizado', 'apollo-hub'); ?>
                        </label>
                        <textarea
                            id="hub-custom-css"
                            class="apollo-textarea hub-builder__input js-hub-custom-css"
                            rows="6"
                            placeholder="/* Seu CSS customizado */"
                            style="font-family:monospace;font-size:12px;resize:vertical;"><?php echo esc_textarea($hub_data['custom_css'] ?? ''); ?></textarea>
                        <p class="hub-builder__hint" style="font-size:11px;color:var(--txt-muted,#888);margin-top:4px;">
                            <?php esc_html_e('CSS aplicado exclusivamente à sua página Hub. Use seletores específicos.', 'apollo-hub'); ?>
                        </p>
                    </div>
                </div>

            </section><!-- .hub-panel--controls -->

            <!-- ─── PAINEL 2: PREVIEW ─── -->
            <section class="hub-panel hub-panel--preview" id="hub-panel-preview">
                <div class="hub-builder__preview-header">
                    <span class="hub-builder__preview-label"><?php esc_html_e('Pré-visualização', 'apollo-hub'); ?></span>
                    <span class="hub-builder__preview-url">
                        <?php echo esc_html(trailingslashit(home_url()) . 'hub/' . $hub_username); ?>
                    </span>
                </div>
                <div class="hub-builder__preview-frame-wrap">
                    <div class="hub-builder__mock-phone" aria-hidden="true">
                        <iframe
                            class="hub-builder__preview-iframe js-hub-preview-iframe"
                            src="<?php echo esc_url($hub_url); ?>"
                            title="<?php esc_attr_e('Visualização do Hub', 'apollo-hub'); ?>"
                            sandbox="allow-same-origin allow-scripts"
                            loading="lazy"></iframe>
                    </div>
                </div>
            </section><!-- .hub-panel--preview -->

        </div><!-- .hub-panels -->

        <!-- ═══ ICON PICKER MODAL — Bottom sheet com <i> tags ═══ -->
        <div class="hub-modal js-icon-modal" id="icon-modal" aria-hidden="true" role="dialog" aria-label="<?php esc_attr_e('Escolher ícone', 'apollo-hub'); ?>">
            <div class="hub-modal__backdrop js-icon-modal-close"></div>
            <div class="hub-modal__content">
                <div class="hub-modal__header">
                    <h3><?php esc_html_e('Escolher ícone', 'apollo-hub'); ?></h3>
                    <button type="button" class="hub-modal__close js-icon-modal-close" aria-label="<?php esc_attr_e('Fechar', 'apollo-hub'); ?>">
                        <i class="ri-close-line" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="hub-modal__search">
                    <i class="ri-search-line" aria-hidden="true"></i>
                    <label class="sr-only" for="icon-search"><?php esc_html_e('Buscar ícone', 'apollo-hub'); ?></label>
                    <input type="search" id="icon-search" class="apollo-input hub-modal__search-input js-icon-search" placeholder="<?php esc_attr_e('Buscar ícone...', 'apollo-hub'); ?>" autocomplete="off">
                </div>
                <div class="hub-modal__body">
                    <div class="hub-icon-grid js-icon-grid" id="icon-grid">
                        <div class="hub-icon-grid__loading">
                            <i class="ri-loader-4-line i-spin" aria-hidden="true"></i>
                            <span><?php esc_html_e('Carregando ícones...', 'apollo-hub'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <div class="hub-builder__toast js-hub-toast" role="status" aria-live="polite" aria-atomic="true"></div>

    </div><!-- .hub-builder -->

    <!-- ═══ MODAL: ADICIONAR BLOCO ═══ -->
    <div class="hub-add-modal" id="hub-add-modal" aria-hidden="true" role="dialog"
        aria-label="<?php esc_attr_e('Adicionar bloco', 'apollo-hub'); ?>">
        <div class="hub-add-modal__backdrop js-add-modal-close" aria-hidden="true"></div>
        <div class="hub-add-modal__box" role="document">
            <div class="hub-add-modal__head">
                <span class="hub-add-modal__title">
                    <i class="ri-add-circle-line" style="color:var(--primary,#f45f00);margin-right:6px;" aria-hidden="true"></i>
                    <?php esc_html_e('Novo bloco', 'apollo-hub'); ?>
                </span>
                <button type="button" class="hub-add-modal__close js-add-modal-close"
                    aria-label="<?php esc_attr_e('Fechar', 'apollo-hub'); ?>">
                    <i class="ri-close-line" aria-hidden="true"></i>
                </button>
            </div>
            <div class="hub-add-modal__grid" id="hub-add-modal-grid">
                <?php
                // Ordena por grupo para exibir grupos agrupados
                $sorted_block_types = [];
                foreach ( APOLLO_HUB_BLOCK_TYPES as $tk => $ti ) {
                    $sorted_block_types[$ti['group'] ?? 'other'][$tk] = $ti;
                }
                $group_labels_map = [
                    'layout'   => __('Layout', 'apollo-hub'),
                    'content'  => __('Conteúdo', 'apollo-hub'),
                    'media'    => __('Mídia', 'apollo-hub'),
                    'advanced' => __('Avançado', 'apollo-hub'),
                    'other'    => __('Outros', 'apollo-hub'),
                ];
                foreach ( $sorted_block_types as $group_key => $group_types ) : ?>
                    <span class="hub-add-modal__group-label"><?php echo esc_html($group_labels_map[$group_key] ?? ucfirst($group_key)); ?></span>
                    <?php foreach ( $group_types as $type_key => $type_info ) : ?>
                        <button type="button"
                            class="hub-add-modal__item js-add-block-type"
                            data-type="<?php echo esc_attr($type_key); ?>"
                            data-label="<?php echo esc_attr($type_info['label']); ?>"
                            data-icon="<?php echo esc_attr($type_info['icon']); ?>">
                            <i class="<?php echo esc_attr($type_info['icon']); ?>" aria-hidden="true"></i>
                            <span><?php echo esc_html($type_info['label']); ?></span>
                        </button>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ═══ POPUP: EDITAR BLOCO ═══ -->
    <div class="hub-edit-popup" id="hub-edit-popup" aria-hidden="true" role="dialog"
        aria-label="<?php esc_attr_e('Editar bloco', 'apollo-hub'); ?>">
        <div class="hub-edit-popup__backdrop js-edit-popup-close" aria-hidden="true"></div>
        <div class="hub-edit-popup__box" role="document">
            <div class="hub-edit-popup__head">
                <div class="hub-edit-popup__icon" id="hub-edit-popup-icon">
                    <i class="ri-link" aria-hidden="true"></i>
                </div>
                <span class="hub-edit-popup__title" id="hub-edit-popup-title">
                    <?php esc_html_e('Editar bloco', 'apollo-hub'); ?>
                </span>
                <button type="button" class="hub-edit-popup__close js-edit-popup-close"
                    aria-label="<?php esc_attr_e('Fechar', 'apollo-hub'); ?>">
                    <i class="ri-close-line" aria-hidden="true"></i>
                </button>
            </div>
            <div class="hub-edit-popup__body" id="hub-edit-popup-body">
                <!-- Campos injetados via JS ao abrir -->
            </div>
            <div class="hub-edit-popup__footer">
                <button type="button" class="hub-btn-cancel js-edit-popup-close">
                    <?php esc_html_e('Cancelar', 'apollo-hub'); ?>
                </button>
                <button type="button" class="hub-btn-apply js-edit-popup-apply">
                    <i class="ri-check-line" aria-hidden="true"></i>
                    <?php esc_html_e('Aplicar', 'apollo-hub'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══ PREVIEW PANE (Mobile / PC) ═══ -->
    <div class="hub-preview-pane" id="hub-preview-pane" aria-hidden="true">
        <div class="hub-preview-pane__bar">
            <span class="hub-preview-pane__label" id="hub-preview-pane-label">
                <?php esc_html_e('Preview', 'apollo-hub'); ?>
            </span>
            <a href="<?php echo esc_url($hub_url); ?>" class="hub-preview-pane__url"
                id="hub-preview-pane-url" target="_blank" rel="noopener noreferrer">
                <?php echo esc_html($hub_url); ?>
            </a>
            <div class="hub-preview-mode-btns">
                <button type="button" class="hub-btn-icon js-preview-pane-switch"
                    data-mode="mobile"
                    data-tooltip="<?php esc_attr_e('Mobile', 'apollo-hub'); ?>">
                    <i class="ri-smartphone-line" aria-hidden="true"></i>
                </button>
                <button type="button" class="hub-btn-icon js-preview-pane-switch"
                    data-mode="pc"
                    data-tooltip="<?php esc_attr_e('Computador', 'apollo-hub'); ?>">
                    <i class="ri-computer-line" aria-hidden="true"></i>
                </button>
                <button type="button" class="hub-btn-icon hub-btn-icon--danger js-preview-pane-close"
                    data-tooltip="<?php esc_attr_e('Fechar preview', 'apollo-hub'); ?>"
                    aria-label="<?php esc_attr_e('Fechar preview', 'apollo-hub'); ?>">
                    <i class="ri-close-line" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="hub-preview-pane__body">
            <div class="hub-preview-mock" id="hub-preview-mock">
                <div class="hub-preview-mock__notch" aria-hidden="true"></div>
                <iframe class="js-preview-pane-iframe"
                    title="<?php esc_attr_e('Preview do Hub', 'apollo-hub'); ?>"
                    sandbox="allow-same-origin allow-scripts"
                    loading="lazy"></iframe>
            </div>
        </div>
    </div>

<?php
if (function_exists('apollo_render_document_close')) {
    apollo_render_document_close();
} else {
    ?>
</body>

</html>
    <?php
}