<?php
/**
 * Template Part: Scripts
 * JS for signing form submission + PDF.js / interact.js / sign-placement.js loader.
 *
 * @package Apollo\Sign
 * @var string $status
 * @var array  $sign_data
 * @var string $hash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cdn_url = defined( 'APOLLO_CDN_URL' ) ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/';
$pdf_url = $sign_data['pdf_url'] ?? '';
?>

<?php if ( $status === 'pending' ) : ?>
	<?php if ( ! empty( $pdf_url ) ) : ?>
		<!-- PDF.js (ESM module) -->
		<script type="module">
			import * as pdfjsLib from '<?php echo esc_url( $cdn_url . 'js/pdf.min.mjs' ); ?>';
			pdfjsLib.GlobalWorkerOptions.workerSrc = '<?php echo esc_url( $cdn_url . 'js/pdf.worker.min.mjs' ); ?>';
			window.pdfjsLib = pdfjsLib;
			window.dispatchEvent(new Event('pdfjsReady'));
		</script>

		<!-- interact.js (UMD) -->
		<script src="<?php echo esc_url( $cdn_url . 'js/interact.min.js' ); ?>"></script>

		<!-- Apollo Sign Placement -->
		<script src="<?php echo esc_url( plugins_url( 'assets/js/sign-placement.js', dirname( __DIR__ ) ) ); ?>" defer></script>

		<!-- Apollo Signature Pad (hand-drawn) -->
		<script src="<?php echo esc_url( plugins_url( 'assets/js/signature-pad.js', dirname( __DIR__ ) ) ); ?>" defer></script>

		<script>
			window.apolloSignConfig = {
				pdfUrl:       '<?php echo esc_js( $pdf_url ); ?>',
				signatureId:  <?php echo absint( $sign_data['id'] ); ?>,
				nonce:        '<?php echo esc_js( wp_create_nonce( 'apollo_sign_nonce' ) ); ?>',
				ajaxUrl:      '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
				restUrl:      '<?php echo esc_url( rest_url( 'apollo/v1/' ) ); ?>',
				restNonce:    '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>',
				defaults: {
					x: 0.65,
					y: 0.85,
					w: 0.28,
					h: 0.06
				}
			};
		</script>
	<?php endif; ?>

	<?php if ( empty( $pdf_url ) ) : ?>
		<!-- Signature Pad standalone (no PDF viewer) -->
		<script src="<?php echo esc_url( plugins_url( 'assets/js/signature-pad.js', dirname( __DIR__ ) ) ); ?>" defer></script>
	<?php endif; ?>

	<!-- Signing Form Handler -->
	<script>
		document.getElementById('sign-form').addEventListener('submit', function(e) {
			e.preventDefault();

			var btn = document.getElementById('sign-btn');
			var err = document.getElementById('sign-error');
			btn.disabled = true;
			btn.innerHTML = '<i class="ri-loader-4-line" style="animation:spin .8s linear infinite"></i> Assinando...';
			err.style.display = 'none';

			var formData = new FormData(this);
			formData.append('action', 'apollo_sign_sign_document');
			formData.append('nonce', '<?php echo esc_js( wp_create_nonce( 'apollo_sign_nonce' ) ); ?>');
			formData.append('signature_id', '<?php echo absint( $sign_data['id'] ); ?>');

			/* Include signature image if drawn */
			var sigImage = document.getElementById('sign-signature-image');
			if (sigImage && sigImage.value) {
				formData.append('signature_image', sigImage.value);
			}

			fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					method: 'POST',
					body: formData,
					credentials: 'same-origin'
				})
				.then(function(r) {
					return r.json();
				})
				.then(function(data) {
					if (data.success) {
						location.reload();
					} else {
						err.textContent = data.data?.message || 'Erro na assinatura.';
						err.style.display = 'block';
						btn.disabled = false;
						btn.innerHTML = '<i class="ri-shield-keyhole-fill"></i> Assinar Documento';
					}
				})
				.catch(function() {
					err.textContent = 'Erro de conexão. Tente novamente.';
					err.style.display = 'block';
					btn.disabled = false;
					btn.innerHTML = '<i class="ri-shield-keyhole-fill"></i> Assinar Documento';
				});
		});
	</script>
	<style>
		@keyframes spin {
			to { transform: rotate(360deg); }
		}
	</style>
<?php endif; ?>
