<?php
/**
 * DetailsMetabox — Capacidade, faixa de preço, descrição extra
 *
 * @package Apollo\Local\Admin\Metabox
 */

declare(strict_types=1);

namespace Apollo\Local\Admin\Metabox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DetailsMetabox {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
	}

	public function register(): void {
		add_meta_box(
			'apollo-loc-details',
			'🏟 ' . __( 'Detalhes do Local', 'apollo-local' ),
			array( $this, 'render' ),
			APOLLO_LOCAL_CPT,
			'normal',
			'default'
		);
	}

	public function render( \WP_Post $post ): void {
		$capacity    = absint( get_post_meta( $post->ID, '_local_capacity', true ) );
		$price_range = get_post_meta( $post->ID, '_local_price_range', true );
		$description = get_post_meta( $post->ID, '_local_description', true );
		$hours       = get_post_meta( $post->ID, '_local_hours', true );
		if ( is_string( $hours ) && '' !== $hours ) {
			$decoded = json_decode( $hours, true );
			$hours   = is_array( $decoded ) ? $decoded : array();
		}
		if ( ! is_array( $hours ) ) {
			$hours = array();
		}
		$day_names = array(
			__( 'Segunda', 'apollo-local' ),
			__( 'Terça', 'apollo-local' ),
			__( 'Quarta', 'apollo-local' ),
			__( 'Quinta', 'apollo-local' ),
			__( 'Sexta', 'apollo-local' ),
			__( 'Sábado', 'apollo-local' ),
			__( 'Domingo', 'apollo-local' ),
		);
		$amenities   = get_post_meta( $post->ID, '_local_amenities', true );
		if ( ! is_array( $amenities ) ) {
			$amenities = array();
		}

		/* ─── 2026-08-25 · CLOSING THE NO-INPUT GAP ───────────────────────
		   These six keys were registered in apollo-core but had no control
		   anywhere in wp-admin, so nothing could ever fill them and every
		   template reading them rendered empty forever. That is the exact
		   failure 21-mockup-field-contract warns about: "Shipping a section
		   fed by a key with no input path is how the mockup ends up
		   permanently empty in production."
		   _local_rooms is stored as an array but edited as one comma line —
		   the count on the single is DERIVED from it, never typed. */
		$region       = (string) get_post_meta( $post->ID, '_local_region', true );
		$tagline      = (string) get_post_meta( $post->ID, '_local_tagline', true );
		$founded_year = absint( get_post_meta( $post->ID, '_local_founded_year', true ) );
		$loc_user_id  = absint( get_post_meta( $post->ID, '_local_user_id', true ) );
		$rooms        = get_post_meta( $post->ID, '_local_rooms', true );
		if ( ! is_array( $rooms ) ) {
			$rooms = array();
		}
		$testimonials = get_post_meta( $post->ID, '_local_testimonials', true );
		if ( ! is_array( $testimonials ) ) {
			$testimonials = array();
		}

		/* Region options mirror the five informal zones the accommodation map
		   filters on (apollo-adverts market regions). Kept as a fixed list, not
		   a taxonomy: nothing else groups by informal zone, and a free-text
		   field would drift out of sync with the map keys immediately. */
		$region_options = array(
			''           => __( '— Selecione —', 'apollo-local' ),
			'zona-sul'   => __( 'Zona Sul', 'apollo-local' ),
			'zona-norte' => __( 'Zona Norte', 'apollo-local' ),
			'centro'     => __( 'Centro', 'apollo-local' ),
			'zona-oeste' => __( 'Zona Oeste', 'apollo-local' ),
			'niteroi-sg' => __( 'Niterói / São Gonçalo', 'apollo-local' ),
		);

		$options = array(
			''     => __( '— Selecione —', 'apollo-local' ),
			'$'    => '$ — ' . __( 'Econômico', 'apollo-local' ),
			'$$'   => '$$ — ' . __( 'Moderado', 'apollo-local' ),
			'$$$'  => '$$$ — ' . __( 'Caro', 'apollo-local' ),
			'$$$$' => '$$$$ — ' . __( 'Luxo', 'apollo-local' ),
		);
		?>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:4px 0;">
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'Capacidade (pessoas)', 'apollo-local' ); ?>
				</label>
				<input type="number" name="_local_capacity" value="<?php echo esc_attr( $capacity ?: '' ); ?>" class="widefat"
				       min="0" placeholder="800">
			</p>
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'Faixa de preço', 'apollo-local' ); ?>
				</label>
				<select name="_local_price_range" class="widefat">
					<?php foreach ( $options as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $price_range, $val ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'Zona / Região', 'apollo-local' ); ?>
				</label>
				<select name="_local_region" class="widefat">
					<?php foreach ( $region_options as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $region, $val ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<span class="description" style="font-size:11px;">
					<?php esc_html_e( 'Usada pelo filtro de zonas do mapa de acomodações.', 'apollo-local' ); ?>
				</span>
			</p>
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'Ano de fundação', 'apollo-local' ); ?>
				</label>
				<input type="number" name="_local_founded_year" value="<?php echo esc_attr( $founded_year ?: '' ); ?>" class="widefat"
				       min="1900" max="<?php echo esc_attr( (string) ( (int) current_time( 'Y' ) ) ); ?>" placeholder="2003">
				<span class="description" style="font-size:11px;">
					<?php esc_html_e( 'O "X anos" no perfil é derivado daqui — nunca digite o número de anos.', 'apollo-local' ); ?>
				</span>
			</p>
			<p style="grid-column:1/-1;margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'Tagline editorial (kicker do hero)', 'apollo-local' ); ?>
				</label>
				<input type="text" name="_local_tagline" value="<?php echo esc_attr( $tagline ); ?>" class="widefat"
				       placeholder="<?php esc_attr_e( 'Ex: Templo do Techno', 'apollo-local' ); ?>">
			</p>
			<p style="grid-column:1/-1;margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'Ambientes / salas', 'apollo-local' ); ?>
				</label>
				<input type="text" name="_local_rooms" value="<?php echo esc_attr( implode( ', ', array_map( 'strval', $rooms ) ) ); ?>" class="widefat"
				       placeholder="<?php esc_attr_e( 'pista, lounge, rooftop', 'apollo-local' ); ?>">
				<span class="description" style="font-size:11px;">
					<?php esc_html_e( 'Separe por vírgula. A contagem exibida no perfil é derivada desta lista — não é a capacidade.', 'apollo-local' ); ?>
				</span>
			</p>
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'User ID vinculado (dono/gestor)', 'apollo-local' ); ?>
				</label>
				<input type="number" name="_local_user_id" value="<?php echo esc_attr( $loc_user_id ?: '' ); ?>" class="widefat"
				       min="0" placeholder="0">
			</p>
			<p style="margin:0;align-self:end;">
				<span class="description" style="font-size:11px;">
					<?php
					/* translators: %d = number of stored testimonials. */
					printf( esc_html__( 'Depoimentos armazenados: %d — geridos pelo apollo-comment, não editáveis aqui.', 'apollo-local' ), count( $testimonials ) );
					?>
				</span>
			</p>
			<p style="grid-column:1/-1;margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'Descrição adicional (exibida no perfil)', 'apollo-local' ); ?>
				</label>
				<textarea name="_local_description" class="widefat" rows="4"
				          placeholder="<?php esc_attr_e( 'Descreva o espaço em detalhes...', 'apollo-local' ); ?>"><?php echo esc_textarea( (string) $description ); ?></textarea>
			</p>
			<div style="grid-column:1/-1;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:6px;">
					<?php esc_html_e( 'Horário de funcionamento (deixe vazio = Fechado)', 'apollo-local' ); ?>
				</label>
				<?php foreach ( $day_names as $idx => $day_name ) :
					$day_val = isset( $hours[ $idx ] )
						? ( is_array( $hours[ $idx ] ) ? trim( ( $hours[ $idx ]['open'] ?? '' ) . ' - ' . ( $hours[ $idx ]['close'] ?? '' ), ' -' ) : (string) $hours[ $idx ] )
						: '';
					?>
					<div style="display:grid;grid-template-columns:110px 1fr;gap:8px;margin-bottom:6px;align-items:center;">
						<span style="font-size:12px;color:#50575e;"><?php echo esc_html( $day_name ); ?></span>
						<input type="text" name="_local_hours[<?php echo esc_attr( (string) $idx ); ?>]" value="<?php echo esc_attr( $day_val ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Ex: 23h – 08h', 'apollo-local' ); ?>">
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div style="margin-top:16px;">
			<label style="font-weight:600;font-size:12px;display:block;margin-bottom:6px;">
				<?php esc_html_e( 'Estrutura & Comodidades', 'apollo-local' ); ?>
			</label>
			<p class="description" style="margin-top:0;">
				<?php esc_html_e( 'Ícone (RemixIcon, ex: ri-speaker-line), nome e descrição curta.', 'apollo-local' ); ?>
			</p>
			<div id="apl-loc-amenities">
				<?php
				$rows = ! empty( $amenities ) ? $amenities : array( array( 'icon' => '', 'name' => '', 'sub' => '' ) );
				foreach ( $rows as $a ) :
					?>
					<div class="apl-loc-amenity-row" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center;">
						<input type="text" name="_local_amenities_icon[]" value="<?php echo esc_attr( (string) ( $a['icon'] ?? '' ) ); ?>" placeholder="ri-speaker-line" class="widefat">
						<input type="text" name="_local_amenities_name[]" value="<?php echo esc_attr( (string) ( $a['name'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Nome', 'apollo-local' ); ?>" class="widefat">
						<input type="text" name="_local_amenities_sub[]" value="<?php echo esc_attr( (string) ( $a['sub'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Descrição curta', 'apollo-local' ); ?>" class="widefat">
						<button type="button" class="button button-small apl-loc-amenity-remove" aria-label="<?php esc_attr_e( 'Remover', 'apollo-local' ); ?>">✕</button>
					</div>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button" id="apl-loc-amenity-add">+ <?php esc_html_e( 'Adicionar comodidade', 'apollo-local' ); ?></button>
		</div>
		<script>
		(function(){
			var wrap = document.getElementById('apl-loc-amenities');
			var add  = document.getElementById('apl-loc-amenity-add');
			if (!wrap || !add) return;
			add.addEventListener('click', function(){
				var row = wrap.querySelector('.apl-loc-amenity-row');
				var clone = row.cloneNode(true);
				clone.querySelectorAll('input').forEach(function(i){ i.value=''; });
				wrap.appendChild(clone);
			});
			wrap.addEventListener('click', function(e){
				if (!e.target.classList.contains('apl-loc-amenity-remove')) return;
				var rows = wrap.querySelectorAll('.apl-loc-amenity-row');
				if (rows.length > 1) e.target.closest('.apl-loc-amenity-row').remove();
				else e.target.closest('.apl-loc-amenity-row').querySelectorAll('input').forEach(function(i){ i.value=''; });
			});
		})();
		</script>
		<?php
	}
}