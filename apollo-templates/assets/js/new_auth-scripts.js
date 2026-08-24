/**
 * ================================================================================
 * APOLLO AUTH SCRIPTS - Main JavaScript
 * ================================================================================
 * Complete authentication logic including:
 * - Security state management (normal, warning, danger, success)
 * - Login/Register form handling
 * - Aptitude Quiz System (Pattern, Simon, Ethics, Reaction)
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
        strings: {
            loginSuccess: 'Acesso autorizado. Redirecionando...',
            loginFailed: 'Credenciais incorretas. Tente novamente.',
            warningState: 'Atenção: última tentativa antes do bloqueio.',
            lockedOut: 'Sistema bloqueado por segurança.',
            quizComplete: 'Teste de aptidão concluído com sucesso!',
            quizFailed: 'Resposta incorreta. Reiniciando pergunta...',
            patternCorrect: '♫♫♫',
            ethicsCorrect: 'É trabalho, renda, a sonoridade e arte favorita de alguem.'
        }
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
        timestampInterval: null,
        glitchInterval: null
    };

    // DOM Elements cache
    let els = {};

    // ========================================================================
    // INITIALIZATION
    // ========================================================================
    
    document.addEventListener('DOMContentLoaded', function() {
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
            switchToLogin: document.getElementById('switch-to-login')
        };

        // Initialize components
        initToggles();
        initFormSwitching();
        initForms();
        initTimestamp();
        initInstagramField();
        initSoundsValidation();

        // Check for existing lockout
        checkExistingLockout();
    });

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
                els.loginSection.classList.add('hidden');
                els.registerSection.classList.remove('hidden');
            });
        }

        if (els.switchToLogin) {
            els.switchToLogin.addEventListener('click', function(e) {
                e.preventDefault();
                els.registerSection.classList.add('hidden');
                els.loginSection.classList.remove('hidden');
            });
        }
    }

    // ========================================================================
    // FORM SUBMISSION HANDLERS
    // ========================================================================
    
    function initForms() {
        if (els.loginForm) {
            els.loginForm.addEventListener('submit', handleLogin);
        }
        if (els.registerForm) {
            els.registerForm.addEventListener('submit', handleRegister);
        }
    }

    /**
     * Handle login form submission
     * Manages security states based on failed attempts
     */
    async function handleLogin(e) {
        e.preventDefault();

        if (state.isLockedOut) {
            shakeElement(els.loginForm);
            return;
        }

        const form = e.target;
        const username = form.querySelector('[name="log"]')?.value || form.querySelector('[name="username"]')?.value;
        const password = form.querySelector('[name="pwd"]')?.value || form.querySelector('[name="password"]')?.value;
        const submitBtn = form.querySelector('button[type="submit"]');

        if (!username || !password) {
            showNotification('Preencha todos os campos.', 'warning');
            return;
        }

        // Disable button during request
        submitBtn.disabled = true;
        submitBtn.textContent = 'VERIFICANDO...';

        try {
            // PHP: Replace with actual AJAX call
            // const response = await fetch(CONFIG.ajaxUrl, {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            //     body: new URLSearchParams({
            //         action: 'apollo_login',
            //         nonce: CONFIG.nonce,
            //         username: username,
            //         password: password
            //     })
            // });
            // const result = await response.json();

            // SIMULATED: Remove in production
            const success = await simulateLoginCheck(username, password);

            if (success) {
                handleLoginSuccess();
            } else {
                handleLoginFailure();
            }
        } catch (error) {
            console.error('Login error:', error);
            showNotification('Erro de conexão. Tente novamente.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'ACESSAR TERMINAL';
        }
    }

    /**
     * Simulated login check (REMOVE IN PRODUCTION)
     */
    function simulateLoginCheck(username, password) {
        return new Promise((resolve) => {
            setTimeout(() => {
                // Simulate: any user with password 'apollo123' works
                resolve(password === 'apollo123');
            }, 800);
        });
    }

    /**
     * Handle successful login
     */
    function handleLoginSuccess() {
        state.failedAttempts = 0;
        setSecurityState('success');
        showNotification(CONFIG.strings.loginSuccess, 'success');

        // Redirect after animation
        setTimeout(() => {
            window.location.href = CONFIG.redirectAfterLogin;
        }, 1500);
    }

    /**
     * Handle failed login attempt
     */
    function handleLoginFailure() {
        state.failedAttempts++;
        shakeElement(els.loginForm);

        if (state.failedAttempts >= CONFIG.maxFailedAttempts) {
            // LOCKOUT - Danger state
            setSecurityState('danger');
            initiateLockout();
        } else if (state.failedAttempts === CONFIG.maxFailedAttempts - 1) {
            // WARNING state
            setSecurityState('warning');
            showNotification(CONFIG.strings.warningState, 'warning');
        } else {
            showNotification(CONFIG.strings.loginFailed, 'error');
        }
    }

    /**
     * Handle registration form submission
     */
    async function handleRegister(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);

        // Validate required fields
        const requiredFields = ['nome', 'email', 'senha'];
        let isValid = true;

        requiredFields.forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            if (!input || !input.value.trim()) {
                isValid = false;
                if (input) input.classList.add('error');
            }
        });

        // Validate document type
        const docType = form.querySelector('[name="doc_type"]')?.value;
        if (docType === 'cpf') {
            const cpf = form.querySelector('[name="cpf"]')?.value;
            if (!cpf || !validateCPF(cpf)) {
                isValid = false;
                showNotification('CPF inválido.', 'error');
                return;
            }
        } else if (docType === 'passport') {
            const passport = form.querySelector('[name="passport"]')?.value;
            if (!passport || passport.length < 5) {
                isValid = false;
                showNotification('Número de passaporte inválido.', 'error');
                return;
            }
        }

        // Validate sounds selection (at least 1)
        const soundsSelected = form.querySelectorAll('[name="sounds[]"]:checked');
        if (soundsSelected.length === 0) {
            isValid = false;
            showNotification('Selecione pelo menos 1 gênero musical.', 'error');
            return;
        }

        // Validate terms
        const termsToggle = form.querySelector('.terms-toggle');
        if (!termsToggle || !termsToggle.classList.contains('active')) {
            isValid = false;
            showNotification('Você deve aceitar os termos de uso.', 'error');
            return;
        }

        if (!isValid) {
            shakeElement(form);
            return;
        }

        // Open aptitude test
        openAptitudeTest();
    }

    // ========================================================================
    // CPF VALIDATION
    // ========================================================================
    
    /**
     * Validate Brazilian CPF
     * @param {string} cpf - CPF string (with or without formatting)
     * @returns {boolean}
     */
    function validateCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        
        if (cpf.length !== 11) return false;
        if (/^(\d)\1{10}$/.test(cpf)) return false;

        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += parseInt(cpf[i]) * (10 - i);
        }
        let d1 = (sum % 11 < 2) ? 0 : 11 - (sum % 11);
        if (parseInt(cpf[9]) !== d1) return false;

        sum = 0;
        for (let i = 0; i < 10; i++) {
            sum += parseInt(cpf[i]) * (11 - i);
        }
        let d2 = (sum % 11 < 2) ? 0 : 11 - (sum % 11);
        return parseInt(cpf[10]) === d2;
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
    
    /**
     * Open the aptitude test overlay
     */
    function openAptitudeTest() {
        els.aptitudeOverlay.classList.add('active');
        state.currentQuizStage = 1;
        runTest(1);
    }

    /**
     * Run a specific test stage
     * @param {number} stage - Test number (1-4)
     */
    function runTest(stage) {
        state.currentQuizStage = stage;
        updateTestProgress(stage);

        switch(stage) {
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
    }

    function updateTestProgress(stage) {
        if (els.testProgress) {
            els.testProgress.textContent = `TESTE ${stage} DE 4`;
        }
    }

    // ========================================================================
    // TEST 1: PATTERN RECOGNITION QUIZ
    // ========================================================================
    
    function renderPatternQuiz() {
        els.testContent.innerHTML = `
            <h3 data-tooltip="Identifique o padrão musical">RECONHECIMENTO DE PADRÕES RÍTMICOS</h3>
            <p class="test-instruction">Identifique o próximo padrão de batida na sequência.</p>
            <div class="pattern-sequence">
                <div class="pattern-item" data-tooltip="Primeiro item">♪</div>
                <div class="pattern-item" data-tooltip="Segundo item">♫</div>
                <div class="pattern-item" data-tooltip="Terceiro item">♪♪</div>
                <div class="pattern-item" data-tooltip="Quarto item">♫♫</div>
                <div class="pattern-item pattern-question" data-tooltip="Qual é o próximo?">?</div>
            </div>
            <div class="pattern-options">
                <div class="pattern-option test-option" data-value="♪♪♪" data-tooltip="Opção A">♪♪♪</div>
                <div class="pattern-option test-option" data-value="♪♪♫" data-tooltip="Opção B">♪♪♫</div>
                <div class="pattern-option test-option" data-value="♫♫♫" data-tooltip="Opção C - Correta">♫♫♫</div>
                <div class="pattern-option test-option" data-value="♫♪♫" data-tooltip="Opção D">♫♪♫</div>
            </div>
        `;

        els.testBtnText.textContent = 'CONFIRMAR PADRÃO';
        els.testBtn.disabled = true;

        // Add click handlers
        const options = els.testContent.querySelectorAll('.pattern-option');
        options.forEach(opt => {
            opt.addEventListener('click', function() {
                options.forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                els.testBtn.disabled = false;
            });
        });

        // Set up confirm button
        els.testBtn.onclick = function() {
            const selected = els.testContent.querySelector('.pattern-option.selected');
            if (!selected) return;

            const value = selected.getAttribute('data-value');
            if (value === CONFIG.strings.patternCorrect) {
                selected.classList.add('correct');
                showNotification('Padrão correto! Avançando...', 'success');
                setTimeout(() => runTest(2), 1000);
            } else {
                selected.classList.add('wrong');
                showNotification(CONFIG.strings.quizFailed, 'error');
                setTimeout(() => {
                    options.forEach(o => o.classList.remove('selected', 'wrong'));
                    els.testBtn.disabled = true;
                }, 1500);
            }
        };
    }

    // ========================================================================
    // TEST 2: SIMON GAME
    // ========================================================================
    
    function renderSimonGame() {
        state.simonSequence = [];
        state.simonUserSequence = [];
        state.simonLevel = 1;

        els.testContent.innerHTML = `
            <h3 data-tooltip="Teste de memória visual">JOGO DA MEMÓRIA: SIMON</h3>
            <p class="test-instruction">Memorize e repita a sequência de cores.</p>
            <p class="simon-level" id="simon-level" data-tooltip="Nível atual">Nível: 1 de ${CONFIG.simonLevels}</p>
            <div class="simon-container">
                <div class="simon-btn simon-red" data-color="red" data-tooltip="Vermelho"></div>
                <div class="simon-btn simon-blue" data-color="blue" data-tooltip="Azul"></div>
                <div class="simon-btn simon-green" data-color="green" data-tooltip="Verde"></div>
                <div class="simon-btn simon-yellow" data-color="yellow" data-tooltip="Amarelo"></div>
            </div>
            <p class="simon-status" id="simon-status" style="margin-top: 16px; font-size: 12px; color: rgba(148,163,184,0.9);">
                Observe a sequência...
            </p>
        `;

        els.testBtnText.textContent = 'AGUARDE...';
        els.testBtn.disabled = true;
        els.testBtn.style.display = 'none'; // Hide during Simon game

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
        const btn = document.querySelector(`.simon-btn[data-color="${color}"]`);
        
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
        document.querySelectorAll('.simon-btn').forEach(btn => {
            btn.style.pointerEvents = disabled ? 'none' : 'auto';
        });
    }

    function attachSimonListeners() {
        document.querySelectorAll('.simon-btn').forEach(btn => {
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
                    if (levelEl) levelEl.textContent = `Nível: ${state.simonLevel} de ${CONFIG.simonLevels}`;

                    if (state.simonLevel > CONFIG.simonLevels) {
                        // All levels complete!
                        const statusEl = document.getElementById('simon-status');
                        if (statusEl) statusEl.textContent = 'Excelente! Memória perfeita!';
                        showNotification('Simon completo! Avançando...', 'success');
                        setTimeout(() => runTest(3), 1500);
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
    // TEST 3: ETHICS QUIZ
    // ========================================================================
    
    function renderEthicsQuiz() {
        els.testContent.innerHTML = `
            <h3 data-tooltip="Teste de convivência comunitária">TESTE DE ÉTICA E RESPEITO</h3>
            <p class="test-instruction">"Não gosto de Eletrônica com Funk / de Tribal / de Techno / de Melódico", logo..</p>
            <div class="pattern-options" style="grid-template-columns: 1fr; max-width: 100%;">
                <div class="pattern-option test-option" data-value="1" data-tooltip="Opção A" style="font-size: 13px; padding: 12px;">
                    Critíco e não me importo
                </div>
                <div class="pattern-option test-option" data-value="2" data-tooltip="Opção B" style="font-size: 13px; padding: 12px;">
                    A depender da lua, posso hablar mal e pesar mão sobre
                </div>
                <div class="pattern-option test-option" data-value="correct" data-tooltip="Opção C" style="font-size: 13px; padding: 12px;">
                    É trabalho, renda, a sonoridade e arte favorita de alguem.
                </div>
                <div class="pattern-option test-option" data-value="4" data-tooltip="Opção D" style="font-size: 13px; padding: 12px;">
                    Tenho dúvidas, mas hablo mal e deixo arder.
                </div>
            </div>
        `;

        els.testBtn.style.display = 'block';
        els.testBtnText.textContent = 'CONFIRMAR RESPOSTA';
        els.testBtn.disabled = true;

        const options = els.testContent.querySelectorAll('.pattern-option');
        options.forEach(opt => {
            opt.addEventListener('click', function() {
                options.forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                els.testBtn.disabled = false;
            });
        });

        els.testBtn.onclick = function() {
            const selected = els.testContent.querySelector('.pattern-option.selected');
            if (!selected) return;

            const value = selected.getAttribute('data-value');
            if (value === 'correct') {
                selected.classList.add('correct');
                showNotification('Resposta correta! Avançando...', 'success');
                setTimeout(() => runTest(4), 1000);
            } else {
                selected.classList.add('wrong');
                showNotification(CONFIG.strings.quizFailed, 'error');
                setTimeout(() => {
                    options.forEach(o => o.classList.remove('selected', 'wrong'));
                    els.testBtn.disabled = true;
                }, 1500);
            }
        };
    }

    // ========================================================================
    // TEST 4: REACTION TEST
    // ========================================================================
    
    function renderReactionTest() {
        state.reactionCaptures = 0;

        els.testContent.innerHTML = `
            <h3 data-tooltip="Teste de reflexos">TESTE DE REAÇÃO & SINCRONIA</h3>
            <p class="test-instruction">Toque nos ícones de som piscando antes que desapareçam.</p>
            <div class="reaction-arena" id="reaction-arena">
                <div class="capture-counter">CAPTURAS: <span id="capture-count">0</span>/${CONFIG.reactionTargets}</div>
            </div>
        `;

        els.testBtn.style.display = 'none';

        // Start spawning targets
        spawnReactionTarget();
    }

    function spawnReactionTarget() {
        if (state.reactionCaptures >= CONFIG.reactionTargets) {
            // Test complete!
            showNotification('Reflexos aprovados! Finalizando...', 'success');
            setTimeout(() => completeQuiz(), 1000);
            return;
        }

        const arena = document.getElementById('reaction-arena');
        if (!arena) return;

        const target = document.createElement('div');
        target.className = 'reaction-target';
        target.innerHTML = '♪';
        target.setAttribute('data-tooltip', 'Clique rápido!');

        // Random position within arena
        const maxX = arena.offsetWidth - 60;
        const maxY = arena.offsetHeight - 60;
        target.style.left = (Math.random() * maxX) + 'px';
        target.style.top = (Math.random() * maxY + 30) + 'px';

        target.onclick = function() {
            this.classList.add('captured');
            state.reactionCaptures++;
            const countEl = document.getElementById('capture-count');
            if (countEl) countEl.textContent = state.reactionCaptures;

            setTimeout(() => {
                this.remove();
                spawnReactionTarget();
            }, 300);
        };

        arena.appendChild(target);

        // Target disappears after 2 seconds if not clicked
        setTimeout(() => {
            if (target.parentNode && !target.classList.contains('captured')) {
                target.remove();
                spawnReactionTarget();
            }
        }, 2000);
    }

    // ========================================================================
    // QUIZ COMPLETION
    // ========================================================================
    
    function completeQuiz() {
        els.testContent.innerHTML = `
            <div style="text-align: center; padding: 40px 20px;">
                <div style="font-size: 48px; margin-bottom: 20px;">✓</div>
                <h3 style="color: var(--color-success);">TESTE CONCLUÍDO</h3>
                <p style="margin-top: 12px; color: rgba(148,163,184,0.9);">
                    Você demonstrou aptidão para participar da comunidade Apollo.
                </p>
            </div>
        `;

        els.testBtn.style.display = 'block';
        els.testBtnText.textContent = 'FINALIZAR REGISTRO';
        els.testBtn.disabled = false;

        els.testBtn.onclick = function() {
            // Submit registration form
            submitRegistration();
        };
    }

    async function submitRegistration() {
        const form = els.registerForm;
        if (!form) return;

        // PHP: Replace with actual AJAX submission
        showNotification('Processando cadastro...', 'info');

        try {
            // const formData = new FormData(form);
            // formData.append('action', 'apollo_register');
            // formData.append('nonce', CONFIG.nonce);
            // formData.append('quiz_passed', 'true');
            //
            // const response = await fetch(CONFIG.ajaxUrl, {
            //     method: 'POST',
            //     body: formData
            // });
            // const result = await response.json();

            // SIMULATED: Remove in production
            await new Promise(resolve => setTimeout(resolve, 1500));

            setSecurityState('success');
            showNotification('Cadastro realizado com sucesso!', 'success');

            setTimeout(() => {
                window.location.href = CONFIG.redirectAfterLogin;
            }, 2000);

        } catch (error) {
            console.error('Registration error:', error);
            showNotification('Erro ao processar cadastro. Tente novamente.', 'error');
        }
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
        openAptitudeTest: openAptitudeTest
    };

})();

