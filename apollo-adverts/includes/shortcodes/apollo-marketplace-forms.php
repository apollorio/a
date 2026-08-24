<?php

/**
 * Marketplace Shortcodes — Sell Ticket & Rent Space
 *
 * Migrated from apollo-classifieds/src/Shortcodes.php (FASE 1 merge).
 * These post to apollo/v1/classifieds with _classified_type meta.
 *
 * [apollo_classifieds_sell_ticket] — Revender ingressos
 * [apollo_classifieds_rent_space]  — Alugar quarto/sofá/espaço
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// SHARED HELPERS
// ─────────────────────────────────────────────────────────────────────────────

function apollo_adverts_marketplace_check_login(): string
{
    if (! is_user_logged_in()) {
        return '<p class="apl-notice">' . esc_html__('Você precisa estar logado para publicar um anúncio.', 'apollo-adverts') . '</p>';
    }
    return '';
}

function apollo_adverts_marketplace_form_styles(): string
{
    return '
	<style>
	.apl-classified-wrap{max-width:600px;margin:0 auto;padding:24px 0}
	.apl-form-header{display:flex;align-items:center;gap:10px;margin-bottom:20px}
	.apl-form-header i{font-size:24px;color:var(--primary,FF9820)}
	.apl-form-header h2{margin:0;font-size:22px;font-weight:700}
	.apl-form-msg{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px}
	.apl-form-msg.ok{background:rgba(34,197,94,.12);color:#22c55e}
	.apl-form-msg.err{background:rgba(239,68,68,.12);color:#ef4444}
	.apl-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
	@media(max-width:480px){.apl-row{grid-template-columns:1fr}}
	.apl-btn-primary{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:13px;border:none;border-radius:10px;background:var(--primary,FF9820);color:#fff;font-size:15px;font-weight:700;cursor:pointer;margin-top:18px}
	.apl-btn-primary:disabled{opacity:.5;cursor:not-allowed}
	.apl-check-group{display:flex;align-items:center;gap:8px;margin:10px 0}
	.apl-check-group label{font-size:14px}

	/* ── Regras da estadia (accommodation form) ──────────────────────────
	   Host-facing rule setter. Presets carry the common answers so most hosts
	   never touch a stepper; steppers cover the rest; the summary line reads
	   the whole thing back as a sentence. Tokens only — inherits whatever
	   theme core.js injected. */
	.apl-stay{border:1px solid var(--border,rgba(128,128,128,.22));border-radius:14px;padding:16px 16px 14px;margin:18px 0 4px}
	.apl-stay-legend{display:flex;align-items:center;gap:7px;padding:0 6px;font-size:13px;font-weight:700;color:var(--txt-heading,inherit)}
	.apl-stay-legend i{color:var(--muted,#888)}
	.apl-stay-block{margin-top:14px}
	.apl-stay-block:first-of-type{margin-top:6px}
	.apl-stay-head{display:flex;flex-direction:column;margin-bottom:8px}
	.apl-stay-label{font-size:14px;font-weight:600;color:var(--txt-heading,inherit)}
	.apl-stay-hint{font-size:11.5px;color:var(--muted,#888);margin-top:1px}
	.apl-preset-row{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px}
	.apl-preset{padding:7px 13px;border:1px solid var(--border,rgba(128,128,128,.22));border-radius:999px;background:transparent;color:var(--muted,#888);font-family:inherit;font-size:12.5px;font-weight:600;cursor:pointer;transition:border-color .2s,color .2s,background .2s}
	.apl-preset:hover{color:var(--txt-heading,inherit);border-color:var(--muted,#888)}
	.apl-preset.is-on{background:var(--txt-heading,#111);border-color:var(--txt-heading,#111);color:var(--bg,#fff)}
	.apl-stepper{display:flex;align-items:center;gap:8px}
	.apl-step-btn{width:38px;height:38px;flex:0 0 38px;display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--border,rgba(128,128,128,.22));border-radius:50%;background:transparent;color:var(--txt-heading,inherit);font-size:17px;cursor:pointer;transition:border-color .2s,background .2s}
	.apl-step-btn:hover{border-color:var(--muted,#888);background:var(--surface,rgba(128,128,128,.08))}
	.apl-step-btn:active{transform:scale(.93)}
	.apl-step-input{width:74px;height:38px;padding:0 10px;text-align:center;border:1px solid var(--border,rgba(128,128,128,.22));border-radius:10px;background:var(--bg,transparent);color:var(--txt-heading,inherit);font-family:inherit;font-size:15px;font-weight:700}
	.apl-step-input::-webkit-outer-spin-button,.apl-step-input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
	.apl-step-input{-moz-appearance:textfield}
	.apl-step-input.has-error{border-color:#ef4444}
	.apl-step-unit{font-size:12.5px;color:var(--muted,#888)}
	.apl-stay input[type="date"].has-error{border-color:#ef4444}
	.apl-stay-summary{margin:14px 0 0;padding:10px 12px;border-radius:10px;background:var(--surface,rgba(128,128,128,.08));font-size:12.5px;line-height:1.45;color:var(--muted,#888)}
	.apl-stay-summary.has-error{background:rgba(239,68,68,.12);color:#ef4444;font-weight:600}
	</style>';
}

// ─────────────────────────────────────────────────────────────────────────────
// [apollo_classifieds_sell_ticket] — Formulário de revenda de ingressos
// ─────────────────────────────────────────────────────────────────────────────

function apollo_adverts_shortcode_sell_ticket($atts = array()): string
{
    $guard = apollo_adverts_marketplace_check_login();
    if ($guard) {
        return $guard;
    }

    $nonce    = wp_create_nonce('wp_rest');
    $rest_url = esc_url_raw(rest_url('apollo/v1/classifieds'));

    $events = get_posts(
        array(
            'post_type'      => 'event',
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'orderby'        => 'date',
            'order'          => 'DESC',
        )
    );

    ob_start();
    echo wp_kses_data(apollo_adverts_marketplace_form_styles());
?>
    <script src="<?php echo esc_url( function_exists('apollo_cdn_core_js_url') ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?v=t0x1x&versao=bb' ); ?>" fetchpriority="high" crossorigin="anonymous"></script>
    <div class="apl-classified-wrap">
        <div class="apl-form-header">
            <i class="ri-ticket-2-line"></i>
            <h2><?php esc_html_e('Revender Ingresso', 'apollo-adverts'); ?></h2>
        </div>
        <form id="aplSellTicketForm" novalidate>
            <div class="input-group">
                <input type="text" id="st_title" name="title" class="apollo-input" placeholder=" " required>
                <label for="st_title" class="apollo-label"><?php esc_html_e('Título do Anúncio', 'apollo-adverts'); ?> *</label>
            </div>

            <?php if ($events) : ?>
                <div class="input-group">
                    <label for="st_event" class="apollo-label" style="position:static;margin-bottom:4px;"><?php esc_html_e('Evento', 'apollo-adverts'); ?></label>
                    <select id="st_event" name="event_id" class="apollo-input">
                        <option value=""><?php esc_html_e('Selecione o evento...', 'apollo-adverts'); ?></option>
                        <?php foreach ($events as $ev) : ?>
                            <option value="<?php echo esc_attr((string) $ev->ID); ?>"><?php echo esc_html($ev->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="input-group">
                <label for="st_ticket_type" class="apollo-label" style="position:static;margin-bottom:4px;"><?php esc_html_e('Tipo de Ingresso', 'apollo-adverts'); ?></label>
                <select id="st_ticket_type" name="condition" class="apollo-input">
                    <option value="pista"><?php esc_html_e('Pista', 'apollo-adverts'); ?></option>
                    <option value="vip"><?php esc_html_e('VIP', 'apollo-adverts'); ?></option>
                    <option value="camarote"><?php esc_html_e('Camarote', 'apollo-adverts'); ?></option>
                    <option value="mesa"><?php esc_html_e('Mesa', 'apollo-adverts'); ?></option>
                    <option value="outro"><?php esc_html_e('Outro', 'apollo-adverts'); ?></option>
                </select>
            </div>

            <div class="apl-row">
                <div class="input-group">
                    <input type="number" id="st_qty" name="quantity" class="apollo-input" placeholder=" " min="1" value="1">
                    <label for="st_qty" class="apollo-label"><?php esc_html_e('Quantidade', 'apollo-adverts'); ?></label>
                </div>
                <div class="input-group">
                    <input type="number" id="st_price" name="price" class="apollo-input" placeholder=" " min="0" step="0.01">
                    <label for="st_price" class="apollo-label"><?php esc_html_e('Preço (R$)', 'apollo-adverts'); ?></label>
                </div>
            </div>

            <div class="apl-check-group">
                <input type="checkbox" id="st_neg" name="negotiable" value="1">
                <label for="st_neg"><?php esc_html_e('Aceito negociar o preço', 'apollo-adverts'); ?></label>
            </div>

            <div class="apl-row">
                <div class="input-group">
                    <input type="tel" id="st_phone" name="contact_phone" class="apollo-input" placeholder=" ">
                    <label for="st_phone" class="apollo-label"><?php esc_html_e('Telefone', 'apollo-adverts'); ?></label>
                </div>
                <div class="input-group">
                    <input type="tel" id="st_wa" name="contact_whatsapp" class="apollo-input" placeholder=" ">
                    <label for="st_wa" class="apollo-label">WhatsApp</label>
                </div>
            </div>

            <div class="input-group">
                <textarea id="st_desc" name="description" class="apollo-input" placeholder=" " rows="3"></textarea>
                <label for="st_desc" class="apollo-label"><?php esc_html_e('Detalhes adicionais', 'apollo-adverts'); ?></label>
            </div>

            <div class="apl-form-msg" id="aplSellTicketMsg" style="display:none;"></div>

            <button type="submit" class="apl-btn-primary" id="aplSellTicketSubmit">
                <i class="ri-send-plane-fill"></i>
                <span><?php esc_html_e('Publicar Anúncio', 'apollo-adverts'); ?></span>
            </button>
        </form>
    </div>
    <script>
        (function() {
            'use strict';
            var NONCE = '<?php echo esc_js($nonce); ?>';
            var REST = '<?php echo esc_js($rest_url); ?>';
            var form = document.getElementById('aplSellTicketForm');
            var msg = document.getElementById('aplSellTicketMsg');
            var btn = document.getElementById('aplSellTicketSubmit');
            if (!form) return;
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                msg.style.display = 'none';
                btn.disabled = true;
                btn.querySelector('span').textContent = 'Publicando...';
                try {
                    var d = {
                        classified_type: 'ticket_sell'
                    };
                    var ti = form.querySelector('[name="title"]').value.trim();
                    if (!ti) throw new Error('Título obrigatório.');
                    d.title = ti;
                    var evEl = form.querySelector('[name="event_id"]');
                    if (evEl && evEl.value) d.event_id = parseInt(evEl.value, 10);
                    var condEl = form.querySelector('[name="condition"]');
                    if (condEl) d.condition = condEl.value;
                    var qtyEl = form.querySelector('[name="quantity"]');
                    if (qtyEl && qtyEl.value) d.quantity = parseInt(qtyEl.value, 10);
                    var prEl = form.querySelector('[name="price"]');
                    if (prEl && prEl.value) d.price = parseFloat(prEl.value);
                    d.currency = 'BRL';
                    var negEl = form.querySelector('[name="negotiable"]');
                    d.negotiable = negEl && negEl.checked ? '1' : '';
                    var phEl = form.querySelector('[name="contact_phone"]');
                    if (phEl && phEl.value.trim()) d.contact_phone = phEl.value.trim();
                    var waEl = form.querySelector('[name="contact_whatsapp"]');
                    if (waEl && waEl.value.trim()) d.contact_whatsapp = waEl.value.trim();
                    var deEl = form.querySelector('[name="description"]');
                    if (deEl && deEl.value.trim()) d.description = deEl.value.trim();
                    var r = await fetch(REST, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': NONCE
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(d)
                    });
                    var res = await r.json();
                    if (!r.ok) throw new Error(res.message || 'Erro ao publicar.');
                    msg.className = 'apl-form-msg ok';
                    msg.textContent = '✓ Anúncio publicado!';
                    msg.style.display = '';
                    form.reset();
                    btn.disabled = false;
                    btn.querySelector('span').textContent = 'Publicar Anúncio';
                    if (res.permalink) setTimeout(function() {
                        window.location.href = res.permalink;
                    }, 1800);
                } catch (err) {
                    msg.className = 'apl-form-msg err';
                    msg.textContent = err.message;
                    msg.style.display = '';
                    btn.disabled = false;
                    btn.querySelector('span').textContent = 'Publicar Anúncio';
                }
            });
        })();
    </script>
<?php
    return ob_get_clean();
}

// ─────────────────────────────────────────────────────────────────────────────
// [apollo_classifieds_rent_space] — Formulário de aluguel de espaço
// ─────────────────────────────────────────────────────────────────────────────

function apollo_adverts_shortcode_rent_space($atts = array()): string
{
    $guard = apollo_adverts_marketplace_check_login();
    if ($guard) {
        return $guard;
    }

    $nonce    = wp_create_nonce('wp_rest');
    $rest_url = esc_url_raw(rest_url('apollo/v1/classifieds'));

    ob_start();
    echo wp_kses_data(apollo_adverts_marketplace_form_styles());
?>
    <script src="<?php echo esc_url( function_exists('apollo_cdn_core_js_url') ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?v=t0x1x&versao=bb' ); ?>" fetchpriority="high" crossorigin="anonymous"></script>
    <div class="apl-classified-wrap">
        <div class="apl-form-header">
            <i class="ri-home-heart-line"></i>
            <h2><?php esc_html_e('Anunciar Quarto / Sofá / Espaço', 'apollo-adverts'); ?></h2>
        </div>
        <form id="aplRentSpaceForm" novalidate>
            <div class="input-group">
                <input type="text" id="rs_title" name="title" class="apollo-input" placeholder=" " required>
                <label for="rs_title" class="apollo-label"><?php esc_html_e('Título do Anúncio', 'apollo-adverts'); ?> *</label>
            </div>

            <div class="input-group">
                <label for="rs_type" class="apollo-label" style="position:static;margin-bottom:4px;"><?php esc_html_e('Tipo de Espaço', 'apollo-adverts'); ?> *</label>
                <select id="rs_type" name="space_type" class="apollo-input" required>
                    <option value="quarto"><?php esc_html_e('Quarto', 'apollo-adverts'); ?></option>
                    <option value="sofa"><?php esc_html_e('Sofá', 'apollo-adverts'); ?></option>
                    <option value="espaco"><?php esc_html_e('Espaço / Sala / Estúdio', 'apollo-adverts'); ?></option>
                </select>
            </div>

            <div class="input-group">
                <input type="text" id="rs_location" name="location" class="apollo-input" placeholder=" ">
                <label for="rs_location" class="apollo-label"><?php esc_html_e('Localização (Bairro / Cidade)', 'apollo-adverts'); ?></label>
            </div>

            <div class="apl-row">
                <div class="input-group">
                    <input type="number" id="rs_price" name="price" class="apollo-input" placeholder=" " min="0" step="0.01">
                    <label for="rs_price" class="apollo-label"><?php esc_html_e('Preço/noite (R$)', 'apollo-adverts'); ?></label>
                </div>
                <div class="input-group">
                    <label for="rs_currency" class="apollo-label" style="position:static;margin-bottom:4px;"><?php esc_html_e('Moeda', 'apollo-adverts'); ?></label>
                    <select id="rs_currency" name="currency" class="apollo-input">
                        <option value="BRL">R$ (BRL)</option>
                        <option value="USD">$ (USD)</option>
                        <option value="EUR">€ (EUR)</option>
                    </select>
                </div>
            </div>

            <?php
            /* ── Regras da estadia ────────────────────────────────────────
               Host-owned rules: how long someone may stay, and when the place
               is free. Modelled on the way Airbnb asks a host to set this —
               tap-first presets for the common answers, steppers for the rest,
               and a plain-language summary that reads back the decision as a
               sentence so the host can sanity-check it without re-reading the
               fields.

               The HOSTEL switch is deliberately absent from this form. It
               decides whether a listing bypasses the auth gate entirely, so it
               is staff-verified in wp-admin only — see
               APOLLO_ADVERTS_ADMIN_ONLY_META in includes/constants.php. Adding
               a hostel field here would let anyone self-declare their spare
               room a public business. */
            ?>
            <fieldset class="apl-stay">
                <legend class="apl-stay-legend">
                    <i class="ri-calendar-check-line" aria-hidden="true"></i>
                    <?php esc_html_e('Regras da estadia', 'apollo-adverts'); ?>
                </legend>

                <!-- Mínimo de noites -->
                <div class="apl-stay-block">
                    <div class="apl-stay-head">
                        <span class="apl-stay-label"><?php esc_html_e('Mínimo de noites', 'apollo-adverts'); ?></span>
                        <span class="apl-stay-hint"><?php esc_html_e('Quanto tempo, no mínimo, alguém precisa ficar', 'apollo-adverts'); ?></span>
                    </div>
                    <div class="apl-preset-row" role="group" aria-label="<?php esc_attr_e('Mínimo de noites', 'apollo-adverts'); ?>">
                        <button type="button" class="apl-preset" data-target="rs_min_nights" data-value="1"><?php esc_html_e('1 noite', 'apollo-adverts'); ?></button>
                        <button type="button" class="apl-preset" data-target="rs_min_nights" data-value="2">2</button>
                        <button type="button" class="apl-preset" data-target="rs_min_nights" data-value="3">3</button>
                        <button type="button" class="apl-preset" data-target="rs_min_nights" data-value="7"><?php esc_html_e('1 semana', 'apollo-adverts'); ?></button>
                    </div>
                    <div class="apl-stepper">
                        <button type="button" class="apl-step-btn" data-step="-1" data-target="rs_min_nights" aria-label="<?php esc_attr_e('Diminuir', 'apollo-adverts'); ?>"><i class="ri-subtract-line"></i></button>
                        <input type="number" id="rs_min_nights" name="min_nights" class="apl-step-input" min="0" max="365" step="1" value="0" inputmode="numeric">
                        <button type="button" class="apl-step-btn" data-step="1" data-target="rs_min_nights" aria-label="<?php esc_attr_e('Aumentar', 'apollo-adverts'); ?>"><i class="ri-add-line"></i></button>
                        <span class="apl-step-unit"><?php esc_html_e('noites', 'apollo-adverts'); ?></span>
                    </div>
                </div>

                <!-- Máximo de dias -->
                <div class="apl-stay-block">
                    <div class="apl-stay-head">
                        <span class="apl-stay-label"><?php esc_html_e('Máximo de dias', 'apollo-adverts'); ?></span>
                        <span class="apl-stay-hint"><?php esc_html_e('Limite por hóspede — deixe 0 para sem limite', 'apollo-adverts'); ?></span>
                    </div>
                    <div class="apl-preset-row" role="group" aria-label="<?php esc_attr_e('Máximo de dias', 'apollo-adverts'); ?>">
                        <button type="button" class="apl-preset" data-target="rs_max_days" data-value="7">7</button>
                        <button type="button" class="apl-preset" data-target="rs_max_days" data-value="14">14</button>
                        <button type="button" class="apl-preset" data-target="rs_max_days" data-value="30"><?php esc_html_e('1 mês', 'apollo-adverts'); ?></button>
                        <button type="button" class="apl-preset" data-target="rs_max_days" data-value="0"><?php esc_html_e('Sem limite', 'apollo-adverts'); ?></button>
                    </div>
                    <div class="apl-stepper">
                        <button type="button" class="apl-step-btn" data-step="-1" data-target="rs_max_days" aria-label="<?php esc_attr_e('Diminuir', 'apollo-adverts'); ?>"><i class="ri-subtract-line"></i></button>
                        <input type="number" id="rs_max_days" name="max_days" class="apl-step-input" min="0" max="3650" step="1" value="0" inputmode="numeric">
                        <button type="button" class="apl-step-btn" data-step="1" data-target="rs_max_days" aria-label="<?php esc_attr_e('Aumentar', 'apollo-adverts'); ?>"><i class="ri-add-line"></i></button>
                        <span class="apl-step-unit"><?php esc_html_e('dias', 'apollo-adverts'); ?></span>
                    </div>
                </div>

                <!-- Janela de disponibilidade -->
                <div class="apl-stay-block">
                    <div class="apl-stay-head">
                        <span class="apl-stay-label"><?php esc_html_e('Janela de disponibilidade', 'apollo-adverts'); ?></span>
                        <span class="apl-stay-hint"><?php esc_html_e('Opcional — deixe em branco se não tem data definida', 'apollo-adverts'); ?></span>
                    </div>
                    <div class="apl-row">
                        <div class="input-group">
                            <input type="date" id="rs_avail_start" name="avail_start" class="apollo-input" placeholder=" " data-apollo-date-picker="true">
                            <label for="rs_avail_start" class="apollo-label"><?php esc_html_e('Disponível a partir de', 'apollo-adverts'); ?></label>
                        </div>
                        <div class="input-group">
                            <input type="date" id="rs_avail_end" name="avail_end" class="apollo-input" placeholder=" " data-apollo-date-picker="true">
                            <label for="rs_avail_end" class="apollo-label"><?php esc_html_e('Disponível até', 'apollo-adverts'); ?></label>
                        </div>
                    </div>
                </div>

                <!-- Resumo em linguagem natural -->
                <p class="apl-stay-summary" id="rsStaySummary" aria-live="polite"></p>
            </fieldset>

            <div class="apl-check-group">
                <input type="checkbox" id="rs_neg" name="negotiable" value="1">
                <label for="rs_neg"><?php esc_html_e('Preço negociável', 'apollo-adverts'); ?></label>
            </div>

            <div class="apl-row">
                <div class="input-group">
                    <input type="tel" id="rs_phone" name="contact_phone" class="apollo-input" placeholder=" ">
                    <label for="rs_phone" class="apollo-label"><?php esc_html_e('Telefone', 'apollo-adverts'); ?></label>
                </div>
                <div class="input-group">
                    <input type="tel" id="rs_wa" name="contact_whatsapp" class="apollo-input" placeholder=" ">
                    <label for="rs_wa" class="apollo-label">WhatsApp</label>
                </div>
            </div>

            <div class="input-group">
                <textarea id="rs_desc" name="description" class="apollo-input" placeholder=" " rows="4"></textarea>
                <label for="rs_desc" class="apollo-label"><?php esc_html_e('Descrição detalhada', 'apollo-adverts'); ?></label>
            </div>

            <div class="apl-form-msg" id="aplRentSpaceMsg" style="display:none;"></div>

            <button type="submit" class="apl-btn-primary" id="aplRentSpaceSubmit">
                <i class="ri-send-plane-fill"></i>
                <span><?php esc_html_e('Publicar Anúncio', 'apollo-adverts'); ?></span>
            </button>
        </form>
    </div>
    <script>
        (function() {
            'use strict';
            var NONCE = '<?php echo esc_js($nonce); ?>';
            var REST = '<?php echo esc_js($rest_url); ?>';
            var form = document.getElementById('aplRentSpaceForm');
            var msg = document.getElementById('aplRentSpaceMsg');
            var btn = document.getElementById('aplRentSpaceSubmit');
            if (!form) return;

            /* ── Regras da estadia ────────────────────────────────────────
               Presets set a value in one tap; steppers handle everything in
               between. The summary reads the rules back as a sentence, so the
               host confirms intent instead of re-parsing four inputs. */
            var minEl = document.getElementById('rs_min_nights');
            var maxEl = document.getElementById('rs_max_days');
            var startEl = document.getElementById('rs_avail_start');
            var endEl = document.getElementById('rs_avail_end');
            var sumEl = document.getElementById('rsStaySummary');

            function num(el) { return Math.max(0, parseInt(el && el.value, 10) || 0); }

            function clamp(el) {
                var v = num(el);
                var mx = parseInt(el.getAttribute('max'), 10);
                if (!isNaN(mx) && v > mx) v = mx;
                el.value = v;
                return v;
            }

            function fmt(iso) {
                var p = String(iso || '').split('-');
                return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : '';
            }

            function syncPresets() {
                document.querySelectorAll('.apl-preset').forEach(function (b) {
                    var t = document.getElementById(b.dataset.target);
                    b.classList.toggle('is-on', !!t && String(num(t)) === b.dataset.value);
                });
            }

            function render() {
                var mn = clamp(minEl), md = clamp(maxEl);

                /* A maximum below the minimum is impossible to satisfy — say so
                   here rather than letting it through and confusing guests. */
                var conflict = md > 0 && mn > 0 && md < mn;
                maxEl.classList.toggle('has-error', conflict);

                /* An end date before the start date is the other impossible
                   pair; the native min= attribute stops most of it, this
                   catches typed input. */
                if (startEl.value) endEl.setAttribute('min', startEl.value);
                var badRange = startEl.value && endEl.value && endEl.value < startEl.value;
                endEl.classList.toggle('has-error', !!badRange);

                var parts = [];
                parts.push(mn > 0
                    ? ('Mínimo ' + mn + (mn === 1 ? ' noite' : ' noites'))
                    : 'Sem mínimo de noites');
                parts.push(md > 0
                    ? ('até ' + md + (md === 1 ? ' dia' : ' dias'))
                    : 'sem limite de dias');
                if (startEl.value || endEl.value) {
                    parts.push('de ' + (fmt(startEl.value) || '…') + ' a ' + (fmt(endEl.value) || '…'));
                } else {
                    parts.push('disponível sem data definida');
                }

                sumEl.textContent = parts.join(' · ');
                sumEl.classList.toggle('has-error', conflict || !!badRange);
                if (conflict) sumEl.textContent = 'O máximo de dias não pode ser menor que o mínimo de noites.';
                else if (badRange) sumEl.textContent = 'A data final não pode ser anterior à data inicial.';

                syncPresets();
            }

            document.querySelectorAll('.apl-preset').forEach(function (b) {
                b.addEventListener('click', function () {
                    var t = document.getElementById(b.dataset.target);
                    if (!t) return;
                    t.value = b.dataset.value;
                    render();
                });
            });

            document.querySelectorAll('.apl-step-btn').forEach(function (b) {
                b.addEventListener('click', function () {
                    var t = document.getElementById(b.dataset.target);
                    if (!t) return;
                    t.value = Math.max(0, num(t) + parseInt(b.dataset.step, 10));
                    render();
                });
            });

            [minEl, maxEl, startEl, endEl].forEach(function (el) {
                if (el) el.addEventListener('input', render);
            });
            render();

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                msg.style.display = 'none';
                btn.disabled = true;
                btn.querySelector('span').textContent = 'Publicando...';
                try {
                    var ti = form.querySelector('[name="title"]').value.trim();
                    if (!ti) throw new Error('Título obrigatório.');

                    /* Block the two impossible combinations at the door — the
                       REST layer would store them happily, and a guest would
                       be the one to discover the contradiction. */
                    if (num(maxEl) > 0 && num(minEl) > 0 && num(maxEl) < num(minEl)) {
                        throw new Error('O máximo de dias não pode ser menor que o mínimo de noites.');
                    }
                    if (startEl.value && endEl.value && endEl.value < startEl.value) {
                        throw new Error('A data final da disponibilidade não pode ser anterior à inicial.');
                    }
                    var d = {
                        title: ti,
                        classified_type: 'rent_space'
                    };
                    var stEl = form.querySelector('[name="space_type"]');
                    if (stEl) {
                        d.space_type = stEl.value;
                        d.condition = stEl.value;
                    }
                    var locEl = form.querySelector('[name="location"]');
                    if (locEl && locEl.value.trim()) d.location = locEl.value.trim();
                    var prEl = form.querySelector('[name="price"]');
                    if (prEl && prEl.value) d.price = parseFloat(prEl.value);
                    var curEl = form.querySelector('[name="currency"]');
                    if (curEl) d.currency = curEl.value;
                    /* Stay rules. avail_end used to be posted as `expires_at`,
                       which wrote the availability window's end into the
                       ADVERT'S expiry date — a room free until December made
                       the whole listing vanish in December. Both dates now go
                       to their own params. */
                    var asEl = form.querySelector('[name="avail_start"]');
                    if (asEl && asEl.value) d.avail_start = asEl.value;
                    var aeEl = form.querySelector('[name="avail_end"]');
                    if (aeEl && aeEl.value) d.avail_end = aeEl.value;
                    var mnEl = form.querySelector('[name="min_nights"]');
                    if (mnEl) d.min_nights = Math.max(0, parseInt(mnEl.value, 10) || 0);
                    var mdEl = form.querySelector('[name="max_days"]');
                    if (mdEl) d.max_days = Math.max(0, parseInt(mdEl.value, 10) || 0);
                    var negEl = form.querySelector('[name="negotiable"]');
                    d.negotiable = negEl && negEl.checked ? '1' : '';
                    var phEl = form.querySelector('[name="contact_phone"]');
                    if (phEl && phEl.value.trim()) d.contact_phone = phEl.value.trim();
                    var waEl = form.querySelector('[name="contact_whatsapp"]');
                    if (waEl && waEl.value.trim()) d.contact_whatsapp = waEl.value.trim();
                    var deEl = form.querySelector('[name="description"]');
                    if (deEl && deEl.value.trim()) d.description = deEl.value.trim();
                    var r = await fetch(REST, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': NONCE
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(d)
                    });
                    var res = await r.json();
                    if (!r.ok) throw new Error(res.message || 'Erro ao publicar.');
                    msg.className = 'apl-form-msg ok';
                    msg.textContent = '✓ Anúncio publicado!';
                    msg.style.display = '';
                    form.reset();
                    btn.disabled = false;
                    btn.querySelector('span').textContent = 'Publicar Anúncio';
                    if (res.permalink) setTimeout(function() {
                        window.location.href = res.permalink;
                    }, 1800);
                } catch (err) {
                    msg.className = 'apl-form-msg err';
                    msg.textContent = err.message;
                    msg.style.display = '';
                    btn.disabled = false;
                    btn.querySelector('span').textContent = 'Publicar Anúncio';
                }
            });
        })();
    </script>
<?php
    return ob_get_clean();
}
