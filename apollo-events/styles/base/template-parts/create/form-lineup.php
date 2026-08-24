<?php
/**
 * Create Event — Line-up builder
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
            <div class="card sh01">
                <div class="flex-between" style="align-items: center;">
                    <div class="tref-sec-lbl" style="margin: 0;"><i class="ri-user-star-line"></i> Line-up</div>
                    <button type="button" class="btn btn-secondary" onclick="openModal('djModal')">
                        <i class="ri-add-line"></i> Novo DJ
                    </button>
                </div>

                <div class="field" style="margin-top: 16px;">
                    <label class="field-label">Buscar DJ</label>
                    <div style="position: relative;">
                        <i class="ri-search-line" style="position: absolute; left: 14px; top: 13px; color: var(--muted);"></i>
                        <input type="text" id="djSearch" class="apollo-input" style="padding-left: 38px;" placeholder="Busque DJs cadastrados para adicionar ao line-up..." onkeypress="handleDJSearch(event)">
                    </div>
                </div>

                <!-- PHP: ordem dos DJs (int[]) + slots [{dj_id,start_time,end_time}], populados por syncModel() -->
                <input type="hidden" id="ev-dj-ids" name="dj_ids" value="[]">
                <input type="hidden" id="ev-dj-slots" name="dj_slots" value="[]">

                <div id="lineupContainer">
                    <label class="field-label mt-3 mb-2">Organizar Line-up</label>
                    <div class="lineup-hint">A ordem abaixo define a exibição na página do evento, horários são opcionais.</div>
                </div>
            </div>
