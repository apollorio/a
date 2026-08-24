<?php
/**
 * Create Event — Basic info + datetime widget
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
            <div class="card sh01">
                <div class="tref-sec-lbl"><i class="ri-information-line"></i> Informações Básicas</div>

                <div class="field" style="margin-top: 16px;">
                    <label class="field-label" for="ev-title">Nome do Evento</label>
                    <div class="ev-title-row">
                        <input type="text" id="ev-title" name="title" class="apollo-input" placeholder="ex.: Summer Vibes 2026" required maxlength="200">
                        <input type="color" id="ev-bg-color-picker" class="ev-color-pill" value="#0a0a0a" title="<?php esc_attr_e( 'Cor de fundo (fallback)', 'apollo-events' ); ?>" aria-label="<?php esc_attr_e( 'Cor de fundo do evento', 'apollo-events' ); ?>"
                               oninput="$id('ev-bg-color').value = this.value;">
                        <input type="hidden" id="ev-bg-color" name="bg_color" value="#0a0a0a">
                    </div>
                </div>

                <!-- ═══ WIDGET UNIFICADO DE DATA E HORÁRIO (unite-01 + unite-02, tema Apollo) ═══ -->
                <div class="field">
                    <label class="field-label">Data e Horário <span class="dtp-tz txt-mono">Rio de Janeiro · UTC−3</span></label>
                    <div class="dtp" id="dtp">
                        <div class="dtp-summary">
                            <button type="button" class="dtp-chip" id="dtpChipStart" onclick="dtpOpen('start')" aria-label="Editar data e horário de início">
                                <span class="dtp-chip-lbl">Começa</span>
                                <span class="dtp-chip-date" id="dtpStartDate">—</span>
                                <span class="dtp-chip-time" id="dtpStartTime">23:00</span>
                            </button>
                            <div class="dtp-arrow">
                                <i class="ri-arrow-right-line"></i>
                                <span class="dtp-dur" id="dtpDur">8H</span>
                            </div>
                            <button type="button" class="dtp-chip" id="dtpChipEnd" onclick="dtpOpen('end')" aria-label="Editar data e horário de término">
                                <span class="dtp-chip-lbl">Termina</span>
                                <span class="dtp-chip-date" id="dtpEndDate">—</span>
                                <span class="dtp-chip-time" id="dtpEndTime">07:00</span>
                            </button>
                        </div>

                        <div class="dtp-panel" id="dtpPanel">
                            <div class="dtp-cal">
                                <div class="dtp-cal-head">
                                    <button type="button" class="dtp-nav" onclick="dtpNav(-1)" aria-label="Mês anterior"><i class="ri-arrow-left-s-line"></i></button>
                                    <div class="dtp-cal-title" id="dtpCalTitle">—</div>
                                    <button type="button" class="dtp-nav" onclick="dtpNav(1)" aria-label="Próximo mês"><i class="ri-arrow-right-s-line"></i></button>
                                </div>
                                <div class="dtp-grid" id="dtpGrid"></div>
                            </div>
                            <div class="dtp-time">
                                <div>
                                    <div class="field-label" id="dtpTimeLbl">Horário de início</div>
                                    <div class="dtp-quick" id="dtpQuick"></div>
                                </div>
                                <div class="dtp-stepper">
                                    <button type="button" class="dtp-step" onclick="dtpStep(-30)" aria-label="Menos 30 minutos"><i class="ri-subtract-line"></i></button>
                                    <input type="time" class="apollo-input dtp-time-input" id="dtpTimeInput" value="23:00" onchange="dtpTimeFromInput(this.value)">
                                    <button type="button" class="dtp-step" onclick="dtpStep(30)" aria-label="Mais 30 minutos"><i class="ri-add-line"></i></button>
                                </div>
                                <div class="dtp-hint" id="dtpHint">Padrão Rio: 23:00 → 07:00 do dia seguinte. O término preenche sozinho, toque em Termina para ajustar.</div>
                                <button type="button" class="btn btn-primary btn-sm dtp-done" onclick="dtpClose()">Concluir</button>
                            </div>
                        </div>

                        <input type="hidden" id="start_date" name="start_date">
                        <input type="hidden" id="start_time" name="start_time">
                        <input type="hidden" id="end_date" name="end_date">
                        <input type="hidden" id="end_time" name="end_time">
                    </div>
                </div>
                <!-- ═══ /WIDGET DE DATA E HORÁRIO ═══ -->

                <?php require __DIR__ . '/about-editor.php'; ?>
            </div>