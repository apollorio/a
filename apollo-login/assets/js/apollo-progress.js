/**
 * ================================================================================
 * APOLLO PROGRESS - Preloader & Animation Controller
 * ================================================================================
 * Handles animated preloader/spinner during login and registration flows.
 * Provides smooth fade-in/fade-out transitions with GSAP.
 *
 * @package Apollo\Login
 * @since 1.0.0
 * ================================================================================
 */

(function() {
    'use strict';

    // ========================================================================
    // PROGRESS PRELOADER CONTROLLER
    // ========================================================================
    
    const ProgressController = {
        preloader: null,
        progressText: null,
        progressPercent: null,
        isVisible: false,

        init: function() {
            this.preloader = document.getElementById('apollo-preloader');
            this.progressText = document.getElementById('apollo-progress-text');
            this.progressPercent = document.getElementById('apollo-progress-percent');
            
            // Create preloader if it doesn't exist
            if (!this.preloader) {
                this.createPreloader();
            }
        },

        createPreloader: function() {
            const preloaderHTML = `
                <div id="apollo-preloader" class="apollo-preloader">
                    <div class="preloader-container">
                        <div class="preloader-spinner">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <div class="preloader-content">
                            <div class="preloader-status" id="apollo-progress-text">Conectando...</div>
                            <div class="preloader-percentage" id="apollo-progress-percent">0%</div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('afterbegin', preloaderHTML);
            this.preloader = document.getElementById('apollo-preloader');
            this.progressText = document.getElementById('apollo-progress-text');
            this.progressPercent = document.getElementById('apollo-progress-percent');
            
            this.injectPreloaderStyles();
        },

        injectPreloaderStyles: function() {
            const styles = document.createElement('style');
            styles.id = 'apollo-preloader-styles';
            styles.textContent = `
                .apollo-preloader {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.85);
                    backdrop-filter: blur(20px);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                    opacity: 0;
                    pointer-events: none;
                    transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1);
                }

                .apollo-preloader.is-visible {
                    opacity: 1;
                    pointer-events: all;
                }

                .preloader-container {
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 24px;
                }

                .preloader-spinner {
                    position: relative;
                    width: 96px;
                    height: 96px;
                    border-radius: 50%;
                    background: linear-gradient(45deg, #CB0000, #FFC700, #c79c00);
                    animation: rotate_3922 1.2s linear infinite;
                }

                .preloader-spinner span {
                    position: absolute;
                    border-radius: 50%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(45deg, #ff0000, #ffc800, #ffc800);
                }

                .preloader-spinner span:nth-child(1) { filter: blur(5px); }
                .preloader-spinner span:nth-child(2) { filter: blur(10px); }
                .preloader-spinner span:nth-child(3) { filter: blur(25px); }
                .preloader-spinner span:nth-child(4) { filter: blur(50px); }

                .preloader-spinner::after {
                    content: "";
                    position: absolute;
                    top: 10px;
                    left: 10px;
                    right: 10px;
                    bottom: 10px;
                    background: var(--bg, #ffffff);
                    border: 5px solid var(--white-6, #e6e6e9);
                    border-radius: 50%;
                }

                @keyframes rotate_3922 {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }

                .preloader-content {
                    text-align: center;
                }

                .preloader-status {
                    font-family: var(--ff-mono, 'SUSE Mono', monospace);
                    font-size: var(--fs-auth-body, 14px);
                    color: var(--txt-heading, #1a1a1a);
                    text-transform: uppercase;
                    letter-spacing: 0.08em;
                    margin-bottom: 8px;
                }

                .preloader-percentage {
                    font-family: var(--ff-main, system-ui, sans-serif);
                    font-size: var(--fs-h4, 1.5rem);
                    font-weight: 700;
                    color: var(--accent, #FFAA33);
                }

                /* Progress phases */
                .preloader-phase-auth { animation-delay: 0s; }
                .preloader-phase-database { animation-delay: 0.1s; }
                .preloader-phase-cdj { animation-delay: 0.2s; }
                .preloader-phase-usb { animation-delay: 0.3s; }
                .preloader-phase-p2p { animation-delay: 0.4s; }
            `;
            document.head.appendChild(styles);
        },

        show: function(message, percent) {
            this.init();
            
            if (this.progressText) {
                this.progressText.textContent = message || 'Conectando...';
            }
            if (this.progressPercent) {
                this.progressPercent.textContent = percent || '0%';
            }

            // GSAP fade in if available, otherwise use CSS
            if (window.gsap) {
                gsap.to(this.preloader, {
                    opacity: 1,
                    duration: 0.4,
                    ease: 'cubic-bezier(0.16, 1, 0.3, 1)'
                });
            } else {
                this.preloader.classList.add('is-visible');
            }
            
            this.isVisible = true;
        },

        update: function(message, percent) {
            if (!this.isVisible) {
                this.show(message, percent);
                return;
            }
            
            if (this.progressText && message) {
                this.progressText.textContent = message;
            }
            if (this.progressPercent && percent) {
                this.progressPercent.textContent = percent;
            }
        },

        hide: function() {
            if (!this.preloader) return;
            
            const hideComplete = () => {
                this.isVisible = false;
                if (this.preloader) {
                    this.preloader.style.display = 'none';
                }
            };

            // GSAP fade out if available
            if (window.gsap) {
                gsap.to(this.preloader, {
                    opacity: 0,
                    duration: 0.5,
                    ease: 'cubic-bezier(0.34, 1.56, 0.64, 1)',
                    onComplete: hideComplete
                });
            } else {
                this.preloader.classList.remove('is-visible');
                setTimeout(hideComplete, 400);
            }
        },

        // Predefined progress flows
        flow: {
            login: function() {
                ProgressController.show('Verificando credenciais...', '0%');
                setTimeout(() => ProgressController.update('Autenticando...', '25%'), 300);
                setTimeout(() => ProgressController.update('Estabelecendo sessão...', '50%'), 600);
                setTimeout(() => ProgressController.update('Carregando terminal...', '75%'), 900);
            },
            
            register: function() {
                ProgressController.show('Preparando cadastro...', '0%');
                setTimeout(() => ProgressController.update('Validando dados...', '30%'), 400);
                setTimeout(() => ProgressController.update('Criando conta...', '60%'), 700);
                setTimeout(() => ProgressController.update('Finalizando...', '90%'), 1000);
            }
        }
    };

    // ========================================================================
    // LOGIN FORM INTEGRATION
    // ========================================================================

    function initLoginProgress() {
        // Wait for apollo-auth-scripts to initialize
        const checkReady = () => {
            if (window.__APOLLO_AUTH_SCRIPTS_LOADED__ || document.getElementById('login-form')) {
                attachLoginHandlers();
            } else {
                setTimeout(checkReady, 100);
            }
        };
        checkReady();
    }

    function attachLoginHandlers() {
        const loginForm = document.getElementById('login-form');
        if (!loginForm) return;

        loginForm.addEventListener('submit', function(e) {
            // Show preloader immediately on submit
            ProgressController.flow.login();
        });

        // Listen for successful login to hide preloader
        document.addEventListener('apollo:login-success', function() {
            ProgressController.update('Redirecionando...', '100%');
            setTimeout(() => ProgressController.hide(), 800);
        });

        // Listen for login failure
        document.addEventListener('apollo:login-failure', function() {
            ProgressController.hide();
        });
    }

    // ========================================================================
    // BOOTSTRAP
    // ========================================================================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoginProgress);
    } else {
        initLoginProgress();
    }

    // Export for manual control
    window.ProgressController = ProgressController;

})();