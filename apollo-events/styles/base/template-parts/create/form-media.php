<?php
/**
 * Create Event — Media and links
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
            <div class="card sh01">
                <div class="tref-sec-lbl"><i class="ri-image-line"></i> Mídia e Links</div>

                <div class="field" style="margin-top: 16px;">
                    <label class="field-label">Imagem de Capa</label>
                    <div class="cover-upload" id="coverUpload">
                        <i class="ri-upload-cloud-2-line"></i>
                        <span>Clique para anexar a capa do evento</span>
                    </div>
                    <!-- PHP: media-library picker seta o attachment id aqui -->
                    <input type="hidden" id="ev-banner" name="banner" value="">
                    <input type="url" id="ev-banner-url" class="apollo-input" style="margin-top:10px"
                        placeholder="https://…/flyer.jpg" autocomplete="off"
                        aria-label="<?php esc_attr_e( 'URL da imagem de capa (mesma da destacada)', 'apollo-events' ); ?>">
                    <span class="txt-mono muted" style="display:block;margin-top:6px;text-transform:none;letter-spacing:0;font-size:11px;">
                        <?php esc_html_e( 'A URL deve ser uma imagem de verdade — é a mesma capa do post e do card no WhatsApp.', 'apollo-events' ); ?>
                    </span>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label class="field-label">Link do Vídeo (YouTube / .mp4 .webm .mov)</label>
                        <input type="url" id="ev-video" name="video_url" class="apollo-input" placeholder="YouTube ou https://…/video.mp4|.webm|.mov" onblur="validateVideoField()">
                    </div>
                    <div class="field">
                        <label class="field-label">Playlist Spotify / SoundCloud</label>
                        <input type="url" id="ev-audio" name="audio_url" class="apollo-input" placeholder="https://open.spotify.com/playlist/..." onblur="validateAudioField()">
                    </div>
                </div>

                <div class="field" style="margin-bottom: 0;">
                    <label class="field-label">Galeria de Fotos <span class="txt-mono muted" style="text-transform:none;letter-spacing:0;">(até 3 imagens)</span></label>
                    <div class="venue-images" id="galleryImages">
                        <div class="frame" data-gallery-slot="0"><i class="ri-add-line"></i></div>
                        <div class="frame" data-gallery-slot="1"><i class="ri-add-line"></i></div>
                        <div class="frame" data-gallery-slot="2"><i class="ri-add-line"></i></div>
                    </div>
                    <!-- PHP: CSV de attachment ids (ex.: 100,101,102) -->
                    <input type="hidden" id="ev-gallery" name="gallery" value="">
                </div>
            </div>