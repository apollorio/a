<?php

/**
 * FrontendForm — Renders inline Report form for frontend
 *
 * Hooks into `apollo/mod/render_report_form`
 *
 * Registry compliance (apollo-mod):
 *   REST: POST /mod/report (auth: logged_in)
 *   Fields: object_type, object_id, reason, details
 *   Tables: apollo_mod_reports (reporter_id, object_type, object_id, reason, details, status, created_at)
 *
 * @package Apollo\Mod
 */

namespace Apollo\Mod;

if (! \defined('ABSPATH')) {
    exit;
}

final class FrontendForm
{


    public function __construct()
    {
        add_action('apollo/mod/render_report_form', array($this, 'render'), 10, 1);
    }

    /**
     * Render the inline report form inside panel-forms.php
     *
     * @param int $user_id Current user ID
     */
    public function render(int $user_id): void
    {
        if (! is_user_logged_in()) {
            echo '<p>' . esc_html__('Faça login para reportar conteúdo.', 'apollo-mod') . '</p>';
            return;
        }

        $rest_url = esc_url_raw(rest_url('apollo/v1/mod/report'));
        $nonce    = wp_create_nonce('wp_rest');
?>
        <div class="apl-form apl-form--report" id="apolloReportForm">
            <script src="https://cdn.apollo.rio.br/v1.0.0/js/forms.js" defer></script>
            <div class="apl-form__header">
                <i class="ri-alarm-warning-fill"></i>
                <h3><?php esc_html_e('Reportar Conteúdo', 'apollo-mod'); ?></h3>
            </div>

            <form id="apl-report-create" autocomplete="off" novalidate>

                <!-- ═══ CONTEÚDO ALVO ═══ -->
                <fieldset class="apl-form__fieldset">
                    <legend><i class="ri-focus-3-line"></i> <?php esc_html_e('Conteúdo Alvo', 'apollo-mod'); ?></legend>

                    <div class="apl-form__row">
                        <div class="apl-form__field apl-form__field--half">
                            <label for="rf_object_type"><?php esc_html_e('Tipo de Conteúdo', 'apollo-mod'); ?> <span class="req">*</span></label>
                            <select id="rf_object_type" name="object_type" required>
                                <option value=""><?php esc_html_e('Selecione...', 'apollo-mod'); ?></option>
                                <option value="post"><?php esc_html_e('Publicação', 'apollo-mod'); ?></option>
                                <option value="event"><?php esc_html_e('Evento', 'apollo-mod'); ?></option>
                                <option value="classified"><?php esc_html_e('Anúncio', 'apollo-mod'); ?></option>
                                <option value="user"><?php esc_html_e('Usuário', 'apollo-mod'); ?></option>
                                <option value="depoimento"><?php esc_html_e('Depoimento', 'apollo-mod'); ?></option>
                                <option value="group"><?php esc_html_e('Grupo', 'apollo-mod'); ?></option>
                                <option value="dj"><?php esc_html_e('DJ', 'apollo-mod'); ?></option>
                                <option value="loc"><?php esc_html_e('Loc', 'apollo-mod'); ?></option>
                            </select>
                        </div>
                        <div class="apl-form__field apl-form__field--half">
                            <label for="rf_object_id"><?php esc_html_e('ID do Conteúdo', 'apollo-mod'); ?> <span class="req">*</span></label>
                            <input type="number" id="rf_object_id" name="object_id" required min="1" placeholder="<?php esc_attr_e('Ex: 1234', 'apollo-mod'); ?>" class="apollo-input">
                            <span class="apl-form__hint"><?php esc_html_e('Número identificador do conteúdo.', 'apollo-mod'); ?></span>
                        </div>
                    </div>
                </fieldset>

                <!-- ═══ MOTIVO ═══ -->
                <fieldset class="apl-form__fieldset">
                    <legend><i class="ri-error-warning-line"></i> <?php esc_html_e('Motivo do Report', 'apollo-mod'); ?></legend>

                    <div class="apl-form__field">
                        <label for="rf_reason"><?php esc_html_e('Razão', 'apollo-mod'); ?> <span class="req">*</span></label>
                        <select id="rf_reason" name="reason" required>
                            <option value=""><?php esc_html_e('Selecione...', 'apollo-mod'); ?></option>
                            <option value="inappropriate"><?php esc_html_e('Conteúdo Inapropriado', 'apollo-mod'); ?></option>
                            <option value="spam"><?php esc_html_e('Spam / Propaganda', 'apollo-mod'); ?></option>
                            <option value="fake"><?php esc_html_e('Informação Falsa / Fake', 'apollo-mod'); ?></option>
                            <option value="harassment"><?php esc_html_e('Assédio / Bullying', 'apollo-mod'); ?></option>
                            <option value="violence"><?php esc_html_e('Violência / Ameaça', 'apollo-mod'); ?></option>
                            <option value="copyright"><?php esc_html_e('Violação de Direitos Autorais', 'apollo-mod'); ?></option>
                            <option value="other"><?php esc_html_e('Outro', 'apollo-mod'); ?></option>
                        </select>
                    </div>

                    <div class="apl-form__field">
                        <label for="rf_details"><?php esc_html_e('Detalhes', 'apollo-mod'); ?></label>
                        <textarea id="rf_details" name="details" rows="4" placeholder="<?php esc_attr_e('Descreva o problema com mais detalhes...', 'apollo-mod'); ?>" class="apollo-textarea"></textarea>
                        <span class="apl-form__hint"><?php esc_html_e('Opcional. Quanto mais informação, melhor para a análise.', 'apollo-mod'); ?></span>
                    </div>
                </fieldset>

                <!-- ═══ SUBMIT ═══ -->
                <div class="apl-form__error" id="rfError" style="display:none;"></div>

                <button type="submit" class="apl-form__submit" id="rfSubmit">
                    <i class="ri-alarm-warning-fill"></i>
                    <span><?php esc_html_e('Enviar Report', 'apollo-mod'); ?></span>
                </button>
            </form>
        </div>

        <style>
            .apl-form--report {
                max-width: 640px;
            }

            .apl-form--report .apl-form__header {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 16px;
            }

            .apl-form--report .apl-form__header i {
                font-size: 22px;
                color: #ef4444;
            }

            .apl-form--report .apl-form__header h3 {
                margin: 0;
                font-size: 18px;
                font-weight: 700;
                color: var(--txt, #fff);
            }

            .apl-form--report .apl-form__fieldset {
                border: none;
                border-bottom: 1px solid var(--border, rgba(var(--rgb-d), 0.12));
                border-radius: 0;
                padding: 16px 0;
                margin: 0 0 16px;
            }

            .apl-form--report .apl-form__fieldset legend {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 14px;
                font-weight: 600;
                color: var(--txt-muted, #aaa);
                padding: 0 8px;
            }

            .apl-form--report .apl-form__fieldset legend i {
                font-size: 16px;
            }

            .apl-form--report .apl-form__field {
                margin-bottom: 12px;
            }

            .apl-form--report .apl-form__field label {
                display: block;
                font-size: 13px;
                font-weight: 600;
                color: var(--txt, #fff);
                margin-bottom: 4px;
            }

            .apl-form--report .apl-form__field .req {
                color: #ef4444;
            }

            .apl-form--report .apl-form__field input,
            .apl-form--report .apl-form__field select,
            .apl-form--report .apl-form__field textarea {
                width: 100%;
                padding: 10px 0;
                background: transparent;
                border: none;
                border-bottom: 1px solid var(--border, rgba(var(--rgb-d), 0.15));
                border-radius: 0;
                color: var(--txt, #1a1a1a);
                font-size: 14px;
                outline: none;
                transition: border-color .2s;
            }

            .apl-form--report .apl-form__field input:focus,
            .apl-form--report .apl-form__field select:focus,
            .apl-form--report .apl-form__field textarea:focus {
                border-bottom-color: var(--primary, FF9820);
            }

            .apl-form--report .apl-form__hint {
                display: block;
                font-size: 11px;
                color: var(--txt-muted, #888);
                margin-top: 4px;
            }

            .apl-form--report .apl-form__row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            @media (max-width: 480px) {
                .apl-form--report .apl-form__row {
                    grid-template-columns: 1fr;
                }
            }

            .apl-form--report .apl-form__error {
                background: rgba(239, 68, 68, .12);
                color: #ef4444;
                padding: 10px 14px;
                border-radius: 8px;
                font-size: 13px;
                margin-bottom: 12px;
            }

            .apl-form--report .apl-form__submit {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                width: 100%;
                padding: 12px;
                border: none;
                border-radius: 10px;
                background: #ef4444;
                color: #fff;
                font-size: 15px;
                font-weight: 700;
                cursor: pointer;
                transition: opacity .2s;
            }

            .apl-form--report .apl-form__submit:hover {
                opacity: .9;
            }

            .apl-form--report .apl-form__submit:disabled {
                opacity: .5;
                cursor: not-allowed;
            }

            .apl-form--report .apl-form__submit i {
                font-size: 18px;
            }
        </style>

        <script>
            (function() {
                'use strict';
                var REST = '<?php echo esc_js($rest_url); ?>';
                var NONCE = '<?php echo esc_js($nonce); ?>';
                var form = document.getElementById('apl-report-create');
                var errEl = document.getElementById('rfError');
                var btn = document.getElementById('rfSubmit');

                if (!form) return;

                /* Pre-fill from external trigger: apollo:form:open with detail.objectType/objectId */
                document.addEventListener('apollo:form:open', function(e) {
                    if (!e.detail) return;
                    if (e.detail.objectType) {
                        var sel = form.querySelector('[name="object_type"]');
                        if (sel) sel.value = e.detail.objectType;
                    }
                    if (e.detail.objectId) {
                        var inp = form.querySelector('[name="object_id"]');
                        if (inp) inp.value = e.detail.objectId;
                    }
                });

                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    errEl.style.display = 'none';
                    btn.disabled = true;
                    btn.querySelector('span').textContent = 'Enviando...';

                    try {
                        var data = {
                            object_type: form.querySelector('[name="object_type"]').value.\trim(),
                            object_id: parseInt(form.querySelector('[name="object_id"]').value, 10) || 0,
                            reason: form.querySelector('[name="reason"]').value.\trim(),
                            details: form.querySelector('[name="details"]').value.\trim()
                        };

                        if (!data.object_type) throw new Error('Selecione o tipo de conteúdo.');
                        if (!data.object_id || data.object_id < 1) throw new Error('Informe o ID do conteúdo.');
                        if (!data.reason) throw new Error('Selecione a razão do report.');

                        var res = await fetch(REST, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': NONCE
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify(data)
                        });

                        var result = await res.json();

                        if (res.ok && result.report_id) {
                            btn.querySelector('span').textContent = 'Report Enviado!';
                            btn.style.background = '#22c55e';
                            form.reset();
                            setTimeout(function() {
                                btn.style.background = '';
                                btn.querySelector('span').textContent = 'Enviar Report';
                                btn.disabled = false;
                            }, 3000);
                        } else {
                            throw new Error(result.error || result.message || 'Erro ao enviar report.');
                        }
                    } catch (err) {
                        errEl.textContent = err.message;
                        errEl.style.display = '';
                        btn.disabled = false;
                        btn.querySelector('span').textContent = 'Enviar Report';
                    }
                });
            })();
        </script>
<?php
    }
}
