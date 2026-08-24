<?php
/**
 * Shared App-Shell — Sidebar navigation (.ax-aside / .s glass drawer)
 *
 * Exact structure from _dev web/screen/single cpt/event/add-new/form.html.
 * Used by both the create/edit form and the "Meus Eventos" dashboard.
 *
 * Expected variables:
 *   $current_user   WP_User   Logged-in user.
 *   $sidebar_events array     Manageable events (author + co-author).
 *   $shell_active   string    'create' | 'dashboard' (drives active nav item).
 *   $is_edit        bool      (create context) whether editing an event.
 *   $edit_id        int       (create context) event being edited.
 *
 * @package Apollo\Event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shell_active   = $shell_active ?? 'create';
$is_dashboard   = ( 'dashboard' === $shell_active );
$user_name      = $current_user->display_name ?? '';
$user_initials  = apollo_event_user_initials( $user_name );
$dash_url       = home_url( '/meus-eventos/' );
$create_url     = home_url( '/novo-evento/' );
$is_edit_ctx    = ! empty( $is_edit );
$edit_id_ctx    = (int) ( $edit_id ?? 0 );
$events_list    = is_array( $sidebar_events ?? null ) ? $sidebar_events : array();
?>
    <aside class="ax-aside" id="ax-aside" aria-label="<?php esc_attr_e( 'Navegação', 'apollo-events' ); ?>">
        <div class="s">
            <div class="hd"><div class="lg"><i class="ri-hexagon-fill"></i></div><span class="wm">apollo<b>::</b>rio</span></div>
            <nav class="ax-aside-scroll">
                <div style="padding: 4px 12px 8px;">
                    <a class="btn btn-primary" style="width: 100%; justify-content: center;" href="<?php echo esc_url( $create_url ); ?>"><i class="ri-add-line"></i> <?php esc_html_e( 'Adicionar Novo Evento', 'apollo-events' ); ?></a>
                </div>
                <div class="nb">
                    <a class="ni <?php echo $is_dashboard ? 'on' : ''; ?>" href="<?php echo esc_url( $dash_url ); ?>"><i class="ri-dashboard-line"></i><span class="sn"><?php esc_html_e( 'Painel', 'apollo-events' ); ?></span></a>
                </div>
                <div class="dv"></div>
                <div class="sh" data-col="col-events"><span class="slbl"><?php esc_html_e( 'Gerenciar Eventos', 'apollo-events' ); ?></span><i class="ri-more-2-line tog"></i></div>
                <div class="col" id="col-events"><div class="inn">
                    <div class="nb" id="sidebarEvents">
						<?php if ( ! $is_dashboard ) : ?>
							<a class="ni <?php echo $is_edit_ctx ? '' : 'on'; ?>" href="<?php echo esc_url( $create_url ); ?>">
								<i class="ri-calendar-event-<?php echo $is_edit_ctx ? 'line' : 'fill'; ?>"></i>
								<span class="sn"><?php esc_html_e( 'Novo Evento (Rascunho)', 'apollo-events' ); ?></span>
							</a>
						<?php endif; ?>
						<?php foreach ( $events_list as $sev ) : ?>
							<?php $on = ! $is_dashboard && $is_edit_ctx && (int) $sev['id'] === $edit_id_ctx; ?>
							<a class="ni <?php echo $on ? 'on' : ''; ?>" href="<?php echo esc_url( $sev['edit_url'] ); ?>">
								<i class="ri-calendar-event-<?php echo $on ? 'fill' : 'line'; ?>"></i>
								<span class="sn"><?php echo esc_html( $sev['title'] ); ?></span>
							</a>
						<?php endforeach; ?>
						<?php if ( empty( $events_list ) && $is_dashboard ) : ?>
							<div class="ni" style="opacity:.5;pointer-events:none;"><i class="ri-calendar-event-line"></i><span class="sn"><?php esc_html_e( 'Nenhum evento ainda', 'apollo-events' ); ?></span></div>
						<?php endif; ?>
					</div>
                </div></div>
            </nav>
            <div class="ft">
                <div class="fni supportBTN"><i class="ri-user-community-line"></i><span class="sn"><?php esc_html_e( 'Ajuda e suporte', 'apollo-events' ); ?></span></div>
                <div class="adj">
                    <button type="button" class="adjl"><i class="ri-equalizer-line"></i><span class="sn"><?php esc_html_e( 'Preferências', 'apollo-events' ); ?></span></button>
                    <button type="button" class="pill on" id="aside-pill" aria-label="<?php esc_attr_e( 'Alternar tema', 'apollo-events' ); ?>" aria-pressed="true"><div class="th"></div></button>
                </div>
                <div class="urow">
                    <div class="avw"><div class="av"><?php echo esc_html( $user_initials ); ?></div><div class="aon"></div></div>
                    <div class="ui"><div class="un"><?php echo esc_html( $user_name ); ?></div><div class="uro">Apollo</div></div>
                    <div class="um" id="aside-user-btn"><i class="ri-more-2-line"></i></div>
                </div>
            </div>
        </div>
    </aside>
