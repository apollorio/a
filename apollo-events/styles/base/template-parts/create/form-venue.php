<?php
/**
 * Create Event — Venue / local
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
            <div class="card sh01">
                <div class="flex-between" style="margin-bottom: 16px;">
                    <div class="tref-sec-lbl" style="margin: 0;"><i class="ri-map-pin-line"></i> Local e Endereço</div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="openModal('venueModal')"><i class="ri-add-line"></i> Cadastrar Local</button>
                </div>

                <div class="field">
                    <label class="field-label">Buscar Local</label>
                    <div style="position: relative;">
                        <i class="ri-search-line" style="position: absolute; left: 14px; top: 13px; color: var(--muted);"></i>
                        <input type="text" id="ev-venue-search" class="apollo-input" style="padding-left: 38px;" placeholder="Digite o nome do local..." autocomplete="off">
                        <!-- Single venue write path: loc_id → _event_loc_id (FK to local CPT). Combobox in create-wire.js. -->
                        <input type="hidden" id="ev-loc-id" name="loc_id" value="">
                    </div>
                </div>

                <div id="venueInfoBlock" style="display:none;padding: 16px; background: var(--surface-1); border-radius: var(--r-sm); border: 1px solid rgba(var(--rgb-diff),.04);">
                    <div class="flex-row gap-2" style="margin-bottom: 12px;">
                        <i class="ri-map-pin-2-fill" style="color: var(--txt-heading); font-size: 20px;"></i>
                        <div>
                            <div id="venueName" style="font-weight: 600; color: var(--txt-heading); font-size: 14px;">—</div>
                            <div id="venueAddress" style="font-size: 12px; color: var(--muted); font-family: var(--ff-mono);">—</div>
                        </div>
                    </div>

                    <label class="field-label mt-2">Imagens do Local</label>
                    <div class="venue-images" id="venueImages"></div>
                </div>
                <!-- Alvo do geocode OSM (Nominatim) — alimenta a previsão do tempo -->
                <input type="hidden" id="lat" value="-22.9068">
                <input type="hidden" id="lon" value="-43.1729">
            </div>
