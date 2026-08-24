<?php
/**
 * Apollo Calendar — /agenda Blank Canvas Template
 *
 * Variables injected by CalendarPage::maybe_render():
 *   $user       WP_User
 *   $js_config  string  JSON-encoded config (already wp_json_encode'd)
 *
 * @package Apollo\Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// We are inside BlankCanvasTrait context via CalendarPage; use $this-aware helpers via closures.
$cdn_core = defined( 'APOLLO_CDN_CORE_JS' ) ? APOLLO_CDN_CORE_JS : 'https://cdn.apollo.rio.br/v1.0.0/core.js?v=Random.3.0&versao=bb';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1, user-scalable=no">
	<meta name="theme-color" content="#000000">
	<title><?php echo esc_html( __( 'Minha Agenda', 'apollo-calendar' ) . ' — ' . get_bloginfo( 'name' ) ); ?></title>
	<script src="<?php echo esc_url( $cdn_core ); ?>" fetchpriority="high"></script>
	<style>
		/* ── Apollo Calendar Styles ── */
		*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
		:root {
			--cal-bg:        #0d0d0d;
			--cal-surface:   #1a1a1a;
			--cal-border:    #2d2d2d;
			--cal-text:      #f0f0f0;
			--cal-muted:     #888;
			--cal-accent:    #6366f1;
			--cal-event:     #f59e0b;
			--cal-sched:     #10b981;
			--cal-holiday:   #e11d48;
			--cal-personal:  #6366f1;
			--cal-today:     rgba(99,102,241,.15);
			--cal-radius:    10px;
		}
		html, body { height: 100%; background: var(--cal-bg); color: var(--cal-text); font-family: system-ui, -apple-system, sans-serif; }

		/* ── Layout ── */
		#apollo-cal { display: flex; flex-direction: column; height: 100vh; }
		#cal-header { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--cal-surface); border-bottom: 1px solid var(--cal-border); flex-shrink: 0; }
		#cal-header h1 { font-size: 1.1rem; font-weight: 600; flex: 1; }
		#cal-header .btn { padding: 6px 14px; border-radius: 8px; border: 1px solid var(--cal-border); background: transparent; color: var(--cal-text); cursor: pointer; font-size: 0.85rem; transition: background .15s; }
		#cal-header .btn:hover { background: var(--cal-border); }
		#cal-header .btn.primary { background: var(--cal-accent); border-color: var(--cal-accent); color: #fff; }
		#cal-tabs { display: flex; gap: 4px; }
		#cal-tabs button { padding: 5px 12px; border: none; border-radius: 6px; background: transparent; color: var(--cal-muted); cursor: pointer; font-size: 0.82rem; }
		#cal-tabs button.active { background: var(--cal-accent); color: #fff; }

		#cal-nav { display: flex; align-items: center; gap: 10px; }
		#cal-nav button { background: none; border: 1px solid var(--cal-border); border-radius: 6px; color: var(--cal-text); cursor: pointer; padding: 4px 9px; font-size: 1rem; }
		#cal-date-label { font-size: 0.9rem; min-width: 160px; text-align: center; font-weight: 500; }

		#cal-body { flex: 1; overflow-y: auto; padding: 0; }

		/* ── Month view ── */
		#cal-month { width: 100%; border-collapse: collapse; }
		#cal-month th { background: var(--cal-surface); padding: 6px 4px; font-size: 0.75rem; color: var(--cal-muted); text-align: center; border: 1px solid var(--cal-border); }
		#cal-month td { vertical-align: top; min-height: 80px; width: 14.28%; border: 1px solid var(--cal-border); padding: 4px; font-size: 0.75rem; cursor: pointer; }
		#cal-month td:hover { background: var(--cal-surface); }
		#cal-month td.today { background: var(--cal-today); }
		#cal-month td.other-month .day-num { color: var(--cal-muted); }
		.day-num { font-size: 0.85rem; font-weight: 500; margin-bottom: 2px; }
		.cal-chip { border-radius: 3px; padding: 1px 4px; margin-bottom: 1px; font-size: 0.7rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; color: #fff; }

		/* ── Week view ── */
		#cal-week { display: grid; grid-template-columns: 50px repeat(7, 1fr); font-size: 0.75rem; }
		.week-header-cell { background: var(--cal-surface); padding: 6px 2px; border: 1px solid var(--cal-border); text-align: center; }
		.week-header-cell.today-col { background: var(--cal-today); }
		.week-time-cell { border: 1px solid var(--cal-border); padding: 2px 4px; color: var(--cal-muted); font-size: 0.7rem; text-align: right; }
		.week-slot { border: 1px solid var(--cal-border); min-height: 28px; position: relative; }
		.week-slot.today-col { background: var(--cal-today); }
		.week-event { position: absolute; left: 2px; right: 2px; border-radius: 3px; padding: 1px 3px; font-size: 0.65rem; color: #fff; overflow: hidden; cursor: pointer; white-space: nowrap; z-index: 1; }

		/* ── Day view ── */
		#cal-day { display: grid; grid-template-columns: 50px 1fr; font-size: 0.75rem; }
		.day-time-label { border: 1px solid var(--cal-border); padding: 2px 6px; color: var(--cal-muted); text-align: right; }
		.day-slot { border: 1px solid var(--cal-border); min-height: 40px; position: relative; }
		.day-event { position: absolute; left: 4px; right: 4px; border-radius: 4px; padding: 2px 6px; font-size: 0.72rem; color: #fff; cursor: pointer; overflow: hidden; }

		/* ── All-day banner strip ── */
		.all-day-strip { border: 1px solid var(--cal-border); min-height: 24px; display: flex; flex-wrap: wrap; gap: 2px; padding: 2px; }

		/* ── Loading spinner ── */
		.cal-loading { display: flex; align-items: center; justify-content: center; height: 200px; color: var(--cal-muted); }
		.spinner { width: 28px; height: 28px; border: 3px solid var(--cal-border); border-top-color: var(--cal-accent); border-radius: 50%; animation: spin .7s linear infinite; margin-right: 10px; }
		@keyframes spin { to { transform: rotate(360deg); } }

		/* ── Modal ── */
		#cal-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65); z-index: 1000; align-items: center; justify-content: center; }
		#cal-modal-overlay.open { display: flex; }
		#cal-modal { background: var(--cal-surface); border: 1px solid var(--cal-border); border-radius: var(--cal-radius); padding: 20px; width: min(420px, 95vw); max-height: 90vh; overflow-y: auto; }
		#cal-modal h2 { font-size: 1rem; margin-bottom: 14px; }
		.cal-form-row { margin-bottom: 12px; }
		.cal-form-row label { display: block; font-size: 0.8rem; color: var(--cal-muted); margin-bottom: 4px; }
		.cal-form-row input, .cal-form-row textarea, .cal-form-row select {
			width: 100%; padding: 8px 10px; background: var(--cal-bg); border: 1px solid var(--cal-border); border-radius: 6px;
			color: var(--cal-text); font-size: 0.85rem; outline: none;
		}
		.cal-form-row input:focus, .cal-form-row textarea:focus { border-color: var(--cal-accent); }
		.cal-modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; }
		.cal-error { color: #f87171; font-size: 0.8rem; margin-top: 6px; }

		/* ── Legend ── */
		#cal-legend { display: flex; gap: 14px; padding: 6px 16px 4px; font-size: 0.72rem; color: var(--cal-muted); flex-shrink: 0; }
		.legend-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 4px; }

		/* ── Detail popup ── */
		#cal-detail { display: none; position: fixed; z-index: 900; background: var(--cal-surface); border: 1px solid var(--cal-border); border-radius: 8px; padding: 12px 16px; max-width: 280px; font-size: 0.82rem; }
		#cal-detail h3 { font-size: 0.9rem; margin-bottom: 6px; }
		#cal-detail .detail-row { margin: 3px 0; color: var(--cal-muted); }
		#cal-detail .detail-actions { margin-top: 10px; display: flex; gap: 8px; }
		#cal-detail .detail-actions a, #cal-detail .detail-actions button { font-size: 0.78rem; color: var(--cal-accent); background: none; border: none; cursor: pointer; padding: 0; text-decoration: none; }
		#cal-detail .close-detail { float: right; background: none; border: none; color: var(--cal-muted); cursor: pointer; font-size: 1rem; }

		@media (max-width: 600px) {
			#cal-month td { min-height: 50px; }
			#cal-header { flex-wrap: wrap; }
		}
	</style>
</head>
<?php
/* Blank Canvas APOLLO+ — opens <body class="ax-body"> and prints the mandatory
   shell chrome (topbar + panels when logged in, login control when not).
   `ax-body` also supplies the top padding the fixed .ax-top requires. */
if ( function_exists( 'apollo_render_blank_canvas_body' ) ) {
	apollo_render_blank_canvas_body( array( 'variant' => 'plus' ) );
} else {
	echo '<body class="ax-body">';
}
?>

<!-- JS Config (nonce + REST root) — logged-in users only -->
<script>
var ApolloCalendarConfig = <?php echo $js_config; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already JSON encoded by wp_json_encode in CalendarPage. ?>;
</script>

<div id="apollo-cal">

	<!-- Header -->
	<header id="cal-header">
		<h1><?php esc_html_e( 'Minha Agenda', 'apollo-calendar' ); ?></h1>

		<div id="cal-nav">
			<button id="btn-prev" title="<?php esc_attr_e( 'Anterior', 'apollo-calendar' ); ?>">&#8249;</button>
			<span id="cal-date-label">…</span>
			<button id="btn-next" title="<?php esc_attr_e( 'Próximo', 'apollo-calendar' ); ?>">&#8250;</button>
			<button id="btn-today" class="btn"><?php esc_html_e( 'Hoje', 'apollo-calendar' ); ?></button>
		</div>

		<div id="cal-tabs">
			<button data-view="month" class="active"><?php esc_html_e( 'Mês', 'apollo-calendar' ); ?></button>
			<button data-view="week"><?php esc_html_e( 'Semana', 'apollo-calendar' ); ?></button>
			<button data-view="day"><?php esc_html_e( 'Dia', 'apollo-calendar' ); ?></button>
		</div>

		<button class="btn primary" id="btn-add-appt">+ <?php esc_html_e( 'Novo', 'apollo-calendar' ); ?></button>
	</header>

	<!-- Legend -->
	<div id="cal-legend">
		<span><span class="legend-dot" style="background:var(--cal-personal)"></span><?php esc_html_e( 'Pessoal', 'apollo-calendar' ); ?></span>
		<span><span class="legend-dot" style="background:var(--cal-event)"></span><?php esc_html_e( 'Eventos', 'apollo-calendar' ); ?></span>
		<span><span class="legend-dot" style="background:var(--cal-sched)"></span><?php esc_html_e( 'Agendamentos', 'apollo-calendar' ); ?></span>
		<span><span class="legend-dot" style="background:var(--cal-holiday)"></span><?php esc_html_e( 'Feriados', 'apollo-calendar' ); ?></span>
	</div>

	<!-- Calendar body -->
	<div id="cal-body">
		<div class="cal-loading"><div class="spinner"></div><?php esc_html_e( 'Carregando…', 'apollo-calendar' ); ?></div>
	</div>

</div><!-- #apollo-cal -->

<!-- Detail popup -->
<div id="cal-detail">
	<button class="close-detail" id="btn-close-detail" aria-label="<?php esc_attr_e( 'Fechar', 'apollo-calendar' ); ?>">✕</button>
	<h3 id="detail-title"></h3>
	<div id="detail-time" class="detail-row"></div>
	<div id="detail-location" class="detail-row"></div>
	<div class="detail-actions" id="detail-actions"></div>
</div>

<!-- Add / Edit Appointment Modal -->
<div id="cal-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-title">
	<div id="cal-modal">
		<h2 id="modal-title"><?php esc_html_e( 'Novo Compromisso', 'apollo-calendar' ); ?></h2>

		<div class="cal-form-row">
			<label for="appt-title"><?php esc_html_e( 'Título *', 'apollo-calendar' ); ?></label>
			<input type="text" id="appt-title" maxlength="255" required>
		</div>
		<div class="cal-form-row">
			<label for="appt-start"><?php esc_html_e( 'Início *', 'apollo-calendar' ); ?></label>
			<input type="datetime-local" id="appt-start" required>
		</div>
		<div class="cal-form-row">
			<label for="appt-end"><?php esc_html_e( 'Fim', 'apollo-calendar' ); ?></label>
			<input type="datetime-local" id="appt-end">
		</div>
		<div class="cal-form-row">
			<label><input type="checkbox" id="appt-allday"> <?php esc_html_e( 'Dia inteiro', 'apollo-calendar' ); ?></label>
		</div>
		<div class="cal-form-row">
			<label for="appt-location"><?php esc_html_e( 'Local', 'apollo-calendar' ); ?></label>
			<input type="text" id="appt-location" maxlength="255">
		</div>
		<div class="cal-form-row">
			<label for="appt-desc"><?php esc_html_e( 'Descrição', 'apollo-calendar' ); ?></label>
			<textarea id="appt-desc" rows="3"></textarea>
		</div>
		<div class="cal-form-row">
			<label for="appt-color"><?php esc_html_e( 'Cor', 'apollo-calendar' ); ?></label>
			<input type="color" id="appt-color" value="#6366f1" style="width:50px;height:32px;padding:2px;">
		</div>
		<div class="cal-form-row">
			<label for="appt-remind"><?php esc_html_e( 'Lembrar (minutos antes)', 'apollo-calendar' ); ?></label>
			<select id="appt-remind">
				<option value="0"><?php esc_html_e( 'Sem lembrete', 'apollo-calendar' ); ?></option>
				<option value="10">10 <?php esc_html_e( 'min', 'apollo-calendar' ); ?></option>
				<option value="30">30 <?php esc_html_e( 'min', 'apollo-calendar' ); ?></option>
				<option value="60">1 <?php esc_html_e( 'hora', 'apollo-calendar' ); ?></option>
				<option value="1440">1 <?php esc_html_e( 'dia', 'apollo-calendar' ); ?></option>
			</select>
		</div>

		<div id="modal-error" class="cal-error" style="display:none"></div>

		<div class="cal-modal-actions">
			<button class="btn" id="btn-modal-cancel"><?php esc_html_e( 'Cancelar', 'apollo-calendar' ); ?></button>
			<button class="btn primary" id="btn-modal-save"><?php esc_html_e( 'Salvar', 'apollo-calendar' ); ?></button>
		</div>
	</div>
</div>

<?php require APOLLO_CALENDAR_DIR . 'templates/parts/calendar.js.php'; ?>

</body>
</html>
