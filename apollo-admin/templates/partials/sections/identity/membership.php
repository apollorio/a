<?php
/**
 * Identity Section — Membership (Billing, Protection, Pages, Taxes)
 *
 * Page ID: page-id-membership
 * Feed-tabs: Billing | Content Protection | Pages | Taxes
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="page" id="page-id-membership">

	<div class="feed-tabs">
		<button class="feed-tab active" data-sub="mem-billing" title="<?php esc_attr_e( 'Plans & Billing', 'apollo-admin' ); ?>"><i class="ri-bank-card-line"></i></button>
		<button class="feed-tab" data-sub="mem-protect" title="<?php esc_attr_e( 'Content Protection', 'apollo-admin' ); ?>"><i class="ri-lock-line"></i></button>
		<button class="feed-tab" data-sub="mem-pages" title="<?php esc_attr_e( 'Pages', 'apollo-admin' ); ?>"><i class="ri-pages-line"></i></button>
		<button class="feed-tab" data-sub="mem-taxes" title="<?php esc_attr_e( 'Taxes', 'apollo-admin' ); ?>"><i class="ri-percent-line"></i></button>
	</div>

	<!-- Plans & Billing -->
	<div class="sub-content visible" id="sub-mem-billing">
		<div class="panel">
			<div class="panel-header"><i class="ri-bank-card-line"></i> <?php esc_html_e( 'Plans & Billing', 'apollo-admin' ); ?> 🔜 <span class="badge">apollo-membership</span></div>
			<div class="panel-body">

				<div class="toggle-row">
					<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[mem_paid_plans]" value="1" <?php checked( $apollo['mem_paid_plans'] ?? false ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Paid Plans', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable paid membership tiers with recurring billing', 'apollo-admin' ); ?></span></div>
				</div>

				<div class="form-grid" style="margin-top:12px">
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Default Currency', 'apollo-admin' ); ?></label>
						<select class="select" name="apollo[mem_currency]">
							<option value="BRL" <?php selected( $apollo['mem_currency'] ?? 'BRL', 'BRL' ); ?>>BRL (R$)</option>
							<option value="USD" <?php selected( $apollo['mem_currency'] ?? 'BRL', 'USD' ); ?>>USD ($)</option>
							<option value="EUR" <?php selected( $apollo['mem_currency'] ?? 'BRL', 'EUR' ); ?>>EUR (€)</option>
						</select>
					</div>
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Currency Symbol Position', 'apollo-admin' ); ?></label>
						<select class="select" name="apollo[mem_currency_pos]">
							<option value="before" <?php selected( $apollo['mem_currency_pos'] ?? 'before', 'before' ); ?>><?php esc_html_e( 'Before amount', 'apollo-admin' ); ?></option>
							<option value="after" <?php selected( $apollo['mem_currency_pos'] ?? 'before', 'after' ); ?>><?php esc_html_e( 'After amount', 'apollo-admin' ); ?></option>
						</select>
					</div>
				</div>

				<div class="section-title"><?php esc_html_e( 'Stripe Gateway', 'apollo-admin' ); ?></div>
				<div class="toggle-row">
					<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[mem_stripe_enabled]" value="1" <?php checked( $apollo['mem_stripe_enabled'] ?? false ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Stripe', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Accept credit card payments via Stripe', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row">
					<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[mem_stripe_test]" value="1" <?php checked( $apollo['mem_stripe_test'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Stripe Test Mode', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Use Stripe sandbox for testing', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="form-grid" style="margin-top:8px">
					<div class="field"><label class="field-label"><?php esc_html_e( 'Stripe Publishable Key', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[mem_stripe_pk]" value="<?php echo esc_attr( $apollo['mem_stripe_pk'] ?? '' ); ?>" placeholder="pk_test_..."></div>
					<div class="field"><label class="field-label"><?php esc_html_e( 'Stripe Secret Key', 'apollo-admin' ); ?></label><input type="password" class="apollo-input input" name="apollo[mem_stripe_sk]" value="<?php echo esc_attr( $apollo['mem_stripe_sk'] ?? '' ); ?>" placeholder="sk_test_..."></div>
				</div>

				<div class="section-title"><?php esc_html_e( 'PayPal Gateway', 'apollo-admin' ); ?></div>
				<div class="toggle-row">
					<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[mem_paypal_enabled]" value="1" <?php checked( $apollo['mem_paypal_enabled'] ?? false ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable PayPal', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Accept payments via PayPal', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="form-grid" style="margin-top:8px">
					<div class="field"><label class="field-label"><?php esc_html_e( 'PayPal Client ID', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[mem_paypal_client_id]" value="<?php echo esc_attr( $apollo['mem_paypal_client_id'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Enter Client ID', 'apollo-admin' ); ?>"></div>
					<div class="field"><label class="field-label"><?php esc_html_e( 'PayPal Client Secret', 'apollo-admin' ); ?></label><input type="password" class="apollo-input input" name="apollo[mem_paypal_client_secret]" value="<?php echo esc_attr( $apollo['mem_paypal_client_secret'] ?? '' ); ?>" placeholder="••••••••"></div>
				</div>

				<div class="section-title"><?php esc_html_e( 'Payment Security', 'apollo-admin' ); ?></div>
				<div class="toggle-row">
					<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[mem_manual_approval]" value="1" <?php checked( $apollo['mem_manual_approval'] ?? false ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Admin Manual Approval', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Force offline payments to "Pending" until admin verifies', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row">
					<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[mem_card_protection]" value="1" <?php checked( $apollo['mem_card_protection'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Card Testing Protection', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Block IP after 5 failed payment attempts in 120 min', 'apollo-admin' ); ?></span></div>
				</div>

			</div>
		</div>
	</div>

	<!-- Content Protection -->
	<div class="sub-content" id="sub-mem-protect">
		<div class="panel">
			<div class="panel-header"><i class="ri-lock-line"></i> <?php esc_html_e( 'Content Protection', 'apollo-admin' ); ?> 🔜</div>
			<div class="panel-body">

				<div class="form-grid">
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Non-Member View', 'apollo-admin' ); ?></label>
						<select class="select" name="apollo[mem_non_member_view]">
							<option value="more_tag" <?php selected( $apollo['mem_non_member_view'] ?? 'more_tag', 'more_tag' ); ?>><?php esc_html_e( 'More Tag excerpt', 'apollo-admin' ); ?></option>
							<option value="excerpt" <?php selected( $apollo['mem_non_member_view'] ?? 'more_tag', 'excerpt' ); ?>><?php esc_html_e( 'Post Excerpt', 'apollo-admin' ); ?></option>
							<option value="custom" <?php selected( $apollo['mem_non_member_view'] ?? 'more_tag', 'custom' ); ?>><?php esc_html_e( 'Custom character count', 'apollo-admin' ); ?></option>
						</select>
						<span class="field-hint"><?php esc_html_e( 'What non-members see on protected content', 'apollo-admin' ); ?></span>
					</div>
				</div>

				<div class="toggle-row">
					<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[mem_filter_search]" value="1" <?php checked( $apollo['mem_filter_search'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Filter Search & Archives', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Hide protected content from search results & archives', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row">
					<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[mem_authorize_bots]" value="1" <?php checked( $apollo['mem_authorize_bots'] ?? false ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Authorize Search Engines', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Let Googlebot bypass paywall for indexing (Schema paywallRequired)', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row">
					<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[mem_metered_access]" value="1" <?php checked( $apollo['mem_metered_access'] ?? false ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Metered Access', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Allow N free views before paywall redirect', 'apollo-admin' ); ?></span></div>
				</div>

				<div class="form-grid" style="margin-top:8px">
					<div class="field"><label class="field-label"><?php esc_html_e( 'Free Views per Session', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[mem_free_views]" value="<?php echo esc_attr( $apollo['mem_free_views'] ?? 3 ); ?>"></div>
				</div>

			</div>
		</div>
	</div>

	<!-- Membership Pages -->
	<div class="sub-content" id="sub-mem-pages">
		<div class="panel">
			<div class="panel-header"><i class="ri-pages-line"></i> <?php esc_html_e( 'Membership Pages', 'apollo-admin' ); ?> 🔜</div>
			<div class="panel-body">
				<div class="form-grid">
					<div class="field"><label class="field-label"><?php esc_html_e( 'Pricing Page', 'apollo-admin' ); ?></label><select class="select" name="apollo[mem_page_pricing]"><option value=""><?php esc_html_e( '— Select page —', 'apollo-admin' ); ?></option><option value="/planos">/planos</option><option value="/pricing">/pricing</option></select></div>
					<div class="field"><label class="field-label"><?php esc_html_e( 'Checkout Page', 'apollo-admin' ); ?></label><select class="select" name="apollo[mem_page_checkout]"><option value=""><?php esc_html_e( '— Select page —', 'apollo-admin' ); ?></option><option value="/checkout">/checkout</option></select></div>
					<div class="field"><label class="field-label"><?php esc_html_e( 'Thank You Page', 'apollo-admin' ); ?></label><select class="select" name="apollo[mem_page_thankyou]"><option value=""><?php esc_html_e( '— Select page —', 'apollo-admin' ); ?></option><option value="/obrigado">/obrigado</option></select></div>
					<div class="field"><label class="field-label"><?php esc_html_e( 'Account Page', 'apollo-admin' ); ?></label><select class="select" name="apollo[mem_page_account]"><option value=""><?php esc_html_e( '— Select page —', 'apollo-admin' ); ?></option><option value="/minha-conta">/minha-conta</option></select></div>
					<div class="field"><label class="field-label"><?php esc_html_e( 'Cancellation Page', 'apollo-admin' ); ?></label><select class="select" name="apollo[mem_page_cancel]"><option value=""><?php esc_html_e( '— Select page —', 'apollo-admin' ); ?></option><option value="/cancelamento">/cancelamento</option></select></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Tax Settings -->
	<div class="sub-content" id="sub-mem-taxes">
		<div class="panel">
			<div class="panel-header"><i class="ri-percent-line"></i> <?php esc_html_e( 'Tax Settings', 'apollo-admin' ); ?> 🔜</div>
			<div class="panel-body">
				<div class="toggle-row">
					<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[mem_enable_taxes]" value="1" <?php checked( $apollo['mem_enable_taxes'] ?? false ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Taxes', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable tax calculation on membership payments', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="form-grid" style="margin-top:12px">
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Tax Calculation Basis', 'apollo-admin' ); ?></label>
						<select class="select" name="apollo[mem_tax_basis]">
							<option value="ip" <?php selected( $apollo['mem_tax_basis'] ?? 'ip', 'ip' ); ?>><?php esc_html_e( 'Customer IP', 'apollo-admin' ); ?></option>
							<option value="address" <?php selected( $apollo['mem_tax_basis'] ?? 'ip', 'address' ); ?>><?php esc_html_e( 'Customer Address', 'apollo-admin' ); ?></option>
							<option value="merchant" <?php selected( $apollo['mem_tax_basis'] ?? 'ip', 'merchant' ); ?>><?php esc_html_e( 'Merchant Address', 'apollo-admin' ); ?></option>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>