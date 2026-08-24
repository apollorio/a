<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * MODELO · DJ — the seed payload
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Data only. No logic, no hooks, no output. `modelo.php` owns the mechanism;
 * this file owns the content, so the two can change independently.
 *
 * ── The naming rule, and why it is absolute ───────────────────────────
 * Nothing here may name a real person, a real act or a real venue. A demo
 * record that says "Leo Janeiro" gets indexed, screenshotted, quoted back,
 * and eventually somebody has to explain to an artist why the platform
 * shipped with their name on a fake profile.
 *
 * So every value describes ITSELF:
 *     name       → "Modelo Nome"
 *     statement  → "Frase de efeito sobre o artista …"
 * Reading the rendered page tells you what each field is FOR, which makes
 * /dj/modelo double as living documentation of the template. That is the
 * same job WordPress's "Hello world!" does, and the reason it survived
 * twenty years.
 *
 * ── Media ─────────────────────────────────────────────────────────────
 * Every asset is an open public source, chosen to be replaced by Apollo's
 * own later. Images: Unsplash (free to use, no attribution required) —
 * the same host the existing /casa placeholders already use, so it is
 * already reachable under the CSP. Video: Big Buck Bunny (Blender
 * Foundation, CC-BY 3.0).
 *
 * ⚠ CSP — the video host `storage.googleapis.com` is NOT known to be in
 * the Apollo media-src allowlist. If the Sobre block renders empty, that
 * is why: allowlist it, or swap `_dj_about_video` for '' and let the
 * template fall back to `_dj_about_photo`, which is what it is designed
 * to do.
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

    /* ── post object ────────────────────────────────────────────────── */
    'post' => array(
        'post_title'   => 'Modelo Nome',
        'post_name'    => 'modelo',
        'post_status'  => 'publish',
        'post_excerpt' => 'Registro modelo do CPT dj — mostra a página de artista com todos os campos preenchidos.',
        'post_content' => 'Este é o registro MODELO do CPT `dj`. Ele existe para mostrar como a página '
            . '/dj/{slug} se comporta com 100% dos campos preenchidos. Nenhum dado aqui é real: '
            . 'nomes, textos e mídias são marcadores que descrevem a si mesmos. Duplique este '
            . 'registro para criar um artista de verdade, ou apague-o quando não precisar mais.',
    ),

    /* ── identidade ─────────────────────────────────────────────────── */
    'meta' => array(
        '_dj_name'      => 'Modelo Nome',
        '_dj_home_city' => 'Cidade Modelo',
        '_dj_verified'  => '0', // um selo de verificação num registro modelo seria mentira

        /* hero */
        '_dj_bio_short' => 'Frase curta de apresentação do artista — duas linhas, no máximo três. '
            . 'É o primeiro texto que alguém lê, então diz de onde vem o som e por que ele importa.',
        '_dj_eyebrow'   => '', // vazio de propósito: mostra a derivação (cidade + 2 sonoridades)
        '_dj_name_lines' => array(), // vazio: mostra o split automático em "Modelo" / "Nome"
        '_dj_banner'    => $img . '1470225620780-dba8ba36b745?w=1800&q=82',
        '_dj_image'     => $img . '1507003211169-0a1dd7228f2d?w=800&q=82',

        /* statement — um <em> permitido (apollo_core_kses_inline_em) */
        '_dj_statement' => 'Frase de efeito sobre o artista, escrita para ser lida devagar. '
            . 'Uma palavra recebe <em>destaque</em> e só uma — é o único acento da página.',

        /* sobre */
        '_dj_bio' => 'Parágrafo longo de biografia. Conta a trajetória: onde começou, o que mudou no '
            . 'meio do caminho, como o som chegou onde está. Este texto aceita formatação rica, '
            . 'então pode ter mais de um parágrafo, links e ênfases. É o campo mais longo da '
            . 'página e o único lugar onde cabe contexto de verdade.',
        '_dj_about_photo' => $img . '1493225457124-a3eb161ffa5f?w=1200&q=82',
        '_dj_about_video' => 'https://storage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',

        /* contato + booking */
        '_dj_booking'        => 'modelo@apollo.rio.br',
        '_dj_booking_status' => 'open',

        /* kit de imprensa */
        '_dj_media_kit_url'   => 'https://drive.google.com/drive/folders/MODELO-SUBSTITUIR',
        '_dj_rider_url'       => 'https://apollo.rio.br/modelo/rider-modelo.pdf',
        /* Números realistas de propósito. A regra de nome genérico existe para não
           atribuir identidade a ninguém — um número não nomeia nada, e zeros por
           toda parte fazem a página parecer QUEBRADA em vez de COMPLETA, que é o
           oposto do trabalho deste registro. */
        '_dj_media_kit_stats' => array(
            array( 'value' => '42 MB',    'label' => 'Arquivo zip' ),
            array( 'value' => '18 fotos', 'label' => '300 dpi' ),
            array( 'value' => 'Rider v4', 'label' => 'PDF' ),
            array( 'value' => '6 promos', 'label' => 'Inéditas' ),
        ),

        /* plataformas — todas preenchidas para mostrar o dock completo */
        '_dj_soundcloud'        => 'https://soundcloud.com/modelo-perfil',
        '_dj_bandcamp'          => 'https://modelo.bandcamp.com',
        '_dj_spotify'           => 'https://open.spotify.com/artist/MODELO',
        '_dj_instagram'         => 'https://instagram.com/modelo_perfil',
        '_dj_youtube'           => 'https://youtube.com/@modelo',
        '_dj_mixcloud'          => 'https://mixcloud.com/modelo',
        '_dj_beatport'          => 'https://beatport.com/artist/modelo',
        '_dj_tiktok'            => 'https://tiktok.com/@modelo',
        '_dj_facebook'          => 'https://facebook.com/modelo',
        '_dj_twitter'           => 'https://x.com/modelo',
        '_dj_resident_advisor'  => 'https://ra.co/dj/modelo',
        '_dj_website'           => 'https://modelo.exemplo.br',
        '_dj_mix_url'           => 'https://soundcloud.com/modelo-perfil/mix-modelo',
        '_dj_set_url'           => 'https://soundcloud.com/modelo-perfil/set-modelo',

        /* projetos */
        '_dj_original_project_1' => 'Projeto Modelo Um — descrição curta do que é',
        '_dj_original_project_2' => 'Projeto Modelo Dois — outra frente de trabalho',
        '_dj_original_project_3' => 'Projeto Modelo Três — colaboração ou selo',

        /* galeria */
        '_dj_gallery' => array(
            $img . '1516450360452-9312f5e86fc7?w=1200&q=80',
            $img . '1534528741775-53994a69daeb?w=1200&q=80',
            $img . '1514525253161-7a46d19cd819?w=1200&q=80',
            $img . '1504898770365-14faca6a7320?w=1200&q=80',
        ),

        /* rodapé */
        '_dj_footer_image' => '', // vazio: mostra a derivação (capa do evento mais recente)

        /* faixas — schema v2, 6 linhas para a linha "06 Ver todos" aparecer */
        '_dj_tracks' => array(
            array(
                'title' => 'Faixa Modelo Um', 'artists' => 'Modelo Nome', 'duration' => '6:12',
                'bpm' => 132, 'release_date' => '2026-05-01', 'genre' => 'Gênero Modelo',
                'album' => 'Álbum Modelo', 'label' => 'Selo Modelo',
                'cover_url' => $img . '1571330735066-03aaa9429d89?w=600&q=80',
                'url_soundcloud' => 'https://soundcloud.com/modelo-perfil/faixa-um',
            ),
            array(
                'title' => 'Faixa Modelo Dois', 'artists' => 'Modelo Nome, Convidado Modelo',
                'duration' => '5:48', 'bpm' => 128, 'release_date' => '2026-04-02',
                'genre' => 'Gênero Modelo', 'label' => 'Selo Modelo',
                'cover_url' => $img . '1493225457124-a3eb161ffa5f?w=600&q=80',
                'url_spotify' => 'https://open.spotify.com/track/MODELO2',
            ),
            array(
                'title' => 'Faixa Modelo Três', 'artists' => 'Modelo Nome', 'duration' => '7:03',
                'bpm' => 135, 'release_date' => '2026-03-03', 'genre' => 'Gênero Modelo',
                'cover_url' => $img . '1470225620780-dba8ba36b745?w=600&q=80',
                'url_bandcamp' => 'https://modelo.bandcamp.com/track/tres',
            ),
            array(
                'title' => 'Faixa Modelo Quatro', 'artists' => 'Modelo Nome', 'duration' => '4:21',
                'bpm' => 126, 'release_date' => '2026-02-04', 'genre' => 'Gênero Modelo',
                'cover_url' => $img . '1516280440614-37939bbacd81?w=600&q=80',
                'url_soundcloud' => 'https://soundcloud.com/modelo-perfil/faixa-quatro',
            ),
            array(
                'title' => 'Faixa Modelo Cinco', 'artists' => 'Modelo Nome', 'duration' => '8:15',
                'bpm' => 130, 'release_date' => '2026-01-05', 'genre' => 'Gênero Modelo',
                'cover_url' => $img . '1459749411175-04bf5292ceea?w=600&q=80',
                'url_download' => 'https://apollo.rio.br/modelo/faixa-cinco.wav',
            ),
            array(
                'title' => 'Faixa Modelo Seis', 'artists' => 'Modelo Nome', 'duration' => '6:40',
                'bpm' => 133, 'release_date' => '2025-12-06', 'genre' => 'Gênero Modelo',
                'cover_url' => $img . '1511671782779-c97d3d27a1d4?w=600&q=80',
                'url_soundcloud' => 'https://soundcloud.com/modelo-perfil/faixa-seis',
            ),
        ),
    ),

    /* ── taxonomias ─────────────────────────────────────────────────── */
    'terms' => array(
        'sound' => array( 'Sonoridade Modelo', 'Segunda Sonoridade', 'Terceira Sonoridade' ),
    ),
);
