<?php

/**
 * Funções auxiliares do plugin
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

namespace Apollo\Event;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Retorna instância principal do plugin
 */
function apollo_event(): Plugin
{
	return Plugin::get_instance();
}

/**
 * Obtém opção do dashboard Apollo Events
 *
 * @param string $key     Chave da opção.
 * @param mixed  $default Valor padrão.
 * @return mixed
 */
function apollo_event_option(string $key, $default = null)
{
	$options = get_option('apollo_event_settings', array());
	return $options[$key] ?? $default;
}

/**
 * Parseia data de início do evento para exibição
 *
 * @param string $date Data no formato Y-m-d.
 * @return array{timestamp: int, day: string, month_pt: string, iso_date: string, weekday_pt: string}
 */
function apollo_event_parse_date(string $date): array
{
	$meses_pt = array(
		1  => 'Jan',
		2  => 'Fev',
		3  => 'Mar',
		4  => 'Abr',
		5  => 'Mai',
		6  => 'Jun',
		7  => 'Jul',
		8  => 'Ago',
		9  => 'Set',
		10 => 'Out',
		11 => 'Nov',
		12 => 'Dez',
	);

	$dias_pt = array(
		'Mon' => 'Seg',
		'Tue' => 'Ter',
		'Wed' => 'Qua',
		'Thu' => 'Qui',
		'Fri' => 'Sex',
		'Sat' => 'Sáb',
		'Sun' => 'Dom',
	);

	$ts  = strtotime($date);
	$m   = (int) date('n', $ts);
	$dow = date('D', $ts);

	return array(
		'timestamp'  => $ts,
		'day'        => date('d', $ts),
		'month_pt'   => $meses_pt[$m] ?? date('M', $ts),
		'iso_date'   => date('Y-m-d', $ts),
		'weekday_pt' => $dias_pt[$dow] ?? $dow,
	);
}

/**
 * Verifica se um evento está "gone" (expirado)
 *
 * 30 minutos após _event_end_date + _event_end_time
 *
 * @param int $post_id ID do evento.
 * @return bool
 */
function apollo_event_is_gone(int $post_id): bool
{
	// Checa meta já calculado
	$gone = get_post_meta($post_id, '_event_is_gone', true);
	if ('1' === $gone) {
		return true;
	}

	$end_date = get_post_meta($post_id, '_event_end_date', true);
	$end_time = get_post_meta($post_id, '_event_end_time', true);

	if (empty($end_date)) {
		$end_date = get_post_meta($post_id, '_event_start_date', true);
	}
	if (empty($end_time)) {
		$end_time = '23:59';
	}

	$end_ts = strtotime($end_date . ' ' . $end_time);
	if (! $end_ts) {
		return false;
	}

	$gone_ts = $end_ts + (APOLLO_EVENT_GONE_OFFSET_MINUTES * 60);
	$now     = current_time('timestamp');

	if ($now >= $gone_ts) {
		update_post_meta($post_id, '_event_is_gone', '1');
		return true;
	}

	return false;
}

// ═══════════════════════════════════════════════════════════════════════════
// INTERNAL RANKING (_event_int_rank) + VIBE TAGS (_event_tag_*) — 2026-08-24
//
// INTERNAL USE ONLY. NEVER RENDER ON ANY FRONTEND SURFACE. Every meta key in
// this block is registered with show_in_rest => false in
// apollo-core/src/Core/MetaRegistry.php (same pattern as _mod_notes / _doc_cpf),
// so it is structurally invisible to REST and to the Gutenberg meta panel —
// the classic metabox in apollo-events/src/Admin/RankMetabox.php is the only
// write path, and it is gated to manage_options a second time on top of that.
//
// Built to be reused by ANY Apollo plugin (not just apollo-telegram) that
// needs to answer "which event is the best/most precise match right now" —
// a WhatsApp bot, a push-notification job, an admin dashboard widget, etc.
// apollo_event_find_best_match() is the single plug-and-play entry point: it
// already excludes gone/expired events and folds rank + tags + date proximity
// into one ordered result, so a caller never has to re-derive that logic.
// ═══════════════════════════════════════════════════════════════════════════

/**
 * INTERNAL ONLY — mapa canônico das vibe tags do evento (slug => meta key).
 *
 * Única fonte de verdade: usada pelo loop de checkboxes em RankMetabox.php,
 * pelo save() do mesmo arquivo, e por todos os helpers de leitura abaixo. Um
 * checkbox novo nasce e morre aqui — nunca hardcoded em três lugares
 * diferentes que podem desalinhar.
 *
 * @return array<string,string> slug => meta_key
 */
function apollo_event_internal_tags(): array
{
	return array(
		'underground' => '_event_tag_underground',
		'mainstream'  => '_event_tag_mainstream',
		'comercial'   => '_event_tag_comercial',
		'lgbtqia'     => '_event_tag_lgbtqia',
		'sexparty'    => '_event_tag_sexparty',
	);
}

/**
 * INTERNAL ONLY — labels PT-BR para a UI do wp-admin.
 *
 * Separado de apollo_event_internal_tags() de propósito: o slug é o contrato
 * de dados que apollo-telegram (ou qualquer outro plugin) já pode estar
 * usando; o label é só apresentação. Trocar o texto do checkbox nunca deve
 * quebrar quem já lê/grava pelo slug.
 *
 * @return array<string,string> slug => label
 */
function apollo_event_internal_tag_labels(): array
{
	return array(
		'underground' => __('Underground', 'apollo-events'),
		'mainstream'  => __('Mainstream', 'apollo-events'),
		'comercial'   => __('Comercial', 'apollo-events'),
		'lgbtqia'     => __('LGBTQIA+', 'apollo-events'),
		'sexparty'    => __('Sex Party', 'apollo-events'),
	);
}

/**
 * INTERNAL ONLY — todas as vibe tags de um evento (marcada ou não).
 *
 * @param int $post_id ID do evento.
 * @return array<string,bool> slug => true|false
 */
function apollo_event_get_tags(int $post_id): array
{
	$out = array();
	foreach (apollo_event_internal_tags() as $slug => $meta_key) {
		$out[$slug] = '1' === (string) get_post_meta($post_id, $meta_key, true);
	}
	return $out;
}

/**
 * INTERNAL ONLY — checa uma única vibe tag.
 *
 * @param int    $post_id ID do evento.
 * @param string $tag     Slug — ver apollo_event_internal_tags().
 * @return bool
 */
function apollo_event_is_tag(int $post_id, string $tag): bool
{
	$tags = apollo_event_internal_tags();
	if (! isset($tags[$tag])) {
		return false;
	}
	return '1' === (string) get_post_meta($post_id, $tags[$tag], true);
}

/**
 * INTERNAL ONLY — define uma vibe tag.
 *
 * ADMIN-ONLY, ENFORCED AT THE WRITE BOUNDARY — mesma regra de
 * apollo_event_set_int_rank(): um chamador programático não contorna a
 * checagem de capacidade só por não passar pelo formulário wp-admin.
 *
 * @param int    $post_id ID do evento.
 * @param string $tag     Slug — ver apollo_event_internal_tags().
 * @param bool   $value   true = marcado, false = desmarcado.
 * @return bool
 */
function apollo_event_set_tag(int $post_id, string $tag, bool $value): bool
{
	if (! current_user_can('manage_options')) {
		return false;
	}
	if (get_post_type($post_id) !== APOLLO_EVENT_CPT) {
		return false;
	}
	$tags = apollo_event_internal_tags();
	if (! isset($tags[$tag])) {
		return false;
	}
	return (bool) update_post_meta($post_id, $tags[$tag], $value ? '1' : '');
}

/**
 * INTERNAL ONLY — o quiz de "que tipo de festa combina com você" (2026-08-25).
 *
 * Fonte única de verdade: as PERGUNTAS que o bot do Telegram mostra ao
 * usuário E a REGRA que classifica um evento em cada letra vêm exatamente
 * daqui — nem o texto da pergunta nem a regra de match vivem duplicados em
 * apollo-telegram. Tabela literal pedida pelo usuário:
 *
 *   a) Underground puro          → underground=true,  comercial=false
 *   b) Underground + comercial   → underground=true,  comercial=true
 *   c) Mainstream + comercial    → mainstream=true,   comercial=true
 *   d) Mainstream puro           → mainstream=true,   comercial=false
 *
 * A letra 'b' menciona "giro LGBTQIA+" no texto de apresentação, mas a REGRA
 * de match usa só underground+comercial, como especificado — a tag lgbtqia
 * não entra como requisito. Se o pedido era "b exige lgbtqia=true também",
 * ajuste o array 'require' da letra 'b' aqui (e só aqui).
 *
 * Bilingual since 2026-08-25 (apollo-telegram now replies in pt or en) — the
 * MATCH RULE (require/exclude) has exactly one language-independent copy;
 * only 'label'/'question' vary by $lang. Default stays 'pt' so any existing
 * caller passing no argument (e.g. wp-admin tooling) is unaffected.
 *
 * @param string $lang 'pt' (default) or 'en'.
 * @return array<string, array{label:string, question:string, require:string[], exclude:string[]}>
 */
function apollo_event_vibe_quiz_definition(string $lang = 'pt'): array
{
	$lang = in_array($lang, array('pt', 'en'), true) ? $lang : 'pt';

	$raw = array(
		'a' => array(
			'label'    => array(
				'pt' => __('Underground raiz', 'apollo-events'),
				'en' => __('Straight-up underground', 'apollo-events'),
			),
			'question' => array(
				'pt' => __('Underground, festa estranha com gente esquisita!', 'apollo-events'),
				'en' => __('Underground, a weird party with weird people!', 'apollo-events'),
			),
			'require'  => array('underground'),
			'exclude'  => array('comercial'),
		),
		'b' => array(
			'label'    => array(
				'pt' => __('Underground comercial / giro LGBTQIA+', 'apollo-events'),
				'en' => __('Commercial underground / LGBTQIA+ scene', 'apollo-events'),
			),
			'question' => array(
				'pt' => __('Underground mais comercialzinho, um giro LGBTQIA+ etc..', 'apollo-events'),
				'en' => __('A bit more commercial underground, an LGBTQIA+ scene etc..', 'apollo-events'),
			),
			'require'  => array('underground', 'comercial'),
			'exclude'  => array(),
		),
		'c' => array(
			'label'    => array(
				'pt' => __('Mainstream comercial', 'apollo-events'),
				'en' => __('Commercial mainstream', 'apollo-events'),
			),
			'question' => array(
				'pt' => __('Mainstream da farofada comercial até o que toca na alma!', 'apollo-events'),
				'en' => __('Mainstream, from full-on commercial hits to the stuff that touches your soul!', 'apollo-events'),
			),
			'require'  => array('mainstream', 'comercial'),
			'exclude'  => array(),
		),
		'd' => array(
			'label'    => array(
				'pt' => __('Mainstream conceito', 'apollo-events'),
				'en' => __('Conceptual mainstream', 'apollo-events'),
			),
			'question' => array(
				'pt' => __('Mainstream mais conceito..', 'apollo-events'),
				'en' => __('Mainstream, but more conceptual..', 'apollo-events'),
			),
			'require'  => array('mainstream'),
			'exclude'  => array('comercial'),
		),
	);

	$out = array();
	foreach ($raw as $letter => $rule) {
		$out[$letter] = array(
			'label'    => $rule['label'][$lang] ?? $rule['label']['pt'],
			'question' => $rule['question'][$lang] ?? $rule['question']['pt'],
			'require'  => $rule['require'],
			'exclude'  => $rule['exclude'],
		);
	}

	return $out;
}

/**
 * INTERNAL ONLY — quais letras do quiz (a/b/c/d) este evento atende.
 *
 * Normalmente 0 ou 1 letra. Pode retornar 2 no caso de borda em que um admin
 * marcou Underground E Mainstream no mesmo evento (dado de entrada
 * inconsistente, não uma regra do quiz) — o evento então serve os dois
 * grupos em vez de sumir da recomendação por causa de uma marcação dupla.
 *
 * @param int $post_id ID do evento.
 * @return string[] Letras (subconjunto de a/b/c/d), pode ser vazio.
 */
function apollo_event_classify_vibe_quiz(int $post_id): array
{
	$tags    = apollo_event_get_tags($post_id);
	$matches = array();

	foreach (apollo_event_vibe_quiz_definition() as $letter => $rule) {
		$ok = true;
		foreach ($rule['require'] as $needed) {
			if (empty($tags[$needed])) {
				$ok = false;
				break;
			}
		}
		if ($ok) {
			foreach ($rule['exclude'] as $unwanted) {
				if (! empty($tags[$unwanted])) {
					$ok = false;
					break;
				}
			}
		}
		if ($ok) {
			$matches[] = $letter;
		}
	}

	return $matches;
}

/**
 * INTERNAL ONLY — este evento atende a letra X do quiz?
 *
 * @param int    $post_id ID do evento.
 * @param string $answer  'a'|'b'|'c'|'d'.
 * @return bool
 */
function apollo_event_matches_vibe_quiz(int $post_id, string $answer): bool
{
	return in_array($answer, apollo_event_classify_vibe_quiz($post_id), true);
}

/**
 * INTERNAL ONLY — ranking editorial (0-10) de um evento.
 *
 * Sem valor definido = 0 (padrão, pior ranking). O clamp aqui é a segunda
 * camada de defesa: a mesma regra 0-10 já vive no sanitize_callback do
 * MetaRegistry e no save() do RankMetabox — nenhuma das três camadas confia
 * sozinha no dado gravado.
 *
 * @param int $post_id ID do evento.
 * @return int 0-10.
 */
function apollo_event_get_int_rank(int $post_id): int
{
	$raw  = get_post_meta($post_id, '_event_int_rank', true);
	$rank = is_numeric($raw) ? (int) $raw : 0;

	return max(0, min(10, $rank));
}

/**
 * INTERNAL ONLY — define o ranking editorial (0-10) de um evento.
 *
 * ADMIN-ONLY, ENFORCED AT THE WRITE BOUNDARY — mesmo padrão de
 * _classified_hostel_id: um chamador programático (ex.: um futuro comando do
 * bot do Telegram) não contorna a checagem de capacidade só por não passar
 * pelo formulário wp-admin.
 *
 * @param int $post_id ID do evento.
 * @param int $rank    0-10 (fora do intervalo é sujado para o extremo mais próximo).
 * @return bool
 */
function apollo_event_set_int_rank(int $post_id, int $rank): bool
{
	if (! current_user_can('manage_options')) {
		return false;
	}
	if (get_post_type($post_id) !== APOLLO_EVENT_CPT) {
		return false;
	}

	return (bool) update_post_meta($post_id, '_event_int_rank', max(0, min(10, $rank)));
}

/**
 * INTERNAL ONLY — eventos futuros/ativos ordenados por ranking editorial.
 *
 * O helper "plug-and-play" para o apollo-telegram decidir qual evento indicar
 * agora. Ordena por rank DESC; empate é resolvido pela data de início mais
 * próxima. Eventos expirados (_event_is_gone) nunca entram — um rank alto em
 * um evento que já acabou não deve ser recomendado a ninguém.
 *
 * @param array $args {
 *     Opcional.
 *     @type int    $limit    Máximo de eventos retornados. Default 5.
 *     @type int    $min_rank Rank mínimo aceito (0-10). Default 1 — eventos
 *                            sem ranking definido (0) ficam de fora por
 *                            padrão; passe 0 explicitamente para incluí-los.
 *     @type string $from     Data Y-m-d mínima de início. Default hoje.
 * }
 * @return array<int, array{id:int, title:string, rank:int, start_date:string, permalink:string}>
 */
function apollo_event_get_top_ranked(array $args = array()): array
{
	$defaults = array(
		'limit'    => 5,
		'min_rank' => 1,
		'from'     => current_time('Y-m-d'),
	);
	$args = wp_parse_args($args, $defaults);

	// posts_per_page é limitado (não -1): o rank vive fora do meta_query
	// (sanitize_callback custom não é confiável para range em SQL), então o
	// filtro final acontece em PHP sobre um lote já ordenado por data.
	$query = apollo_event_query(array(
		'posts_per_page' => 200,
		'meta_key'       => '_event_start_date',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => '_event_start_date',
				'value'   => sanitize_text_field((string) $args['from']),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
	));

	$ranked = array();
	foreach ($query->posts as $post) {
		if (apollo_event_is_gone($post->ID)) {
			continue;
		}
		$rank = apollo_event_get_int_rank($post->ID);
		if ($rank < (int) $args['min_rank']) {
			continue;
		}
		$ranked[] = array(
			'id'         => $post->ID,
			'title'      => $post->post_title,
			'rank'       => $rank,
			'start_date' => (string) get_post_meta($post->ID, '_event_start_date', true),
			'permalink'  => (string) get_permalink($post->ID),
		);
	}

	usort($ranked, function ($a, $b) {
		if ($a['rank'] === $b['rank']) {
			return strcmp($a['start_date'], $b['start_date']);
		}
		return $b['rank'] <=> $a['rank'];
	});

	return array_slice($ranked, 0, max(1, (int) $args['limit']));
}

/**
 * INTERNAL ONLY — motor genérico de "qual evento é o mais preciso agora".
 *
 * O ENTRY POINT plug-and-play. Combina _event_int_rank + as vibe tags
 * (_event_tag_*) + proximidade de data num único critério de busca
 * reaproveitável por QUALQUER plugin Apollo — não só apollo-telegram. Um
 * consumidor não precisa conhecer a forma interna do meta: passa o que já
 * sabe sobre o pedido ("algo underground pra hoje à noite", "um evento
 * LGBTQIA+ neste local") e recebe de volta uma lista pronta pra
 * exibir/recomendar, melhor primeiro.
 *
 * @param array $criteria {
 *     Opcional.
 *     @type string[] $tags      Slugs de apollo_event_internal_tags() desejados
 *                                (underground, mainstream, comercial, lgbtqia,
 *                                sexparty). Vazio (padrão) = ignora tags.
 *     @type string   $tags_mode 'any' (padrão) — bate pelo menos uma tag pedida —
 *                                ou 'all' — precisa bater todas.
 *     @type int      $min_rank  Rank mínimo aceito. Default 1 (mesma regra de
 *                                apollo_event_get_top_ranked — eventos sem
 *                                ranking definido ficam de fora por padrão).
 *     @type int      $loc_id    Filtra por local (loc CPT) específico. 0 = ignora.
 *     @type string   $from      Data Y-m-d mínima de início. Default hoje.
 *     @type int      $limit     Quantos retornar. Default 1 — "o melhor".
 * }
 * @return array<int, array<string, mixed>> Lista ordenada (melhor primeiro).
 *         Cada item: id, title, permalink, rank, start_date, start_time,
 *         tags (assoc slug=>bool), loc (array|null), djs (array).
 */
function apollo_event_find_best_match(array $criteria = array()): array
{
	$defaults = array(
		'tags'      => array(),
		'tags_mode' => 'any',
		'min_rank'  => 1,
		'loc_id'    => 0,
		'from'      => current_time('Y-m-d'),
		'limit'     => 1,
	);
	$criteria = wp_parse_args($criteria, $defaults);

	// Lote amplo mas limitado — rank e expiração já filtrados aqui pelo
	// apollo_event_get_top_ranked(); tags/local são um filtro fino em PHP
	// sobre um lote já pequeno e ordenado, não uma segunda query.
	$pool = apollo_event_get_top_ranked(array(
		'limit'    => 200,
		'min_rank' => (int) $criteria['min_rank'],
		'from'     => (string) $criteria['from'],
	));

	$wanted_tags = array_values(array_filter(array_map('sanitize_key', (array) $criteria['tags'])));
	$tags_mode   = 'all' === $criteria['tags_mode'] ? 'all' : 'any';
	$loc_filter  = absint($criteria['loc_id']);

	$matches = array();
	foreach ($pool as $row) {
		$tags = apollo_event_get_tags($row['id']);

		if (! empty($wanted_tags)) {
			$hits = array_intersect($wanted_tags, array_keys(array_filter($tags)));
			if ('all' === $tags_mode && count($hits) < count($wanted_tags)) {
				continue;
			}
			if ('any' === $tags_mode && empty($hits)) {
				continue;
			}
		}

		if ($loc_filter > 0 && (int) get_post_meta($row['id'], '_event_loc_id', true) !== $loc_filter) {
			continue;
		}

		$matches[] = array_merge(
			$row,
			array(
				'start_time' => (string) get_post_meta($row['id'], '_event_start_time', true),
				'tags'       => $tags,
				'loc'        => apollo_event_get_loc($row['id']),
				'djs'        => apollo_event_get_djs($row['id']),
			)
		);
	}

	return array_slice($matches, 0, max(1, (int) $criteria['limit']));
}

/**
 * Obtém lineup de DJs de um evento
 *
 * @param int $post_id ID do evento.
 * @return array Lista de DJs com id, title, image.
 */
function apollo_event_get_djs(int $post_id): array
{
	$dj_ids = get_post_meta($post_id, '_event_dj_ids', true);

	if (empty($dj_ids) || ! is_array($dj_ids)) {
		return array();
	}

	$djs = array();
	foreach ($dj_ids as $dj_id) {
		$dj_post = get_post((int) $dj_id);
		if (! $dj_post || 'publish' !== $dj_post->post_status) {
			continue;
		}

		$audio = (string) (
			get_post_meta($dj_post->ID, '_dj_set_url', true)
			?: get_post_meta($dj_post->ID, '_dj_audio_url', true)
			?: get_post_meta($dj_post->ID, '_dj_preview', true)
			?: ''
		);

		$djs[] = array(
			'id'    => $dj_post->ID,
			'title' => $dj_post->post_title,
			'image' => get_the_post_thumbnail_url($dj_post->ID, 'medium') ?: (get_the_post_thumbnail_url($dj_post->ID, 'thumbnail') ?: ''),
			'slug'  => $dj_post->post_name,
			'audio' => $audio,
			'role'  => (string) (get_post_meta($dj_post->ID, '_dj_role', true) ?: ''),
		);
	}

	return $djs;
}

/**
 * Obtém dados de localização (loc CPT) de um evento
 *
 * @param int $post_id ID do evento.
 * @return array|null Dados do local ou null.
 */
function apollo_event_get_loc(int $post_id): ?array
{
	$loc_id = (int) get_post_meta($post_id, '_event_loc_id', true);
	if (! $loc_id) {
		return null;
	}

	$loc_post = get_post($loc_id);
	if (! $loc_post instanceof \WP_Post) {
		return null;
	}

	/*
	 * Admin column `event_loc` and the create form both need the linked local
	 * even when it is pending/private (quick-add without publish_posts).
	 * Only trash / auto-draft are unusable.
	 */
	if (\in_array( $loc_post->post_status, array( 'trash', 'auto-draft' ), true )) {
		return null;
	}

	$gallery_raw = get_post_meta($loc_id, '_local_gallery', true);
	if ( ! is_array( $gallery_raw ) ) {
		$gallery_raw = get_post_meta($loc_id, '_loc_gallery', true);
	}
	$gallery     = array();
	if (is_array($gallery_raw)) {
		foreach ($gallery_raw as $att_id) {
			$url = wp_get_attachment_image_url(absint($att_id), 'large');
			if ($url) {
				$gallery[] = $url;
			}
		}
	}

	return array(
		'id'      => $loc_post->ID,
		'title'   => $loc_post->post_title,
		'slug'    => $loc_post->post_name,
		'address' => (string) ( get_post_meta($loc_id, '_local_address', true) ?: get_post_meta($loc_id, '_loc_address', true) ),
		'city'    => (string) ( get_post_meta($loc_id, '_local_city', true) ?: get_post_meta($loc_id, '_loc_city', true) ),
		'lat'     => (float) ( get_post_meta($loc_id, '_local_lat', true) ?: get_post_meta($loc_id, '_loc_lat', true) ),
		'lng'     => (float) ( get_post_meta($loc_id, '_local_lng', true) ?: get_post_meta($loc_id, '_loc_lng', true) ),
		'gallery' => $gallery,
		'status'  => $loc_post->post_status,
	);
}

/**
 * Retorna URL do banner do evento com fallback para thumbnail
 *
 * @param int    $post_id ID do evento.
 * @param string $size    Tamanho da imagem.
 * @return string URL da imagem ou placeholder.
 */
function apollo_event_get_banner(int $post_id, string $size = 'large'): string
{
	/*
	 * _event_banner holds EITHER an attachment id (uploaded to the Apollo media
	 * library) OR an absolute URL to an image hosted elsewhere — users without
	 * upload_files can only supply the latter.
	 */
	$banner = get_post_meta($post_id, '_event_banner', true);
	$thumb_id = (int) get_post_thumbnail_id($post_id);
	$branch = 'grain';
	$result = '';

	if (is_string($banner) && '' !== $banner && ! is_numeric($banner)) {
		$branch = 'meta_url';
		$result = esc_url_raw($banner);
	} else {
		$banner_id = (int) $banner;

		if ($banner_id) {
			$url = wp_get_attachment_image_url($banner_id, $size);
			if ($url) {
				$branch = 'meta_attachment';
				$result = $url;
			} else {
				$branch = 'meta_attachment_broken';
			}
		}

		if ('' === $result) {
			$thumb = get_the_post_thumbnail_url($post_id, $size);
			if ($thumb) {
				$branch = 'featured_thumb';
				$result = $thumb;
			}
		}
	}

	if ('' === $result) {
		$branch = 'grain';
		$result = defined( 'APOLLO_ASSETS_GRAIN_URL' )
			? APOLLO_ASSETS_GRAIN_URL
			: 'https://assets.apollo.rio.br/img/bg/grain-001.jpg';
	}

	/* A debug beacon lived here (removed 2026-08-17). apollo_event_get_banner()
	   is called on every event card and every event page, and this fired a
	   server-side wp_remote_post() to http://127.0.0.1:7514 plus a
	   file_put_contents() to D:/dev/_livro.rvalle.com.br/… on the hot render
	   path. $apollo_rule.data_flow: no debug output in production. */

	return $result;
}

/**
 * Force event cover === CPT featured image.
 *
 * Contract for event CPT: `_event_banner` and `_thumbnail_id` always point at
 * the same attachment. Remote URLs (BlueTicket CDN, etc.) are sideloaded into
 * the media library first — never leave a URL in meta with a missing thumb.
 *
 * @param int   $post_id Event post id.
 * @param mixed $ref     Attachment id, https image URL, or empty to clear.
 * @return int|\WP_Error Attachment id, or WP_Error when a URL cannot be sideloaded.
 */
function apollo_event_set_banner(int $post_id, $ref)
{
	$post_id = absint($post_id);
	if (! $post_id) {
		return new \WP_Error('apollo_banner_no_post', 'Missing event id.');
	}

	if (class_exists('\\Apollo\\Event\\API\\EventsController')) {
		$ref = \Apollo\Event\API\EventsController::sanitize_image_ref($ref);
	} elseif (is_numeric($ref)) {
		$ref = absint($ref);
	} elseif (is_string($ref)) {
		$ref = esc_url_raw(trim($ref), array('http', 'https'));
	} else {
		$ref = '';
	}

	/* Clear both sides. */
	if ('' === $ref || 0 === $ref) {
		delete_post_meta($post_id, '_event_banner');
		delete_post_thumbnail($post_id);
		return 0;
	}

	$att_id = 0;
	$source = 'attachment';

	if (is_int($ref) && $ref > 0) {
		$att_id = $ref;
	} elseif (is_string($ref) && '' !== $ref) {
		$source = 'sideload';
		$att_id = apollo_event_sideload_image($ref, $post_id, get_the_title($post_id) ?: 'evento');
		if (is_wp_error($att_id)) {
			return $att_id;
		}
	}

	$att_id = absint($att_id);
	if (! $att_id || 'attachment' !== get_post_type($att_id)) {
		return new \WP_Error('apollo_banner_bad_attachment', 'Banner attachment not found.');
	}

	update_post_meta($post_id, '_event_banner', $att_id);
	set_post_thumbnail($post_id, $att_id);

	/* Second debug beacon removed here 2026-08-17 — fired on every banner sync,
	   same foreign log path and same localhost POST as the one above. */

	return $att_id;
}

/**
 * Sideload a remote image into the media library attached to an event.
 *
 * @param string $url     Remote image URL.
 * @param int    $post_id Parent event id.
 * @param string $title   Attachment title fallback.
 * @return int|\WP_Error Attachment id.
 */
function apollo_event_sideload_image(string $url, int $post_id, string $title = 'evento')
{
	if (! function_exists('media_handle_sideload')) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$tmp = download_url($url, 25);
	if (is_wp_error($tmp)) {
		return $tmp;
	}

	$name = basename((string) wp_parse_url($url, PHP_URL_PATH));
	if ('' === $name || ! preg_match('~\.[a-z0-9]{2,5}$~i', $name)) {
		$name = sanitize_title($title ?: 'evento') . '.jpg';
	}

	$att = media_handle_sideload(
		array(
			'name'     => $name,
			'tmp_name' => $tmp,
		),
		$post_id,
		$title
	);

	if (is_wp_error($att) && file_exists($tmp)) {
		@unlink($tmp);
	}

	return $att;
}

/**
 * Retorna estilo ativo para templates
 *
 * Prioridade: shortcode attr > opção dashboard > default (base)
 *
 * @param string $shortcode_style Estilo passado pelo shortcode.
 * @return string Nome do estilo.
 */
function apollo_event_get_active_style(string $shortcode_style = ''): string
{
	if ($shortcode_style && in_array($shortcode_style, APOLLO_EVENT_STYLES, true)) {
		return $shortcode_style;
	}

	$option = apollo_event_option('default_style', APOLLO_EVENT_DEFAULT_STYLE);

	if (in_array($option, APOLLO_EVENT_STYLES, true)) {
		return $option;
	}

	return APOLLO_EVENT_DEFAULT_STYLE;
}

/**
 * Monta query de eventos com cache
 *
 * @param array $args Argumentos do WP_Query.
 * @return \WP_Query
 */
function apollo_event_query(array $args = array()): \WP_Query
{
	$defaults = array(
		'post_type'      => APOLLO_EVENT_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'orderby'        => 'meta_value',
		'meta_key'       => '_event_start_date',
		'order'          => 'ASC',
	);

	$args      = wp_parse_args($args, $defaults);
	$cache_key = 'apollo_eve_' . md5(wp_json_encode($args));

	// Tenta cache de object cache
	$cached = wp_cache_get($cache_key, APOLLO_EVENT_CACHE_GROUP);
	if (false !== $cached && $cached instanceof \WP_Query) {
		return $cached;
	}

	$query = new \WP_Query($args);
	wp_cache_set($cache_key, $query, APOLLO_EVENT_CACHE_GROUP, APOLLO_EVENT_CACHE_TTL);

	return $query;
}

/**
 * Invalida cache de eventos quando um evento é salvo
 *
 * @param int $post_id ID do post.
 */
function apollo_event_flush_cache(int $post_id): void
{
	if (get_post_type($post_id) !== APOLLO_EVENT_CPT) {
		return;
	}
	wp_cache_flush_group(APOLLO_EVENT_CACHE_GROUP);
}
add_action('save_post_' . APOLLO_EVENT_CPT, __NAMESPACE__ . '\\apollo_event_flush_cache');

/**
 * Initials from a display name (e.g. "Rafael Valle" → "RV").
 */
function apollo_event_user_initials(string $name): string
{
	$name  = trim(preg_replace('/\s+/', ' ', $name) ?? '');
	$parts = $name !== '' ? explode(' ', $name) : array();
	if (count($parts) >= 2) {
		return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[count($parts) - 1], 0, 1));
	}
	return strtoupper(mb_substr($name !== '' ? $name : 'A', 0, 2));
}

/**
 * Whether a user is co-author of an event (apollo-coauthor `_coauthors` meta).
 */
function apollo_event_user_is_coauthor(int $post_id, int $user_id): bool
{
	if ($post_id <= 0 || $user_id <= 0) {
		return false;
	}

	if (function_exists('apollo_get_coauthors')) {
		foreach (apollo_get_coauthors($post_id) as $ca) {
			if ((int) ($ca['user_id'] ?? 0) === $user_id) {
				return true;
			}
		}
	}

	$meta_key = defined('APOLLO_COAUTHOR_META_KEY') ? APOLLO_COAUTHOR_META_KEY : '_coauthors';
	$raw      = get_post_meta($post_id, $meta_key, true);
	if (! is_array($raw)) {
		$legacy = get_post_meta($post_id, '_event_coauthors', true);
		$raw    = is_array($legacy) ? $legacy : array();
	}

	return in_array($user_id, array_map('intval', $raw), true);
}

/**
 * Co-author user IDs for an event (_coauthors / apollo-coauthor).
 *
 * @return int[]
 */
function apollo_event_get_coauthor_ids(int $post_id): array
{
	if ($post_id <= 0) {
		return array();
	}

	$meta_key = defined('APOLLO_COAUTHOR_META_KEY') ? APOLLO_COAUTHOR_META_KEY : '_coauthors';
	$raw      = get_post_meta($post_id, $meta_key, true);
	if (! is_array($raw)) {
		$legacy = get_post_meta($post_id, '_event_coauthors', true);
		$raw    = is_array($legacy) ? $legacy : array();
	}

	$ids = array_values(array_filter(array_map('absint', $raw)));
	$author_id = (int) get_post_field('post_author', $post_id);
	if ($author_id > 0) {
		$ids = array_values(array_diff($ids, array($author_id)));
	}
	return $ids;
}

/**
 * Maximum extra Equipe do Evento members (author is always implied).
 */
const APOLLO_EVENT_MAX_TEAM = 20;

/**
 * Persist Equipe do Evento for an event (replace). Empty array clears.
 * Author is never stored in the list — they always have edit rights as post_author.
 * Newly added members are notified (equipe vocabulary via apollo/coauthor/added).
 *
 * @param int   $post_id  Event ID.
 * @param int[] $user_ids Team member user IDs.
 */
function apollo_event_save_coauthors(int $post_id, array $user_ids): void
{
	if ($post_id <= 0) {
		return;
	}

	$previous  = apollo_event_get_coauthor_ids($post_id);
	$author_id = (int) get_post_field('post_author', $post_id);
	$ids       = array();
	foreach ($user_ids as $uid) {
		$uid = absint($uid);
		if ($uid <= 0 || $uid === $author_id) {
			continue;
		}
		if (! get_userdata($uid)) {
			continue;
		}
		$ids[] = $uid;
		if (count($ids) >= APOLLO_EVENT_MAX_TEAM) {
			break;
		}
	}
	$ids = array_values(array_unique($ids));

	$meta_key = defined('APOLLO_COAUTHOR_META_KEY') ? APOLLO_COAUTHOR_META_KEY : '_coauthors';
	$tax      = defined('APOLLO_COAUTHOR_TAX') ? APOLLO_COAUTHOR_TAX : 'coauthor';

	if (empty($ids)) {
		delete_post_meta($post_id, $meta_key);
		delete_post_meta($post_id, '_event_coauthors');
		if (taxonomy_exists($tax)) {
			wp_set_object_terms($post_id, array(), $tax);
		}
		if (function_exists('wp_cache_delete') && defined('APOLLO_COAUTHOR_CACHE_GROUP')) {
			wp_cache_delete('coauthors_' . $post_id, APOLLO_COAUTHOR_CACHE_GROUP);
		}
		do_action('apollo/coauthor/cleared', $post_id);
		/* Ecosystem contract: arg #2 is always a string action name. */
		do_action('apollo/events/team_updated', $post_id, 'team_cleared', array(), $previous);
		return;
	}

	if (function_exists('apollo_set_coauthors')) {
		$ok = apollo_set_coauthors($post_id, $ids, false);
		if (! $ok) {
			return;
		}
		delete_post_meta($post_id, '_event_coauthors');
	} else {
		update_post_meta($post_id, $meta_key, $ids);
		update_post_meta($post_id, '_event_coauthors', $ids);
	}

	$added = array_values(array_diff($ids, $previous));
	foreach ($added as $uid) {
		do_action('apollo/coauthor/added', $post_id, (int) $uid);
	}
	/* Ecosystem contract: arg #2 is always a string action name. */
	do_action('apollo/events/team_updated', $post_id, 'team_updated', $ids, $previous);
}

/**
 * Catalog of all WP users for the create/edit co-author picker.
 *
 * @return array<int, array{id:int,name:string,login:string,email:string,avatar:string}>
 */
function apollo_event_get_all_users_for_coauthor_picker(int $exclude_user_id = 0): array
{
	$users = get_users(
		array(
			'orderby' => 'display_name',
			'order'   => 'ASC',
			'fields'  => array('ID', 'display_name', 'user_login', 'user_email'),
		)
	);

	$out = array();
	foreach ($users as $user) {
		$uid = (int) $user->ID;
		if ($exclude_user_id > 0 && $uid === $exclude_user_id) {
			continue;
		}
		$out[] = array(
			'id'     => $uid,
			'name'   => (string) $user->display_name,
			'login'  => (string) $user->user_login,
			'email'  => (string) $user->user_email,
			'avatar' => get_avatar_url($uid, array('size' => 64)) ?: '',
		);
	}
	return $out;
}

/**
 * Events the user can manage (author OR co-author), all statuses.
 *
 * @return array<int, array<string, mixed>>
 */
function apollo_event_get_user_manageable_events(int $user_id, int $limit = 50): array
{
	if ($user_id <= 0) {
		return array();
	}

	$statuses = array('publish', 'draft', 'future', 'pending', 'private');
	$meta_key = defined('APOLLO_COAUTHOR_META_KEY') ? APOLLO_COAUTHOR_META_KEY : '_coauthors';

	$authored = get_posts(
		array(
			'post_type'              => APOLLO_EVENT_CPT,
			'post_status'            => $statuses,
			'author'                 => $user_id,
			'posts_per_page'         => $limit,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);

	$coauthored = get_posts(
		array(
			'post_type'              => APOLLO_EVENT_CPT,
			'post_status'            => $statuses,
			'posts_per_page'         => $limit,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
			'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => $meta_key,
					'value'   => sprintf('i:%d;', $user_id),
					'compare' => 'LIKE',
				),
				array(
					'key'     => '_event_coauthors',
					'value'   => sprintf('i:%d;', $user_id),
					'compare' => 'LIKE',
				),
			),
		)
	);

	$by_id = array();
	foreach (array_merge($authored, $coauthored) as $post) {
		$by_id[(int) $post->ID] = $post;
	}

	uasort(
		$by_id,
		static function ($a, $b) {
			return strcmp($b->post_date, $a->post_date);
		}
	);

	$out = array();
	foreach (array_slice(array_values($by_id), 0, $limit) as $post) {
		$eid      = (int) $post->ID;
		$start    = (string) get_post_meta($eid, '_event_start_date', true);
		$loc_id   = (int) get_post_meta($eid, '_event_loc_id', true);
		$coauthors = array();

		if (function_exists('apollo_get_coauthors')) {
			foreach (apollo_get_coauthors($eid) as $ca) {
				$coauthors[] = array(
					'id'     => (int) ($ca['user_id'] ?? 0),
					'name'   => (string) ($ca['display_name'] ?? ''),
					'avatar' => (string) ($ca['avatar_url'] ?? ''),
				);
			}
		}

		$out[] = array(
			'id'           => $eid,
			'title'        => get_the_title($eid),
			'status'       => $post->post_status,
			'start_date'   => $start,
			'banner'       => apollo_event_get_banner($eid, 'medium'),
			'loc_name'     => $loc_id ? get_the_title($loc_id) : '',
			'edit_url'     => home_url('/novo-evento/?edit=' . $eid),
			'view_url'     => get_permalink($eid),
			'is_author'    => (int) $post->post_author === $user_id,
			'is_coauthor'  => apollo_event_user_is_coauthor($eid, $user_id),
			'coauthors'    => $coauthors,
		);
	}

	return $out;
}

/**
 * Payload for the create/edit form JS (matches form.html field map).
 *
 * @return array<string, mixed>
 */
/**
 * Cache-busting version for a plugin asset.
 *
 * Versioning assets by APOLLO_EVENT_VERSION means every CSS/JS change is served
 * stale from the browser cache until someone remembers to bump the constant —
 * which is exactly how a deployed fix keeps looking "still broken". Using the
 * file's own mtime makes the URL change the moment the file does.
 *
 * @param string $relative Path relative to the plugin root, e.g. 'assets/js/x.js'.
 * @return string Version string for the query arg.
 */
function apollo_event_asset_ver(string $relative): string
{
	$fallback = defined('APOLLO_EVENT_VERSION') ? APOLLO_EVENT_VERSION : '1.0.0';

	if (! defined('APOLLO_EVENT_DIR')) {
		return $fallback;
	}

	$path = APOLLO_EVENT_DIR . ltrim($relative, '/');
	if (! is_readable($path)) {
		return $fallback;
	}

	$mtime = filemtime($path);

	return $mtime ? $fallback . '.' . $mtime : $fallback;
}

/**
 * Sanitize rich "Sobre o Evento" HTML from the Apollo about editor.
 *
 * Portable output uses inline styles/classes on spans, divs, and images.
 * Plain wp_kses_post strips too much of that formatting.
 *
 * @param string $html Raw HTML.
 * @return string
 */
function apollo_event_kses_about( string $html ): string {
	$allowed = wp_kses_allowed_html( 'post' );

	$inline = array(
		'class' => true,
		'style' => true,
		'id'    => true,
	);

	foreach ( array( 'span', 'div', 'p', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'u', 'br' ) as $tag ) {
		if ( ! isset( $allowed[ $tag ] ) || ! is_array( $allowed[ $tag ] ) ) {
			$allowed[ $tag ] = array();
		}
		$allowed[ $tag ] = array_merge( $allowed[ $tag ], $inline );
	}

	if ( ! isset( $allowed['a'] ) || ! is_array( $allowed['a'] ) ) {
		$allowed['a'] = array();
	}
	$allowed['a'] = array_merge(
		$allowed['a'],
		$inline,
		array(
			'href'   => true,
			'title'  => true,
			'target' => true,
			'rel'    => true,
		)
	);

	if ( ! isset( $allowed['img'] ) || ! is_array( $allowed['img'] ) ) {
		$allowed['img'] = array();
	}
	$allowed['img'] = array_merge(
		$allowed['img'],
		$inline,
		array(
			'src'      => true,
			'alt'      => true,
			'width'    => true,
			'height'   => true,
			'loading'  => true,
			'decoding' => true,
		)
	);

	/**
	 * Filter allowed HTML for event about content.
	 *
	 * @param array  $allowed Allowed tags.
	 * @param string $html    Incoming HTML.
	 */
	$allowed = apply_filters( 'apollo_event_kses_about_allowed', $allowed, $html );

	return wp_kses( $html, $allowed );
}

/**
 * Resolve one gallery entry to a displayable URL.
 *
 * Entries are attachment ids for library uploads and absolute URLs for images
 * hosted outside Apollo. Returns '' when the reference no longer resolves.
 *
 * @param mixed  $ref  Attachment id or URL.
 * @param string $size Image size for attachments.
 * @return string
 */
function apollo_event_image_url($ref, string $size = 'large'): string
{
	if (is_string($ref) && '' !== $ref && ! is_numeric($ref)) {
		return esc_url_raw($ref);
	}

	$id = absint($ref);
	if (! $id) {
		return '';
	}

	return (string) (wp_get_attachment_image_url($id, $size) ?: '');
}

/**
 * Thumbnail URL for a DJ, with the _dj_image fallback.
 *
 * The dj CPT stores artwork either as a featured image or in _dj_image — which
 * is an attachment id on some rows and a raw URL on others. Reading only the
 * featured image left most DJs with a blank avatar in the line-up picker.
 *
 * @param int $dj_id DJ post id.
 * @return string Image URL, or '' when the DJ has no artwork.
 */
function apollo_event_dj_thumb(int $dj_id): string
{
	$thumb = (string) (get_the_post_thumbnail_url($dj_id, 'thumbnail') ?: '');
	if ('' !== $thumb) {
		return $thumb;
	}

	$image = get_post_meta($dj_id, '_dj_image', true);
	if (is_numeric($image)) {
		return (string) (wp_get_attachment_image_url(absint($image), 'thumbnail') ?: '');
	}
	if (is_string($image) && '' !== $image) {
		return esc_url_raw($image);
	}

	return '';
}

function apollo_event_prepare_form_payload(int $post_id): array
{
	$post = get_post($post_id);
	if (! $post || APOLLO_EVENT_CPT !== $post->post_type) {
		return array();
	}

	/*
	 * Banner is an attachment id OR an absolute URL (image hosted outside
	 * Apollo). Casting to int turned every external URL into 0, so opening the
	 * edit form and saving again silently WIPED the banner. Keep the raw ref.
	 */
	$banner_ref = get_post_meta($post_id, '_event_banner', true);
	if (is_string($banner_ref) && '' !== $banner_ref && ! is_numeric($banner_ref)) {
		$banner_id = $banner_ref;
	} else {
		$banner_id = (int) $banner_ref;
		if (! $banner_id) {
			$banner_id = (int) get_post_thumbnail_id($post_id);
		}
	}

	$gallery = get_post_meta($post_id, '_event_gallery', true);
	if (! is_array($gallery)) {
		$gallery = array();
	}

	$dj_ids = get_post_meta($post_id, '_event_dj_ids', true);
	if (! is_array($dj_ids)) {
		$dj_ids = array();
	}

	$dj_slots = get_post_meta($post_id, '_event_dj_slots', true);
	if (! is_array($dj_slots)) {
		$dj_slots = array();
	}

	$lineup = array();
	foreach ($dj_ids as $idx => $dj_id) {
		$dj_id = (int) $dj_id;
		$slot  = is_array($dj_slots[$idx] ?? null) ? $dj_slots[$idx] : array();
		foreach ($dj_slots as $s) {
			if (is_array($s) && (int) ($s['dj_id'] ?? 0) === $dj_id) {
				$slot = $s;
				break;
			}
		}
		$lineup[] = array(
			'id'    => $dj_id,
			'name'  => get_the_title($dj_id),
			'thumb' => apollo_event_dj_thumb($dj_id),
			'start' => (string) ($slot['start_time'] ?? ''),
			'end'   => (string) ($slot['end_time'] ?? ''),
			'badge' => (string) ($slot['badge'] ?? ''),
		);
	}

	$sound_terms = wp_get_object_terms($post_id, defined('APOLLO_EVENT_TAX_SOUND') ? APOLLO_EVENT_TAX_SOUND : 'sound', array('fields' => 'slugs'));
	if (is_wp_error($sound_terms)) {
		$sound_terms = array();
	}

	$season_terms = wp_get_object_terms($post_id, defined('APOLLO_EVENT_TAX_SEASON') ? APOLLO_EVENT_TAX_SEASON : 'season', array('fields' => 'slugs'));
	if (is_wp_error($season_terms)) {
		$season_terms = array();
	}

	$loc     = apollo_event_get_loc($post_id);
	$loc_id  = (int) get_post_meta($post_id, '_event_loc_id', true);
	$coupons = (string) get_post_meta($post_id, '_event_coupon_code', true);
	$coupon_list = array_values(
		array_filter(
			array_map('trim', explode(',', $coupons))
		)
	);

	// Early Bird e Listas — raw repeater for the edit form. Events never saved
	// through the new UI (metadata_exists() false) get their legacy toggles
	// converted to rows here purely for display/continuity in the edit form;
	// public rendering keeps reading the legacy fields until this event is
	// actually re-saved (see apollo_event_build_access_payload()).
	if (metadata_exists('post', $post_id, '_event_access_buttons')) {
		$access_buttons = get_post_meta($post_id, '_event_access_buttons', true);
		$access_buttons = is_array($access_buttons) ? $access_buttons : array();
	} else {
		$access_buttons = array();
		if ((string) get_post_meta($post_id, '_event_earlybird_enabled', true) === '1') {
			$access_buttons[] = array(
				'kind'  => 'ticket',
				'style' => 'soft',
				'label' => (string) get_post_meta($post_id, '_event_earlybird_name', true) ?: __('Early Bird', 'apollo-events'),
				'sub'   => (string) get_post_meta($post_id, '_event_earlybird_sub', true),
				'url'   => (string) get_post_meta($post_id, '_event_earlybird_url', true),
			);
		}
		if ((string) get_post_meta($post_id, '_event_lista_geral_enabled', true) === '1') {
			$access_buttons[] = array(
				'kind'  => 'lista',
				'style' => 'lista',
				'label' => __('Lista Geral', 'apollo-events'),
				'sub'   => (string) get_post_meta($post_id, '_event_lista_geral_sub', true),
				'url'   => '',
			);
		}
		if ((string) get_post_meta($post_id, '_event_lista_fem_enabled', true) === '1') {
			$access_buttons[] = array(
				'kind'  => 'lista',
				'style' => 'fem',
				'label' => __('Lista Feminina', 'apollo-events'),
				'sub'   => (string) get_post_meta($post_id, '_event_lista_fem_sub', true),
				'url'   => '',
			);
		}
	}

	return array(
		'id'            => $post_id,
		'title'         => $post->post_title,
		'content'       => $post->post_content,
		'start_date'    => (string) get_post_meta($post_id, '_event_start_date', true),
		'end_date'      => (string) get_post_meta($post_id, '_event_end_date', true),
		'start_time'    => (string) get_post_meta($post_id, '_event_start_time', true),
		'end_time'      => (string) get_post_meta($post_id, '_event_end_time', true),
		'banner'        => $banner_id,
		'banner_url'    => apollo_event_image_url($banner_id, 'large') ?: apollo_event_get_banner($post_id),
		'bg_color'      => (string) (get_post_meta($post_id, '_event_bg_color', true) ?: '#0a0a0a'),
		'video_url'     => (string) get_post_meta($post_id, '_event_video_url', true),
		'audio_url'     => (string) get_post_meta($post_id, '_event_audio_url', true),
		/* Preserve id-or-URL refs; intval() here wiped external images on re-save. */
		'gallery'       => array_values(
			array_filter(
				array_map(
					static function ($ref) {
						return is_numeric($ref) ? (int) $ref : (string) $ref;
					},
					$gallery
				),
				static function ($ref): bool {
					return '' !== $ref && 0 !== $ref;
				}
			)
		),
		'ticket_url'    => (string) get_post_meta($post_id, '_event_ticket_url', true),
		'ticket_price'  => (string) get_post_meta($post_id, '_event_ticket_price', true),
		'ticket_status' => (string) (get_post_meta($post_id, '_event_ticket_status', true) ?: 'available'),
		'ticket_btn_style' => (string) (get_post_meta($post_id, '_event_ticket_btn_style', true) ?: 'main'),
		'list_btn_style'   => (string) (get_post_meta($post_id, '_event_list_btn_style', true) ?: 'lista'),
		'list_url'      => (string) get_post_meta($post_id, '_event_list_url', true),
		'coupon_code'   => $coupons,
		'coupons'       => $coupon_list,
		'privacy'       => (string) (get_post_meta($post_id, '_event_privacy', true) ?: 'public'),
		'event_status'  => (string) (get_post_meta($post_id, '_event_status', true) ?: 'scheduled'),
		'loc_id'        => $loc_id,
		'loc'           => $loc,
		'dj_ids'        => array_map('intval', $dj_ids),
		'dj_slots'      => $dj_slots,
		'lineup'        => $lineup,
		'sounds'        => array_values($sound_terms),
		'seasons'       => array_values($season_terms),
		'season'        => ! empty($season_terms[0]) ? (string) $season_terms[0] : '',
		'earlybird_enabled'   => (string) get_post_meta($post_id, '_event_earlybird_enabled', true) === '1',
		'earlybird_name'      => (string) get_post_meta($post_id, '_event_earlybird_name', true),
		'earlybird_sub'       => (string) get_post_meta($post_id, '_event_earlybird_sub', true),
		'earlybird_url'       => (string) get_post_meta($post_id, '_event_earlybird_url', true),
		'lista_geral_enabled' => (string) get_post_meta($post_id, '_event_lista_geral_enabled', true) === '1',
		'lista_geral_sub'     => (string) get_post_meta($post_id, '_event_lista_geral_sub', true),
		'lista_fem_enabled'   => (string) get_post_meta($post_id, '_event_lista_fem_enabled', true) === '1',
		'lista_fem_sub'       => (string) get_post_meta($post_id, '_event_lista_fem_sub', true),
		'lista_cta_label'     => (string) get_post_meta($post_id, '_event_lista_cta_label', true),
		'access_buttons'      => $access_buttons,
		'coauthors'           => apollo_event_get_coauthor_ids($post_id),
	);
}

/**
 * Build single-event access section payload (tickets / listas / CTA).
 *
 * Source of truth for template-parts/single/access.php and REST prepare_event.
 *
 * @return array{
 *   ticketsLabel:string,
 *   tickets:array<int,array{kind:string,name:string,sub:string,url:string,icon:string}>,
 *   coupon:?array{code:string,hint:string},
 *   listaLabel:string,
 *   listas:array<int,array{kind:string,name:string,sub:string,icon:string}>,
 *   listaCta:?array{url:string,label:string}
 * }
 */
function apollo_event_build_access_payload(int $post_id): array
{
	$ticket_url    = (string) get_post_meta($post_id, '_event_ticket_url', true);
	$ticket_price  = (string) get_post_meta($post_id, '_event_ticket_price', true);
	$ticket_status = (string) (get_post_meta($post_id, '_event_ticket_status', true) ?: 'available');
	$list_url      = (string) get_post_meta($post_id, '_event_list_url', true);
	$coupon_code   = (string) get_post_meta($post_id, '_event_coupon_code', true);

	$earlybird_on  = (string) get_post_meta($post_id, '_event_earlybird_enabled', true) === '1';
	$earlybird_name = (string) get_post_meta($post_id, '_event_earlybird_name', true);
	$earlybird_sub  = (string) get_post_meta($post_id, '_event_earlybird_sub', true);
	$earlybird_url  = (string) get_post_meta($post_id, '_event_earlybird_url', true);

	$lista_geral_on  = (string) get_post_meta($post_id, '_event_lista_geral_enabled', true) === '1';
	$lista_geral_sub = (string) get_post_meta($post_id, '_event_lista_geral_sub', true);
	$lista_fem_on    = (string) get_post_meta($post_id, '_event_lista_fem_enabled', true) === '1';
	$lista_fem_sub   = (string) get_post_meta($post_id, '_event_lista_fem_sub', true);
	$lista_cta_label = (string) get_post_meta($post_id, '_event_lista_cta_label', true);

	/*
	 * Heal legacy imports that stored the ticket page URL in _event_ticket_price
	 * (button title) and left _event_ticket_url empty — the row rendered as a
	 * non-link <div>. Prefer the URL as href and use the default title.
	 */
	if ('' === $ticket_url && preg_match('~^https?://~i', $ticket_price)) {
		$ticket_url   = $ticket_price;
		$ticket_price = '';
	}

	$host = $ticket_url ? (string) wp_parse_url($ticket_url, PHP_URL_HOST) : '';

	$tickets = array();
	if ($ticket_url || $ticket_price) {
		/* ticket_price is the free-text title shown on /evento/… (form label:
		   "Nome do ingresso"). Empty → default Ingressos do Evento. */
		$name = $ticket_price !== '' ? $ticket_price : __('Ingressos do Evento', 'apollo-events');
		$tickets[] = array(
			'kind' => 'main',
			'name' => $name,
			'sub'  => $host ?: $ticket_status,
			'url'  => $ticket_url,
			'icon' => 'ri-ticket-2-line',
		);
	}

	$listas = array();

	// Early Bird e Listas — unified repeater ("+ Novo Ticket ou Lista").
	// metadata_exists() (not empty()) is the migration marker: once an event is
	// saved through the new UI the key exists (even as []), and legacy toggles
	// are retired for that event. Events never touched under the new UI keep
	// rendering from the old discrete fields below.
	$cta_btn = null;

	if (metadata_exists('post', $post_id, '_event_access_buttons')) {
		$access_buttons = get_post_meta($post_id, '_event_access_buttons', true);
		$access_buttons = is_array($access_buttons) ? $access_buttons : array();

		foreach ($access_buttons as $btn) {
			$label = (string) ($btn['label'] ?? '');
			if ('' === $label) {
				continue;
			}
			$url   = (string) ($btn['url'] ?? '');
			$style = (string) ($btn['style'] ?? 'soft');
			$style = in_array($style, array('main', 'soft', 'lista', 'fem', 'cta'), true) ? $style : 'soft';
			$kind  = (string) ($btn['kind'] ?? 'ticket');
			$sub   = (string) ($btn['sub'] ?? '');

			// TYPE 004 (mockup): wide ev-lista-cta pill — routed to the listaCta slot.
			if ('cta' === $style) {
				if (null === $cta_btn && '' !== $url) {
					$cta_btn = array(
						'url'   => $url,
						'label' => $label,
					);
				}
				continue;
			}

			if ('lista' === $kind) {
				/* kind=lista → icon ALWAYS ri-vip-line (style is chrome only). */
				$listas[] = array(
					'kind' => $style,
					'name' => $label,
					'sub'  => $sub,
					'url'  => $url,
					'icon' => 'ri-vip-line',
				);
			} else {
				/* kind=ticket (ingresso) → icon ALWAYS ri-ticket-2-line. */
				$tickets[] = array(
					'kind' => $style,
					'name' => $label,
					'sub'  => $sub,
					'url'  => $url,
					'icon' => 'ri-ticket-2-line',
				);
			}
		}
	} else {
		// Legacy fallback (events saved before the repeater existed).
		if ($earlybird_on) {
			$tickets[] = array(
				'kind' => 'soft',
				'name' => $earlybird_name !== '' ? $earlybird_name : __('Early Bird', 'apollo-events'),
				'sub'  => $earlybird_sub !== '' ? $earlybird_sub : __('Lote antecipado', 'apollo-events'),
				'url'  => $earlybird_url,
				'icon' => 'ri-ticket-2-line',
			);
		}

		if ($lista_geral_on) {
			$listas[] = array(
				'kind' => 'lista',
				'name' => __('Lista Geral', 'apollo-events'),
				'sub'  => $lista_geral_sub !== '' ? $lista_geral_sub : __('Nome na porta', 'apollo-events'),
				'icon' => 'ri-vip-line',
			);
		}
		if ($lista_fem_on) {
			$listas[] = array(
				'kind' => 'fem',
				'name' => __('Lista Feminina', 'apollo-events'),
				'sub'  => $lista_fem_sub !== '' ? $lista_fem_sub : __('Nome na porta', 'apollo-events'),
				'icon' => 'ri-vip-line',
			);
		}

		// Legacy fallback: old list_url as a lista-style card when discrete metas empty.
		if (empty($listas) && $list_url && ! $lista_geral_on && ! $lista_fem_on) {
			$list_style = (string) (get_post_meta($post_id, '_event_list_btn_style', true) ?: 'lista');
			$kind       = in_array($list_style, array('lista', 'fem', 'soft'), true) ? $list_style : 'lista';
			$listas[]   = array(
				'kind' => $kind,
				'name' => __('Lista Amiga', 'apollo-events'),
				'sub'  => __('Entrar na lista', 'apollo-events'),
				'icon' => 'ri-vip-line',
			);
		}
	}

	$coupon = null;
	if ($coupon_code !== '') {
		$coupon = array(
			'code' => $coupon_code,
			'hint' => __('Toque para copiar', 'apollo-events'),
		);
	}

	// CTA slot: new-UI cta-style button wins; legacy list_url is the fallback.
	$lista_cta = $cta_btn;
	if (null === $lista_cta && $list_url !== '') {
		$lista_cta = array(
			'url'   => $list_url,
			'label' => $lista_cta_label !== '' ? $lista_cta_label : __('Entrar para uma Lista', 'apollo-events'),
		);
	}

	return array(
		'ticketsLabel' => __('Ingressos', 'apollo-events'),
		'tickets'      => $tickets,
		'coupon'       => $coupon,
		'listaLabel'   => __('Listas', 'apollo-events'),
		'listas'       => $listas,
		'listaCta'     => $lista_cta,
	);
}

/**
 * Whether a promotional video URL is allowed on the create form / REST create.
 * Empty is valid (optional field). Accepts YouTube or direct .mp4 / .webm / .mov
 * (case-insensitive; query strings after the extension are OK).
 */
function apollo_event_is_valid_video_url(string $url): bool
{
	$url = trim($url);
	if ('' === $url) {
		return true;
	}
	return (bool) preg_match('~youtube\.com|youtu\.be|\.(?:mp4|webm|mov)(?:\?|$)~i', $url);
}

/**
 * Sanitize event video URL: esc_url_raw, then drop values that fail the form rule.
 */
function apollo_event_sanitize_video_url($value): string
{
	$url = esc_url_raw((string) $value);
	if ('' === $url) {
		return '';
	}
	return apollo_event_is_valid_video_url($url) ? $url : '';
}
