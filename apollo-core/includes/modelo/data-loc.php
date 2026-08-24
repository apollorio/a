<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * MODELO · LOCAL — the seed payload
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Data only. Same contract and same naming rule as data-dj.php: nothing
 * here may name a real venue, a real address or a real business. A demo
 * record carrying a real club's name and coordinates is a liability the
 * moment somebody screenshots it.
 *
 * Coordinates are a deliberate non-place: 0,0 in the Gulf of Guinea. It
 * renders a map, it is unmistakably fake, and no real address is implied.
 *
 * Media: Unsplash (free to use, no attribution required) — already the
 * host used by the existing placeholders, so already reachable under CSP.
 *
 * @package Apollo\Core
 * @since   6.2.8
 * @return  array<string,mixed>
 */

if (! defined('ABSPATH')) {
    exit;
}

$img = 'https://images.unsplash.com/photo-';

return array(

    'post' => array(
        'post_title'   => 'Modelo Espaço',
        'post_name'    => 'modelo',
        'post_status'  => 'publish',
        'post_excerpt' => 'Registro modelo do CPT local — mostra a página de espaço com todos os campos preenchidos.',
        'post_content' => 'Este é o registro MODELO do CPT `local`. Ele existe para mostrar como a página '
            . '/local/{slug} se comporta com 100% dos campos preenchidos. Nenhum dado aqui é real: '
            . 'nome, endereço, coordenadas e mídias são marcadores. As coordenadas apontam para '
            . '0,0 — um ponto no oceano — justamente para que nenhum endereço verdadeiro seja sugerido.',
    ),

    'meta' => array(
        /* identidade */
        '_local_name'    => 'Modelo Espaço',
        '_local_tagline' => 'Frase editorial que define o espaço',

        /* endereço — não-lugar, por decisão */
        '_local_address' => 'Rua Modelo, 000 — Bairro Modelo',
        '_local_city'    => 'Cidade Modelo',
        '_local_state'   => 'UF',
        '_local_country' => 'Brasil',
        /* CEP deixado neutro DE PROPÓSITO: um CEP plausível aponta para uma rua
           real. As coordenadas já são 0,0 (não-lugar) e o endereço acompanha. */
        '_local_postal'  => '00000-000',
        '_local_lat'     => 0.0,
        '_local_lng'     => 0.0,

        /* fatos */
        /* Ano realista deste século — a página deriva "15+ anos de pista" daqui,
           e 2000 fazia o número parecer arredondado demais para ser real. */
        '_local_founded_year' => 2011,
        '_local_rooms'        => array( 'Ambiente Um', 'Ambiente Dois', 'Ambiente Três' ),
        '_local_capacity'     => 850, // registrado e realista, mas a Apollo NUNCA renderiza lotação — ver VIO no mockup
        '_local_price_range'  => '$$',

        /* descrição — um <em> permitido */
        '_local_description' => 'Parágrafo sobre o espaço: o que ele é, o que se ouve ali e o que o '
            . 'distingue. Uma expressão recebe <em>destaque</em> e só uma, do mesmo jeito que na '
            . 'página de artista. Este texto é o statement fixado no scroll.',

        /* horários — 0=Seg … 6=Dom */
        '_local_hours' => array(
            'Fechado', 'Fechado', 'Fechado', 'Fechado',
            '23h — 07h', '23h — 08h', 'Fechado',
        ),

        /* estrutura — alimenta a seção Estrutura e o marquee */
        '_local_amenities' => array(
            array( 'icon' => 'ri-speaker-line',      'name' => 'Item de estrutura um',   'sub' => 'Detalhe curto do item' ),
            array( 'icon' => 'ri-goblet-line',       'name' => 'Item de estrutura dois', 'sub' => 'Detalhe curto do item' ),
            array( 'icon' => 'ri-parking-box-line',  'name' => 'Item de estrutura três', 'sub' => 'Detalhe curto do item' ),
            array( 'icon' => 'ri-wheelchair-line',   'name' => 'Item de estrutura quatro', 'sub' => 'Detalhe curto do item' ),
            array( 'icon' => 'ri-shield-check-line', 'name' => 'Item de estrutura cinco', 'sub' => 'Detalhe curto do item' ),
            array( 'icon' => 'ri-restaurant-line',   'name' => 'Item de estrutura seis',  'sub' => 'Detalhe curto do item' ),
        ),

        /* galeria — os cinco slots, para provar que o builder consome todos */
        '_local_image_1' => $img . '1571266028243-e4733b0f0bb0?w=1800&q=82',
        '_local_image_2' => $img . '1516450360452-9312f5e86fc7?w=1400&q=80',
        '_local_image_3' => $img . '1534528741775-53994a69daeb?w=1400&q=80',
        '_local_image_4' => $img . '1492684223066-81342ee5ff30?w=1400&q=80',
        '_local_image_5' => $img . '1514525253161-7a46d19cd819?w=1400&q=80',

        /* contato */
        /* Formato realista, prefixo 5555-01xx — o mesmo padrão que a ficção usa
           para telefones que não tocam em ninguém. Não é uma faixa formalmente
           reservada no Brasil, então é convenção, não garantia. */
        '_local_phone'     => '+55 21 5555-0142',
        '_local_whatsapp'  => 'https://wa.me/552155550142',
        '_local_website'   => 'https://modelo-espaco.exemplo.br',
        '_local_instagram' => 'https://instagram.com/modelo_espaco',
        '_local_facebook'  => 'https://facebook.com/modelo.espaco',
    ),

    'terms' => array(
        'local_type' => array( 'Tipo Modelo' ),
        'local_area' => array( 'Bairro Modelo' ),
    ),
);
