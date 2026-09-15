<?php
/**
 * Create Event — DJ/venue modals + toast
 *
 * DJ quick-add (djPaneNew) seeds the fields needed for the DJ business card
 * and lineup: name*, bio, platforms, booking, first Out now! track, genres.
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
    <!-- Modal: Adicionar DJ ao Line-up (2 opções) -->
    <div class="modal-backdrop" id="djModal">
        <div class="modal modal--dj">
            <h2 class="modal-title">Adicionar DJ ao Line-up</h2>

            <div class="dj-tabs">
                <button type="button" class="dj-tab is-on" id="djTabPick" onclick="djTab('pick')">Selecionar da Apollo</button>
                <button type="button" class="dj-tab" id="djTabNew" onclick="djTab('new')">Cadastrar novo</button>
            </div>

            <!-- OPÇÃO A · selecionar DJ já cadastrado -->
            <div id="djPanePick">
                <div class="input-group" style="margin-top: 10px;">
                    <div class="as2 as2--dj" id="djSelect">
                        <input type="text" class="as2-input" id="djSelectInput" placeholder=" " autocomplete="off" role="combobox" aria-expanded="false" aria-controls="djSelectDrop">
                        <label class="apollo-label">DJ cadastrado na Apollo</label>
                        <span class="as2-line"></span>
                        <button type="button" class="as2-arrow-btn" tabindex="-1" aria-label="Abrir lista"><i class="ri-arrow-down-s-line as2-arrow"></i></button>
                        <div class="as2-drop" id="djSelectDrop" role="listbox">
                            <div class="as2-search-wrap">
                                <i class="ri-search-line as2-search-icon"></i>
                                <input type="text" class="as2-search" placeholder="Buscar DJ...">
                            </div>
                            <div class="as2-opts" id="djSelectOpts"></div>
                            <div class="as2-empty">Nenhum DJ encontrado</div>
                        </div>
                    </div>
                </div>
                <div class="dj-picked" id="djPicked" hidden>
                    <img id="djPickedImg" src="" alt="">
                    <div><div class="n" id="djPickedName">—</div><div class="h" id="djPickedHandle">—</div></div>
                </div>
                <div class="flex-row" style="justify-content: flex-end; margin-top: 24px; gap: 8px;">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('djModal')">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="djPickAddBtn" onclick="addPickedDJ()" disabled>Adicionar ao Line-up</button>
                </div>
            </div>

            <!-- OPÇÃO B · cadastrar DJ novo (inputs alinhados ao cartão CPT dj) -->
            <div id="djPaneNew" hidden>
                <p class="field-hint" style="margin:10px 0 14px;font-size:calc(var(--fs-r,1)*11px);color:var(--muted);line-height:1.45;">
                    Campos abaixo alimentam o CPT <strong>dj</strong> e o cartão de artista. Out now! completo (várias faixas) fica no admin do DJ.
                </p>

                <div class="field">
                    <label class="field-label">Nome / Nome Artístico <span class="accent">*</span></label>
                    <input type="text" id="djNewName" class="apollo-input" placeholder="Digite o nome (obrigatório)" autocomplete="off">
                    <div class="field-err" id="djNewNameErr" style="display:none;">O nome é obrigatório</div>
                </div>

                <div class="field">
                    <label class="field-label">Bio curta (hero do cartão)</label>
                    <textarea id="djNewBio" class="apollo-input" rows="2" maxlength="280" placeholder="Máx. 280 caracteres"></textarea>
                </div>

                <div class="grid-2" style="gap:12px;">
                    <div class="field" style="margin-bottom:0;">
                        <label class="field-label">Instagram</label>
                        <input type="text" id="djNewHandle" class="apollo-input" placeholder="@usuario">
                    </div>
                    <div class="field" style="margin-bottom:0;">
                        <label class="field-label">E-mail booking</label>
                        <input type="email" id="djNewBooking" class="apollo-input" placeholder="booking@…">
                    </div>
                </div>

                <div class="field" style="margin-top:16px;">
                    <label class="field-label">SoundCloud (link)</label>
                    <input type="url" id="djNewSoundcloud" class="apollo-input" placeholder="https://soundcloud.com/…">
                </div>
                <div class="grid-2" style="gap:12px;">
                    <div class="field" style="margin-bottom:0;">
                        <label class="field-label">Spotify (link)</label>
                        <input type="url" id="djNewSpotify" class="apollo-input" placeholder="https://open.spotify.com/…">
                    </div>
                    <div class="field" style="margin-bottom:0;">
                        <label class="field-label">Bandcamp (link)</label>
                        <input type="url" id="djNewBandcamp" class="apollo-input" placeholder="https://….bandcamp.com">
                    </div>
                </div>

                <div class="field" style="margin-top:16px;">
                    <label class="field-label">Estilos / sons <span class="txt-mono muted" style="text-transform:none;letter-spacing:0;">(vírgula)</span></label>
                    <input type="text" id="djNewGenres" class="apollo-input" placeholder="Hard Groove, Peak Time, Techno">
                </div>

                <div class="field">
                    <label class="field-label">Foto / Avatar, link (opcional)</label>
                    <input type="url" id="djNewPhoto" class="apollo-input" placeholder="https://…  (vazio = avatar gerado)">
                </div>

                <div class="field">
                    <label class="field-label">Kit Promo, link Drive (opcional)</label>
                    <input type="url" id="djNewKit" class="apollo-input" placeholder="https://drive.google.com/…">
                </div>

                <fieldset class="dj-track-seed" style="margin:8px 0 0;padding:14px 0 0;border:0;">
                    <legend class="field-label" style="padding:0;margin-bottom:10px;">Out now!, 1ª faixa (opcional)</legend>
                    <div class="field">
                        <label class="field-label">Nome da faixa</label>
                        <input type="text" id="djNewTrackTitle" class="apollo-input" placeholder="Nome do lançamento">
                    </div>
                    <div class="field">
                        <label class="field-label">Link da faixa</label>
                        <input type="url" id="djNewTrackUrl" class="apollo-input" placeholder="https://soundcloud.com/…/track">
                    </div>
                    <div class="grid-2" style="gap:12px;">
                        <div class="field" style="margin-bottom:0;">
                            <label class="field-label">Duração</label>
                            <input type="text" id="djNewTrackDuration" class="apollo-input" placeholder="6:12" inputmode="numeric">
                        </div>
                        <div class="field" style="margin-bottom:0;">
                            <label class="field-label">Ano de postagem</label>
                            <input type="text" id="djNewTrackYear" class="apollo-input" placeholder="2026" maxlength="4" inputmode="numeric">
                        </div>
                    </div>
                    <p class="field-hint" style="margin:10px 0 0;font-size:calc(var(--fs-r,1)*10px);color:var(--muted);">No cartão: <span class="txt-mono">{ano} · RIO DE JANEIRO · {duração}</span></p>
                </fieldset>

                <div class="flex-row" style="justify-content: flex-end; margin-top: 24px; gap: 8px;">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('djModal')">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="registerNewDJ()">Cadastrar e Adicionar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Cadastrar Local -->
    <div class="modal-backdrop" id="venueModal">
        <div class="modal">
            <h2 class="modal-title">Cadastrar Novo Local</h2>
            <div class="field">
                <label class="field-label">Nome do Local</label>
                <input type="text" id="vm-name" class="apollo-input" placeholder="Digite o nome do local">
            </div>
            <div class="field">
                <label class="field-label">Endereço Completo</label>
                <input type="text" id="vm-address" class="apollo-input" placeholder="Rua, Número, Cidade, Estado" onblur="geocodeVenueModal(this.value)">
            </div>
            <div class="field">
                <label class="field-label">Imagens do Local (links, máx. 3)</label>
                <input type="url" id="vm-img1" class="apollo-input" placeholder="Link da imagem 1" style="margin-bottom: 8px;">
                <input type="url" id="vm-img2" class="apollo-input" placeholder="Link da imagem 2" style="margin-bottom: 8px;">
                <input type="url" id="vm-img3" class="apollo-input" placeholder="Link da imagem 3">
            </div>
            <div class="grid-2" style="gap: 12px;">
                <div class="field" style="margin-bottom: 0;">
                    <label class="field-label">Latitude (auto)</label>
                    <input type="text" id="vm-lat" class="apollo-input txt-mono" placeholder="auto via OpenStreetMap" readonly>
                </div>
                <div class="field" style="margin-bottom: 0;">
                    <label class="field-label">Longitude (auto)</label>
                    <input type="text" id="vm-lon" class="apollo-input txt-mono" placeholder="auto via OpenStreetMap" readonly>
                </div>
            </div>
            <div class="flex-row" style="justify-content: flex-end; margin-top: 32px;">
                <button type="button" class="btn btn-ghost" onclick="closeModal('venueModal')">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="registerVenue()">Cadastrar Local</button>
            </div>
        </div>
    </div>

    <!-- Modal: Adicionar outro acesso (type / style / title / URL) -->
    <div class="modal-backdrop" id="accessBtnModal">
        <div class="modal">
            <h2 class="modal-title" id="axbModalTitle">Adicionar outro acesso</h2>

            <div class="field">
                <label class="field-label">Tipo</label>
                <div class="axb-type-toggle" id="axbTypeToggle" role="switch" aria-checked="false" tabindex="0"
                     onclick="axbToggleType()" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();axbToggleType();}">
                    <div class="axb-type-thumb"><i class="ri-coupon-2-fill" id="axbTypeIcon" title="Ingresso"></i></div>
                    <span class="axb-type-lbl">Ingresso</span>
                    <span class="axb-type-lbl">Lista</span>
                </div>
                <input type="hidden" id="axb-kind" value="ticket">
            </div>

            <div class="field">
                <label class="field-label">Estilo do Botão</label>
                <select id="axb-style" class="apollo-select" onchange="axbRenderPreview()">
                    <option value="main">Principal, escuro (ev-ticket)</option>
                    <option value="soft" selected>Early Bird, suave (is-soft)</option>
                    <option value="lista">Lista, claro (is-lista)</option>
                    <option value="fem">Lista Fem, rosa (is-fem)</option>
                    <option value="cta">CTA Lista, botão largo (ev-lista-cta)</option>
                </select>
            </div>

            <div class="grid-2">
                <div class="field">
                    <label class="field-label">Nome</label>
                    <input type="text" id="axb-label" class="apollo-input" placeholder="ex.: Early Bird · Nome na lista" oninput="axbRenderPreview()">
                </div>
                <div class="field">
                    <label class="field-label">Condição (opcional)</label>
                    <input type="text" id="axb-sub" class="apollo-input" placeholder="ex.: Até 00h30" oninput="axbRenderPreview()">
                </div>
            </div>

            <div class="field">
                <label class="field-label">Link <span class="txt-mono muted" style="text-transform:none;letter-spacing:0;">(abre em nova aba)</span></label>
                <input type="url" id="axb-url" class="apollo-input" placeholder="https://..." oninput="axbRenderPreview()">
            </div>

            <div class="field" style="margin-bottom: 0;">
                <label class="field-label">Preview</label>
                <div class="axb-preview-wrap" id="axbPreviewWrap"></div>
            </div>

            <div class="flex-row" style="justify-content: flex-end; margin-top: 28px;">
                <div class="flex-row" style="gap: 8px;">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('accessBtnModal')">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="axbSave()">Adicionar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: confirmar exclusão do evento (só em edição) -->
    <div class="modal-backdrop" id="deleteEventModal">
        <div class="modal">
            <h2 class="modal-title"><?php esc_html_e( 'Você tem certeza?', 'apollo-events' ); ?></h2>
            <p class="txt-secondary" style="margin: 8px 0 0;">
                <?php esc_html_e( 'Esta ação move o evento para a lixeira. Você pode desfazer depois no painel, se precisar.', 'apollo-events' ); ?>
            </p>
            <div class="flex-row" style="justify-content: flex-end; gap: 8px; margin-top: 28px;">
                <button type="button" class="btn btn-ghost" onclick="closeModal('deleteEventModal')"><?php esc_html_e( 'Cancelar', 'apollo-events' ); ?></button>
                <button type="button" class="btn btn-primary" id="confirmDeleteEventBtn" onclick="confirmDeleteEvent()"><?php esc_html_e( 'Quero deletar este evento', 'apollo-events' ); ?></button>
            </div>
        </div>
    </div>

    <div class="apx-toast" id="apxToast"></div>
