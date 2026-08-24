<?php
/**
 * Create Event — Cupoms
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
            <div class="card sh01">
                <div class="tref-sec-lbl"><i class="ri-ticket-2-line"></i> Cupoms e Descontos</div>

                <div style="margin-top: 16px;">
                    <label class="toggle-wrap">
                        <input type="checkbox" class="toggle-input" id="couponToggle" onchange="toggleCoupons()">
                        <div class="toggle-track"></div>
                        <span class="toggle-label">Ativar cupom</span>
                    </label>
                </div>

                <!-- PHP: WP guarda um único _event_coupon_code; syncModel() une as tags por vírgula -->
                <input type="hidden" id="ev-coupon-code" name="coupon_code" value="">

                <div id="couponArea" class="coupon-section">
                    <div class="flex-row gap-2" style="align-items: flex-end;">
                        <div class="field" style="flex: 1; margin: 0;">
                            <label class="field-label">Novo Código de Cupom</label>
                            <input type="text" id="newCouponInput" class="apollo-input" placeholder="ex.: SUMMER20">
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="addCoupon()">Adicionar</button>
                    </div>

                    <div class="tags" id="couponTags" style="margin-top: 16px;"></div>
                </div>
            </div>