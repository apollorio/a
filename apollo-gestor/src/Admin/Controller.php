<?php

/**
 * Admin Controller — menu registration, asset enqueueing, permissions AJAX
 *
 * All domain AJAX handlers live in their respective modules (includes/modules/).
 * Controller only handles save_permissions (not owned by any module).
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Admin;

use Apollo\Gestor\Admin\RoleAccess;
use Apollo\Gestor\Model\Team;
use Apollo\Gestor\Model\Activity;
use Apollo\Gestor\Core\Module_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Controller {


	/** Admin page hook suffix */
	private string $hook = '';

	/** Module manager reference */
	private Module_Manager $modules;

	public function __construct( Module_Manager $modules ) {
		$this->modules = $modules;
	}

	/**
	 * Bootstrap admin hooks
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX: save_permissions (only handler not in any module)
		add_action( 'wp_ajax_apollo_gestor_save_permissions', array( $this, 'save_permissions' ) );
	}

	/**
	 * Register admin menu page
	 */
	public function register_menu(): void {
		$this->hook = add_menu_page(
			'Gestor de Projetos',
			'Gestor',
			'read',
			'apollo-gestor',
			array( $this, 'render_page' ),
			'dashicons-clipboard',
			27
		);
	}

	/**
	 * Enqueue CSS + JS only on our page
	 */
	public function enqueue_assets( string $hook ): void {
		if ( $hook !== $this->hook ) {
			return;
		}

		// Remove admin bar padding
		remove_action( 'wp_head', '_admin_bar_bump_cb' );

		$v   = APOLLO_GESTOR_VERSION;
		$url = APOLLO_GESTOR_URL;

		// ─── CSS ──────────────────────────────────────────────
		wp_enqueue_style( 'apollo-gestor', $url . 'assets/css/gestor.css', [], $v );

		// ─── JS Modules (L0 → L6 load order) ─────────────────
		$js_modules = [
			'gestor-helpers'        => 'gestor.helpers.js',
			'gestor-data'           => 'gestor.data.js',
			'gestor-loader'         => 'gestor.loader.js',
			'gestor-tabs'           => 'gestor.tabs.js',
			'gestor-event-selector' => 'gestor.event-selector.js',
			'gestor-kanban'         => 'gestor.kanban.js',
			'gestor-slideover'      => 'gestor.slideover.js',
			'gestor-checklist'      => 'gestor.checklist.js',
			'gestor-budget'         => 'gestor.budget.js',
			'gestor-calculator'     => 'gestor.calculator.js',
			'gestor-notifications'  => 'gestor.notifications.js',
			'gestor-cmdpalette'     => 'gestor.cmdpalette.js',
			'gestor-permissions'    => 'gestor.permissions.js',
			'gestor-modals'         => 'gestor.modals.js',
			'gestor-chart-periods'  => 'gestor.chart-periods.js',
			'gestor-fab'            => 'gestor.fab.js',
			'gestor-animations'     => 'gestor.animations.js',
			'gestor-charts'         => 'gestor.charts.js',
			'gestor-gantt'          => 'gestor.gantt.js',
			'gestor-task-detail'       => 'gestor.task-detail.js',
			'gestor-app'              => 'gestor.app.js',
			'gestor-financeiro-ctrl'  => 'gestor.financeiro-ctrl.js',
		];

		$prev = [];
		foreach ( $js_modules as $handle => $file ) {
			wp_enqueue_script( $handle, $url . 'assets/js/' . $file, $prev, $v, true );
			$prev = [ $handle ];
		}

		// ─── Localize on first script ─────────────────────────
		$cdn_url = defined( 'APOLLO_CDN_URL' ) ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/';

		$role_ctx = RoleAccess::resolve();

		wp_localize_script(
			'gestor-helpers',
			'ApolloGestor',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'apollo_gestor_nonce' ),
				'docsNonce'   => wp_create_nonce( 'apollo_docs_nonce' ),
				'restUrl'     => rest_url( 'apollo/v1/' ),
				'cdnUrl'      => $cdn_url,
				'assetsUrl'   => 'https://assets.apollo.rio.br/i/',
				'currentUser' => get_current_user_id(),
				'isAdmin'     => current_user_can( 'manage_options' ),
				'roleAccess'  => [
					'level'      => $role_ctx['level'],
					'display'    => $role_ctx['display'],
					'canEdit'    => $role_ctx['can_edit'],
					'isTeam'     => $role_ctx['is_team'],
					'allowedTabs' => RoleAccess::allowed_tabs( $role_ctx['level'] ),
				],
				'settings'    => [
					'enableMilestones' => (bool) \Apollo\Gestor\Plugin::get_setting( 'enable_milestones' ),
					'enablePayments'   => (bool) \Apollo\Gestor\Plugin::get_setting( 'enable_payments' ),
					'enableTeam'       => (bool) \Apollo\Gestor\Plugin::get_setting( 'enable_team' ),
					'defaultCurrency'  => \Apollo\Gestor\Plugin::get_setting( 'default_currency' ),
				],
				'i18n'        => [
					'loading'    => 'Carregando…',
					'error'      => 'Erro ao carregar dados',
					'saved'      => 'Salvo com sucesso',
					'confirm'    => 'Tem certeza?',
					'noProjects' => 'Nenhum projeto encontrado',
					'noTasks'    => 'Sem tarefas pendentes',
					'copied'     => 'Copiado!',
					'planned'    => 'Planejado',
					'tostart'    => 'A Iniciar',
					'ongoing'    => 'Em Andamento',
					'delayed'    => 'Atrasado',
					'canceled'   => 'Cancelado',
					'delivered'  => 'Entregue',
					'projeto'    => 'Projeto',
					'tarefa'     => 'Tarefa',
					'equipe'     => 'Equipe',
					'financeiro' => 'Financeiro',
				],
				'statusMap'   => [
					'planned'   => [ 'label' => 'Planejado',     'color' => '#6366f1' ],
					'tostart'   => [ 'label' => 'A Iniciar',     'color' => '#0ea5e9' ],
					'ongoing'   => [ 'label' => 'Em Andamento',  'color' => '#d97706' ],
					'delayed'   => [ 'label' => 'Atrasado',      'color' => '#dc2626' ],
					'canceled'  => [ 'label' => 'Cancelado',     'color' => '#94a3b8' ],
					'delivered' => [ 'label' => 'Entregue',      'color' => '#16a34a' ],
				],
			]
		);
	}

	/**
	 * Render admin page (loads template)
	 */
	public function render_page(): void {
		$role_ctx = RoleAccess::resolve();

		if ( $role_ctx['level'] <= RoleAccess::LEVEL_NONE ) {
			wp_die( esc_html__( 'Acesso negado.', 'apollo-gestor' ) );
		}

		$modules      = $this->modules;
		$access_level = $role_ctx['level'];
		$access_ctx   = $role_ctx;
		include APOLLO_GESTOR_DIR . 'templates/gestor.php';
	}

	/*
	═══════════════════════════════════════════════════════════
	 * AJAX: Security + JSON helpers
	 * ═══════════════════════════════════════════════════════════ */

	private function verify_nonce(): bool {
		return check_ajax_referer( 'apollo_gestor_nonce', 'nonce', false ) !== false;
	}

	private function json_success( $data = null ): void {
		wp_send_json_success( $data );
	}

	private function json_error( string $msg = 'Erro', int $code = 400 ): void {
		wp_send_json_error( array( 'message' => $msg ), $code );
	}

	/*
	═══════════════════════════════════════════════════════════
	 * AJAX: Permissions
	 * ═══════════════════════════════════════════════════════════ */

	/**
	 * Save team member permissions (batch role updates)
	 */
	public function save_permissions(): void {
		if ( ! $this->verify_nonce() ) {
			$this->json_error( 'Nonce inválido', 403 );
		}

		$event_id = absint( $_POST['event_id'] ?? 0 );
		if ( ! $event_id ) {
			$this->json_error( 'Evento inválido' );
		}

		$user_id = get_current_user_id();
		if ( ! current_user_can( 'manage_options' ) && ! Team::can_manage( $user_id, $event_id ) ) {
			$this->json_error( 'Sem permissão', 403 );
		}

		$perms   = isset( $_POST['permissions'] ) ? (array) $_POST['permissions'] : array();
		$updated = 0;

		foreach ( $perms as $perm ) {
			$member_id = absint( $perm['member_id'] ?? 0 );
			$role      = sanitize_key( $perm['role'] ?? '' );
			if ( $member_id && $role && in_array( $role, array( 'adm', 'gestor', 'tgestor', 'team' ), true ) ) {
				Team::update( $member_id, array( 'role' => $role ) );
				++$updated;
			}
		}

		Activity::log(
			array(
				'event_id'    => $event_id,
				'action'      => 'perms_updated',
				'entity_type' => 'team',
				'entity_id'   => 0,
				'meta'        => array( 'count' => $updated ),
			)
		);

		$this->json_success( array( 'updated' => $updated ) );
	}
}
