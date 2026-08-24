<?php
/**
 * Apollo Auth - JavaScript Controller
 *
 * ================================================================================
 * Contains all JavaScript for:
 * - Security State Management (Normal, Warning, Danger, Success)
 * - Login/Registration Form Handlers
 * - CPF Validation (Brazilian algorithm)
 * - Passport Number Validation
 * - SOUNDS Selection Validation (mandatory)
 * - Aptitude Quiz System (4 tests)
 * - Simon Game, Pattern Quiz, Ethics Quiz, Reaction Test
 *
 * @package Apollo_Social
 * @since 1.0.0
 * ================================================================================
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script>
'use strict';

/* 
================================================================================
CONFIGURATION
================================================================================
*/
const CONFIG = {
	// Security settings
	maxAttempts: APOLLO_AUTH_CONFIG.maxAttempts || 3,
	lockoutDuration: APOLLO_AUTH_CONFIG.lockoutDuration || 30000,
	
	// Theme colors
	colors: {
		normal: '#fb923c',
		warning: '#facc15',
		danger: '#ef4444',
		success: '#22c55e'
	},
	
	// AJAX Config
	ajaxUrl: APOLLO_AUTH_CONFIG.ajaxUrl,
	nonce: APOLLO_AUTH_CONFIG.nonce,
	feedUrl: APOLLO_AUTH_CONFIG.feedUrl,
	
	// Available sounds
	sounds: APOLLO_AUTH_CONFIG.sounds || []
};

/* 
================================================================================
STATE MANAGEMENT
================================================================================
*/
const STATE = {
	attempts: 0,
	isLocked: false,
	currentView: 'login',
	activeTest: 0,
	simonSequence: [],
	simonPlayerSequence: [],
	simonLevel: 1,
	reactionScore: 0,
	documentType: 'cpf',
	registrationData: {}
};

/* 
================================================================================
DOM ELEMENTS CACHE
================================================================================
*/
const els = {
	body: document.body,
	terminal: document.getElementById('terminal'),
	loginView: document.getElementById('login-view'),
	registerView: document.getElementById('register-view'),
	aptitudeModule: document.getElementById('aptitude-module'),
	notificationArea: document.getElementById('notification-area'),
	clock: document.getElementById('clock'),
	timestamp: document.getElementById('timestamp'),
	coordinates: document.getElementById('coordinates'),
	testContent: document.getElementById('test-content-area'),
	testStep: document.getElementById('test-step'),
	testBtn: document.getElementById('btn-test-action'),
	lockoutTimer: document.getElementById('lockout-timer'),
	lockoutSeconds: document.getElementById('lockout-seconds'),
	dangerFlash: document.getElementById('danger-flash'),
	loginStatusMsg: document.getElementById('login-status-msg'),
	cpfField: document.getElementById('cpf-field'),
	passportField: document.getElementById('passport-field'),
	cpfInput: document.getElementById('reg-cpf'),
	passportInput: document.getElementById('reg-passport'),
	cpfValidationMsg: document.getElementById('cpf-validation-msg'),
	passportValidationMsg: document.getElementById('passport-validation-msg'),
	soundsError: document.getElementById('sounds-error'),
	passwordStrength: document.getElementById('password-strength')
};

/* 
================================================================================
CPF VALIDATION - STRONG BRAZILIAN ALGORITHM
================================================================================
This validates CPF using the official Brazilian algorithm with both check digits.
*/
function validateCPF(cpf) {
	// Remove non-digits
	cpf = cpf.replace(/[^\d]/g, '');
	
	// Must be 11 digits
	if (cpf.length !== 11) {
		return { valid: false, message: 'CPF deve ter 11 dígitos' };
	}
	
	// Check for known invalid CPFs (all same digits)
	if (/^(\d)\1{10}$/.test(cpf)) {
		return { valid: false, message: 'CPF inválido (dígitos repetidos)' };
	}
	
	// Validate first check digit
	let sum = 0;
	for (let i = 0; i < 9; i++) {
		sum += parseInt(cpf.charAt(i)) * (10 - i);
	}
	let remainder = (sum * 10) % 11;
	if (remainder === 10 || remainder === 11) remainder = 0;
	if (remainder !== parseInt(cpf.charAt(9))) {
		return { valid: false, message: 'CPF inválido (dígito verificador 1)' };
	}
	
	// Validate second check digit
	sum = 0;
	for (let i = 0; i < 10; i++) {
		sum += parseInt(cpf.charAt(i)) * (11 - i);
	}
	remainder = (sum * 10) % 11;
	if (remainder === 10 || remainder === 11) remainder = 0;
	if (remainder !== parseInt(cpf.charAt(10))) {
		return { valid: false, message: 'CPF inválido (dígito verificador 2)' };
	}
	
	return { valid: true, message: '✓ CPF válido' };
}

/**
 * Format CPF as user types (000.000.000-00)
 */
function formatCPF(value) {
	value = value.replace(/\D/g, '');
	if (value.length <= 11) {
		value = value.replace(/(\d{3})(\d)/, '$1.$2');
		value = value.replace(/(\d{3})(\d)/, '$1.$2');
		value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
	}
	return value;
}

/* 
================================================================================
PASSPORT VALIDATION
================================================================================
Validates passport format (alphanumeric, 6-20 characters)
*/
function validatePassport(passport) {
	// Remove spaces
	passport = passport.replace(/\s/g, '').toUpperCase();
	
	// Must be 6-20 alphanumeric characters
	if (passport.length < 6 || passport.length > 20) {
		return { valid: false, message: 'Passaporte deve ter entre 6 e 20 caracteres' };
	}
	
	// Only alphanumeric allowed
	if (!/^[A-Z0-9]+$/.test(passport)) {
		return { valid: false, message: 'Passaporte deve conter apenas letras e números' };
	}
	
	// Common passport formats:
	// Brazil: 2 letters + 6 digits (e.g., FG123456)
	// USA: 9 characters (e.g., 123456789)
	// UK: 9 characters (e.g., 123456789)
	// EU: Various formats
	
	// Check for at least one letter and one number (most passports)
	const hasLetter = /[A-Z]/.test(passport);
	const hasNumber = /[0-9]/.test(passport);
	
	if (!hasLetter && !hasNumber) {
		return { valid: false, message: 'Formato de passaporte inválido' };
	}
	
	return { valid: true, message: '✓ Formato de passaporte válido' };
}

/* 
================================================================================
PASSWORD STRENGTH CHECKER
================================================================================
*/
function checkPasswordStrength(password) {
	let strength = 0;
	let feedback = [];
	
	if (password.length >= 8) strength++;
	else feedback.push('8+ caracteres');
	
	if (password.length >= 12) strength++;
	
	if (/[A-Z]/.test(password)) strength++;
	else feedback.push('letra maiúscula');
	
	if (/[a-z]/.test(password)) strength++;
	else feedback.push('letra minúscula');
	
	if (/[0-9]/.test(password)) strength++;
	else feedback.push('número');
	
	if (/[^A-Za-z0-9]/.test(password)) strength++;
	else feedback.push('símbolo');
	
	const levels = {
		0: { class: '', text: '' },
		1: { class: 'weak', text: 'Fraca' },
		2: { class: 'weak', text: 'Fraca' },
		3: { class: 'fair', text: 'Razoável' },
		4: { class: 'good', text: 'Boa' },
		5: { class: 'strong', text: 'Forte' },
		6: { class: 'strong', text: 'Muito forte' }
	};
	
	return {
		strength: strength,
		level: levels[strength] || levels[0],
		feedback: feedback
	};
}

/* 
================================================================================
SOUNDS VALIDATION - MANDATORY AT LEAST 1
================================================================================
*/
function validateSounds() {
	const checkedSounds = document.querySelectorAll('.sound-checkbox:checked');
	return checkedSounds.length > 0;
}

function getSelectedSounds() {
	const checkedSounds = document.querySelectorAll('.sound-checkbox:checked');
	return Array.from(checkedSounds).map(cb => cb.value);
}

/* 
================================================================================
UTILITY FUNCTIONS
================================================================================
*/
function updateClock() {
	const now = new Date();
	const utc = now.toISOString().split('T')[1].split('.')[0];
	if (els.clock) els.clock.textContent = utc + ' UTC';
	
	const brt = now.toLocaleTimeString('pt-BR', { hour12: false, timeZone: 'America/Sao_Paulo' });
	if (els.timestamp) els.timestamp.textContent = brt + ' BRT';
}

function setSecurityState(state) {
	els.body.setAttribute('data-state', state);
	
	if (state === 'danger' && els.dangerFlash) {
		els.dangerFlash.style.display = 'block';
		startGlitchingTimestamp();
		if (els.loginStatusMsg) corruptText(els.loginStatusMsg);
	} else if (els.dangerFlash) {
		els.dangerFlash.style.display = 'none';
	}
}

function notify(msg, type = 'info') {
	const div = document.createElement('div');
	div.className = 'auth-alert';
	div.innerHTML = '> ' + msg;
	
	const colors = {
		error: CONFIG.colors.danger,
		success: CONFIG.colors.success,
		warning: CONFIG.colors.warning,
		info: 'rgba(148,163,184,0.7)'
	};
	
	div.style.borderColor = colors[type] || colors.info;
	div.style.color = colors[type] || '#e5e7eb';
	
	if (els.notificationArea) {
		els.notificationArea.appendChild(div);
		
		setTimeout(() => {
			div.style.opacity = '0';
			div.style.transition = 'opacity 0.4s';
			setTimeout(() => div.remove(), 400);
		}, 3500);
	}
}

function playShake() {
	if (els.terminal) {
		els.terminal.classList.remove('shake');
		void els.terminal.offsetWidth;
		els.terminal.classList.add('shake');
	}
}

function corruptText(element) {
	if (!element) return;
	const originalHTML = element.innerHTML;
	const corruptChars = '!@#$%^&*<>/\\|{}[]01█▓▒░';
	
	let timesRun = 0;
	const corruptionInterval = setInterval(() => {
		timesRun++;
		if (timesRun > 20) {
			clearInterval(corruptionInterval);
			setTimeout(() => {
				element.innerHTML = originalHTML;
				element.classList.remove('corrupted');
			}, 5000);
			return;
		}
		
		let text = element.textContent;
		let corruptedText = '';
		for (let i = 0; i < text.length; i++) {
			if (Math.random() > 0.7) {
				corruptedText += corruptChars.charAt(Math.floor(Math.random() * corruptChars.length));
			} else {
				corruptedText += text.charAt(i);
			}
		}
		
		element.textContent = corruptedText;
		element.classList.add('corrupted');
	}, 100);
}

function startGlitchingTimestamp() {
	if (els.coordinates) els.coordinates.classList.add('glitching');
	
	const glitchInterval = setInterval(() => {
		if (els.body.getAttribute('data-state') !== 'danger') {
			clearInterval(glitchInterval);
			if (els.coordinates) els.coordinates.classList.remove('glitching');
			return;
		}
		
		const lat = (Math.random() * 180 - 90).toFixed(2);
		const lng = (Math.random() * 360 - 180).toFixed(2);
		const randomYear = Math.floor(Math.random() * 50) + 1990;
		
		if (els.coordinates) els.coordinates.textContent = `${lat}°? · ${lng}°?`;
		if (els.timestamp) els.timestamp.textContent = `${randomYear}-??-?? ??:??:??`;
	}, 200);
}

/* 
================================================================================
LOGIN HANDLER
================================================================================
*/
function handleLogin(e) {
	e.preventDefault();
	if (STATE.isLocked) return;
	
	const id = document.getElementById('user-id').value.trim();
	const pass = document.getElementById('user-pass').value.trim();
	
	const btn = document.getElementById('btn-login');
	const originalHTML = btn.innerHTML;
	btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> verificando…';
	btn.disabled = true;
	
	// AJAX Login
	fetch(CONFIG.ajaxUrl, {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: new URLSearchParams({
			action: 'apollo_login',
			user_login: id,
			user_password: pass,
			remember: document.getElementById('remember-toggle')?.classList.contains('active') ? '1' : '0',
			nonce: CONFIG.nonce
		})
	})
	.then(res => res.json())
	.then(data => {
		if (data.success) {
			setSecurityState('success');
			notify('✓ acesso liberado · bem-vinde de volta!', 'success');
			STATE.attempts = 0;
			
			setTimeout(() => {
				notify('redirecionando para o portal…', 'success');
				window.location.href = CONFIG.feedUrl;
			}, 1000);
		} else {
			STATE.attempts++;
			playShake();
			
			if (STATE.attempts === 1) {
				notify('credenciais inválidas · tente novamente.', 'error');
			} else if (STATE.attempts === 2) {
				setSecurityState('warning');
				notify('⚠ atenção · atividade suspeita detectada.', 'warning');
			} else if (STATE.attempts >= CONFIG.maxAttempts) {
				setSecurityState('danger');
				STATE.isLocked = true;
				notify('🚨 BLOQUEIO DE SEGURANÇA ATIVADO', 'error');
				
				let remaining = CONFIG.lockoutDuration / 1000;
				const countdownInterval = setInterval(() => {
					remaining--;
					if (els.lockoutSeconds) els.lockoutSeconds.textContent = remaining;
					
					if (remaining <= 0) {
						clearInterval(countdownInterval);
						STATE.isLocked = false;
						STATE.attempts = 0;
						setSecurityState('normal');
						notify('bloqueio encerrado · tente novamente.', 'success');
					}
				}, 1000);
			}
		}
		
		btn.innerHTML = originalHTML;
		btn.disabled = false;
	})
	.catch(err => {
		console.error('Login error:', err);
		notify('Erro de conexão. Tente novamente.', 'error');
		btn.innerHTML = originalHTML;
		btn.disabled = false;
	});
}

/* 
================================================================================
REGISTRATION HANDLER
================================================================================
*/
function handleRegister(e) {
	e.preventDefault();
	
	const name = document.getElementById('reg-name').value.trim();
	const instagram = document.getElementById('reg-instagram').value.trim();
	const email = document.getElementById('reg-email').value.trim();
	const pass = document.getElementById('reg-pass').value.trim();
	const termsToggle = document.getElementById('terms-toggle');
	
	// Basic validation
	if (!name || !instagram || !email || !pass) {
		notify('Preencha todos os campos obrigatórios.', 'error');
		playShake();
		return;
	}
	
	// Document validation (CPF or Passport)
	if (STATE.documentType === 'cpf') {
		const cpfValue = document.getElementById('reg-cpf').value.trim();
		const cpfResult = validateCPF(cpfValue);
		if (!cpfResult.valid) {
			notify('CPF inválido: ' + cpfResult.message, 'error');
			playShake();
			return;
		}
		STATE.registrationData.cpf = cpfValue.replace(/\D/g, '');
		STATE.registrationData.docType = 'cpf';
	} else {
		const passportValue = document.getElementById('reg-passport').value.trim();
		const passportResult = validatePassport(passportValue);
		if (!passportResult.valid) {
			notify('Passaporte inválido: ' + passportResult.message, 'error');
			playShake();
			return;
		}
		STATE.registrationData.passport = passportValue.toUpperCase();
		STATE.registrationData.docType = 'passport';
	}
	
	// SOUNDS validation (mandatory)
	if (!validateSounds()) {
		notify('Selecione pelo menos 1 gênero musical.', 'error');
		if (els.soundsError) els.soundsError.style.display = 'block';
		playShake();
		return;
	}
	if (els.soundsError) els.soundsError.style.display = 'none';
	
	// Terms validation
	if (!termsToggle.classList.contains('active')) {
		notify('Você precisa aceitar os termos e política de privacidade.', 'error');
		playShake();
		return;
	}
	
	// Store registration data
	STATE.registrationData.name = name;
	STATE.registrationData.instagram = instagram.replace('@', '');
	STATE.registrationData.email = email;
	STATE.registrationData.password = pass;
	STATE.registrationData.sounds = getSelectedSounds();
	
	notify('Dados básicos ok · abrindo quiz de acesso…', 'success');
	setTimeout(openAptitudeTest, 800);
}

/* 
================================================================================
APTITUDE TEST SYSTEM (4 Tests)
================================================================================
*/
function openAptitudeTest() {
	if (els.aptitudeModule) {
		els.aptitudeModule.classList.add('active');
	}
	runTest(1);
}

function runTest(step) {
	STATE.activeTest = step;
	if (els.testStep) els.testStep.textContent = `ETAPA ${step}/4`;
	if (els.testBtn) {
		els.testBtn.style.display = 'flex';
		els.testBtn.innerHTML = step < 4 ? '<span>confirmar resposta</span>' : '<span>finalizar registro</span>';
	}
	
	switch(step) {
		case 1: renderPatternQuiz(); break;
		case 2: renderSimonGame(); break;
		case 3: renderEthicsQuiz(); break;
		case 4: renderReactionTest(); break;
	}
}

// TEST 1: Pattern Recognition (Music Notes)
// IMPORTANT: All text must be WHITE for visibility on dark background!
function renderPatternQuiz() {
	if (!els.testContent) return;
	
	els.testContent.innerHTML = `
		<h3 style="color: #ffffff !important; font-size: 16px; font-weight: 600; margin-bottom: 8px;" data-tooltip="Título do teste de padrões">RECONHECIMENTO DE PADRÕES RÍTMICOS</h3>
		<p style="color: #e2e8f0 !important; font-size: 11px; margin-bottom: 16px; max-width: 280px;" data-tooltip="Instrução do teste">
			Identifique o próximo padrão de batida na sequência.
		</p>
		<div style="display: flex; gap: 12px; justify-content: center; margin-bottom: 20px; font-size: 24px;" data-tooltip="Sequência de padrões musicais">
			<div style="padding: 8px 14px; background: rgba(15,23,42,0.8); border: 1px solid rgba(148,163,184,0.5); border-radius: 8px; color: #fb923c !important;" data-tooltip="Padrão 1">♪</div>
			<div style="padding: 8px 14px; background: rgba(15,23,42,0.8); border: 1px solid rgba(148,163,184,0.5); border-radius: 8px; color: #fb923c !important;" data-tooltip="Padrão 2">♫</div>
			<div style="padding: 8px 14px; background: rgba(15,23,42,0.8); border: 1px solid rgba(148,163,184,0.5); border-radius: 8px; color: #fb923c !important;" data-tooltip="Padrão 3">♪♪</div>
			<div style="padding: 8px 14px; background: rgba(15,23,42,0.8); border: 1px solid rgba(148,163,184,0.5); border-radius: 8px; color: #fb923c !important;" data-tooltip="Padrão 4">♫♫</div>
			<div style="padding: 8px 14px; background: rgba(15,23,42,0.8); border: 1px solid rgba(250,204,21,0.8); border-radius: 8px; color: #facc15 !important; animation: pulse 1s infinite;" data-tooltip="Próximo padrão">?</div>
		</div>
		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; max-width: 280px; margin: 0 auto;" data-tooltip="Opções de resposta">
			<button class="pattern-option test-option" style="padding: 14px; font-size: 20px; background: rgba(15,23,42,0.8); border: 1px solid rgba(148,163,184,0.6); border-radius: 10px; color: #ffffff !important; cursor: pointer; transition: all 0.2s;" data-value="♪♪♪" data-correct="true" data-tooltip="Opção ♪♪♪">♪♪♪</button>
			<button class="pattern-option test-option" style="padding: 14px; font-size: 20px; background: rgba(15,23,42,0.8); border: 1px solid rgba(148,163,184,0.6); border-radius: 10px; color: #ffffff !important; cursor: pointer; transition: all 0.2s;" data-value="♪♪♫" data-tooltip="Opção ♪♪♫">♪♪♫</button>
			<button class="pattern-option test-option" style="padding: 14px; font-size: 20px; background: rgba(15,23,42,0.8); border: 1px solid rgba(148,163,184,0.6); border-radius: 10px; color: #ffffff !important; cursor: pointer; transition: all 0.2s;" data-value="♫♫♫" data-tooltip="Opção ♫♫♫">♫♫♫</button>
			<button class="pattern-option test-option" style="padding: 14px; font-size: 20px; background: rgba(15,23,42,0.8); border: 1px solid rgba(148,163,184,0.6); border-radius: 10px; color: #ffffff !important; cursor: pointer; transition: all 0.2s;" data-value="♫♪♫" data-tooltip="Opção ♫♪♫">♫♪♫</button>
		</div>
		<p style="color: #94a3b8 !important; font-size: 10px; margin-top: 16px; max-width: 280px;" data-tooltip="Dica do teste">
			Dica: observe o padrão de crescimento dos símbolos
		</p>
	`;
	attachPatternListeners();
}

function attachPatternListeners() {
	const options = els.testContent.querySelectorAll('.pattern-option');
	options.forEach(btn => {
		btn.addEventListener('click', () => {
			options.forEach(b => b.classList.remove('selected'));
			btn.classList.add('selected');
		});
	});
}

// TEST 2: Simon Game
// IMPORTANT: All text must be WHITE for visibility on dark background!
function renderSimonGame() {
	STATE.simonSequence = [];
	STATE.simonPlayerSequence = [];
	STATE.simonLevel = 1;
	
	if (!els.testContent) return;
	
	els.testContent.innerHTML = `
		<h3 style="color: #ffffff !important; font-size: 16px; font-weight: 600; margin-bottom: 8px;" data-tooltip="Título do teste Simon">TESTE DE MEMÓRIA SIMON</h3>
		<p style="color: #e2e8f0 !important; font-size: 11px; margin-bottom: 16px; max-width: 280px;" data-tooltip="Instrução do Simon">
			Memorize a sequência de cores. O teste tem 4 níveis.
		</p>
		<div style="text-align: center; margin-bottom: 16px;" data-tooltip="Indicador de nível">
			<span style="font-size: 10px; font-family: monospace; padding: 4px 10px; border-radius: 4px; border: 1px solid rgba(100,116,139,0.7); color: #ffffff !important;">
				NÍVEL: <span id="simon-level" style="color: #fb923c !important;" data-tooltip="Nível atual">1</span>/4
			</span>
		</div>
		<div class="simon-container" id="simon-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; max-width: 200px; margin: 0 auto;" data-tooltip="Grid do jogo Simon">
			<button class="simon-btn simon-red" style="width: 80px; height: 80px; background: #dc2626; border: none; border-radius: 12px; cursor: pointer; transition: all 0.2s;" data-color="red" data-tooltip="Botão vermelho"></button>
			<button class="simon-btn simon-blue" style="width: 80px; height: 80px; background: #2563eb; border: none; border-radius: 12px; cursor: pointer; transition: all 0.2s;" data-color="blue" data-tooltip="Botão azul"></button>
			<button class="simon-btn simon-green" style="width: 80px; height: 80px; background: #16a34a; border: none; border-radius: 12px; cursor: pointer; transition: all 0.2s;" data-color="green" data-tooltip="Botão verde"></button>
			<button class="simon-btn simon-yellow" style="width: 80px; height: 80px; background: #ca8a04; border: none; border-radius: 12px; cursor: pointer; transition: all 0.2s;" data-color="yellow" data-tooltip="Botão amarelo"></button>
		</div>
		<p id="simon-status" style="color: #fbbf24 !important; font-size: 11px; font-family: monospace; margin-top: 16px;" data-tooltip="Status do Simon">
			Observe a sequência...
		</p>
	`;
	
	setTimeout(() => startSimonLevel(), 1000);
}

function startSimonLevel() {
	STATE.simonPlayerSequence = [];
	const colors = ['red', 'blue', 'green', 'yellow'];
	STATE.simonSequence.push(colors[Math.floor(Math.random() * 4)]);
	
	const levelEl = document.getElementById('simon-level');
	const statusEl = document.getElementById('simon-status');
	if (levelEl) levelEl.textContent = STATE.simonLevel;
	if (statusEl) statusEl.textContent = 'Observe a sequência...';
	
	const buttons = document.querySelectorAll('.simon-btn');
	buttons.forEach(btn => btn.style.pointerEvents = 'none');
	
	playSimonSequence(0);
}

function playSimonSequence(index) {
	if (index >= STATE.simonSequence.length) {
		const statusEl = document.getElementById('simon-status');
		if (statusEl) statusEl.textContent = 'Sua vez! Repita a sequência.';
		
		const buttons = document.querySelectorAll('.simon-btn');
		buttons.forEach(btn => {
			btn.style.pointerEvents = 'auto';
			btn.addEventListener('click', handleSimonClick);
		});
		return;
	}
	
	const color = STATE.simonSequence[index];
	const btn = document.querySelector(`.simon-${color}`);
	
	setTimeout(() => {
		if (btn) {
			btn.classList.add('flash');
			setTimeout(() => {
				btn.classList.remove('flash');
				playSimonSequence(index + 1);
			}, 400);
		}
	}, 500);
}

function handleSimonClick(e) {
	const clickedColor = e.target.dataset.color;
	STATE.simonPlayerSequence.push(clickedColor);
	const currentIndex = STATE.simonPlayerSequence.length - 1;
	
	e.target.classList.add('flash');
	setTimeout(() => e.target.classList.remove('flash'), 200);
	
	if (STATE.simonPlayerSequence[currentIndex] !== STATE.simonSequence[currentIndex]) {
		notify('Sequência errada! Reiniciando...', 'error');
		playShake();
		STATE.simonSequence = [];
		STATE.simonLevel = 1;
		setTimeout(() => startSimonLevel(), 1500);
		return;
	}
	
	if (STATE.simonPlayerSequence.length === STATE.simonSequence.length) {
		STATE.simonLevel++;
		
		if (STATE.simonLevel > 4) {
			const statusEl = document.getElementById('simon-status');
			if (statusEl) statusEl.textContent = '✓ Memória aprovada!';
			notify('Excelente memória! Próximo teste...', 'success');
			
			const buttons = document.querySelectorAll('.simon-btn');
			buttons.forEach(btn => btn.removeEventListener('click', handleSimonClick));
			
			if (els.testBtn) els.testBtn.innerHTML = '<span>próximo teste</span>';
		} else {
			notify(`Nível ${STATE.simonLevel - 1} completo!`, 'success');
			const statusEl = document.getElementById('simon-status');
			if (statusEl) statusEl.textContent = 'Próximo nível...';
			
			const buttons = document.querySelectorAll('.simon-btn');
			buttons.forEach(btn => btn.removeEventListener('click', handleSimonClick));
			
			setTimeout(() => startSimonLevel(), 1500);
		}
	}
}

// TEST 3: Ethics Quiz
// IMPORTANT: All text must be WHITE (#ffffff) for visibility on dark background!
function renderEthicsQuiz() {
	if (!els.testContent) return;
	
	els.testContent.innerHTML = `
		<h3 style="color: #ffffff !important; font-size: 16px; font-weight: 600; margin-bottom: 8px;" data-tooltip="Título do teste de ética">TESTE DE ÉTICA E RESPEITO</h3>
		<p style="color: #e2e8f0 !important; font-size: 11px; margin-bottom: 16px; max-width: 280px; line-height: 1.5;" data-tooltip="Pergunta do teste">
			"Não gosto de Eletrônica com Funk / de Tribal / de Techno / de Melódico", logo...
		</p>
		<div style="display: flex; flex-direction: column; gap: 8px; width: 100%; max-width: 280px; text-align: left;" data-tooltip="Opções de resposta">
			<button class="test-option" style="color: #ffffff !important; background: rgba(15,23,42,0.7); border: 1px solid rgba(100,116,139,0.8); border-radius: 6px; padding: 12px; font-size: 11px; text-align: left; cursor: pointer; transition: all 0.2s;" data-value="bad1" data-tooltip="Opção incorreta">
				Critico e não me importo com quem trabalha com isso.
			</button>
			<button class="test-option" style="color: #ffffff !important; background: rgba(15,23,42,0.7); border: 1px solid rgba(100,116,139,0.8); border-radius: 6px; padding: 12px; font-size: 11px; text-align: left; cursor: pointer; transition: all 0.2s;" data-value="bad2" data-tooltip="Opção incorreta">
				A depender da lua, posso falar mal e pesar a mão sobre.
			</button>
			<button class="test-option" style="color: #ffffff !important; background: rgba(15,23,42,0.7); border: 1px solid rgba(100,116,139,0.8); border-radius: 6px; padding: 12px; font-size: 11px; text-align: left; cursor: pointer; transition: all 0.2s;" data-value="good" data-correct="true" data-tooltip="Opção correta - Respeito">
				É trabalho, renda, a sonoridade e arte favorita de alguém. Respeito.
			</button>
			<button class="test-option" style="color: #ffffff !important; background: rgba(15,23,42,0.7); border: 1px solid rgba(100,116,139,0.8); border-radius: 6px; padding: 12px; font-size: 11px; text-align: left; cursor: pointer; transition: all 0.2s;" data-value="bad3" data-tooltip="Opção incorreta">
				Tenho dúvidas, mas falo mal e deixo arder mesmo assim.
			</button>
		</div>
		<p style="color: #94a3b8 !important; font-size: 10px; margin-top: 16px; max-width: 280px;" data-tooltip="Nota sobre comunidade">
			A comunidade Apollo é um espaço de respeito mútuo entre todos os sons.
		</p>
	`;
	attachEthicsListeners();
}

function attachEthicsListeners() {
	const options = els.testContent.querySelectorAll('.test-option');
	options.forEach(btn => {
		btn.addEventListener('click', () => {
			options.forEach(b => b.classList.remove('selected'));
			btn.classList.add('selected');
		});
	});
}

// TEST 4: Reaction Test
// IMPORTANT: All text must be WHITE for visibility on dark background!
function renderReactionTest() {
	STATE.reactionScore = 0;
	
	if (!els.testContent) return;
	
	els.testContent.innerHTML = `
		<h3 style="color: #ffffff !important; font-size: 16px; font-weight: 600; margin-bottom: 8px;" data-tooltip="Título do teste de reação">TESTE DE REAÇÃO & SINCRONIA</h3>
		<p style="color: #e2e8f0 !important; font-size: 11px; margin-bottom: 16px; max-width: 280px;" data-tooltip="Instrução do teste">
			Toque nos ícones de som piscando antes que desapareçam.
		</p>
		<div class="reaction-arena" id="reaction-arena" style="position: relative; width: 100%; max-width: 300px; height: 200px; margin: 0 auto; background: rgba(15,23,42,0.6); border: 1px solid rgba(148,163,184,0.4); border-radius: 12px;" data-tooltip="Área do teste de reação">
			<div style="position: absolute; top: 10px; left: 10px; font-family: monospace; font-size: 12px; color: #fb923c !important; background: rgba(var(--rgb-d),0.6); padding: 4px 8px; border-radius: 6px;" data-tooltip="Contador de capturas">CAPTURAS: <span id="reaction-score" style="color: #22c55e !important;">0</span>/4</div>
		</div>
		<p id="reaction-status" style="color: #fbbf24 !important; font-size: 11px; font-family: monospace; margin-top: 16px;" data-tooltip="Status do teste">
			Capture o ícone!
		</p>
	`;
	
	setTimeout(() => spawnReactionTarget(), 1000);
}

function spawnReactionTarget() {
	if (STATE.reactionScore >= 4) return;
	
	const arena = document.getElementById('reaction-arena');
	if (!arena) return;
	
	const icons = ['ri-music-2-line', 'ri-disc-line', 'ri-headphone-line', 'ri-equalizer-line'];
	
	const target = document.createElement('div');
	target.className = 'reaction-target';
	target.innerHTML = `<i class="${icons[Math.floor(Math.random() * icons.length)]}"></i>`;
	target.setAttribute('data-tooltip', 'Clique para capturar!');
	
	const maxX = arena.offsetWidth - 60;
	const maxY = arena.offsetHeight - 60;
	target.style.left = Math.random() * maxX + 'px';
	target.style.top = (Math.random() * (maxY - 40) + 40) + 'px';
	
	arena.appendChild(target);
	
	const statusEl = document.getElementById('reaction-status');
	if (statusEl) statusEl.textContent = 'Capture o ícone!';
	
	target.addEventListener('click', () => {
		target.classList.add('captured');
		STATE.reactionScore++;
		const scoreEl = document.getElementById('reaction-score');
		if (scoreEl) scoreEl.textContent = STATE.reactionScore;
		
		if (STATE.reactionScore >= 4) {
			if (statusEl) statusEl.textContent = '✓ Reflexos aprovados!';
			notify('Excelente tempo de reação!', 'success');
			if (els.testBtn) els.testBtn.innerHTML = '<span>finalizar registro</span>';
		}
		
		setTimeout(() => target.remove(), 300);
	});
	
	setTimeout(() => {
		if (!target.classList.contains('captured') && arena.contains(target)) {
			target.remove();
			if (STATE.reactionScore < 4) {
				setTimeout(() => spawnReactionTarget(), 500);
			}
		}
	}, 1500);
	
	if (STATE.reactionScore < 4) {
		setTimeout(() => spawnReactionTarget(), 2000);
	}
}

// Test Navigation Handler
function handleTestNext() {
	const selected = els.testContent?.querySelectorAll('.test-option.selected');
	
	if (STATE.activeTest === 1) {
		if (!selected || selected.length === 0) {
			notify('Selecione uma opção antes de continuar.', 'error');
			playShake();
			return;
		}
		const isCorrect = selected[0].dataset.correct === 'true';
		if (!isCorrect) {
			notify('Resposta incorreta! Tente novamente.', 'error');
			playShake();
			selected[0].classList.add('wrong');
			selected[0].classList.remove('selected');
			return;
		}
		notify('Padrão correto! Próximo teste...', 'success');
		selected[0].classList.add('correct');
		setTimeout(() => runTest(2), 1000);
		return;
	}
	
	if (STATE.activeTest === 2) {
		if (STATE.simonLevel <= 4) {
			notify('Complete todos os 4 níveis do Simon!', 'warning');
			return;
		}
		runTest(3);
		return;
	}
	
	if (STATE.activeTest === 3) {
		if (!selected || selected.length === 0) {
			notify('Selecione uma opção antes de continuar.', 'error');
			playShake();
			return;
		}
		const isCorrect = selected[0].dataset.correct === 'true';
		if (!isCorrect) {
			notify('Essa não é a resposta esperada. Reflita e tente novamente.', 'error');
			playShake();
			selected[0].classList.add('wrong');
			selected[0].classList.remove('selected');
			return;
		}
		notify('Resposta correta! Último teste...', 'success');
		selected[0].classList.add('correct');
		setTimeout(() => runTest(4), 1000);
		return;
	}
	
	if (STATE.activeTest === 4) {
		if (STATE.reactionScore < 4) {
			notify('Capture todos os 4 ícones para continuar.', 'warning');
			return;
		}
		
		// Submit registration via AJAX
		submitRegistration();
	}
}

// Submit Registration
function submitRegistration() {
	if (els.testContent) {
		els.testContent.innerHTML = `
			<i class="ri-loader-4-line text-6xl text-amber-400 mb-3 animate-spin"></i>
			<h2 class="text-xl font-bold text-amber-100 mb-1">Criando seu perfil...</h2>
			<p class="text-[11px] text-slate-300 max-w-xs mx-auto">
				Aguarde enquanto configuramos sua conta.
			</p>
		`;
	}
	if (els.testBtn) els.testBtn.style.display = 'none';
	
	fetch(CONFIG.ajaxUrl, {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: new URLSearchParams({
			action: 'apollo_register',
			nonce: CONFIG.nonce,
			user_name: STATE.registrationData.name,
			user_instagram: STATE.registrationData.instagram,
			user_email: STATE.registrationData.email,
			user_password: STATE.registrationData.password,
			doc_type: STATE.registrationData.docType,
			user_cpf: STATE.registrationData.cpf || '',
			user_passport: STATE.registrationData.passport || '',
			user_sounds: JSON.stringify(STATE.registrationData.sounds)
		})
	})
	.then(res => res.json())
	.then(data => {
		if (data.success) {
			if (els.testContent) {
				els.testContent.innerHTML = `
					<i class="ri-checkbox-circle-fill text-6xl text-emerald-400 mb-3"></i>
					<h2 class="text-xl font-bold text-emerald-100 mb-1">REGISTRO CONCLUÍDO!</h2>
					<p class="text-[11px] text-slate-300 max-w-xs mx-auto">
						Seu perfil clubber foi criado com sucesso. A partir de agora,
						usamos essas respostas para curar seus convites e zelar pela pista.
					</p>
				`;
			}
			
			notify('bem-vinde à tripulação apollo::rio ✦', 'success');
			setSecurityState('success');
			
			setTimeout(() => {
				window.location.href = CONFIG.feedUrl;
			}, 2500);
		} else {
			if (els.testContent) {
				els.testContent.innerHTML = `
					<i class="ri-error-warning-fill text-6xl text-red-400 mb-3"></i>
					<h2 class="text-xl font-bold text-red-100 mb-1">Erro no registro</h2>
					<p class="text-[11px] text-slate-300 max-w-xs mx-auto">
						${data.data?.message || 'Erro desconhecido. Tente novamente.'}
					</p>
				`;
			}
			if (els.testBtn) {
				els.testBtn.style.display = 'flex';
				els.testBtn.innerHTML = '<span>tentar novamente</span>';
			}
		}
	})
	.catch(err => {
		console.error('Registration error:', err);
		notify('Erro de conexão. Tente novamente.', 'error');
	});
}

/* 
================================================================================
INITIALIZATION
================================================================================
*/
document.addEventListener('DOMContentLoaded', () => {
	// Start clock
	updateClock();
	setInterval(updateClock, 1000);
	
	// View toggles
	document.getElementById('btn-to-register')?.addEventListener('click', () => {
		if (els.loginView) els.loginView.classList.add('hidden');
		if (els.registerView) els.registerView.classList.remove('hidden');
	});
	
	document.getElementById('btn-to-login')?.addEventListener('click', () => {
		if (els.registerView) els.registerView.classList.add('hidden');
		if (els.loginView) els.loginView.classList.remove('hidden');
	});
	
	// Custom toggles
	document.querySelectorAll('.custom-toggle').forEach(t => {
		t.addEventListener('click', () => t.classList.toggle('active'));
	});
	
	// Document type selection
	document.querySelectorAll('input[name="doc_type"]').forEach(radio => {
		radio.addEventListener('change', (e) => {
			STATE.documentType = e.target.value;
			if (e.target.value === 'cpf') {
				if (els.cpfField) els.cpfField.classList.remove('hidden');
				if (els.passportField) els.passportField.classList.add('hidden');
			} else {
				if (els.cpfField) els.cpfField.classList.add('hidden');
				if (els.passportField) els.passportField.classList.remove('hidden');
			}
		});
	});
	
	// CPF Input formatting and validation
	document.getElementById('reg-cpf')?.addEventListener('input', (e) => {
		e.target.value = formatCPF(e.target.value);
		
		if (e.target.value.replace(/\D/g, '').length === 11) {
			const result = validateCPF(e.target.value);
			if (els.cpfValidationMsg) {
				els.cpfValidationMsg.textContent = result.message;
				els.cpfValidationMsg.className = 'small-note cpf-validation-msg ' + (result.valid ? 'valid' : 'invalid');
				els.cpfValidationMsg.style.display = 'block';
			}
		} else if (els.cpfValidationMsg) {
			els.cpfValidationMsg.style.display = 'none';
		}
	});
	
	// Passport validation
	document.getElementById('reg-passport')?.addEventListener('input', (e) => {
		e.target.value = e.target.value.toUpperCase();
		
		if (e.target.value.length >= 6) {
			const result = validatePassport(e.target.value);
			if (els.passportValidationMsg) {
				els.passportValidationMsg.textContent = result.message;
				els.passportValidationMsg.className = 'small-note passport-validation-msg ' + (result.valid ? 'valid' : 'invalid');
				els.passportValidationMsg.style.display = 'block';
			}
		} else if (els.passportValidationMsg) {
			els.passportValidationMsg.style.display = 'none';
		}
	});
	
	// Password strength checker
	document.getElementById('reg-pass')?.addEventListener('input', (e) => {
		const result = checkPasswordStrength(e.target.value);
		const strengthEl = document.getElementById('password-strength');
		if (strengthEl) {
			const fill = strengthEl.querySelector('.strength-fill');
			const text = document.getElementById('strength-text');
			if (fill) fill.className = 'strength-fill h-full transition-all duration-300 ' + result.level.class;
			if (text) text.textContent = result.level.text + (result.feedback.length ? ' - Adicione: ' + result.feedback.join(', ') : '');
		}
	});
	
	// Sound chip selection
	document.querySelectorAll('.sound-checkbox').forEach(cb => {
		cb.addEventListener('change', () => {
			if (validateSounds() && els.soundsError) {
				els.soundsError.style.display = 'none';
			}
		});
	});
	
	// Form submissions
	document.getElementById('login-form')?.addEventListener('submit', handleLogin);
	document.getElementById('register-form')?.addEventListener('submit', handleRegister);
	
	// Quiz button
	if (els.testBtn) {
		els.testBtn.addEventListener('click', handleTestNext);
	}
	
	// Instagram input - prevent @ in value
	document.getElementById('reg-instagram')?.addEventListener('input', (e) => {
		e.target.value = e.target.value.replace(/[@\s]/g, '').toLowerCase();
	});
});
</script>

