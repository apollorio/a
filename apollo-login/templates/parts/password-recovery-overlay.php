<?php

/**
 * Password Recovery Overlay
 * Fullscreen slide-in panel from right (100vw → 0)
 *
 * @package Apollo\Login
 * @since 6.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<!-- PASSWORD RECOVERY OVERLAY (Fullscreen Slide-in from Right) -->
<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
<!-- apollo-email: AJAX apollo_forgot_password → PasswordReset → apollo/login/password_reset_requested → apollo_send_email(password-reset) -->
<?php endif; ?>
<div id="password-recovery-overlay" class="password-recovery-overlay" style="display: none;">
    <div class="overlay-backdrop" data-close-overlay="true"></div>

    <div class="overlay-panel">
        <!-- Close Button -->
        <button type="button" class="overlay-close" aria-label="<?php esc_attr_e('Fechar', 'apollo-login'); ?>" data-close-overlay="true">
            <i class="ri-close-line"></i>
        </button>

        <!-- Panel Header -->
        <header class="overlay-header">
            <div class="logo-mark">
                <svg width="48" height="48" viewBox="0 0 150 150" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M75 135C59.087 135 45 130.217 45 124.5C45 118.783 59.087 114 75 114C90.913 114 105 118.783 105 124.5C105 130.217 90.913 135 75 135Z" fill="url(#paint0_linear_logo)" />
                    <circle cx="75" cy="45" r="30" fill="url(#paint1_linear_logo)" />
                    <path d="M75 75L105 120H45L75 75Z" fill="url(#paint2_linear_logo)" />
                    <defs>
                        <linearGradient id="paint0_linear_logo" x1="45" y1="124.5" x2="105" y2="124.5" gradientUnits="userSpaceOnUse">
                            <stop stop-color="FF9820" />
                            <stop offset="1" stop-color="#FF8640" />
                        </linearGradient>
                        <linearGradient id="paint1_linear_logo" x1="45" y1="45" x2="105" y2="45" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#651FFF" />
                            <stop offset="1" stop-color="#9C4DFF" />
                        </linearGradient>
                        <linearGradient id="paint2_linear_logo" x1="75" y1="75" x2="75" y2="120" gradientUnits="userSpaceOnUse">
                            <stop stop-color="FF9820" />
                            <stop offset="1" stop-color="#651FFF" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <h1><?php esc_html_e('Recuperar Senha', 'apollo-login'); ?></h1>
            <p class="subtitle"><?php esc_html_e('Digite seu e-mail para receber o link de recuperação', 'apollo-login'); ?></p>
        </header>

        <!-- Form Content -->
        <div class="overlay-content">
            <form id="forgot-password-form" method="post" class="apollo-form">
                <?php wp_nonce_field('apollo_forgot_password_action', 'apollo_forgot_password_nonce'); ?>

                <div class="input-group">
                    <input
                        type="email"
                        id="forgot_email"
                        name="user_email"
                        class="apollo-input form-input"
                        placeholder=" "
                        required
                        autocomplete="email"
                        autofocus />
                    <label class="apollo-label" for="forgot_email"><?php esc_html_e('E-mail', 'apollo-login'); ?></label>
                    <span class="field-hint"><?php esc_html_e('Usamos e-mail para autenticação por segurança', 'apollo-login'); ?></span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block" id="forgot-password-submit">
                        <span><?php esc_html_e('ENVIAR LINK DE RECUPERAÇÃO', 'apollo-login'); ?></span>
                        <i class="ri-mail-send-line"></i>
                    </button>

                    <div id="forgot-password-success" class="auth-alert auth-alert-success" style="display: none;" role="status" aria-live="polite"></div>

                    <button type="button" class="btn btn-ghost btn-block" data-close-overlay="true">
                        <i class="ri-arrow-left-line"></i>
                        <span><?php esc_html_e('Voltar ao Login', 'apollo-login'); ?></span>
                    </button>
                </div>

                <!-- Security Notice -->
                <div class="security-notice">
                    <i class="ri-shield-check-line"></i>
                    <p><?php esc_html_e('Por segurança, o link expira em 1 hora e só pode ser usado uma vez.', 'apollo-login'); ?></p>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <footer class="overlay-footer">
            <p class="help-text">
                <?php esc_html_e('Problemas?', 'apollo-login'); ?>
                <a href="<?php echo esc_url( home_url( '/contato/' ) ); ?>" class="link-orange"><?php esc_html_e('Entre em contato', 'apollo-login'); ?></a>
            </p>
        </footer>
    </div>
</div>

<script>
    window.openPasswordOverlay = window.openPasswordOverlay || function() {};
    window.closePasswordOverlay = window.closePasswordOverlay || function() {};
</script>

<script>
    (function() {
        const overlay = document.getElementById('password-recovery-overlay');
        const panel = overlay?.querySelector('.overlay-panel');

        if (!overlay || !panel) {
            return;
        }

        function bootPasswordOverlay() {
        // Check URL params
        const urlParams = new URLSearchParams(window.location.search);
        const showOverlay = urlParams.get('quero') === 'recuperar-chave' ||
            urlParams.get('action') === 'lostpassword';

        // Initialize GSAP
        if (typeof gsap !== 'undefined') {
            gsap.set(panel, {
                x: '100%'
            });
        }

        window.openPasswordOverlay = function() {
            overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            const emailInput = document.getElementById('forgot_email');
            const submitBtn = document.getElementById('forgot-password-submit');
            const successBox = document.getElementById('forgot-password-success');
            if (emailInput) {
                emailInput.readOnly = false;
            }
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('is-sent');
                const span = submitBtn.querySelector('span');
                if (span) {
                    span.textContent = '<?php echo esc_js(__('ENVIAR LINK DE RECUPERAÇÃO', 'apollo-login')); ?>';
                }
            }
            if (successBox) {
                successBox.style.display = 'none';
                successBox.textContent = '';
            }

            if (typeof gsap !== 'undefined') {
                gsap.to(panel, {
                    x: 0,
                    duration: 0.6,
                    ease: 'power3.out'
                });
            } else {
                panel.style.transform = 'translateX(0)';
            }

            setTimeout(() => {
                const emailInput = document.getElementById('forgot_email');
                if (emailInput) {
                    emailInput.focus();
                }
            }, 650);
        };

        window.closePasswordOverlay = function() {
            if (typeof gsap !== 'undefined') {
                gsap.to(panel, {
                    x: '100%',
                    duration: 0.5,
                    ease: 'power3.in',
                    onComplete: () => {
                        overlay.style.display = 'none';
                        document.body.style.overflow = '';
                    }
                });
            } else {
                panel.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    overlay.style.display = 'none';
                    document.body.style.overflow = '';
                }, 500);
            }

            const url = new URL(window.location.href);
            url.searchParams.delete('quero');
            url.searchParams.delete('action');
            window.history.replaceState({}, '', url);
        };

        if (showOverlay) {
            window.openPasswordOverlay();
        }

        // Close on backdrop/button click
        overlay.addEventListener('click', function(e) {
            if (e.target.dataset.closeOverlay || e.target.closest('[data-close-overlay]')) {
                closePasswordOverlay();
            }
        });

        // Close on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && overlay.style.display === 'flex') {
                closePasswordOverlay();
            }
        });

        // Handle form submission
        const form = document.getElementById('forgot-password-form');
        if (form) {
            function showAuthNotification(message, type) {
                if (window.ApolloAuth?.showNotification) {
                    window.ApolloAuth.showNotification(message, type);
                    return;
                }
                if (window.showNotification) {
                    window.showNotification(message, type);
                }
            }

            function parseAjaxError(data) {
                if (typeof data === 'string') {
                    return data;
                }
                if (data && typeof data.message === 'string') {
                    return data.message;
                }
                return '<?php echo esc_js( __( 'Erro ao enviar e-mail. Verifique se o e-mail está correto.', 'apollo-login' ) ); ?>';
            }

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const emailInput = document.getElementById('forgot_email');
                const submitBtn = document.getElementById('forgot-password-submit');
                const successBox = document.getElementById('forgot-password-success');
                const originalHTML = submitBtn.innerHTML;

                if (successBox) {
                    successBox.style.display = 'none';
                    successBox.textContent = '';
                }

                submitBtn.disabled = true;
                submitBtn.classList.remove('is-sent');
                submitBtn.innerHTML = '<span><?php echo esc_js( __( 'ENVIANDO...', 'apollo-login' ) ); ?></span><i class="ri-loader-4-line ri-spin"></i>';

                try {
                    const formData = new FormData(form);
                    formData.append('action', 'apollo_forgot_password');

                    const response = await fetch(window.apolloAuthConfig?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });

                    const result = await response.json();

                    if (result.success) {
                        const message = result.data?.message || '<?php echo esc_js( __( 'E-mail enviado. Verifique sua caixa de entrada (e o spam).', 'apollo-login' ) ); ?>';
                        const devResetUrl = result.data?.dev_reset_url || '';
                        const localHint = result.data?.local_mail_hint || '';
                        const emailSent = result.data?.email_sent === true;
                        const deliveryFailed = result.data?.delivery_failed === true;

                        if (deliveryFailed) {
                            const failMsg = '<?php echo esc_js( __( 'Não foi possível enviar o e-mail agora. Tente novamente em instantes ou entre em contato.', 'apollo-login' ) ); ?>';
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('is-sent');
                            submitBtn.innerHTML = originalHTML;

                            if (successBox) {
                                successBox.innerHTML = failMsg;
                                successBox.className = 'auth-alert auth-alert-error';
                                successBox.style.display = 'block';
                            }

                            showAuthNotification(failMsg, 'error');
                            return;
                        }

                        if (emailSent) {
                            submitBtn.innerHTML = '<span><?php echo esc_js( __( 'E-mail enviado', 'apollo-login' ) ); ?></span><i class="ri-check-line"></i>';
                            submitBtn.classList.add('is-sent');
                            submitBtn.disabled = true;

                            if (emailInput) {
                                emailInput.readOnly = true;
                            }
                        } else {
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('is-sent');
                            submitBtn.innerHTML = originalHTML;
                        }

                        if (successBox) {
                            let html = message;
                            if (localHint) {
                                html += '<br><small>' + localHint + '</small>';
                            }
                            if (devResetUrl) {
                                html += '<br><a href="' + devResetUrl + '" class="link-orange" style="margin-top:8px;display:inline-block;"><?php echo esc_js( __( 'Abrir link de recuperação (local)', 'apollo-login' ) ); ?></a>';
                            }
                            successBox.innerHTML = html;
                            successBox.className = 'auth-alert auth-alert-success';
                            successBox.style.display = 'block';
                        }

                        showAuthNotification(message, emailSent ? 'success' : 'info');
                        if (localHint) {
                            showAuthNotification(localHint, 'info');
                        }
                    } else {
                        showAuthNotification(parseAjaxError(result.data), 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHTML;
                    }
                } catch (error) {
                    console.error('Forgot password error:', error);
                    showAuthNotification(
                        '<?php echo esc_js( __( 'Erro de conexão. Tente novamente.', 'apollo-login' ) ); ?>',
                        'error'
                    );
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHTML;
                }
            });
        }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootPasswordOverlay, { once: true });
        } else {
            bootPasswordOverlay();
        }
    })();
</script>