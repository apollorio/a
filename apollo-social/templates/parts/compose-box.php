<?php

/**
 * Template Part: Compose Box — Text + URL embed inputs (NO image upload)
 *
 * 4 media action buttons: SoundCloud, Spotify, Apollo Event, YouTube
 * Each toggles a URL input field. Pasted URLs auto-detect embeds.
 * Character counter ring (280 limit, URLs excluded).
 *
 * Security: NO image upload capability — text + URL only.
 *
 * Expected vars: $avatar, $char_limit
 *
 * @package Apollo\Social
 * @since   3.0.0
 */

defined('ABSPATH') || exit;

$avatar     = isset($avatar) ? (string) $avatar : get_avatar_url(get_current_user_id(), array('size' => 80));
$char_limit = isset($char_limit) ? (int) $char_limit : 280;
?>
<div class="compose-card gsap-el" id="feed-compose">
    <div class="compose-row">
        <img src="<?php echo esc_url($avatar); ?>" alt="" class="compose-avatar" loading="lazy">
        <div class="compose-input-wrap">
            <textarea id="feed-compose-text" class="apollo-input" placeholder="No que você está pensando?" maxlength="2000" rows="1"></textarea>

            <!-- URL Input Bar (toggled by media buttons) -->
            <div id="compose-url-bar" class="compose-url-bar" style="display:none;">
                <i id="compose-url-icon" class="ri-link"></i>
                <input type="url" id="compose-url-input" class="apollo-input compose-url-input" placeholder="Cole a URL aqui..." autocomplete="off">
                <button id="compose-url-add" class="compose-url-add" type="button" title="Adicionar">
                    <i class="ri-add-line"></i>
                </button>
                <button id="compose-url-close" class="compose-url-close" type="button" title="Fechar">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <!-- Embed Preview (auto-detected from text or URL bar) -->
            <div id="compose-embed-preview" class="compose-embed-preview" style="display:none;"></div>
        </div>
    </div>

    <div class="compose-footer">
        <div class="compose-media-actions">
            <button type="button" class="compose-media-btn compose-media-soundcloud" data-media="soundcloud" title="SoundCloud">
                <i class="ri-soundcloud-fill"></i>
            </button>
            <button type="button" class="compose-media-btn compose-media-spotify" data-media="spotify" title="Spotify">
                <i class="ri-spotify-fill"></i>
            </button>
            <button type="button" class="compose-media-btn compose-media-event" data-media="event" title="Evento Apollo">
                <i class="ri-calendar-event-fill"></i>
            </button>
            <button type="button" class="compose-media-btn compose-media-youtube" data-media="youtube" title="YouTube">
                <i class="ri-youtube-fill"></i>
            </button>
        </div>
        <div class="compose-footer-right">
            <div class="char-counter" id="char-counter">
                <svg class="char-ring" viewBox="0 0 36 36">
                    <circle class="char-ring-bg" cx="18" cy="18" r="15.5" fill="none" stroke-width="3" />
                    <circle class="char-ring-fill" cx="18" cy="18" r="15.5" fill="none" stroke-width="3" stroke-dasharray="97.4" stroke-dashoffset="97.4" />
                </svg>
                <span class="char-count-text" id="char-count-text"><?php echo (int) $char_limit; ?></span>
            </div>
            <button id="feed-post-btn" class="compose-btn" disabled>Publicar</button>
        </div>
    </div>
</div>