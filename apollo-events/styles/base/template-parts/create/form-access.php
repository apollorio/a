<?php
/**
 * Create Event — Ingressos e listas (unified access card)
 *
 * One card: primary ticket (title + URL) always inline — NO × remove.
 * Extras from `_event_access_buttons` list title+URL with × at row end.
 * "+ Adicionar outro acesso" opens the lightbox (type / style / title / URL).
 *
 * Icon registry (form list, modal preview, single /evento/…):
 *   kind === "lista"  → ALWAYS class="ri-vip-line"
 *   kind === "ticket" → ALWAYS class="ri-ticket-2-line"
 * Style (soft/main/fem/cta) is chrome only — never swaps the icon.
 *
 * Save shim: title → `_event_ticket_price`, URL → `_event_ticket_url`;
 * list_url / lista_cta_label kept hidden so legacy values are not wiped.
 * Extras → `_event_access_buttons`.
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$access_buttons = array();
if ( ! empty( $edit_payload['access_buttons'] ) && is_array( $edit_payload['access_buttons'] ) ) {
	$access_buttons = $edit_payload['access_buttons'];
}

$axp_ticket_url  = isset( $edit_payload['ticket_url'] ) ? (string) $edit_payload['ticket_url'] : '';
$axp_ticket_pric = isset( $edit_payload['ticket_price'] ) ? (string) $edit_payload['ticket_price'] : '';
$axp_list_url    = isset( $edit_payload['list_url'] ) ? (string) $edit_payload['list_url'] : '';
$axp_lista_cta   = isset( $edit_payload['lista_cta_label'] ) ? (string) $edit_payload['lista_cta_label'] : '';
?>
            <style>
                .axb-empty{margin:10px 0 0;font-size:12.5px;color:var(--muted);}
                .axb-list{display:flex;flex-direction:column;gap:8px;margin:12px 0 0;}
                .axb-row{display:grid;grid-template-columns:36px minmax(0,1fr) minmax(0,1.2fr) auto;align-items:center;gap:8px;}
                .axb-row .apollo-input{margin:0;min-width:0;}
                .axb-row-ico{width:36px;height:36px;border-radius:10px;flex-shrink:0;display:grid;place-items:center;font-size:17px;background:var(--white-5,rgba(255,255,255,.08));color:var(--accent,#d1860a);}
                .axb-del{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;padding:0;border:0;border-radius:var(--r-sm,10px);background:transparent;color:var(--muted);cursor:pointer;flex-shrink:0;font-size:18px;line-height:1;transition:color .2s,background .2s;}
                .axb-del:hover{color:var(--txt-heading,#e1e1e1);background:var(--white-4,rgba(255,255,255,.06));}
                .axb-add{margin-top:14px;width:100%;justify-content:center;}
                .axb-primary{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.2fr);gap:10px;margin-top:12px;}
                @media (max-width:560px){
                    .axb-primary{grid-template-columns:1fr;}
                    .axb-row{grid-template-columns:36px 1fr auto;}
                    .axb-row .apollo-input[data-axb-field="url"]{grid-column:2 / -1;}
                    .axb-row .axb-del{grid-column:3;grid-row:1;}
                }

                .axb-type-toggle{position:relative;display:flex;align-items:center;width:120px;height:40px;padding:3px;
                    border-radius:var(--r-pill,999px);background:var(--white-4,rgba(255,255,255,.06));cursor:pointer;user-select:none;}
                .axb-type-toggle .axb-type-thumb{position:absolute;left:3px;top:3px;width:34px;height:34px;border-radius:50%;
                    background:var(--accent,#d1860a);color:#111;display:grid;place-items:center;font-size:16px;
                    transition:transform .3s cubic-bezier(.2,.9,.3,1);z-index:2;}
                .axb-type-toggle.is-lista .axb-type-thumb{transform:translateX(80px);}
                .axb-type-lbl{flex:1;text-align:center;font-family:var(--ff-mono);font-size:10px;text-transform:uppercase;
                    letter-spacing:.08em;color:var(--muted);z-index:1;}

                .axb-preview-wrap{margin-top:6px;}
                .axb-preview-btn{display:flex;align-items:center;gap:12px;width:100%;padding:14px 16px;border-radius:var(--r-sm);
                    background:var(--white-3,rgba(255,255,255,.04));color:var(--txt-heading,#e1e1e1);}
                .axb-preview-btn.axb-main{background:var(--accent,#d1860a);color:#111;}
                .axb-preview-btn.axb-soft{background:var(--white-3,rgba(255,255,255,.04));border:1px dashed var(--white-10,rgba(255,255,255,.18));}
                .axb-preview-btn.axb-lista,.axb-preview-btn.axb-fem{background:var(--white-2,rgba(255,255,255,.03));border:1px solid var(--white-5,rgba(255,255,255,.08));}
                .axb-p-ico{width:38px;height:38px;border-radius:10px;flex-shrink:0;display:grid;place-items:center;font-size:18px;background:rgba(255,255,255,.12);}
                .axb-preview-btn.axb-main .axb-p-ico{background:rgba(0,0,0,.14);}
                .axb-p-info{flex:1;min-width:0;}
                .axb-p-name{display:block;font-size:14px;font-weight:700;}
                .axb-p-sub{display:block;font-family:var(--ff-mono);font-size:10.5px;opacity:.65;margin-top:2px;}
                .axb-p-arr{font-size:17px;opacity:.5;flex-shrink:0;}
                .axb-preview-btn.axb-cta{justify-content:center;gap:9px;font-weight:700;font-size:14px;
                    border-radius:var(--r-pill,999px);background:var(--accent,#d1860a);color:#111;padding:15px 18px;}
                .axb-preview-btn.axb-cta i{font-size:17px;}
            </style>

            <div class="card sh01">
                <div class="tref-sec-lbl"><i class="ri-ticket-line"></i> Ingressos e listas</div>
                <p class="txt-secondary mt-2" style="margin-bottom: 4px;">Ingresso principal do evento e outros acessos (early bird, listas, CTAs), o nome é o que aparece no single <code>/evento/…</code>.</p>

                <?php /* Primary ingresso, no ×. Icon on single is always ri-ticket-2-line. */ ?>
                <div class="axb-primary">
                    <div class="field" style="margin:0;">
                        <label class="field-label" for="ev-ticket-price">Nome do ingresso</label>
                        <input type="text" id="ev-ticket-price" name="ticket_price" class="apollo-input" placeholder="ex.: Ingresso · 2º lote" value="<?php echo esc_attr( $axp_ticket_pric ); ?>">
                    </div>
                    <div class="field" style="margin:0;">
                        <label class="field-label" for="ev-tickets-url">Link do ingresso</label>
                        <input type="url" id="ev-tickets-url" name="ticket_url" class="apollo-input" placeholder="https://vendas.exemplo.com/evento" value="<?php echo esc_url( $axp_ticket_url ); ?>">
                    </div>
                </div>

                <?php /* Legacy scalars kept hidden so collectPayload does not wipe them. */ ?>
                <input type="hidden" id="ev-list-url" name="list_url" value="<?php echo esc_url( $axp_list_url ); ?>">
                <input type="hidden" id="ev-lista-cta-label" name="lista_cta_label" value="<?php echo esc_attr( $axp_lista_cta ); ?>">

                <?php
                /*
                 * Hidden JSON for extras (_event_access_buttons). Each row:
                 *   { kind, style, label, sub, url }
                 * kind "lista"  → icon ALWAYS ri-vip-line  (list / ingresso=false)
                 * kind "ticket" → icon ALWAYS ri-ticket-2-line
                 * Only these extras render a trailing × remove control — not the primary row above.
                 */
                ?>
                <input type="hidden" id="ev-access-buttons" name="access_buttons" value="<?php echo esc_attr( wp_json_encode( $access_buttons ) ); ?>">

                <div class="axb-list" id="axbList"></div>

                <button type="button" class="btn btn-secondary axb-add" onclick="openAccessBtnModal()">
                    <i class="ri-add-line"></i> Adicionar outro acesso
                </button>
            </div>
