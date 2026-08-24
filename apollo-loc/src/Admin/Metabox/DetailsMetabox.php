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