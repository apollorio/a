<?php
/**
 * Create Event — Receipt + weather + save
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
            <!-- ═══ FECHAMENTO: RESUMO DO REGISTRO (65%) + CLIMA (35%) + GRAVAR DADOS ═══ -->
            <div class="grid-rcpt">
                <div class="card sh01">
                    <div class="tref-sec-lbl"><i class="ri-file-list-3-line"></i> Resumo deste evento</div>
                    <div id="receipt" style="margin-top: 8px;"></div>
                </div>
                <div class="flex-col" style="gap: 16px;">
                    <div class="card sh01" style="margin-bottom: 0;">
                        <div class="flex-between" style="margin-bottom: 4px;">
                            <div class="tref-sec-lbl" style="margin: 0;"><i class="ri-sun-cloudy-line"></i> Previsão do Tempo</div>
                            <span class="tag">UTC−3</span>
                        </div>
                        <div id="weather-preview" style="margin-top: 10px;">
                            <div class="wx-note"><i class="ri-loader-4-line"></i> Escolha uma data para carregar a previsão…</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" id="recordBtn" onclick="saveEvent('recordBtn')" style="width: 100%; justify-content: center; padding: 14px 20px;">
                        <i class="ri-save-3-line"></i> Salvar informações
                    </button>
                </div>
            </div>