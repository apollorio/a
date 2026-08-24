<?php
/**
 * Shared App-Shell — Activity / apps / profile panels
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* #panel-act / #apps-pop / #panel-profile belong to the canonical app-shell —
   skip this local copy when it already rendered (fallback only). */
if ( defined( 'APOLLO_APP_SHELL_LOADED' ) ) {
	return;
}

/* ─── Real user identity (was hardcoded "Rafael Valle" demo content) ─── */
$profile_name      = $current_user->display_name ?? '';
$profile_initials  = apollo_event_user_initials( $profile_name );
$profile_role_map  = array(
	'administrator' => __( 'Núcleo Apollo · Gestor', 'apollo-events' ),
	'editor'        => __( 'Núcleo Apollo · Editor', 'apollo-events' ),
	'author'        => __( 'Produtor de Eventos', 'apollo-events' ),
	'contributor'   => __( 'Colaborador', 'apollo-events' ),
	'subscriber'    => __( 'Membro Apollo', 'apollo-events' ),
);
$profile_role_key  = ! empty( $current_user->roles[0] ) ? $current_user->roles[0] : 'subscriber';
$profile_role      = $profile_role_map[ $profile_role_key ] ?? ucfirst( $profile_role_key );
$profile_can_mod   = current_user_can( 'moderate_comments' ) || current_user_can( 'edit_others_posts' );
$profile_ev_count  = is_array( $sidebar_events ?? null ) ? count( $sidebar_events ) : 0;
?>
    <!-- ═══ PAINEL DE ATIVIDADES ═══ -->
    <div class="panel-r" id="panel-act" aria-label="Atividades" aria-hidden="true">
        <div class="panel-r-hd">
            <span class="panel-r-title">Atividades</span>
            <button class="ax-ic" data-close="panel-act" aria-label="Fechar"><i class="ri-close-line"></i></button>
        </div>
        <div class="panel-tabs-row" role="tablist">
            <button class="panel-tab active" data-ptab="notif-pane" role="tab" aria-selected="true">Notificações</button>
            <button class="panel-tab" data-ptab="msgs-pane" role="tab" aria-selected="false">Mensagens</button>
        </div>
        <div class="panel-r-body">
            <div class="panel-tab-pane active" id="notif-pane" role="tabpanel">
                <div class="notif-item unread">
                    <div class="notif-av"><i class="ri-calendar-event-line"></i></div>
                    <div class="notif-body"><p class="notif-text"><strong>Summer Vibes '26</strong> confirmou 3 novos DJs no line up</p><p class="notif-time">há 4 min</p></div>
                    <span class="notif-dot"></span>
                </div>
                <div class="notif-item unread">
                    <div class="notif-av"><i class="ri-ticket-line"></i></div>
                    <div class="notif-body"><p class="notif-text"><strong>Techno Warehouse</strong> está quase esgotado</p><p class="notif-time">há 22 min</p></div>
                    <span class="notif-dot"></span>
                </div>
                <div class="notif-item">
                    <div class="notif-av"><i class="ri-user-add-line"></i></div>
                    <div class="notif-body"><p class="notif-text"><strong>Marina Costa</strong> entrou como coautora de NYE Copacabana</p><p class="notif-time">há 1 h</p></div>
                </div>
                <div class="notif-item">
                    <div class="notif-av"><i class="ri-sun-cloudy-line"></i></div>
                    <div class="notif-body"><p class="notif-text">Previsão atualizada para <strong>Sunset Rooftop Party</strong></p><p class="notif-time">Ontem</p></div>
                </div>
            </div>
            <div class="panel-tab-pane" id="msgs-pane" role="tabpanel" hidden>
                <div class="notif-item unread">
                    <div class="notif-av notif-av--initials">MC</div>
                    <div class="notif-body"><p class="notif-text"><strong>Marina Costa</strong> · Confirma o horário do Mochakk?</p><p class="notif-time">Agora</p></div>
                    <span class="notif-dot"></span>
                </div>
                <div class="notif-item">
                    <div class="notif-av notif-av--initials">LM</div>
                    <div class="notif-body"><p class="notif-text"><strong>Leo Martins</strong> · Fechei o Armazém da Utopia</p><p class="notif-time">há 3 h</p></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ POPUP DE APPS ═══ -->
    <div class="apps-pop" id="apps-pop" aria-hidden="true">
        <p class="apps-pop-title">Apollo Suite</p>
        <div class="apps-grid">
            <a class="app-cell" href="<?php echo esc_url( home_url( '/meus-eventos/' ) ); ?>"><div class="app-icon"><i class="ri-calendar-event-line"></i></div><span>Eventos</span></a>
            <a class="app-cell" href="<?php echo esc_url( home_url( '/novo-evento/' ) ); ?>"><div class="app-icon"><i class="ri-add-circle-line"></i></div><span>Novo</span></a>
            <a class="app-cell" href="#"><div class="app-icon"><i class="ri-map-pin-line"></i></div><span>Locais</span></a>
            <a class="app-cell" href="#"><div class="app-icon"><i class="ri-bar-chart-2-line"></i></div><span>Análises</span></a>
            <a class="app-cell" href="#"><div class="app-icon"><i class="ri-ticket-2-line"></i></div><span>Ingressos</span></a>
            <a class="app-cell" href="#"><div class="app-icon"><i class="ri-megaphone-line"></i></div><span>Marketing</span></a>
            <a class="app-cell" href="#"><div class="app-icon"><i class="ri-global-line"></i></div><span>Páginas</span></a>
            <a class="app-cell" href="#"><div class="app-icon"><i class="ri-compasses-2-line"></i></div><span>Config</span></a>
        </div>
    </div>

    <!-- ═══ PAINEL DE PERFIL ═══ -->
    <div class="panel-r" id="panel-profile" aria-label="Usuário" aria-hidden="true">
        <div class="panel-r-hd">
            <span class="panel-r-title">Usuá::rio</span>
            <button class="ax-ic" data-close="panel-profile" aria-label="Fechar"><i class="ri-close-line"></i></button>
        </div>
        <div class="panel-r-body">
            <div class="profile-panel-top">
                <div class="profile-panel-av"><?php echo esc_html( $profile_initials ); ?></div>
                <p class="profile-panel-name"><?php echo esc_html( $profile_name ); ?></p>
                <p class="profile-panel-role"><?php echo esc_html( $profile_role ); ?></p>
                <div class="flex-row" style="justify-content:center;margin-top:10px;gap:5px;">
                    <span class="tag tag-accent">Apollo</span>
                    <?php if ( $profile_can_mod ) : ?>
                        <span class="tag"><?php esc_html_e( 'Moderação', 'apollo-events' ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            $profile_pub_count = 0;
            $profile_co_count  = 0;
            if ( is_array( $sidebar_events ?? null ) ) {
                foreach ( $sidebar_events as $__pe ) {
                    if ( 'publish' === ( $__pe['status'] ?? '' ) ) {
                        $profile_pub_count++;
                    }
                    if ( ! empty( $__pe['is_coauthor'] ) ) {
                        $profile_co_count++;
                    }
                }
            }
            ?>
            <div class="profile-panel-stats">
                <div class="profile-stat"><div class="profile-stat-val"><?php echo esc_html( (string) $profile_ev_count ); ?></div><div class="profile-stat-lbl"><?php esc_html_e( 'Eventos', 'apollo-events' ); ?></div></div>
                <div class="profile-stat"><div class="profile-stat-val"><?php echo esc_html( (string) $profile_pub_count ); ?></div><div class="profile-stat-lbl"><?php esc_html_e( 'Publicados', 'apollo-events' ); ?></div></div>
                <div class="profile-stat"><div class="profile-stat-val"><?php echo esc_html( (string) $profile_co_count ); ?></div><div class="profile-stat-lbl"><?php esc_html_e( 'Co-autor', 'apollo-events' ); ?></div></div>
            </div>
            <div class="profile-panel-actions">
                <a class="profile-action" href="<?php echo esc_url( home_url( '/id/' . ( $current_user->user_nicename ?? '' ) ) ); ?>"><i class="ri-id-card-line"></i><?php esc_html_e( 'Meu perfil', 'apollo-events' ); ?></a>
                <div class="profile-action"><i class="ri-compasses-2-line"></i><?php esc_html_e( 'Configurações da conta', 'apollo-events' ); ?></div>
                <div class="profile-action"><i class="ri-palette-line"></i><?php esc_html_e( 'Aparência', 'apollo-events' ); ?></div>
                <div class="profile-action supportBTN"><i class="ri-user-community-line"></i><?php esc_html_e( 'Ajuda e suporte', 'apollo-events' ); ?></div>
                <a class="profile-action danger" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><i class="ri-door-open-line"></i><?php esc_html_e( 'Sair', 'apollo-events' ); ?></a>
            </div>
        </div>
    </div>
