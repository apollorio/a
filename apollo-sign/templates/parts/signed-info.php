<?php
/**
 * Template Part: Signed Info
 * Certificate details shown when document is already signed.
 *
 * @package Apollo\Sign
 * @var string $status
 * @var array  $sign_data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $status === 'signed' ) : ?>
	<div class="sign-status signed">
		<i class="ri-checkbox-circle-fill"></i> Documento Assinado
	</div>

	<?php
	/* Show visual stamp if available */
	$stamp_file = $sign_data['stamp_path'] ?? '';
	if ( $stamp_file && file_exists( $stamp_file ) ) :
		$stamp_data = base64_encode( file_get_contents( $stamp_file ) );
		?>
		<div class="sign-stamp-preview" style="margin-bottom:16px;text-align:center;">
			<img src="data:image/png;base64,<?php echo $stamp_data; ?>"
				alt="Selo visual de assinatura"
				style="max-width:100%;border-radius:8px;border:1px solid var(--brd);background:#0e0e10;padding:8px;">
		</div>
	<?php endif; ?>

	<div class="sign-cert-info">
		<h4><i class="ri-shield-check-fill"></i> Certificado Digital</h4>
		<div class="sign-cert-row">
			<span class="sign-cert-label">Titular</span>
			<span class="sign-cert-value"><?php echo esc_html( $sign_data['certificate_cn'] ); ?></span>
		</div>
		<div class="sign-cert-row">
			<span class="sign-cert-label">Emissor</span>
			<span class="sign-cert-value"><?php echo esc_html( $sign_data['certificate_issuer'] ); ?></span>
		</div>
		<?php
		if ( ! empty( $sign_data['signer_cpf'] ) ) :
			$cpf        = $sign_data['signer_cpf'];
			$masked_cpf = strlen( $cpf ) >= 11 ? substr( $cpf, 0, 3 ) . '.***.***-' . substr( $cpf, 9, 2 ) : $cpf;
			?>
		<div class="sign-cert-row">
			<span class="sign-cert-label">CPF</span>
			<span class="sign-cert-value"><?php echo esc_html( $masked_cpf ); ?></span>
		</div>
		<?php endif; ?>
		<div class="sign-cert-row">
			<span class="sign-cert-label">Assinado em</span>
			<span class="sign-cert-value"><?php echo esc_html( wp_date( 'd/m/Y H:i:s', strtotime( $sign_data['signed_at'] ) ) ); ?></span>
		</div>
		<div class="sign-cert-row">
			<span class="sign-cert-label">Algoritmo</span>
			<span class="sign-cert-value"><?php echo esc_html( $sign_data['algorithm'] ); ?></span>
		</div>
		<div class="sign-cert-row">
			<span class="sign-cert-label">Validade</span>
			<span class="sign-cert-value">
			<?php
				echo esc_html( wp_date( 'd/m/Y', strtotime( $sign_data['certificate_valid_from'] ) ) );
				echo ' — ';
				echo esc_html( wp_date( 'd/m/Y', strtotime( $sign_data['certificate_valid_to'] ) ) );
			?>
			</span>
		</div>
	</div>
<?php elseif ( $status !== 'pending' ) : ?>
	<div class="sign-status" style="background:var(--error-soft);color:var(--error)">
		<i class="ri-error-warning-fill"></i> Status: <?php echo esc_html( ucfirst( $status ) ); ?>
	</div>
<?php endif; ?>
