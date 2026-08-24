<?php
/**
 * Panel: Mural — project bulletin board
 *
 * Available to ALL access levels (team members can post here).
 * Posts stored via ajax (Proj_Board module) in the activity log as 'board_post'.
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
$avatar_url   = get_avatar_url( $current_user->ID, [ 'size' => 64 ] );
?>
<section class="panel" id="panel-mural">

    <div class="section-hdr">
        <span class="section-title"><i class="ri-chat-quote-line"></i> Mural do Projeto</span>
    </div>

    <!-- Post form — always visible (team can post) -->
    <form class="mural-compose" id="muralCompose">
        <div class="mural-compose-row">
            <img
                src="<?php echo esc_url( $avatar_url ); ?>"
                alt="<?php echo esc_attr( $current_user->display_name ); ?>"
                class="mural-compose-avatar"
            >
            <textarea
                id="muralText"
                class="apollo-input mural-textarea"
                placeholder="Escreva um aviso, atualização ou mensagem para a equipe…"
                rows="2"
                required
            ></textarea>
        </div>
        <div class="mural-compose-actions">
            <button type="submit" class="btn btn-primary"><i class="ri-send-plane-line"></i> Postar</button>
        </div>
    </form>

    <!-- Feed de posts -->
    <div class="mural-feed" id="muralFeed">
        <div class="mural-loading" id="muralLoading">
            <i class="ri-loader-4-line ri-spin" style="font-size:24px;color:var(--ghost)"></i>
            <span>Carregando mural…</span>
        </div>
    </div>

</section>