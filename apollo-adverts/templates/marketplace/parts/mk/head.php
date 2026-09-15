<?php
/**
 * Marketplace — hero, safety CTA, portal search, actions.
 *
 * @package Apollo\Adverts
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mk_logged = is_user_logged_in();
$mk_create = $mk_logged
	? home_url( '/novo-anuncio/' )
	: home_url( '/acesso?redirect=' . rawurlencode( home_url( '/novo-anuncio/' ) ) );
$mk_mine   = $mk_logged
	? home_url( '/anuncios/meus/' )
	: home_url( '/acesso?redirect=' . rawurlencode( home_url( '/anuncios/meus/' ) ) );
?>
<div class="mk-head-row">
	<div class="market-hero">
		<h1><?php esc_html_e( 'CLASSIFICA', 'apollo-adverts' ); ?><span>::</span><?php esc_html_e( 'DOS', 'apollo-adverts' ); ?></h1>
		<p class="classificados-eyebrow"><?php esc_html_e( 'Repasses de ingresso · projeto social', 'apollo-adverts' ); ?></p>
	</div>
	<div class="mk-head-actions">
		<a class="btn btn-primary mk-cta" href="<?php echo esc_url( $mk_create ); ?>">
			<i class="ri-add-line" aria-hidden="true"></i>
			<?php echo $mk_logged ? esc_html__( 'Novo anúncio', 'apollo-adverts' ) : esc_html__( 'Anunciar', 'apollo-adverts' ); ?>
		</a>
		<?php if ( $mk_logged ) : ?>
			<a class="btn btn-secondary mk-ghost" href="<?php echo esc_url( $mk_mine ); ?>">
				<i class="ri-list-check-2" aria-hidden="true"></i>
				<?php esc_html_e( 'Meus', 'apollo-adverts' ); ?>
			</a>
		<?php endif; ?>
		<button type="button" class="btn btn-secondary mk-safety" data-apollo-suporte>
			<i class="ri-shield-check-line" data-apollo-suporte aria-hidden="true"></i>
			<span data-apollo-suporte><?php esc_html_e( 'Segurança', 'apollo-adverts' ); ?></span>
		</button>
	</div>
</div>

<div class="cta-card reveal-up" id="ctaSafetyAwareness">
	<div class="cta-icon-badge"><i class="ri-shield-flash-line" aria-hidden="true"></i></div>
	<div class="cta-card-body">
		<h4 class="cta-card-title"><?php esc_html_e( 'Garanta o rolê sem dor de cabeça!', 'apollo-adverts' ); ?></h4>
		<p><?php esc_html_e( 'O apollo::rio é só a ponte para te conectar à galera. A negociação e o pagamento rolam 100% entre vocês, fora da plataforma. Antes de mandar qualquer Pix, faça a checagem básica de segurança para não cair em furada!', 'apollo-adverts' ); ?></p>
	</div>
	<button type="button" class="btn" id="btnSafetyTips">
		<i class="ri-shield-check-line" aria-hidden="true"></i>
		<?php esc_html_e( 'Dicas de Segurança', 'apollo-adverts' ); ?>
	</button>
</div>

<div class="classificados-search reveal-up" id="classificadosSearch">
	<div class="classificados-search-row">
		<label class="sr-only" for="classificadosSearchScope"><?php esc_html_e( 'Buscar em', 'apollo-adverts' ); ?></label>
		<select id="classificadosSearchScope" aria-label="<?php esc_attr_e( 'Buscar em', 'apollo-adverts' ); ?>">
			<option value="all"><?php esc_html_e( 'Tudo', 'apollo-adverts' ); ?></option>
			<option value="event"><?php esc_html_e( 'Evento', 'apollo-adverts' ); ?></option>
			<option value="seller"><?php esc_html_e( 'Vendedor', 'apollo-adverts' ); ?></option>
			<option value="venue"><?php esc_html_e( 'Local', 'apollo-adverts' ); ?></option>
		</select>
		<input
			type="search"
			id="classificadosSearchInput"
			data-mk-search
			autocomplete="off"
			placeholder="<?php esc_attr_e( 'Buscar ingressos, eventos, vendedores…', 'apollo-adverts' ); ?>"
			aria-label="<?php esc_attr_e( 'Buscar anúncios', 'apollo-adverts' ); ?>"
		>
		<button type="button" class="classificados-search-btn" id="classificadosSearchBtn" aria-label="<?php esc_attr_e( 'Buscar', 'apollo-adverts' ); ?>">
			<i class="ri-search-line" aria-hidden="true"></i>
		</button>
	</div>
</div>
