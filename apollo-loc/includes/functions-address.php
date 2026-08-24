<?php
/**
 * Funções de endereço do Apollo Local
 *
 * Helpers para montar, formatar e exibir endereços e links sociais.
 *
 * @package Apollo\Local
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna endereço completo formatado.
 *
 * @param int $local_id Post ID do local.
 * @return string Endereço no formato "Rua X, Bairro, Cidade - Estado".
 */
function apollo_local_get_address( int $local_id ): string {
	$address = get_post_meta( $local_id, '_local_address', true );
	$city    = get_post_meta( $local_id, '_local_city', true );
	$state   = get_post_meta( $local_id, '_local_state', true );

	$parts = array_filter( array( $address, $city, $state ) );
	return implode( ', ', $parts );
}

/**
 * Retorna links sociais do local em formato estruturado.
 *
 * @param int $local_id Post ID do local.
 * @return array<int, array{url:string, icon:string, label:string}> Lista de links.
 */
function apollo_local_get_links( int $local_id ): array {
	$links = array();

	$defs = array(
		array( '_local_website',   'ri-global-line',          __( 'Site oficial', 'apollo-local' ) ),
		array( '_local_instagram', 'ri-instagram-line',        'Instagram' ),
		array( '_local_facebook',  'ri-facebook-circle-line',  'Facebook' ),
		array( '_local_whatsapp',  'ri-whatsapp-line',         'WhatsApp' ),
	);

	foreach ( $defs as [ $key, $icon, $label ] ) {
		$url = get_post_meta( $local_id, $key, true );
		if ( ! $url ) {
			continue;
		}
		if ( $key === '_local_whatsapp' ) {
			$digits = preg_replace( '/[^0-9]/', '', $url );
			if ( $digits ) {
				$url = 'https://wa.me/' . $digits;
			}
		}
		$links[] = array(
			'url'   => esc_url_raw( $url ),
			'icon'  => $icon,
			'label' => $label,
		);
	}

	$phone = get_post_meta( $local_id, '_local_phone', true );
	if ( $phone ) {
		$links[] = array(
			'url'   => 'tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ),
			'icon'  => 'ri-phone-line',
			'label' => $phone,
		);
	}

	return $links;
}

/**
 * Retorna tipos (taxonomia local_type) do local como array de nomes.
 *
 * @param int $local_id Post ID do local.
 * @return string[] Lista de nomes dos tipos.
 */
function apollo_local_get_types( int $local_id ): array {
	$terms = wp_get_post_terms( $local_id, APOLLO_LOCAL_TAX_TYPE, array( 'fields' => 'names' ) );
	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Retorna zonas/áreas (taxonomia local_area) do local como array de nomes.
 *
 * @param int $local_id Post ID do local.
 * @return string[] Lista de nomes das áreas.
 */
function apollo_local_get_areas( int $local_id ): array {
	$terms = wp_get_post_terms( $local_id, APOLLO_LOCAL_TAX_AREA, array( 'fields' => 'names' ) );
	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Retorna URL da thumbnail do local (primeiro meta ou featured image).
 *
 * @param int    $local_id Post ID.
 * @param string $size     Tamanho do thumbnail. Default 'large'.
 * @return string URL da imagem.
 */
function apollo_local_get_image( int $local_id, string $size = 'large' ): string {
	$img_meta = get_post_meta( $local_id, '_local_image_1', true );
	if ( $img_meta ) {
		return is_numeric( $img_meta )
			? (string) wp_get_attachment_image_url( (int) $img_meta, $size )
			: $img_meta;
	}

	$thumb = get_the_post_thumbnail_url( $local_id, $size );
	if ( $thumb ) {
		return $thumb;
	}

	return APOLLO_LOCAL_URL . 'assets/images/placeholder-local.svg';
}
