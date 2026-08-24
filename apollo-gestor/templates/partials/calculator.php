<?php
/**
 * Partial: Cachê calculator overlay
 *
 * Formula: (horas × valor/hora + extras) - desconto%
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="calc-overlay" id="calcOverlay">
    <div class="calc-box">
        <div class="calc-header">
            <i class="ri-calculator-line"></i>
            <span>Calculadora de Cachê</span>
            <button class="btn-icon calc-close" id="calcClose" type="button"><i class="ri-close-fill"></i></button>
        </div>

        <div class="calc-body">
            <div class="calc-field">
                <label for="calcHoras">Horas</label>
                <input type="number" class="apollo-input" id="calcHoras" value="6" min="1" step="1">
            </div>
            <div class="calc-field">
                <label for="calcValorHora">Valor / hora (R$)</label>
                <input type="number" class="apollo-input" id="calcValorHora" value="50" min="0" step="10">
            </div>
            <div class="calc-field">
                <label for="calcExtras">Extras (R$)</label>
                <input type="number" class="apollo-input" id="calcExtras" value="0" min="0" step="10">
            </div>
            <div class="calc-field">
                <label for="calcDesconto">Desconto (%)</label>
                <input type="number" class="apollo-input" id="calcDesconto" value="0" min="0" max="100" step="5">
            </div>
        </div>

        <div class="calc-result">
            <span class="calc-result-label">Total</span>
            <span class="calc-result-value" id="calcResult">R$ 300,00</span>
        </div>

        <div class="calc-actions">
            <button class="btn btn-ghost" id="calcClear" type="button">Limpar</button>
            <button class="btn btn-primary" id="calcAddBudget" type="button">
                <i class="ri-add-line"></i> Adicionar ao Budget
            </button>
        </div>
    </div>
</div>