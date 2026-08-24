/**
 * ================================================================================
 * APOLLO AUTH SCRIPTS - Main JavaScript
 * ================================================================================
 * Complete authentication logic including:
 * - Security state management (normal, warning, danger, success)
 * - Login/Register form handling
 * - Aptitude Quiz System (Pattern, Simon, Ethics, Reaction — 4 stages)
 * - Visual effects (corruption, glitch, siren)
 *
 * @package Apollo_Social
 * @since 1.0.0
 *
 * PHP CONVERSION NOTES:
 * - Configuration should be passed via wp_localize_script()
 * - Quiz questions can be loaded from WordPress options
 * - AJAX endpoints should be registered via wp_ajax_* hooks
 * - Nonce verification required for all form submissions
 * ================================================================================
 */

(function() {
    'use strict';

    // ========================================================================
    // CONFIGURATION
    // ========================================================================
    // PHP: These values should come from wp_localize_script('apollo-auth-scripts', 'apolloAuthConfig', {...})
    const CONFIG = window.apolloAuthConfig || {
        ajaxUrl: '/wp-admin/admin-ajax.php',
        nonce: '',
        maxFailedAttempts: 3,
        lockoutDuration: 60, // seconds
        simonLevels: 4,
        reactionTargets: 4,
        redirectAfterLogin: '/feed/',
        availableSounds: {},
        termsUrl: '/termos-e-politica/',
        strings: {
            loginSuccess: 'Acesso autorizado. Redirecionando...',
            loginFailed: 'Credenciais incorretas. Tente novamente.',
            warningState: 'Atenção: última tentativa antes do bloqueio.',
            lockedOut: 'Sistema bloqueado por segurança.',
            quizComplete: 'Teste de aptidão concluído com sucesso!',
            quizFailed: 'Resposta incorreta. Reiniciando pergunta...',
            patternCorrect: 'seq-double-16',
            ethicsCorrect: 'É trabalho, renda, a sonoridade e arte favorita de alguem.'
        },
        quizTotalStages: 4,
        restUrl: '/wp-json/apollo/v1'
    };

    function debugClientLog() { /* no-op */ }

    const QUIZ_STAGE_META = {
        1: { short: 'PADRÃO', label: 'Padrão visual' },
        2: { short: 'SIMON', label: 'Jogo da memória' },
        3: { short: 'ÉTICA', label: 'Ética e respeito' },
        4: { short: 'REAÇÃO', label: 'Reação e sincronia' }
    };

    // State management
    let state = {
        failedAttempts: 0,
        isLockedOut: false,
        lockoutEndTime: null,
        currentQuizStage: 0,
        simonSequence: [],
        simonUserSequence: [],
        simonLevel: 1,
        reactionCaptures: 0,
        reactionActive: false,
        reactionTweens: [],
        reactionPulseTweens: [],
        reactionSpawnAttempts: 0,
        clubberUniverse: '',
        selectedSounds: [],
        authSubDefault: '',
        timestampInterval: null,
        glitchInterval: null,
        quizToken: ''
    };

    // DOM Elements cache
    let els = {};

    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    function bootAuth() {
        document.addEventListener('apollo-auth-notify', function(e) {
            if (e.detail && e.detail.message) {
                showNotification(e.detail.message, e.detail.type || 'info');
            }
        });
        // Cache DOM elements
        els = {
            body: document.body,
            loginForm: document.getElementById('login-form'),
            registerForm: document.getElementById('register-form'),
            loginSection: document.getElementById('login-section'),
            registerSection: document.getElementById('register-section'),
            aptitudeOverlay: document.getElementById('aptitude-overlay'),
            lockoutOverlay: document.querySelector('.lockout-overlay'),
            lockoutTimer: document.getElementById('lockout-timer'),
            timestamp: document.getElementById('timestamp'),
            testContent: document.getElementById('test-content'),
            testBtn: document.getElementById('test-btn'),
            testBtnText: document.getElementById('test-btn-text'),
            testProgress: document.getElementById('test-progress'),
            dangerFlash: null,
            toggles: document.querySelectorAll('.custom-toggle'),
            switchToRegister: document.getElementById('switch-to-register'),
            switchToLogin: document.getElementById('switch-to-login'),
            authCard: document.getElementById('auth-card'),
            authSub: document.querySelector('#auth-card .auth-sub'),
            authScrollArea: document.querySelector('#auth-card > .scroll-area')
        };

        if (els.authSub) {
            state.authSubDefault = els.authSub.textContent.trim();
        }

        // Initialize components
        initToggles();
        initFormSwitching();
        initForms();
        initTimestamp();
        initInstagramField();
        initSoundsValidation();
        initLoginAntiAutofill();
        initPasswordToggle();

        // Check for existing lockout
        checkExistingLockout();
        initVerifyEmailEntrance();
        initReportModalTrigger();
    }

    /** Bug report "aqui" — opens/closes canonical apollo-core #apolloModal (body.apollo-open). */
    function initReportModalTrigger() {
        function closeReportModal() {
            document.body.classList.remove('apollo-open');
            const modal = document.getElementById('apolloModal');
            const form = document.getElementById('apolloForm');
            const err = document.getElementById('apolloError');
            if (modal) {
                modal.setAttribute('aria-hidden', 'true');
            }
            if (err) {
                err.style.display = 'none';
                err.textContent = '';
            }
            setTimeout(() => {
                modal?.classList.remove('form-submitted');
                form?.reset();
            }, 600);
        }

        document.addEventListener('click', function(e) {
            if (e.target.closest('[data-apollo-report-close], #apolloClose, .modal-close')) {
                e.preventDefault();
                closeReportModal();
                return;
            }
            if (e.target.id === 'apolloOverlay') {
                closeReportModal();
                return;
            }
            const trigger = e.target.closest('[data-apollo-report-trigger]');
            if (!trigger) {
                return;
            }
            e.preventDefault();
            if (document.getElementById('apolloModal')) {
                document.body.classList.add('apollo-open');
                document.getElementById('apolloModal')?.setAttribute('aria-hidden', 'false');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.body.classList.contains('apollo-open')) {
                closeReportModal();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootAuth, { once: true });
    } else {
        bootAuth();
    }

    // ========================================================================
    // TOGGLE SWITCHES
    // ========================================================================

    function initToggles() {
        els.toggles.forEach(t => {
            t.addEventListener('click', () => {
                t.classList.toggle('active');
                // If this is a required toggle (like terms), validate
                const input = t.querySelector('input[type="hidden"]');
                if (input) {
                    input.value = t.classList.contains('active') ? '1' : '0';
                }
            });
        });
    }

    // ========================================================================
    // FORM SWITCHING (Login <-> Register)
    // ========================================================================

    function initFormSwitching() {
        if (els.switchToRegister) {
            els.switchToRegister.addEventListener('click', function(e) {
                e.preventDefault();
                // Redirect to dedicated register page instead of showing/hiding sections
                window.location.href = CONFIG.registerUrl || '/registre/';
            });
        }

        if (els.switchToLogin) {
            els.switchToLogin.addEventListener('click', function(e) {
                e.preventDefault();
                // Redirect to dedicated login page instead of showing/hiding sections
                window.location.href = CONFIG.loginUrl || '/acesso/';
            });
        }
    }

    // ========================================================================
    // FORM SUBMISSION HANDLERS
    // ========================================================================

    function initForms() {
        if (els.loginForm) {
            els.loginForm.addEventListener('submit', handleLogin);
            refreshAuthNonce(els.loginForm);
        }
        if (els.registerForm) {
            initRegisterSteps();
            initCPFValidation();
            initBirthdayValidation();
            initPartyRoleSelection();
        }
        
        // Forgot Password Handler — Opens fullscreen overlay
        const forgotPasswordBtn = document.getElementById('forgot-password');
        if (forgotPasswordBtn) {
            forgotPasswordBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (typeof window.openPasswordOverlay === 'function') {
                    window.openPasswordOverlay();
                } else {
                    // Fallback to URL redirect if overlay not loaded
                    window.location.href = CONFIG.passwordRecoveryUrl || '/acesso/?quero=recuperar-chave';
                }
            });
        }
    }

    /**
     * Resolve login nonce — prefer live form field over localized config (cache-safe).
     */
    function getLoginNonce(form) {
        const fromForm = form?.querySelector('[name="nonce"]')?.value;
        if (fromForm) {
            return fromForm;
        }
        return CONFIG.nonce || '';
    }

    /**
     * Sync fresh nonce into CONFIG + hidden input.
     */
    function applyAuthNonce(form, nonce) {
        if (!nonce) {
            return;
        }
        CONFIG.nonce = nonce;
        const input = form?.querySelector('[name="nonce"]');
        if (input) {
            input.value = nonce;
        }
    }

    /**
     * Fetch a fresh nonce (stale page cache / expired tick).
     */
    async function refreshAuthNonce(form) {
        try {
            const response = await fetch(CONFIG.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'apollo_auth_nonce' })
            });
            const result = await response.json();
            const nonce = result?.data?.nonce || result?.nonce;
            if (nonce) {
                applyAuthNonce(form, nonce);
                return nonce;
            }
        } catch (err) {
            console.warn('[Apollo Login] nonce refresh failed', err);
        }
        return null;
    }

    /**
     * Normalize REST vs admin-ajax login responses into { success, data }.
     */
    function normalizeLoginResponse(raw, httpOk) {
        if (raw && raw.success === true) {
            if (raw.data && typeof raw.data === 'object') {
                return raw;
            }
            return {
                success: true,
                data: {
                    message: raw.message,
                    redirect: raw.redirect,
                    user_id: raw.user_id
                }
            };
        }

        if (raw && raw.code) {
            return {
                success: false,
                data: {
                    code: raw.code,
                    message: raw.message,
                    redirect: raw.data?.redirect,
                    status: raw.data?.status,
                    attempts: raw.data?.attempts,
                    max: raw.data?.max
                }
            };
        }

        if (!httpOk && raw && !raw.success) {
            return {
                success: false,
                data: {
                    code: raw.code || 'login_failed',
                    message: raw.message || CONFIG.strings.loginFailed
                }
            };
        }

        return raw;
    }

    /**
     * POST login attempt via REST API.
     */
    async function postLoginRequest(form, username, password) {
        const rememberVal = form.querySelector('[name="rememberme"]')?.value || '0';
        const remember = rememberVal === '1';

        const response = await fetch(`${CONFIG.restUrl}/auth/login`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password, remember })
        });

        const result = await response.json();
        return normalizeLoginResponse(result, response.ok);
    }

    /**
     * Handle login form submission
     * REST login with wp_authenticate + wp_set_auth_cookie
     */
    async function handleLogin(e) {
        e.preventDefault();

        if (state.isLockedOut) {
            shakeElement(els.loginForm);
            return;
        }

        const form = e.target;
        const username = (
            form.querySelector('[name="apollo_log"]')?.value
            || form.querySelector('[name="log"]')?.value
            || form.querySelector('[name="username"]')?.value
            || ''
        ).trim();
        const password = (
            form.querySelector('[name="apollo_pwd"]')?.value
            || form.querySelector('[name="pwd"]')?.value
            || form.querySelector('[name="password"]')?.value
            || ''
        ).trim();
        const submitBtn = form.querySelector('button[type="submit"]');

        if (!username || !password) {
            showNotification('Preencha todos os campos.', 'warning');
            shakeElement(form);
            return;
        }

        // Disable button during request
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>VERIFICANDO...</span>';

        try {
            const result = await postLoginRequest(form, username, password);

            if (result.success) {
                handleLoginSuccess(result.data);
            } else {
                const msg = result.data?.message || CONFIG.strings.loginFailed;
                handleLoginFailure(msg, result.data);
            }
        } catch (error) {
            console.error('Apollo Login error:', error);
            showNotification('Erro de conexão. Tente novamente.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }



    /**
     * Handle successful login
     */
    function handleLoginSuccess(data) {
        state.failedAttempts = 0;
        setSecurityState('success');

        const msg = data?.message || CONFIG.strings.loginSuccess;
        showNotification(msg, 'success');

        // Dispatch event for preloader
        document.dispatchEvent(new CustomEvent('apollo:login-success', { detail: data }));

        // Redirect after animation
        const redirect = data?.redirect || CONFIG.redirectAfterLogin;
        setTimeout(() => {
            window.location.href = redirect;
        }, 1200);
    }

    /**
     * Handle failed login attempt
     */
    function handleLoginFailure(message, data) {
        const skipAttempt = data && ['nonce_failed', 'email_not_verified', 'rate_limited'].includes(data.code);
        if (!skipAttempt) {
            state.failedAttempts++;
        }

        const submitBtn = els.loginForm?.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span>ACESSAR TERMINAL</span><i class="ri-arrow-right-line"></i>';
        }

        shakeElement(els.loginForm);

        // Dispatch event for preloader to hide
        document.dispatchEvent(new CustomEvent('apollo:login-failure', { detail: data }));

        // Check server-side attempt count if available
        const serverAttempts = data?.attempts || state.failedAttempts;
        const maxAttempts = data?.max || CONFIG.maxFailedAttempts;

        if (serverAttempts >= maxAttempts) {
            // LOCKOUT - Danger state
            setSecurityState('danger');
            initiateLockout();
        } else if (serverAttempts >= maxAttempts - 1) {
            // WARNING state
            setSecurityState('warning');
            showNotification(message || CONFIG.strings.warningState, 'warning');
        } else {
            showNotification(message || CONFIG.strings.loginFailed, 'error');
        }
    }

    /**
     * Sync visible #documento input into hidden doc_type/cpf/passport fields.
     * Mirrors new_register-form.php inline auto-detect (digits→cpf, letters→passport).
     */
    function syncDocumentFieldsToForm() {
        const documento = document.getElementById('documento');
        const docTypeInput = document.getElementById('doc_type');
        const cpfHidden = document.getElementById('cpf');
        const passportHidden = document.getElementById('passport');

        if (!documento || !docTypeInput) {
            return;
        }

        const raw = documento.value;
        const trimmed = raw.trim();

        if ('' === trimmed) {
            if (cpfHidden && cpfHidden.value) {
                docTypeInput.value = 'cpf';
                return;
            }
            if (passportHidden && passportHidden.value) {
                docTypeInput.value = 'passport';
                return;
            }
            if (docTypeInput.value) {
                return;
            }
            docTypeInput.value = '';
            if (cpfHidden) cpfHidden.value = '';
            if (passportHidden) passportHidden.value = '';
            return;
        }

        if (/^\d/.test(trimmed)) {
            const digits = raw.replace(/\D/g, '').slice(0, 11);
            docTypeInput.value = 'cpf';
            if (cpfHidden) cpfHidden.value = digits;
            if (passportHidden) passportHidden.value = '';
        } else if (/^[a-zA-Z]/.test(trimmed)) {
            const passport = raw.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().slice(0, 20);
            docTypeInput.value = 'passport';
            if (passportHidden) passportHidden.value = passport;
            if (cpfHidden) cpfHidden.value = '';
        }
    }

    function showRegisterStep(step) {
        const panel1a = document.getElementById('register-step-1a');
        const panel1b = document.getElementById('register-step-1b');
        const hint = document.getElementById('register-step-hint');
        const form = els.registerForm;

        if (panel1a) {
            panel1a.classList.toggle('is-active', step === '1a');
            panel1a.hidden = step !== '1a';
        }
        if (panel1b) {
            panel1b.classList.toggle('is-active', step === '1b');
            panel1b.hidden = step !== '1b';
        }
        if (hint) {
            const label = step === '1b' ? 'ETAPA 1b — Perfil e telefone' : 'ETAPA 1a — Identidade';
            hint.innerHTML = '<span><span class="terminal-prompt">&gt;</span> ' + label + '</span>';
        }
        if (form) {
            form.dataset.registerStep = step;
        }
    }

    function validateRegisterStep1a(form) {
        syncDocumentFieldsToForm();

        const requiredFields = ['nome', 'email', 'instagram'];
        for (const field of requiredFields) {
            const input = form.querySelector(`[name="${field}"]`);
            if (!input || !input.value.trim()) {
                showNotification('Preencha todos os campos obrigatórios.', 'error');
                input?.focus();
                shakeElement(form);
                return false;
            }
        }

        const docType = form.querySelector('[name="doc_type"]')?.value;
        if (!docType) {
            showNotification('Informe CPF (números) ou passaporte (letras).', 'error');
            document.getElementById('documento')?.focus();
            return false;
        }

        if (docType === 'cpf') {
            const cpfInput = document.getElementById('documento') || form.querySelector('[name="cpf"]');
            const cpf = form.querySelector('[name="cpf"]')?.value;
            if (!cpf || !validateCPF(cpf)) {
                showNotification('CPF inválido — verifique os dígitos.', 'error');
                cpfInput?.focus();
                return false;
            }
            if (cpfInput && cpfInput.classList.contains('cpf-invalid')) {
                showNotification('Esse CPF já está em uso ou é inválido.', 'error');
                cpfInput.focus();
                return false;
            }
        } else if (docType === 'passport') {
            const passport = form.querySelector('[name="passport"]')?.value;
            const passportResult = validatePassport(passport);
            if (!passportResult.valid) {
                showNotification(passportResult.message || 'Número de passaporte inválido.', 'error');
                document.getElementById('documento')?.focus();
                return false;
            }
        }

        return true;
    }

    function advanceRegisterStep1a() {
        const form = els.registerForm;
        if (!form || !validateRegisterStep1a(form)) {
            return;
        }
        showRegisterStep('1b');
        document.getElementById('bday_day')?.focus();
    }

    function advanceRegisterStep1b() {
        const form = els.registerForm;
        if (!form) return;

        const birthValidation = validateBirthdayFields(form);
        if (!birthValidation.valid) {
            showNotification(birthValidation.error || 'Data de nascimento inválida.', 'error');
            document.getElementById('bday_day')?.focus();
            return;
        }

        const partyRole = form.querySelector('[name="party_role"]')?.value;
        if (!partyRole) {
            showNotification('Selecione como você é em uma festa.', 'error');
            document.getElementById('party-role-field')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const phoneOk = window.ApolloPhoneVerify && typeof window.ApolloPhoneVerify.isVerified === 'function'
            ? window.ApolloPhoneVerify.isVerified()
            : form.querySelector('[name="phone_verified"]')?.value === '1';

        if (!phoneOk) {
            showNotification('Confirme seu telefone via Telegram antes de prosseguir.', 'error');
            document.getElementById('reg-phone-field')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        openAptitudeTest();
    }

    function initRegisterSteps() {
        const form = els.registerForm;
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
        });

        document.getElementById('register-step-1a-btn')?.addEventListener('click', advanceRegisterStep1a);
        document.getElementById('register-step-1b-btn')?.addEventListener('click', advanceRegisterStep1b);
        document.getElementById('register-step-1b-back')?.addEventListener('click', function() {
            showRegisterStep('1a');
        });

        showRegisterStep('1a');
    }

    /**
     * Legacy form submit handler (unused — steps use advanceRegisterStep1a/1b).
     */
    async function handleRegister(e) {
        e.preventDefault();
        const form = e.target;
        const step = form.dataset.registerStep || '1a';
        if (step === '1a') {
            advanceRegisterStep1a();
        } else {
            advanceRegisterStep1b();
        }
    }

    // ========================================================================
    // CPF VALIDATION — Full chain: format → algorithm → uniqueness (AJAX)
    // ========================================================================

    /**
     * Validate Brazilian CPF (Receita Federal Mod-11 algorithm)
     *
     * Checks performed:
     * 1. Strip non-digits
     * 2. Exactly 11 digits
     * 3. Not all-same-digit (000…, 111…, etc.)
     * 4. First check digit (d1) via weighted sum mod 11
     * 5. Second check digit (d2) via weighted sum mod 11
     *
     * @param {string} cpf - CPF string (with or without mask)
     * @returns {boolean}
     */
    function validateCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');

        if (cpf.length !== 11) return false;

        // Reject all-same-digit sequences
        if (/^(\d)\1{10}$/.test(cpf)) return false;

        // First check digit (d1)
        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += parseInt(cpf[i]) * (10 - i);
        }
        let d1 = (sum % 11 < 2) ? 0 : 11 - (sum % 11);
        if (parseInt(cpf[9]) !== d1) return false;

        // Second check digit (d2)
        sum = 0;
        for (let i = 0; i < 10; i++) {
            sum += parseInt(cpf[i]) * (11 - i);
        }
        let d2 = (sum % 11 < 2) ? 0 : 11 - (sum % 11);
        return parseInt(cpf[10]) === d2;
    }

    /**
     * Passport format validation (registration strict mode).
     *
     * @param {string} passport
     * @returns {{valid: boolean, message: string}}
     */
    function validatePassport(passport) {
        const normalized = String(passport || '').replace(/[^a-zA-Z0-9]/g, '').toUpperCase();

        if (normalized.length < 6 || normalized.length > 20) {
            return { valid: false, message: 'Use 6 a 20 caracteres alfanuméricos.' };
        }
        if (!/^[A-Z0-9]+$/.test(normalized)) {
            return { valid: false, message: 'Apenas letras e números.' };
        }
        if (!/[A-Z]/.test(normalized) || !/[0-9]/.test(normalized)) {
            return { valid: false, message: 'Passaporte deve conter letras e números.' };
        }
        return { valid: true, message: '' };
    }

    /**
     * Real-time CPF validation with AJAX uniqueness check
     * Binds to the CPF input field and shows live feedback.
     */
    let cpfDebounceTimer = null;

    function initCPFValidation() {
        const cpfInput = document.getElementById('documento') || document.getElementById('cpf');
        if (!cpfInput) return;

        const docField = document.getElementById('doc-field');
        const wrapper = docField || cpfInput.closest('.input-group') || cpfInput.closest('.form-group');
        let feedback = wrapper?.querySelector('.doc-type-meta .cpf-feedback')
            || wrapper?.querySelector('.cpf-feedback');
        if (!feedback && wrapper) {
            const meta = wrapper.querySelector('.doc-type-meta');
            feedback = document.createElement('div');
            feedback.className = 'cpf-feedback';
            feedback.setAttribute('aria-live', 'polite');
            if (meta) {
                meta.appendChild(feedback);
            } else {
                wrapper.appendChild(feedback);
            }
        }
        if (!feedback) return;

        function handleCpfInput() {
            const docType = document.getElementById('doc_type')?.value;
            if (docType !== 'cpf') {
                feedback.innerHTML = '';
                cpfInput.classList.remove('cpf-valid', 'cpf-invalid');
                return;
            }

            const raw = cpfInput.value.replace(/\D/g, '');
            const cpfHidden = document.getElementById('cpf');
            if (cpfHidden) {
                cpfHidden.value = raw;
            }

            if (cpfDebounceTimer) clearTimeout(cpfDebounceTimer);

            feedback.innerHTML = '';
            cpfInput.classList.remove('cpf-valid', 'cpf-invalid');

            if (raw.length < 11) {
                return;
            }

            if (raw.length === 11) {
                if (!validateCPF(raw)) {
                    feedback.innerHTML = '<i class="ri-error-warning-fill" style="color: #ef4444;"></i> <span style="color: #ef4444;">CPF inválido — dígitos verificadores incorretos</span>';
                    cpfInput.classList.add('cpf-invalid');
                    cpfInput.classList.remove('cpf-valid');
                    return;
                }

                feedback.innerHTML = '<i class="ri-loader-4-line" style="color: rgba(5,5,5,0.45); animation: spin 1s linear infinite;"></i> <span style="color: rgba(5,5,5,0.55);">Verificando CPF...</span>';

                cpfDebounceTimer = setTimeout(() => {
                    checkCPFUniqueness(raw, feedback, cpfInput);
                }, 400);
            }
        }

        cpfInput.addEventListener('input', handleCpfInput);
        cpfInput.addEventListener('cpf-sync', handleCpfInput);
    }

    /**
     * Check CPF uniqueness via AJAX
     */
    async function checkCPFUniqueness(cpf, feedbackEl, inputEl) {
        try {
            const formData = new FormData();
            formData.append('action', 'apollo_validate_cpf');
            formData.append('cpf', cpf);

            // Try to get nonce from form or config
            const nonceInput = document.querySelector('[name="apollo_register_nonce"]') ||
                               document.querySelector('[name="nonce"]');
            const nonce = nonceInput ? nonceInput.value : CONFIG.nonce;
            formData.append('nonce', nonce);

            const response = await fetch(CONFIG.ajaxUrl, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                feedbackEl.innerHTML = '<i class="ri-checkbox-circle-fill" style="color: #22c55e;"></i> <span style="color: #22c55e;">' + result.data.message + '</span>';
                inputEl.classList.add('cpf-valid');
                inputEl.classList.remove('cpf-invalid');
            } else {
                let icon = 'ri-error-warning-fill';
                let color = '#ef4444';

                if (result.data && result.data.code === 'cpf_exists') {
                    icon = 'ri-user-forbid-fill';
                    color = '#f59e0b'; // warning amber for "already registered"
                }

                const msg = (result.data && result.data.message) ? result.data.message : 'CPF inválido.';
                feedbackEl.innerHTML = '<i class="' + icon + '" style="color: ' + color + ';"></i> <span style="color: ' + color + ';">' + msg + '</span>';
                inputEl.classList.add('cpf-invalid');
                inputEl.classList.remove('cpf-valid');
            }
        } catch (err) {
            // Network error — fallback to client-only validation
            if (validateCPF(cpf)) {
                feedbackEl.innerHTML = '<i class="ri-checkbox-circle-fill" style="color: #22c55e;"></i> <span style="color: #22c55e;">CPF válido (verificação offline)</span>';
                inputEl.classList.add('cpf-valid');
                inputEl.classList.remove('cpf-invalid');
            } else {
                feedbackEl.innerHTML = '<i class="ri-error-warning-fill" style="color: #ef4444;"></i> <span style="color: #ef4444;">CPF inválido</span>';
                inputEl.classList.add('cpf-invalid');
                inputEl.classList.remove('cpf-valid');
            }
        }
    }

    // ========================================================================
    // BIRTHDAY + ZODIAC VALIDATION
    // ========================================================================

    const ZODIAC_LABELS = {
        aries: 'Áries',
        taurus: 'Touro',
        gemini: 'Gêmeos',
        cancer: 'Câncer',
        leo: 'Leão',
        virgo: 'Virgem',
        libra: 'Libra',
        scorpio: 'Escorpião',
        sagittarius: 'Sagitário',
        capricorn: 'Capricórnio',
        aquarius: 'Aquário',
        pisces: 'Peixes'
    };

    const MIN_REGISTRATION_AGE = 18;

    function zodiacFromDate(day, month) {
        const ranges = [
            ['capricorn', 1, 1, 1, 19],
            ['aquarius', 1, 20, 2, 18],
            ['pisces', 2, 19, 3, 20],
            ['aries', 3, 21, 4, 19],
            ['taurus', 4, 20, 5, 20],
            ['gemini', 5, 21, 6, 20],
            ['cancer', 6, 21, 7, 22],
            ['leo', 7, 23, 8, 22],
            ['virgo', 8, 23, 9, 22],
            ['libra', 9, 23, 10, 22],
            ['scorpio', 10, 23, 11, 21],
            ['sagittarius', 11, 22, 12, 21],
            ['capricorn', 12, 22, 12, 31]
        ];

        for (const [sign, startM, startD, endM, endD] of ranges) {
            if (month === startM && day >= startD) return sign;
            if (month === endM && day <= endD) return sign;
            if (startM < endM && month > startM && month < endM) return sign;
        }
        return null;
    }

    function validateBirthdayFields(form) {
        const day = parseInt(form.querySelector('[name="bday_day"]')?.value || '0', 10);
        const month = parseInt(form.querySelector('[name="bday_month"]')?.value || '0', 10);
        const year = parseInt(form.querySelector('[name="bday_year"]')?.value || '0', 10);
        const hidden = form.querySelector('[name="birth_date"]');

        if (!day || !month || !year) {
            return { valid: false, error: 'Informe dia, mês e ano de nascimento.' };
        }

        const dateObj = new Date(year, month - 1, day);
        if (
            dateObj.getFullYear() !== year ||
            dateObj.getMonth() !== month - 1 ||
            dateObj.getDate() !== day
        ) {
            return { valid: false, error: 'Data de nascimento inválida.' };
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (dateObj > today) {
            return { valid: false, error: 'Data de nascimento não pode ser no futuro.' };
        }

        const minBorn = new Date(today.getFullYear() - MIN_REGISTRATION_AGE, today.getMonth(), today.getDate());
        if (dateObj > minBorn) {
            return { valid: false, error: `É necessário ter pelo menos ${MIN_REGISTRATION_AGE} anos para se registrar.` };
        }

        const zodiac = zodiacFromDate(day, month);
        if (!zodiac) {
            return { valid: false, error: 'Não foi possível calcular o signo.' };
        }

        const ymd = `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        if (hidden) {
            hidden.value = ymd;
        }

        return { valid: true, date: ymd, zodiac, error: '' };
    }

    let bdayDebounceTimer = null;

    function setBirthdayFieldState(inputs, stateClass) {
        inputs.forEach((input) => {
            input.classList.remove('bday-valid', 'bday-invalid');
            if (stateClass) {
                input.classList.add(stateClass);
            }
        });
    }

    function initBirthdayValidation() {
        const field = document.getElementById('bday-field');
        if (!field) return;

        const dayInput = document.getElementById('bday_day');
        const monthInput = document.getElementById('bday_month');
        const yearInput = document.getElementById('bday_year');
        const hidden = document.getElementById('birth_date');
        const feedback = field.querySelector('.bday-feedback');
        const inputs = [dayInput, monthInput, yearInput].filter(Boolean);
        const form = els.registerForm;

        if (!dayInput || !monthInput || !yearInput || !feedback || !form) return;

        function updateBirthdayFeedback() {
            if (bdayDebounceTimer) clearTimeout(bdayDebounceTimer);

            const dayRaw = dayInput.value.trim();
            const monthRaw = monthInput.value.trim();
            const yearRaw = yearInput.value.trim();

            if (!dayRaw && !monthRaw && !yearRaw) {
                feedback.innerHTML = '';
                if (hidden) hidden.value = '';
                setBirthdayFieldState(inputs, null);
                return;
            }

            if (!dayRaw || !monthRaw || yearRaw.length < 4) {
                feedback.innerHTML = '';
                if (hidden) hidden.value = '';
                setBirthdayFieldState(inputs, null);
                return;
            }

            bdayDebounceTimer = setTimeout(() => {
                const result = validateBirthdayFields(form);
                if (result.valid) {
                    const signLabel = ZODIAC_LABELS[result.zodiac] || result.zodiac;
                    feedback.innerHTML = '<i class="ri-checkbox-circle-fill" style="color: #22c55e;"></i> <span style="color: #22c55e;">Válido — Signo: ' + signLabel + '</span>';
                    setBirthdayFieldState(inputs, 'bday-valid');
                } else {
                    feedback.innerHTML = '<i class="ri-error-warning-fill" style="color: #ef4444;"></i> <span style="color: #ef4444;">' + result.error + '</span>';
                    if (hidden) hidden.value = '';
                    setBirthdayFieldState(inputs, 'bday-invalid');
                }
            }, 250);
        }

        inputs.forEach((input) => {
            input.addEventListener('input', updateBirthdayFeedback);
            input.addEventListener('change', updateBirthdayFeedback);
        });
    }

    function initPartyRoleSelection() {
        const field = document.getElementById('party-role-field');
        const hidden = document.getElementById('party_role');
        if (!field || !hidden) return;

        const options = field.querySelectorAll('.clubber-option[data-value]');
        options.forEach((btn) => {
            btn.addEventListener('click', function() {
                const value = btn.getAttribute('data-value') || '';
                hidden.value = value;
                options.forEach((other) => {
                    const selected = other === btn;
                    other.classList.toggle('is-selected', selected);
                    other.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });
            });
        });
    }

    // ========================================================================
    // SECURITY STATES
    // ========================================================================

    /**
     * Set the security state of the page
     * @param {string} newState - 'normal', 'warning', 'danger', 'success'
     */
    function setSecurityState(newState) {
        els.body.setAttribute('data-state', newState);

        // Handle danger-specific effects
        if (newState === 'danger') {
            addDangerFlash();
            corruptVisibleText();
            startGlitchingTimestamp();
        } else {
            removeDangerFlash();
            stopGlitchingTimestamp();
        }

        // Handle success-specific effects
        if (newState === 'success') {
            playSuccessSound();
        }
    }

    function addDangerFlash() {
        if (!els.dangerFlash) {
            els.dangerFlash = document.createElement('div');
            els.dangerFlash.className = 'danger-flash';
            document.body.appendChild(els.dangerFlash);
        }
    }

    function removeDangerFlash() {
        if (els.dangerFlash) {
            els.dangerFlash.remove();
            els.dangerFlash = null;
        }
    }

    // ========================================================================
    // LOCKOUT SYSTEM
    // ========================================================================

    function initiateLockout() {
        state.isLockedOut = true;
        state.lockoutEndTime = Date.now() + (CONFIG.lockoutDuration * 1000);

        // Save to localStorage for persistence
        localStorage.setItem('apollo_lockout_end', state.lockoutEndTime);

        showNotification(CONFIG.strings.lockedOut, 'error');
        updateLockoutTimer();

        const timerInterval = setInterval(() => {
            const remaining = Math.ceil((state.lockoutEndTime - Date.now()) / 1000);

            if (remaining <= 0) {
                clearInterval(timerInterval);
                endLockout();
            } else {
                updateLockoutTimer(remaining);
            }
        }, 1000);
    }

    function updateLockoutTimer(seconds) {
        if (els.lockoutTimer) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            els.lockoutTimer.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
        }
    }

    function endLockout() {
        state.isLockedOut = false;
        state.failedAttempts = 0;
        state.lockoutEndTime = null;
        localStorage.removeItem('apollo_lockout_end');
        setSecurityState('normal');
    }

    function checkExistingLockout() {
        const savedLockout = localStorage.getItem('apollo_lockout_end');
        if (savedLockout) {
            const endTime = parseInt(savedLockout, 10);
            if (endTime > Date.now()) {
                state.lockoutEndTime = endTime;
                state.isLockedOut = true;
                setSecurityState('danger');
                initiateLockout();
            } else {
                localStorage.removeItem('apollo_lockout_end');
            }
        }
    }

    // ========================================================================
    // TEXT CORRUPTION EFFECTS
    // ========================================================================

    /**
     * Corrupt visible text elements during danger state
     */
    function corruptVisibleText() {
        const elementsToCorrupt = document.querySelectorAll('h1, h2, .logo-text .brand');
        elementsToCorrupt.forEach(el => corruptText(el));
    }

    /**
     * Apply text corruption effect to an element
     * @param {HTMLElement} element
     */
    function corruptText(element) {
        const originalText = element.textContent;
        const corruptChars = '!@#$%^&*<>/\\|{}[]01';
        let timesRun = 0;

        const corruptionInterval = setInterval(() => {
            timesRun++;
            if (timesRun > 20) {
                clearInterval(corruptionInterval);
                setTimeout(() => {
                    element.textContent = originalText;
                }, 5000);
                return;
            }

            let corruptedText = '';
            for (let i = 0; i < originalText.length; i++) {
                if (Math.random() > 0.7) {
                    corruptedText += corruptChars.charAt(Math.floor(Math.random() * corruptChars.length));
                } else {
                    corruptedText += originalText.charAt(i);
                }
            }
            element.textContent = corruptedText;
        }, 200);
    }

    /**
     * Start glitching the timestamp display
     */
    function startGlitchingTimestamp() {
        if (!els.timestamp) return;

        els.timestamp.classList.add('glitching');

        state.glitchInterval = setInterval(() => {
            const year = Math.floor(Math.random() * 50) + 2000;
            const month = Math.floor(Math.random() * 12) + 1;
            const day = Math.floor(Math.random() * 28) + 1;
            const hour = Math.floor(Math.random() * 24);
            const minute = Math.floor(Math.random() * 60);
            const second = Math.floor(Math.random() * 60);

            const glitchedDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')} ` +
                                 `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}:${second.toString().padStart(2, '0')} UTC`;
            els.timestamp.textContent = glitchedDate;
        }, 100);
    }

    function stopGlitchingTimestamp() {
        if (state.glitchInterval) {
            clearInterval(state.glitchInterval);
            state.glitchInterval = null;
        }
        if (els.timestamp) {
            els.timestamp.classList.remove('glitching');
            updateTimestamp();
        }
    }

    // ========================================================================
    // TIMESTAMP MANAGEMENT
    // ========================================================================

    function initTimestamp() {
        updateTimestamp();
        state.timestampInterval = setInterval(updateTimestamp, 1000);
    }

    function updateTimestamp() {
        if (!els.timestamp || state.glitchInterval) return;
        const now = new Date();
        els.timestamp.textContent = now.toISOString().replace('T', ' ').split('.')[0] + ' UTC';
    }

    // ========================================================================
    // APTITUDE QUIZ SYSTEM
    // ========================================================================

    function generateQuizToken() {
        if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
            const bytes = new Uint8Array(16);
            window.crypto.getRandomValues(bytes);
            return Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
        }
        return `${Date.now().toString(36)}${Math.random().toString(36).slice(2, 18)}`;
    }

    function getQuizTokenInput() {
        return els.registerForm?.querySelector('[name="apollo_quiz_token"]') || document.getElementById('apollo-quiz-token');
    }

    function ensureQuizToken() {
        if (!state.quizToken) {
            state.quizToken = generateQuizToken();
        }
        const input = getQuizTokenInput();
        if (input) {
            input.value = state.quizToken;
        }
        return state.quizToken;
    }

    function getRestBase() {
        const base = CONFIG.restUrl || '/wp-json/apollo/v1';
        return String(base).replace(/\/$/, '');
    }

    async function submitQuizStage(stage, answers) {
        const token = ensureQuizToken();
        try {
            const response = await fetch(`${getRestBase()}/quiz/submit`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ stage, answers, token })
            });
            const data = await response.json().catch(() => ({}));
            if (data.token) {
                state.quizToken = data.token;
                const input = getQuizTokenInput();
                if (input) {
                    input.value = data.token;
                }
            }
            return response.ok && data.success !== false;
        } catch (error) {
            console.warn('[Apollo Auth] quiz submit failed:', stage, error);
            return false;
        }
    }

    async function submitSimonStage(level, sequence) {
        const token = ensureQuizToken();
        try {
            const response = await fetch(`${getRestBase()}/simon/submit`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ level, sequence, success: true, token })
            });
            const data = await response.json().catch(() => ({}));
            if (data.token) {
                state.quizToken = data.token;
                const input = getQuizTokenInput();
                if (input) {
                    input.value = data.token;
                }
            }
            return response.ok && data.success !== false;
        } catch (error) {
            console.warn('[Apollo Auth] simon submit failed:', error);
            return false;
        }
    }

    /**
     * Gated layout diagnostics (?apollo_debug=1 or localStorage.apollo_auth_debug=1).
     */
    function isAuthLayoutDebug() {
        try {
            if (window.location.search.indexOf('apollo_debug=1') !== -1) {
                return true;
            }
            return localStorage.getItem('apollo_auth_debug') === '1';
        } catch (e) {
            return false;
        }
    }

    function logQuizLayout(stage, source) {
        if (!isAuthLayoutDebug()) {
            return;
        }
        requestAnimationFrame(function() {
            const card = els.authCard || document.getElementById('auth-card');
            const overlay = els.aptitudeOverlay || document.getElementById('aptitude-overlay');
            const quizScroll = document.querySelector('#auth-card .quiz-scroll');
            const testContent = document.getElementById('test-content');
            const testBtn = document.getElementById('test-btn');
            const overlayRect = overlay ? overlay.getBoundingClientRect() : null;
            const btnRect = testBtn ? testBtn.getBoundingClientRect() : null;
            let actionsVisible = null;
            if (overlayRect && btnRect) {
                actionsVisible = btnRect.bottom <= overlayRect.bottom && btnRect.top >= overlayRect.top;
            }
            console.log('[Apollo Register Layout]', source || 'quiz', {
                stage: stage,
                cardClientH: card ? card.clientHeight : null,
                cardScrollH: card ? card.scrollHeight : null,
                overlayClientH: overlay ? overlay.clientHeight : null,
                quizScrollClientH: quizScroll ? quizScroll.clientHeight : null,
                quizScrollScrollH: quizScroll ? quizScroll.scrollHeight : null,
                testContentScrollH: testContent ? testContent.scrollHeight : null,
                actionsVisible: actionsVisible,
                isQuizOpen: card ? card.classList.contains('is-quiz-open') : false
            });
        });
    }

    function resetQuizScrollTop() {
        const quizScroll = document.querySelector('#auth-card .quiz-scroll');
        if (quizScroll) {
            quizScroll.scrollTop = 0;
        }
    }

    /**
     * Open the aptitude test overlay
     */
    function openAptitudeTest() {
        ensureQuizToken();
        if (els.authCard) {
            els.authCard.classList.add('is-quiz-open');
        }
        if (els.authScrollArea) {
            els.authScrollArea.scrollTop = 0;
        }
        if (els.aptitudeOverlay) {
            els.aptitudeOverlay.classList.remove('is-complete');
        }
        els.aptitudeOverlay.classList.add('active');
        state.currentQuizStage = 1;
        updateTestProgress(1);
        runTest(1);
        logQuizLayout(1, 'openAptitudeTest');
    }

    function closeQuizShell() {
        if (els.authCard) {
            els.authCard.classList.remove('is-quiz-open');
        }
        if (els.aptitudeOverlay) {
            els.aptitudeOverlay.classList.remove('active');
        }
        if (els.authSub && state.authSubDefault) {
            els.authSub.textContent = state.authSubDefault;
        }
    }

    function whenGsapReady(fn) {
        if (typeof gsap !== 'undefined') {
            fn(gsap);
            return;
        }
        let ran = false;
        const run = function() {
            if (ran) {
                return;
            }
            ran = true;
            if (typeof gsap !== 'undefined') {
                fn(gsap);
            } else {
                fn(null);
            }
        };
        document.addEventListener('apollo:gsap-ready', function onReady() {
            document.removeEventListener('apollo:gsap-ready', onReady);
            run();
        }, { once: true });
        window.setTimeout(run, 2500);
    }

    function prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function animateQuizEnter(root, extraSelectors) {
        if (!root) {
            return;
        }
        whenGsapReady((g) => {
            if (prefersReducedMotion()) {
                return;
            }
            const items = root.querySelectorAll(
                '.quiz-title, .test-instruction, .beat-measure, .beat-option, .ethics-option, .reaction-hud, .reaction-status'
            );
            if (extraSelectors) {
                root.querySelectorAll(extraSelectors).forEach((el) => items.length);
            }
            g.from(items, {
                y: 14,
                opacity: 0,
                duration: 0.42,
                stagger: 0.05,
                ease: 'power2.out'
            });
        });
    }

    function buildBeatSlots(hitCount, total) {
        const slots = total || 4;
        let html = '';
        for (let i = 0; i < slots; i++) {
            html += `<span class="beat-slot${i < hitCount ? ' is-hit' : ''}" aria-hidden="true"></span>`;
        }
        return html;
    }

    function bindPatternOptions(container, onSelect) {
        const options = container.querySelectorAll('.pattern-option');
        options.forEach((opt) => {
            opt.addEventListener('click', function() {
                options.forEach((o) => o.classList.remove('selected'));
                this.classList.add('selected');
                if (typeof onSelect === 'function') {
                    onSelect(this);
                }
            });
        });
        return options;
    }

    function cleanupReactionTest() {
        state.reactionActive = false;
        if (state.reactionTweens && state.reactionTweens.length) {
            state.reactionTweens.forEach((tween) => {
                if (tween && typeof tween.kill === 'function') {
                    tween.kill();
                }
            });
        }
        if (state.reactionPulseTweens && state.reactionPulseTweens.length) {
            state.reactionPulseTweens.forEach((tween) => {
                if (tween && typeof tween.kill === 'function') {
                    tween.kill();
                }
            });
        }
        state.reactionTweens = [];
        state.reactionPulseTweens = [];
        document.querySelectorAll('.reaction-target').forEach((el) => el.remove());
    }

    /**
     * Run a specific test stage (4 stages: pattern → simon → ethics → reaction).
     * @param {number} stage - Test number (1-4)
     */
    function runTest(stage) {
        cleanupReactionTest();
        if (els.testContent) {
            els.testContent.classList.remove('is-reaction-stage');
        }
        state.currentQuizStage = stage;
        state.reactionSpawnAttempts = 0;
        updateTestProgress(stage);

        switch (stage) {
            case 1:
                renderPatternQuiz();
                break;
            case 2:
                renderSimonGame();
                break;
            case 3:
                renderEthicsQuiz();
                break;
            case 4:
                renderReactionTest();
                break;
            default:
                completeQuiz();
        }

        resetQuizScrollTop();
        logQuizLayout(stage, 'runTest');
    }

    function updateTestProgress(stage) {
        const total = CONFIG.quizTotalStages || 4;
        const meta = QUIZ_STAGE_META[stage] || {};
        const progressLabel = `ETAPA ${stage} DE ${total}`;
        const subLabel = meta.short
            ? `${progressLabel} · ${meta.short}`
            : progressLabel;
        if (els.testProgress) {
            els.testProgress.textContent = progressLabel;
        }
        if (els.authSub) {
            els.authSub.textContent = subLabel;
        }
    }

    function scheduleReactionSpawn() {
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                spawnReactionTarget();
            });
        });
    }

    // ========================================================================
    // TEST 1: PATTERN RECOGNITION QUIZ
    // ========================================================================

    function buildVisualDots(count) {
        let html = '';
        for (let i = 0; i < count; i += 1) {
            html += '<span class="visual-dot" aria-hidden="true"></span>';
        }
        return html;
    }

    function renderPatternQuiz() {
        const steps = [
            { count: 2, label: '1' },
            { count: 4, label: '2' },
            { count: 8, label: '3' },
            { count: 0, label: '?' }
        ];

        const stepsHtml = steps.map((step) => `
            <div class="visual-step${step.count === 0 ? ' visual-step--question' : ''}" data-tooltip="Etapa ${step.label}">
                <span class="visual-step-label">${step.label}</span>
                <div class="visual-dots">${step.count ? buildVisualDots(step.count) : '<span class="visual-dot visual-dot--ghost">?</span>'}</div>
            </div>
        `).join('');

        els.testContent.innerHTML = `
            <div class="visual-pattern-quiz">
                <h3 class="quiz-title" data-tooltip="Identifique o padrão">
                    <i class="ri-layout-grid-line" aria-hidden="true"></i>
                    PADRÃO VISUAL
                </h3>
                <p class="test-instruction">Cada etapa dobra a quantidade de pontos. Qual vem depois de 2 → 4 → 8?</p>
                <div class="visual-sequence" role="group" aria-label="Sequência visual">${stepsHtml}</div>
                <div class="beat-options pattern-options visual-options">
                    <button type="button" class="pattern-option beat-option test-option" data-value="seq-double-12" data-tooltip="Doze pontos">
                        <div class="visual-dots visual-dots--preview">${buildVisualDots(12)}</div>
                        <span class="beat-option-label">12 pontos</span>
                    </button>
                    <button type="button" class="pattern-option beat-option test-option" data-value="seq-double-14" data-tooltip="Quatorze pontos">
                        <div class="visual-dots visual-dots--preview">${buildVisualDots(14)}</div>
                        <span class="beat-option-label">14 pontos</span>
                    </button>
                    <button type="button" class="pattern-option beat-option test-option" data-value="seq-double-16" data-tooltip="Dezesseis pontos">
                        <div class="visual-dots visual-dots--preview">${buildVisualDots(16)}</div>
                        <span class="beat-option-label">16 pontos</span>
                    </button>
                    <button type="button" class="pattern-option beat-option test-option" data-value="seq-double-18" data-tooltip="Dezoito pontos">
                        <div class="visual-dots visual-dots--preview">${buildVisualDots(18)}</div>
                        <span class="beat-option-label">18 pontos</span>
                    </button>
                </div>
            </div>
        `;

        els.testBtn.style.display = 'flex';
        els.testBtnText.textContent = 'CONFIRMAR PADRÃO';
        els.testBtn.disabled = true;

        animateQuizEnter(els.testContent, '.visual-step, .visual-option');

        whenGsapReady((g) => {
            if (prefersReducedMotion()) {
                return;
            }
            g.fromTo(
                els.testContent.querySelectorAll('.visual-dot:not(.visual-dot--ghost)'),
                { scale: 0.35, opacity: 0 },
                { scale: 1, opacity: 1, duration: 0.32, stagger: 0.025, ease: 'back.out(1.8)', delay: 0.15 }
            );
        });

        const options = bindPatternOptions(els.testContent, () => {
            els.testBtn.disabled = false;
        });

        els.testBtn.onclick = function() {
            const selected = els.testContent.querySelector('.pattern-option.selected');
            if (!selected) {
                return;
            }

            const value = selected.getAttribute('data-value');
            if (value === CONFIG.strings.patternCorrect) {
                selected.classList.add('correct');
                showNotification('Padrão correto! Avançando...', 'success');
                submitQuizStage('pattern', { 1: 32, 2: 'O', 3: 'pentagon' }).finally(() => {
                    setTimeout(() => runTest(2), 900);
                });
            } else {
                selected.classList.add('wrong');
                showNotification(CONFIG.strings.quizFailed, 'error');
                setTimeout(() => {
                    options.forEach((o) => o.classList.remove('selected', 'wrong', 'correct'));
                    els.testBtn.disabled = true;
                }, 1500);
            }
        };
    }

    // ========================================================================
    // TEST 2: SIMON MEMORY GAME (+ optional genre profile before Simon)
    // ========================================================================

    function renderProfileBeforeSimon() {
        const form = els.registerForm;
        if (!form) {
            showNotification('Formulário de registro indisponível.', 'error');
            return;
        }

        const catalog = Array.isArray(CONFIG.soundsCatalog) && CONFIG.soundsCatalog.length
            ? CONFIG.soundsCatalog
            : null;
        const soundsMap = CONFIG.availableSounds && typeof CONFIG.availableSounds === 'object'
            ? CONFIG.availableSounds
            : {};

        if (!catalog && Object.keys(soundsMap).length === 0) {
            showNotification('Nenhum gênero musical disponível. Seguindo para Simon.', 'warning');
            renderSimonGame();
            return;
        }

        const selectedFromForm = Array.from(form.querySelectorAll('input[name="sounds[]"]:checked')).map(input => input.value);
        if (selectedFromForm.length > 0) {
            state.selectedSounds = selectedFromForm;
        }

        els.testContent.innerHTML = `
            <h3 class="quiz-title" data-tooltip="Perfil de pista">PERFIL DE PISTA</h3>
            <div id="quiz-genre-root"></div>
        `;

        els.testBtn.style.display = 'block';
        els.testBtnText.textContent = 'CONTINUAR PARA SIMON';

        let genreApi = null;
        const genreRoot = document.getElementById('quiz-genre-root');

        if (window.ApolloAuthGenres && typeof window.ApolloAuthGenres.mount === 'function') {
            genreApi = window.ApolloAuthGenres.mount(genreRoot);
            if (genreApi && state.selectedSounds && state.selectedSounds.length) {
                genreApi.setSelected(state.selectedSounds);
            }
            if (genreApi) {
                genreApi.onChange = updateContinueState;
            }
        } else {
            els.testContent.innerHTML += '<p class="test-instruction">Módulo de gêneros indisponível. Recarregue a página.</p>';
        }

        function updateContinueState() {
            if (!genreApi) {
                els.testBtn.disabled = true;
                return;
            }
            els.testBtn.disabled = !genreApi.isValid();
        }

        els.testBtn.onclick = function() {
            if (!genreApi || !genreApi.isValid()) {
                showNotification('Selecione pelo menos 3 gêneros musicais (máximo 5).', 'error');
                return;
            }

            const universeValue = genreApi.getUniverseValue();
            const selectedSounds = genreApi.getSelected();

            state.clubberUniverse = universeValue;
            state.selectedSounds = selectedSounds;

            const universeInput = form.querySelector('input[name="clubber_universe"]');
            if (universeInput) {
                universeInput.value = universeValue;
            }

            const soundInputs = form.querySelectorAll('input[name="sounds[]"]');
            if (soundInputs.length) {
                soundInputs.forEach(input => {
                    input.checked = selectedSounds.includes(input.value);
                });
            } else {
                selectedSounds.forEach(slug => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'sounds[]';
                    input.value = slug;
                    input.setAttribute('data-quiz-sound', '1');
                    form.appendChild(input);
                });
            }

            if (typeof genreApi.closeImmersive === 'function') {
                genreApi.closeImmersive();
            }

            renderSimonGame();
        };

        updateContinueState();
    }

    // ========================================================================
    // TEST 2B: SIMON GAME (also reachable via renderProfileBeforeSimon)
    // ========================================================================

    function renderSimonGame() {
        state.simonSequence = [];
        state.simonUserSequence = [];
        state.simonLevel = 1;

        els.testContent.innerHTML = `
            <h3 class="quiz-title" data-tooltip="Teste de memória visual">
                <i class="ri-gamepad-line" aria-hidden="true"></i>
                JOGO DA MEMÓRIA: SIMON
            </h3>
            <p class="test-instruction">Observe a sequência de cores e repita na mesma ordem. Toque nos quadrantes.</p>
            <div class="simon-machine" role="group" aria-label="Simon — máquina de memória">
                <p class="simon-level" id="simon-level" data-tooltip="Nível atual">Nível 1 de ${CONFIG.simonLevels}</p>
                <div class="simon-cabinet">
                    <button type="button" class="simon-pad simon-red" data-color="red" aria-label="Vermelho"></button>
                    <button type="button" class="simon-pad simon-green" data-color="green" aria-label="Verde"></button>
                    <button type="button" class="simon-pad simon-blue" data-color="blue" aria-label="Azul"></button>
                    <button type="button" class="simon-pad simon-yellow" data-color="yellow" aria-label="Amarelo"></button>
                    <div class="simon-hub" aria-hidden="true">
                       <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="simon-hub-logo" width="48" height="48" ><path d="M 5.3 0 L 10.675 0 L 10.675 5.3 L 5.3 5.3 L 0 5.3 L 0 10.675 L 5.3 10.675 L 5.3 5.3 L 5.3 0 Z M 0 13.325 L 5.3 13.325 L 5.3 18.624 L 10.675 18.624 L 10.675 24 L 5.3 24 L 5.3 18.624 L 0 18.624 L 0 13.325 Z M 13.325 18.624 L 18.625 18.624 L 18.625 13.325 L 24 13.325 L 24 18.624 L 18.625 18.624 L 18.625 24 L 13.325 24 L 13.325 18.624 Z M 18.625 5.3 L 24 5.3 L 24 10.675 L 18.625 10.675 L 18.625 5.3 L 13.325 5.3 L 13.325 0 L 18.625 0 L 18.625 5.3 Z M 9.312 9.312 L 14.688 9.312 L 14.688 14.688 L 9.312 14.688 L 9.312 9.312 Z"></path></svg>
                    </div>
                </div>
                <p class="simon-status" id="simon-status">Observe a sequência...</p>
            </div>
        `;

        els.testBtnText.textContent = 'AGUARDE...';
        els.testBtn.disabled = true;
        els.testBtn.style.display = 'none'; // Hide during Simon game

        const cabinet = els.testContent.querySelector('.simon-cabinet');
        if (cabinet && typeof gsap !== 'undefined' && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            gsap.fromTo(cabinet, { scale: 0.85, opacity: 0 }, { scale: 1, opacity: 1, duration: 0.6, ease: 'back.out(1.4)' });
        }

        // Initialize Simon game
        setTimeout(() => startSimonRound(), 1000);
    }

    function startSimonRound() {
        const colors = ['red', 'blue', 'green', 'yellow'];
        state.simonSequence.push(colors[Math.floor(Math.random() * colors.length)]);
        state.simonUserSequence = [];

        const statusEl = document.getElementById('simon-status');
        if (statusEl) statusEl.textContent = 'Observe a sequência...';

        // Disable buttons during playback
        disableSimonButtons(true);

        // Play the sequence
        playSimonSequence(0);
    }

    function playSimonSequence(index) {
        if (index >= state.simonSequence.length) {
            // Sequence complete, enable player input
            const statusEl = document.getElementById('simon-status');
            if (statusEl) statusEl.textContent = 'Sua vez! Repita a sequência.';
            disableSimonButtons(false);
            attachSimonListeners();
            return;
        }

        const color = state.simonSequence[index];
        const btn = document.querySelector(`.simon-pad[data-color="${color}"]`);

        setTimeout(() => {
            flashSimonButton(btn);
            setTimeout(() => playSimonSequence(index + 1), 600);
        }, 300);
    }

    function flashSimonButton(btn) {
        btn.classList.add('flash');
        playTone(btn.getAttribute('data-color'));
        setTimeout(() => btn.classList.remove('flash'), 400);
    }

    function disableSimonButtons(disabled) {
        document.querySelectorAll('.simon-pad').forEach(btn => {
            btn.style.pointerEvents = disabled ? 'none' : 'auto';
        });
    }

    function attachSimonListeners() {
        document.querySelectorAll('.simon-pad').forEach(btn => {
            btn.onclick = function() {
                const color = this.getAttribute('data-color');
                flashSimonButton(this);
                state.simonUserSequence.push(color);

                const currentIndex = state.simonUserSequence.length - 1;

                if (state.simonUserSequence[currentIndex] !== state.simonSequence[currentIndex]) {
                    // Wrong! Reset this level
                    const statusEl = document.getElementById('simon-status');
                    if (statusEl) statusEl.textContent = 'Errado! Reiniciando...';
                    showNotification('Sequência incorreta. Tente novamente.', 'error');
                    disableSimonButtons(true);

                    setTimeout(() => {
                        state.simonSequence.pop(); // Remove the last added color
                        startSimonRound(); // Restart with same length
                    }, 1500);
                    return;
                }

                if (state.simonUserSequence.length === state.simonSequence.length) {
                    // Level complete!
                    state.simonLevel++;
                    const levelEl = document.getElementById('simon-level');
                    if (levelEl) levelEl.textContent = `Nível ${state.simonLevel} de ${CONFIG.simonLevels}`;

                    if (state.simonLevel > CONFIG.simonLevels) {
                        // All levels complete!
                        const statusEl = document.getElementById('simon-status');
                        if (statusEl) statusEl.textContent = 'Excelente! Memória perfeita!';
                        showNotification('Simon completo! Avançando...', 'success');
                        submitSimonStage(CONFIG.simonLevels, state.simonSequence).finally(() => {
                            setTimeout(() => runTest(3), 1500);
                        });
                    } else {
                        const statusEl = document.getElementById('simon-status');
                        if (statusEl) statusEl.textContent = `Nível ${state.simonLevel - 1} completo!`;
                        disableSimonButtons(true);
                        setTimeout(() => startSimonRound(), 1000);
                    }
                }
            };
        });
    }

    function playTone(color) {
        // Simple audio feedback using Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            const frequencies = {
                red: 329.63,    // E4
                blue: 261.63,   // C4
                green: 392.00,  // G4
                yellow: 440.00  // A4
            };

            oscillator.frequency.value = frequencies[color] || 440;
            oscillator.type = 'sine';
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            gainNode.gain.value = 0.1;

            oscillator.start();
            setTimeout(() => {
                oscillator.stop();
                audioContext.close();
            }, 200);
        } catch (e) {
            // Audio not supported, continue silently
        }
    }

    // ========================================================================
    // TEST 3: ETHICS QUIZ (stage 3 of 4)
    // ========================================================================

    function renderEthicsQuiz() {
        els.testContent.innerHTML = `
            <h3 class="quiz-title" data-tooltip="Teste de convivência comunitária">
                <i class="ri-shield-user-line" aria-hidden="true"></i>
                TESTE DE ÉTICA E RESPEITO
            </h3>
            <p class="test-instruction">"Não gosto do estilo ou preferência de outra pessoa", logo…</p>
            <div class="pattern-options is-stack is-wide">
                <div class="pattern-option test-option is-text ethics-option" data-value="1" data-tooltip="Opção A">
                    <i class="ri-emotion-unhappy-line ethics-option-icon" aria-hidden="true"></i>
                    <span class="ethics-option-text">Critíco e não me importo</span>
                </div>
                <div class="pattern-option test-option is-text ethics-option" data-value="2" data-tooltip="Opção B">
                    <i class="ri-moon-line ethics-option-icon" aria-hidden="true"></i>
                    <span class="ethics-option-text">A depender da lua, posso hablar mal e pesar mão sobre</span>
                </div>
                <div class="pattern-option test-option is-text ethics-option" data-value="correct" data-tooltip="Opção C">
                    <i class="ri-heart-3-line ethics-option-icon" aria-hidden="true"></i>
                    <span class="ethics-option-text">É trabalho, renda, a sonoridade e arte favorita de alguem.</span>
                </div>
                <div class="pattern-option test-option is-text ethics-option" data-value="4" data-tooltip="Opção D">
                    <i class="ri-fire-line ethics-option-icon" aria-hidden="true"></i>
                    <span class="ethics-option-text">Tenho dúvidas, mas hablo mal e deixo arder.</span>
                </div>
            </div>
        `;

        els.testBtn.style.display = 'block';
        els.testBtnText.textContent = 'CONFIRMAR RESPOSTA';
        els.testBtn.disabled = true;

        animateQuizEnter(els.testContent);

        const options = bindPatternOptions(els.testContent, () => {
            els.testBtn.disabled = false;
        });

        els.testBtn.onclick = function() {
            const selected = els.testContent.querySelector('.pattern-option.selected');
            if (!selected) {
                return;
            }

            const value = selected.getAttribute('data-value');
            if (value === 'correct') {
                selected.classList.add('correct');
                showNotification('Resposta correta! Avançando...', 'success');
                submitQuizStage('ethics', { 1: 'b', 2: 'c', 3: 'b' }).finally(() => {
                    setTimeout(() => runTest(4), 900);
                });
            } else {
                selected.classList.add('wrong');
                showNotification(CONFIG.strings.quizFailed, 'error');
                setTimeout(() => {
                    options.forEach((o) => o.classList.remove('selected', 'wrong', 'correct'));
                    els.testBtn.disabled = true;
                }, 1500);
            }
        };
    }

    // ========================================================================
    // TEST 4: REACTION TEST (stage 4 of 4)
    // ========================================================================

    const REACTION_ICONS = [
        'ri-cursor-fill',
        'ri-hand-heart-fill',
        'ri-star-fill',
        'ri-flashlight-fill',
        'ri-compass-3-fill'
    ];

    function renderReactionTest() {
        cleanupReactionTest();
        state.reactionCaptures = 0;
        state.reactionActive = true;
        state.reactionSpawnAttempts = 0;

        if (els.testContent) {
            els.testContent.classList.add('is-reaction-stage');
        }

        els.testContent.innerHTML = `
            <div class="reaction-test">
                <h3 class="quiz-title" data-tooltip="Teste de reflexos">
                    <i class="ri-flashlight-fill" aria-hidden="true"></i>
                    TESTE DE REAÇÃO &amp; SINCRONIA
                </h3>
                <p class="test-instruction">Toque nos ícones em movimento antes que cruzem a pista.</p>
                <div class="reaction-hud">
                    <span>Capturas: <strong id="capture-count">0</strong> / ${CONFIG.reactionTargets}</span>
                    <span id="reaction-timer">Pronto</span>
                </div>
                <div class="reaction-arena" id="reaction-arena" role="application" aria-label="Arena de reação">
                    <div class="capture-counter">SYNC</div>
                </div>
                <p class="reaction-status" id="reaction-status">Aguardando primeiro alvo…</p>
            </div>
        `;

        els.testBtn.style.display = 'none';
        animateQuizEnter(els.testContent);

        const arena = document.getElementById('reaction-arena');
        if (arena) {
            arena.style.opacity = '1';
            whenGsapReady((g) => {
                if (!prefersReducedMotion()) {
                    g.fromTo(
                        arena,
                        { scale: 0.94, opacity: 0 },
                        { scale: 1, opacity: 1, duration: 0.45, ease: 'power2.out', clearProps: 'opacity' }
                    );
                }
            });
        }

        scheduleReactionSpawn();
    }

    function updateReactionStatus(text) {
        const statusEl = document.getElementById('reaction-status');
        if (statusEl) {
            statusEl.textContent = text;
        }
    }

    function spawnReactionTarget() {
        if (!state.reactionActive) {
            return;
        }

        if (state.reactionCaptures >= CONFIG.reactionTargets) {
            updateReactionStatus('Sincronia aprovada!');
            showNotification('Reflexos aprovados! Finalizando...', 'success');
            submitQuizStage('reaction', { completed: true }).finally(() => {
                setTimeout(() => completeQuiz(), 1000);
            });
            return;
        }

        const arena = document.getElementById('reaction-arena');
        if (!arena) {
            return;
        }

        const pad = 56;
        const rect = arena.getBoundingClientRect();
        const w = rect.width;
        const h = rect.height;

        if (w < pad + 20 || h < pad + 20) {
            state.reactionSpawnAttempts = (state.reactionSpawnAttempts || 0) + 1;
            if (state.reactionSpawnAttempts > 40) {
                console.warn('[Apollo Auth] reaction arena has no measurable size', { w: w, h: h });
                updateReactionStatus('Arena indisponível — concluindo teste automaticamente.');
                debugClientLog(
                    'apollo-auth-scripts.js:spawnReactionTarget',
                    'reaction_arena_fallback',
                    { width: w, height: h, attempts: state.reactionSpawnAttempts },
                    'H6'
                );
                state.reactionCaptures = CONFIG.reactionTargets;
                submitQuizStage('reaction', { completed: true, fallback: true }).finally(() => {
                    setTimeout(() => completeQuiz(), 600);
                });
                return;
            }
            setTimeout(spawnReactionTarget, 80);
            return;
        }

        const icon = REACTION_ICONS[Math.floor(Math.random() * REACTION_ICONS.length)];
        const target = document.createElement('button');
        target.type = 'button';
        target.className = 'reaction-target';
        target.setAttribute('aria-label', 'Capturar alvo');
        target.innerHTML = `<i class="${icon}" aria-hidden="true"></i>`;

        const side = Math.floor(Math.random() * 4);
        let startX;
        let startY;
        let endX;
        let endY;

        if (side === 0) {
            startX = Math.random() * (w - pad);
            startY = -pad;
            endX = Math.random() * (w - pad);
            endY = h + pad;
        } else if (side === 1) {
            startX = w + pad;
            startY = Math.random() * (h - pad);
            endX = -pad;
            endY = Math.random() * (h - pad);
        } else if (side === 2) {
            startX = Math.random() * (w - pad);
            startY = h + pad;
            endX = Math.random() * (w - pad);
            endY = -pad;
        } else {
            startX = -pad;
            startY = Math.random() * (h - pad);
            endX = w + pad;
            endY = Math.random() * (h - pad);
        }

        target.style.left = `${startX}px`;
        target.style.top = `${startY}px`;
        arena.appendChild(target);

        updateReactionStatus('Alvo em movimento — toque rápido!');
        const timerEl = document.getElementById('reaction-timer');
        if (timerEl) {
            timerEl.textContent = 'Em jogo';
        }

        const duration = 2.1 + Math.random() * 0.9;
        let moveTween = null;
        let pulseTween = null;

        const onMiss = () => {
            if (!target.parentNode || target.classList.contains('captured')) {
                return;
            }
            if (typeof gsap !== 'undefined') {
                gsap.to(target, {
                    opacity: 0,
                    scale: 0.4,
                    duration: 0.25,
                    onComplete: () => {
                        target.remove();
                        spawnReactionTarget();
                    }
                });
            } else {
                target.remove();
                spawnReactionTarget();
            }
        };

        target.onclick = function() {
            if (target.classList.contains('captured')) {
                return;
            }
            target.classList.add('captured');
            state.reactionCaptures += 1;

            const countEl = document.getElementById('capture-count');
            if (countEl) {
                countEl.textContent = String(state.reactionCaptures);
            }

            if (moveTween && typeof moveTween.kill === 'function') {
                moveTween.kill();
            }
            if (pulseTween && typeof pulseTween.kill === 'function') {
                pulseTween.kill();
            }

            if (typeof gsap !== 'undefined') {
                gsap.to(target, {
                    scale: 1.35,
                    opacity: 0,
                    duration: 0.28,
                    ease: 'power2.out',
                    onComplete: () => {
                        target.remove();
                        spawnReactionTarget();
                    }
                });
            } else {
                setTimeout(() => {
                    target.remove();
                    spawnReactionTarget();
                }, 200);
            }
        };

        if (typeof gsap !== 'undefined' && !prefersReducedMotion()) {
            gsap.set(target, { scale: 0 });
            gsap.to(target, { scale: 1, duration: 0.35, ease: 'back.out(1.7)' });
            pulseTween = gsap.to(target, {
                scale: 1.12,
                repeat: -1,
                yoyo: true,
                duration: 0.42,
                ease: 'sine.inOut'
            });
            moveTween = gsap.to(target, {
                left: endX,
                top: endY,
                duration,
                ease: 'none',
                onComplete: onMiss
            });
            state.reactionTweens.push(moveTween);
            state.reactionPulseTweens.push(pulseTween);
        } else {
            const dx = (endX - startX) / (duration * 60);
            const dy = (endY - startY) / (duration * 60);
            let frame = 0;
            const maxFrames = duration * 60;
            const tick = () => {
                if (!target.parentNode || target.classList.contains('captured')) {
                    return;
                }
                frame += 1;
                target.style.left = `${startX + dx * frame}px`;
                target.style.top = `${startY + dy * frame}px`;
                if (frame >= maxFrames) {
                    onMiss();
                    return;
                }
                requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        }
    }

    // ========================================================================
    // QUIZ COMPLETION
    // ========================================================================

    let finishRegistrationHandler = null;

    function persistQuizDataToForm() {
        const form = els.registerForm;
        if (!form) {
            return false;
        }

        const universeInput = form.querySelector('[name="clubber_universe"]');
        const universeValue = state.clubberUniverse || universeInput?.value || 'both';
        if (universeInput) {
            universeInput.value = universeValue;
        }

        const sounds = (state.selectedSounds && state.selectedSounds.length)
            ? state.selectedSounds
            : Array.from(form.querySelectorAll('input[name="sounds[]"]:checked')).map(input => input.value);

        form.querySelectorAll('[data-quiz-sound]').forEach(input => input.remove());

        const soundInputs = form.querySelectorAll('input[name="sounds[]"]');
        if (soundInputs.length) {
            soundInputs.forEach(input => {
                input.checked = sounds.includes(input.value);
            });
        } else if (sounds.length) {
            sounds.forEach(slug => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'sounds[]';
                input.value = slug;
                input.setAttribute('data-quiz-sound', '1');
                form.appendChild(input);
            });
        }

        const quizFlag = form.querySelector('[name="quiz_passed"]');
        if (quizFlag) {
            quizFlag.value = '1';
        }

        ensureQuizToken();

        return true;
    }

    function resetFinishRegistrationButton() {
        if (!els.testBtn) {
            return;
        }

        els.testBtn.removeAttribute('disabled');
        delete els.testBtn.dataset.submitting;

        if (els.testBtnText) {
            els.testBtnText.textContent = 'FINALIZAR REGISTRO';
        }

        updateFinishButtonConsentState();
    }

    function bindFinishRegistrationButton() {
        if (!els.testBtn) {
            return;
        }

        if (finishRegistrationHandler) {
            els.testBtn.removeEventListener('click', finishRegistrationHandler);
        }

        finishRegistrationHandler = function(e) {
            e.preventDefault();
            e.stopPropagation();
            debugClientLog(
                'apollo-auth-scripts.js:finishRegistrationHandler',
                'finish_button_click',
                { termsChecked: Boolean(document.getElementById('final-terms-accepted')?.checked) },
                'H4'
            );
            submitRegistration();
        };

        els.testBtn.style.display = 'flex';
        els.testBtn.disabled = true;
        els.testBtn.setAttribute('aria-disabled', 'true');
        els.testBtn.removeAttribute('data-tooltip');
        els.testBtn.setAttribute('aria-label', 'Finalizar registro');

        if (els.testBtnText) {
            els.testBtnText.textContent = 'FINALIZAR REGISTRO';
        }

        els.testBtn.addEventListener('click', finishRegistrationHandler);
    }

    function syncFinalConsentsToForm() {
        const form = els.registerForm;
        if (!form) {
            return { termsAccepted: false, marketingOptIn: false };
        }

        const termsCheckbox = document.getElementById('final-terms-accepted');
        const marketingCheckbox = document.getElementById('final-marketing-opt-in');
        const termsInput = form.querySelector('[name="terms_accepted"]');
        const marketingInput = form.querySelector('[name="marketing_opt_in"]');
        const termsAccepted = Boolean(termsCheckbox?.checked);
        const marketingOptIn = Boolean(marketingCheckbox?.checked);

        if (termsInput) {
            termsInput.value = termsAccepted ? '1' : '0';
        }
        if (marketingInput) {
            marketingInput.value = marketingOptIn ? '1' : '0';
        }

        return { termsAccepted, marketingOptIn };
    }

    function updateFinishButtonConsentState() {
        const termsCheckbox = document.getElementById('final-terms-accepted');
        const accepted = Boolean(termsCheckbox?.checked);

        if (els.testBtn) {
            els.testBtn.disabled = !accepted;
            if (!accepted) {
                els.testBtn.setAttribute('aria-disabled', 'true');
            } else {
                els.testBtn.removeAttribute('aria-disabled');
            }
        }

        syncFinalConsentsToForm();
    }

    function bindFinalConsentCheckboxes() {
        const termsCheckbox = document.getElementById('final-terms-accepted');
        const marketingCheckbox = document.getElementById('final-marketing-opt-in');

        termsCheckbox?.addEventListener('change', updateFinishButtonConsentState);
        marketingCheckbox?.addEventListener('change', syncFinalConsentsToForm);

        updateFinishButtonConsentState();
    }

    function completeQuiz() {
        if (els.aptitudeOverlay) {
            els.aptitudeOverlay.classList.add('is-complete');
        }

        if (els.testProgress) {
            els.testProgress.textContent = 'TESTE CONCLUÍDO';
        }

        const termsUrl = escapeAttr(String(CONFIG.termsUrl || '/termos-e-politica/'));

        els.testContent.innerHTML = `
            <div class="quiz-complete">
                <div class="quiz-complete-icon" aria-hidden="true">✓</div>
                <h3 class="quiz-title">TESTE CONCLUÍDO</h3>
                <p>Você demonstrou aptidão para participar da comunidade Apollo.</p>
            </div>
            <div class="register-security-copy">
                <h2>Difícil criar essa conta?</h2>
                <p>Queremos um espaço seguro e protegido contra fraudes e golpes.</p>
                <p>Dificultar a porta de entrada e monitorar qualquer atividade suspeita fazem parte de nossas regras por aqui.</p>
                <p><b>Viu algum movimento suspeito?</b> Só fazer seu login e chamar a gente via 'Suporte' e denúnciar tudo!</p>
                <br>
                <p>Por um apollo::rio colaborativo, seguro e diferente!</p>
            </div>
            <div class="input-group" id="register-password-field">
                <div class="input-icon-wrap">
                    <span class="input-prefix" aria-hidden="true">&gt;&nbsp;&nbsp;&nbsp;</span>
                    <input type="password" id="reg-senha-input" class="apollo-input" minlength="8" autocomplete="new-password" placeholder=" " required>
                    <label class="apollo-label" for="reg-senha-input">Crie sua chave de acesso <span class="req-mark">*</span></label>
                </div>
            </div>
            <div class="ethics-terms register-final-consents">
                <label class="custom-checkbox">
                    <input type="checkbox" id="final-terms-accepted" name="final_terms_accepted" required>
                    <span class="checkmark"></span>
                    <span>
                        Li e aceito o
                        <a href="${termsUrl}" target="_blank" rel="noopener noreferrer">
                            Protocolo de Convivência, Termos e Política de Privacidade
                        </a>.
                    </span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="final-marketing-opt-in" name="final_marketing_opt_in">
                    <span class="checkmark"></span>
                    <span>Quero ser avisado se algum evento que confirmei presença soltar nova informação <small style="opacity:.5">(Adiado / Nova locação / Cancelado / Atrações / Timetable / Sold-out ...)</small></span>
                </label>
            </div>
        `;

        bindFinalConsentCheckboxes();
        bindFinishRegistrationButton();

        debugClientLog('apollo-auth-scripts.js:completeQuiz', 'quiz_complete_ui', {}, 'H6');

        requestAnimationFrame(() => {
            const quizScroll = document.querySelector('#auth-card .quiz-scroll');
            const actions = document.querySelector('#auth-card .quiz-actions');
            if (actions) {
                actions.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else if (quizScroll) {
                quizScroll.scrollTop = quizScroll.scrollHeight;
            }
            els.testBtn?.focus({ preventScroll: true });
        });
    }

    function initVerifyEmailEntrance() {
        if (document.body?.dataset.authPage !== 'verify-email') {
            return;
        }

        if (!sessionStorage.getItem('apollo_auth_email_flow')) {
            return;
        }

        sessionStorage.removeItem('apollo_auth_email_flow');
        const card = els.authCard || document.getElementById('auth-card');
        if (!card) {
            return;
        }

        card.classList.add('auth-card--verify-enter');

        if (prefersReducedMotion()) {
            return;
        }

        whenGsapReady((g) => {
            g.fromTo(card, { y: 28, scale: 0.94, opacity: 0 }, {
                y: 0,
                scale: 1,
                opacity: 1,
                duration: 0.65,
                ease: 'power3.out'
            });
        });
    }

    function playEmailSendTransition(emailSent, redirectUrl, devVerifyUrl) {
        return new Promise((resolve) => {
            const redirect = redirectUrl || CONFIG.verifyEmailUrl || '/verificar-email/?pending=1';
            let flow = document.getElementById('auth-email-flow');

            if (!flow) {
                flow = document.createElement('div');
                flow.id = 'auth-email-flow';
                flow.className = 'auth-email-flow';
                flow.innerHTML = `
                    <div class="auth-email-flow__backdrop" aria-hidden="true"></div>
                    <div class="auth-email-flow__pane" role="status" aria-live="polite">
                        <div class="auth-email-flow__icon"><i class="ri-mail-send-line" aria-hidden="true"></i></div>
                        <h2 class="auth-email-flow__title">Enviando confirmação…</h2>
                        <p class="auth-email-flow__sub"></p>
                        <p class="auth-email-flow__dev-link" style="display:none;margin-top:12px;"></p>
                        <div class="auth-email-flow__trail" aria-hidden="true"><span></span><span></span><span></span></div>
                    </div>`;
                document.body.appendChild(flow);
            }

            const sub = flow.querySelector('.auth-email-flow__sub');
            const devLink = flow.querySelector('.auth-email-flow__dev-link');
            if (sub) {
                sub.textContent = emailSent
                    ? (devVerifyUrl
                        ? 'Ambiente local: use o link abaixo (Mailpit, não Gmail).'
                        : 'Verifique sua caixa de entrada e a pasta de spam.')
                    : 'Cadastro salvo — use o reenvio na próxima tela.';
            }
            if (devLink) {
                if (devVerifyUrl) {
                    devLink.innerHTML = '<a href="' + devVerifyUrl + '" class="link-orange">Confirmar cadastro (link local)</a>';
                    devLink.style.display = 'block';
                } else {
                    devLink.style.display = 'none';
                    devLink.textContent = '';
                }
            }

            const finish = () => {
                sessionStorage.setItem('apollo_auth_email_flow', '1');
                if (devVerifyUrl) {
                    sessionStorage.setItem('apollo_dev_verify_url', devVerifyUrl);
                }
                resolve(redirect);
            };

            if (prefersReducedMotion()) {
                flow.classList.add('is-visible');
                setTimeout(finish, devVerifyUrl ? 4000 : 1200);
                return;
            }

            whenGsapReady((g) => {
                if (!g) {
                    flow.classList.add('is-visible');
                    setTimeout(finish, 1200);
                    return;
                }
                document.body.classList.add('auth-email-flow-active');
                const pane = flow.querySelector('.auth-email-flow__pane');
                const card = els.authCard;
                const overlay = els.aptitudeOverlay;
                const trails = flow.querySelectorAll('.auth-email-flow__trail span');

                g.set(flow, { display: 'flex', opacity: 0 });
                g.set(pane, { scale: 0.86, y: 36, opacity: 0 });
                g.set(trails, { scaleX: 0, transformOrigin: 'left center' });

                const tl = g.timeline({ onComplete: () => setTimeout(finish, devVerifyUrl ? 3500 : 700) });
                tl.to(flow, { opacity: 1, duration: 0.35, ease: 'power2.out' })
                    .to(pane, { scale: 1, y: 0, opacity: 1, duration: 0.55, ease: 'power3.out' }, 0.08)
                    .to(trails, { scaleX: 1, duration: 0.45, stagger: 0.07, ease: 'power2.out' }, 0.25);

                if (card) {
                    tl.to(card, { scale: 0.97, opacity: 0.35, duration: 0.45, ease: 'power2.inOut' }, 0);
                }
                if (overlay) {
                    tl.to(overlay, { opacity: 0.2, duration: 0.4, ease: 'power2.inOut' }, 0);
                }

                const icon = flow.querySelector('.auth-email-flow__icon');
                if (icon) {
                    tl.fromTo(icon, { y: -8, rotate: -12 }, { y: 0, rotate: 0, duration: 0.5, ease: 'back.out(1.7)' }, 0.15);
                }
            });
        });
    }

    async function submitRegistration() {
        const form = els.registerForm;
        if (!form) {
            debugClientLog('apollo-auth-scripts.js:submitRegistration', 'abort_no_form', {}, 'H4');
            showNotification('Formulário de registro não encontrado. Recarregue a página.', 'error');
            return;
        }

        if (els.testBtn?.dataset.submitting === '1') {
            debugClientLog('apollo-auth-scripts.js:submitRegistration', 'abort_already_submitting', {}, 'H4');
            return;
        }

        if (!persistQuizDataToForm()) {
            debugClientLog('apollo-auth-scripts.js:submitRegistration', 'abort_quiz_persist', {}, 'H4');
            showNotification('Erro ao preparar dados do quiz. Tente novamente.', 'error');
            return;
        }

        const consents = syncFinalConsentsToForm();
        if (!consents.termsAccepted) {
            debugClientLog('apollo-auth-scripts.js:submitRegistration', 'abort_terms', {}, 'H4');
            showNotification('Para finalizar, aceite os Termos e a Política de Privacidade.', 'warning');
            updateFinishButtonConsentState();
            return;
        }

        syncDocumentFieldsToForm();

        const birthValidation = validateBirthdayFields(form);
        if (!birthValidation.valid) {
            showNotification(birthValidation.error || 'Data de nascimento inválida.', 'error');
            if (els.testBtn) {
                els.testBtn.disabled = false;
                delete els.testBtn.dataset.submitting;
                if (els.testBtnText) {
                    els.testBtnText.textContent = 'FINALIZAR REGISTRO';
                }
            }
            return;
        }

        const partyRole = form.querySelector('[name="party_role"]')?.value;
        if (!partyRole) {
            showNotification('Selecione como você é em uma festa.', 'error');
            if (els.testBtn) {
                els.testBtn.disabled = false;
                delete els.testBtn.dataset.submitting;
                if (els.testBtnText) els.testBtnText.textContent = 'FINALIZAR REGISTRO';
            }
            return;
        }

        const passwordInput = document.getElementById('reg-senha-input');
        const password = passwordInput?.value || '';
        if (!password || password.length < 8) {
            showNotification('Senha deve ter pelo menos 8 caracteres.', 'error');
            passwordInput?.focus();
            if (els.testBtn) {
                els.testBtn.disabled = false;
                delete els.testBtn.dataset.submitting;
                if (els.testBtnText) els.testBtnText.textContent = 'FINALIZAR REGISTRO';
            }
            return;
        }

        const senhaHidden = form.querySelector('[name="senha"]');
        if (senhaHidden) senhaHidden.value = password;

        const phoneOk = window.ApolloPhoneVerify?.isVerified?.() || form.querySelector('[name="phone_verified"]')?.value === '1';
        if (!phoneOk) {
            showNotification('Telefone não verificado. Volte e confirme via Telegram.', 'error');
            if (els.testBtn) {
                els.testBtn.disabled = false;
                delete els.testBtn.dataset.submitting;
                if (els.testBtnText) els.testBtnText.textContent = 'FINALIZAR REGISTRO';
            }
            return;
        }

        if (els.testBtn) {
            els.testBtn.disabled = true;
            els.testBtn.dataset.submitting = '1';
            if (els.testBtnText) {
                els.testBtnText.textContent = 'PROCESSANDO...';
            }
        }

        showNotification('Processando cadastro...', 'info');

        try {
            const formData = new FormData(form);
            formData.append('action', 'apollo_register');
            formData.append('nonce', CONFIG.nonce);
            formData.set('quiz_passed', '1');
            formData.set('apollo_quiz_token', ensureQuizToken());
            formData.set('terms_accepted', consents.termsAccepted ? '1' : '0');
            formData.set('marketing_opt_in', consents.marketingOptIn ? '1' : '0');
            formData.set('birth_date', birthValidation.date);
            formData.set('party_role', partyRole);
            formData.set('senha', password);
            formData.set('phone', form.querySelector('[name="phone"]')?.value || '');
            formData.set('phone_request_id', form.querySelector('[name="phone_request_id"]')?.value || '');
            formData.set('phone_verified', form.querySelector('[name="phone_verified"]')?.value || '0');

            const response = await fetch(CONFIG.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const responseText = await response.text();
            let result = null;
            try {
                result = responseText ? JSON.parse(responseText) : null;
            } catch (parseError) {
                console.error('Registration JSON parse error:', parseError, responseText);
                throw new Error('invalid_json');
            }

            if (!result) {
                throw new Error('server_error');
            }

            if (result.success) {
                const emailSent = result.data?.email_sent !== false;
                const redirect = result.data?.redirect
                    || (CONFIG.verifyEmailUrl || '/verificar-email/?pending=1');
                const emailMsg = result.data?.message
                    || (emailSent
                        ? 'Cadastro realizado! Verifique seu e-mail para confirmar a conta.'
                        : 'Cadastro realizado — reenvie o e-mail na próxima tela.');

                if (els.testBtnText) {
                    els.testBtnText.textContent = emailSent ? 'E-MAIL ENVIADO' : 'CADASTRO OK';
                }

                showNotification(emailMsg, emailSent ? 'success' : 'warning');

                const devVerifyUrl = result.data?.dev_verify_url || '';
                if (result.data?.local_mail_hint) {
                    showNotification(result.data.local_mail_hint, 'info');
                }

                await playEmailSendTransition(emailSent, redirect, devVerifyUrl);
                window.location.href = redirect;
            } else {
                resetFinishRegistrationButton();

                const msg = result.data?.message || 'Erro ao processar cadastro.';
                showNotification(msg, 'error');

                if (result.data?.action_url) {
                    const code = result.data?.email_conflict_code || '';
                    const hint = code === 'email_taken_unverified'
                        ? 'Abra /verificar-email/ para reenviar o link de confirmação.'
                        : 'Use /acesso/ para entrar ou recuperar sua senha.';
                    showNotification(hint, 'warning');
                }

                if (result.data?.errors && Array.isArray(result.data.errors)) {
                    result.data.errors.forEach(err => {
                        showNotification(err, 'error');
                    });
                }
            }
        } catch (error) {
            console.error('Registration error:', error);
            resetFinishRegistrationButton();
            const msg = error.message === 'invalid_json' || error.message === 'server_error'
                ? 'Erro no servidor ao finalizar cadastro. Tente novamente em instantes.'
                : (error.message || 'Erro ao processar cadastro. Tente novamente.');
            showNotification(msg, 'error');
        }
    }

    // ========================================================================
    // LOGIN ANTI-AUTOFILL + PASSWORD VISIBILITY
    // ========================================================================

    function initLoginAntiAutofill() {
        const logInput = document.getElementById('log');
        const pwdInput = document.getElementById('pwd');
        if (!logInput && !pwdInput) {
            return;
        }

        const markEdited = (input) => {
            if (input) {
                input.dataset.apolloUserEdited = '1';
            }
        };

        logInput?.addEventListener('input', () => markEdited(logInput));
        logInput?.addEventListener('keydown', () => markEdited(logInput));
        pwdInput?.addEventListener('input', () => markEdited(pwdInput));
        pwdInput?.addEventListener('keydown', () => markEdited(pwdInput));

        const scrubAutofill = () => {
            if (logInput && logInput.dataset.apolloUserEdited !== '1') {
                logInput.value = '';
            }
            if (pwdInput && pwdInput.dataset.apolloUserEdited !== '1') {
                pwdInput.value = '';
            }
        };

        scrubAutofill();
        requestAnimationFrame(scrubAutofill);
        [50, 250, 750, 1500].forEach((ms) => window.setTimeout(scrubAutofill, ms));
    }

    function initPasswordToggle() {
        const toggle = document.getElementById('pwd-toggle');
        const pwdInput = document.getElementById('pwd');
        if (!toggle || !pwdInput) {
            return;
        }

        toggle.addEventListener('click', function() {
            const reveal = pwdInput.type === 'password';
            pwdInput.type = reveal ? 'text' : 'password';
            const icon = toggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('ri-eye-line', !reveal);
                icon.classList.toggle('ri-eye-off-line', reveal);
            }
            toggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
            toggle.setAttribute(
                'aria-label',
                reveal ? 'Ocultar senha' : 'Mostrar senha'
            );
        });
    }

    // ========================================================================
    // INSTAGRAM FIELD SETUP
    // ========================================================================

    function initInstagramField() {
        const igInput = document.querySelector('input[name="instagram"]');
        if (igInput) {
            igInput.addEventListener('input', function() {
                // Remove @ if user types it
                if (this.value.startsWith('@')) {
                    this.value = this.value.substring(1);
                }
            });
        }
    }

    // ========================================================================
    // SOUNDS VALIDATION
    // ========================================================================

    function initSoundsValidation() {
        const soundsContainer = document.querySelector('.sounds-chips');
        if (!soundsContainer) return;

        const chips = soundsContainer.querySelectorAll('.quiz-chip');
        chips.forEach(chip => {
            chip.addEventListener('click', function() {
                this.classList.toggle('selected');
                // Update hidden input or data attribute
                updateSelectedSounds();
            });
        });
    }

    function updateSelectedSounds() {
        const selected = document.querySelectorAll('.sounds-chips .quiz-chip.selected');
        const hiddenInput = document.querySelector('input[name="sounds"]');
        if (hiddenInput) {
            hiddenInput.value = Array.from(selected).map(c => c.dataset.value).join(',');
        }
    }

    // ========================================================================
    // UTILITY FUNCTIONS
    // ========================================================================

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escapeAttr(value) {
        return escapeHtml(value);
    }

    function showNotification(message, type = 'info') {
        const area = document.querySelector('.notification-area') || createNotificationArea();

        const alert = document.createElement('div');
        alert.className = `auth-alert auth-alert-${type}`;
        alert.textContent = message;

        area.appendChild(alert);

        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 4000);
    }

    function createNotificationArea() {
        const area = document.createElement('div');
        area.className = 'notification-area';
        document.querySelector('.terminal-wrapper').appendChild(area);
        return area;
    }

    function shakeElement(element) {
        element.classList.add('shake');
        setTimeout(() => element.classList.remove('shake'), 500);
    }

    function playSuccessSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.frequency.value = 523.25; // C5
            oscillator.type = 'sine';
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            gainNode.gain.value = 0.08;

            oscillator.start();
            setTimeout(() => {
                oscillator.frequency.value = 659.25; // E5
                setTimeout(() => {
                    oscillator.frequency.value = 783.99; // G5
                    setTimeout(() => {
                        oscillator.stop();
                        audioContext.close();
                    }, 150);
                }, 150);
            }, 150);
        } catch (e) {
            // Audio not supported
        }
    }

    // ========================================================================
    // EXPOSE GLOBAL FUNCTIONS (for PHP integration)
    // ========================================================================

    window.ApolloAuth = {
        setSecurityState: setSecurityState,
        showNotification: showNotification,
        validateCPF: validateCPF,
        openAptitudeTest: openAptitudeTest,
        closeQuizShell: closeQuizShell
    };

    window.showNotification = showNotification;

})();

