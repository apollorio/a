<?php
/**
 * Panel: Forms — Create/Edit Content (Logged DOWN)
 *
 * All creation forms slide DOWN from center.
 * data-panel="forms" data-dir="down"
 *
 * Form sections switchable via [data-show-form] triggers.
 * Each section loads plugin content via hooks.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 * @see     _inventory/pages-layout.json → forms_rule
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
?>
<section data-panel="forms" data-glyph="✎"
	<?php if ( $user_id ) : ?> data-a-user="<?php echo esc_attr( (string) $user_id ); ?>"<?php endif; ?>
	data-a-component="panel-forms">

	<!-- ── Header ── -->
	<header class="pnl-head">
		<button class="pnl-head__back" onclick="ApolloSlider&&ApolloSlider.back()" aria-label="Voltar">
			<i class="ri-arrow-left-s-line"></i>
		</button>
		<h2 class="pnl-head__title"><?php esc_html_e( 'Criar', 'apollo-templates' ); ?></h2>
	</header>

	<div class="pnl-body">

		<!-- ── Form Navigation (shown by default) ── -->
		<div class="pnl-form-nav" id="formsNav">

			<button class="pnl-card" data-show-form="create-event">
				<div class="pnl-card__row">
					<span class="pnl-card__avatar" style="background:rgba(244,95,0,.12);color:var(--primary)"><i class="ri-calendar-event-fill"></i></span>
					<div class="pnl-card__info">
						<span class="pnl-card__name"><?php esc_html_e( 'Criar Evento', 'apollo-templates' ); ?></span>
						<span class="pnl-card__meta"><?php esc_html_e( 'Adicionar novo evento à plataforma', 'apollo-templates' ); ?></span>
					</div>
					<i class="ri-arrow-right-s-line" style="color:var(--txt-muted)"></i>
				</div>
			</button>

			<button class="pnl-card" data-show-form="create-ad">
				<div class="pnl-card__row">
					<span class="pnl-card__avatar" style="background:rgba(59,130,246,.12);color:#3b82f6"><i class="ri-exchange-box-line"></i></span>
					<div class="pnl-card__info">
						<span class="pnl-card__name"><?php esc_html_e( 'Criar Anúncio', 'apollo-templates' ); ?></span>
						<span class="pnl-card__meta"><?php esc_html_e( 'Publicar no marketplace', 'apollo-templates' ); ?></span>
					</div>
					<i class="ri-arrow-right-s-line" style="color:var(--txt-muted)"></i>
				</div>
			</button>

			<button class="pnl-card" data-show-form="create-group">
				<div class="pnl-card__row">
					<span class="pnl-card__avatar" style="background:rgba(234,179,8,.12);color:#eab308"><i class="ri-team-fill"></i></span>
					<div class="pnl-card__info">
						<span class="pnl-card__name"><?php esc_html_e( 'Criar Grupo', 'apollo-templates' ); ?></span>
						<span class="pnl-card__meta"><?php esc_html_e( 'Nova comuna ou grupo', 'apollo-templates' ); ?></span>
					</div>
					<i class="ri-arrow-right-s-line" style="color:var(--txt-muted)"></i>
				</div>
			</button>

			<button class="pnl-card" data-show-form="report">
				<div class="pnl-card__row">
					<span class="pnl-card__avatar" style="background:rgba(239,68,68,.12);color:#ef4444"><i class="ri-alarm-warning-fill"></i></span>
					<div class="pnl-card__info">
						<span class="pnl-card__name"><?php esc_html_e( 'Reportar', 'apollo-templates' ); ?></span>
						<span class="pnl-card__meta"><?php esc_html_e( 'Denunciar conteúdo ou problema', 'apollo-templates' ); ?></span>
					</div>
					<i class="ri-arrow-right-s-line" style="color:var(--txt-muted)"></i>
				</div>
			</button>

			<button class="pnl-card" data-show-form="depoimento">
				<div class="pnl-card__row">
					<span class="pnl-card__avatar" style="background:rgba(168,85,247,.12);color:#a855f7"><i class="ri-quill-pen-fill"></i></span>
					<div class="pnl-card__info">
						<span class="pnl-card__name"><?php esc_html_e( 'Depoimento', 'apollo-templates' ); ?></span>
						<span class="pnl-card__meta"><?php esc_html_e( 'Escrever review ou depoimento', 'apollo-templates' ); ?></span>
					</div>
					<i class="ri-arrow-right-s-line" style="color:var(--txt-muted)"></i>
				</div>
			</button>

			<?php
			/**
			 * Hook: apollo/forms/extra_nav_items
			 * Plugins can add more form navigation cards.
			 */
			do_action( 'apollo/forms/extra_nav_items', $user_id );
			?>
		</div>

		<!-- ── Form Sections (hidden, shown when nav card clicked) ── -->

		<div class="pnl-form-section" data-form-id="create-event" hidden>
			<button class="pnl-form-back" data-form-back>
				<i class="ri-arrow-left-s-line"></i> <?php esc_html_e( 'Voltar', 'apollo-templates' ); ?>
			</button>
			<?php do_action( 'apollo/events/render_create_form', $user_id ); ?>
		</div>

		<div class="pnl-form-section" data-form-id="create-ad" hidden>
			<button class="pnl-form-back" data-form-back>
				<i class="ri-arrow-left-s-line"></i> <?php esc_html_e( 'Voltar', 'apollo-templates' ); ?>
			</button>
			<?php do_action( 'apollo/adverts/render_create_form', $user_id ); ?>
		</div>

		<div class="pnl-form-section" data-form-id="create-group" hidden>
			<button class="pnl-form-back" data-form-back>
				<i class="ri-arrow-left-s-line"></i> <?php esc_html_e( 'Voltar', 'apollo-templates' ); ?>
			</button>
			<?php do_action( 'apollo/groups/render_create_form', $user_id ); ?>
		</div>

		<div class="pnl-form-section" data-form-id="report" hidden>
			<button class="pnl-form-back" data-form-back>
				<i class="ri-arrow-left-s-line"></i> <?php esc_html_e( 'Voltar', 'apollo-templates' ); ?>
			</button>
			<?php do_action( 'apollo/mod/render_report_form', $user_id ); ?>
		</div>

		<div class="pnl-form-section" data-form-id="depoimento" hidden>
			<button class="pnl-form-back" data-form-back>
				<i class="ri-arrow-left-s-line"></i> <?php esc_html_e( 'Voltar', 'apollo-templates' ); ?>
			</button>
			<?php do_action( 'apollo/comment/render_depoimento_form', $user_id ); ?>
		</div>

		<?php do_action( 'apollo/forms/extra_sections', $user_id ); ?>
	</div>
</section>

<script>
(function(){
	var nav = document.getElementById('formsNav');
	if (!nav) return;
	var sections = document.querySelectorAll('.pnl-form-section');

	/* Show form section */
	nav.addEventListener('click', function(e){
		var trigger = e.target.closest('[data-show-form]');
		if (!trigger) return;
		var id = trigger.getAttribute('data-show-form');
		nav.hidden = true;
		sections.forEach(function(s){
			s.hidden = s.getAttribute('data-form-id') !== id;
		});
	});

	/* Back to nav */
	document.addEventListener('click', function(e){
		if (!e.target.closest('[data-form-back]')) return;
		nav.hidden = false;
		sections.forEach(function(s){ s.hidden = true; });
	});

	/* External trigger (from FAB or other panels): data-form="create-event" etc */
	document.addEventListener('apollo:form:open', function(e){
		var id = e.detail && e.detail.form;
		if (!id) return;
		nav.hidden = true;
		sections.forEach(function(s){
			s.hidden = s.getAttribute('data-form-id') !== id;
		});
	});
})();
</script>
