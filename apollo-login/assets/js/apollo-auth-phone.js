/**
 * Apollo registration — inline Telegram phone verification (STEP 1b).
 * Reuses apollo-telegram REST endpoints; mirrors /telegram page flow.
 */
(function () {
    'use strict';

    const COUNTRIES = [
        { name: 'Brazil', prefix: '55', flag: 'br' },
        { name: 'United States', prefix: '1', flag: 'us' },
        { name: 'United Kingdom', prefix: '44', flag: 'gb' },
        { name: 'Portugal', prefix: '351', flag: 'pt' },
        { name: 'Germany', prefix: '49', flag: 'de' },
        { name: 'France', prefix: '33', flag: 'fr' },
        { name: 'Spain', prefix: '34', flag: 'es' },
        { name: 'Italy', prefix: '39', flag: 'it' },
        { name: 'Argentina', prefix: '54', flag: 'ar' },
        { name: 'Mexico', prefix: '52', flag: 'mx' },
        { name: 'Canada', prefix: '1', flag: 'ca' },
        { name: 'Australia', prefix: '61', flag: 'au' },
        { name: 'Japan', prefix: '81', flag: 'jp' },
        { name: 'India', prefix: '91', flag: 'in' },
        { name: 'Netherlands', prefix: '31', flag: 'nl' },
    ];

    let verified = false;
    let currentPhone = null;
    let currentRequestId = null;
    let currentDeepLink = null;
    let pollTimer = null;

    function getConfig() {
        const cfg = window.apolloAuthConfig || {};
        return {
            restUrl: (cfg.telegramRestUrl || '/wp-json/apollo-telegram/v1').replace(/\/$/, ''),
            nonce: cfg.telegramNonce || '',
            botUser: cfg.telegramBotUser || 'apolloRio_bot',
        };
    }

    // #region agent log
    function dbg() { /* no-op — debug session closed */ }
    // #endregion

    function restPost(path, body) {
        const CONFIG = getConfig();
        return fetch(CONFIG.restUrl + path, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Apollo-Telegram-Nonce': CONFIG.nonce,
            },
            credentials: 'same-origin',
            body: JSON.stringify(Object.assign({ nonce: CONFIG.nonce }, body || {})),
        });
    }

    function setHiddenFields(phone, requestId, isVerified) {
        const phoneEl = document.getElementById('phone');
        const reqEl = document.getElementById('phone_request_id');
        const verEl = document.getElementById('phone_verified');
        if (phoneEl) phoneEl.value = phone || '';
        if (reqEl) reqEl.value = requestId || '';
        if (verEl) verEl.value = isVerified ? '1' : '0';
    }

    function showFeedback(html, isValid) {
        const feedback = document.getElementById('regPhoneFeedback');
        const phoneInput = document.getElementById('regPhoneInput');
        if (!feedback) return;
        feedback.innerHTML = html;
        if (phoneInput) {
            phoneInput.classList.remove('phone-valid', 'phone-invalid');
            if (isValid === true) phoneInput.classList.add('phone-valid');
            if (isValid === false) phoneInput.classList.add('phone-invalid');
        }
        document.querySelectorAll('#regPhoneGroup .bday-part, #regPhoneGroup .phone-input').forEach((el) => {
            el.classList.remove('bday-valid', 'bday-invalid', 'phone-valid', 'phone-invalid');
        });
        if (phoneInput && isValid === true) phoneInput.classList.add('phone-valid');
        if (phoneInput && isValid === false) phoneInput.classList.add('phone-invalid');
    }

    function lockPhoneRowSuccess() {
        const validateBtn = document.getElementById('regValidateBtn');
        const validateIcon = document.getElementById('regValidateIcon');
        const phoneInput = document.getElementById('regPhoneInput');
        const flagTrigger = document.getElementById('regFlagTrigger');
        if (validateBtn) {
            validateBtn.classList.add('is-success');
            validateBtn.style.pointerEvents = 'none';
            validateBtn.disabled = true;
        }
        if (validateIcon) validateIcon.className = 'ri-checkbox-circle-fill';
        if (phoneInput) phoneInput.disabled = true;
        if (flagTrigger) flagTrigger.style.pointerEvents = 'none';
        showFeedback('<i class="ri-checkbox-circle-fill" style="color: #22c55e;"></i> <span style="color: #22c55e;">Telefone confirmado</span>', true);
    }

    function resetPhoneRow() {
        const validateBtn = document.getElementById('regValidateBtn');
        const validateIcon = document.getElementById('regValidateIcon');
        const phoneInput = document.getElementById('regPhoneInput');
        const flagTrigger = document.getElementById('regFlagTrigger');
        if (validateBtn) {
            validateBtn.classList.remove('is-success');
            validateBtn.style.pointerEvents = 'auto';
            validateBtn.disabled = false;
        }
        if (validateIcon) validateIcon.className = 'ri-arrow-right-line';
        if (phoneInput) phoneInput.disabled = false;
        if (flagTrigger) flagTrigger.style.pointerEvents = 'auto';
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    function openModal() {
        const modal = document.getElementById('regVerificationModal');
        const preStep = document.getElementById('regTelegramPreStep');
        const core = document.getElementById('regVerificationCore');
        const submitBtn = document.getElementById('regSubmitCodeBtn');
        const digits = document.querySelectorAll('#regCodeContainer .code-digit');
        if (modal) {
            modal.classList.add('is-active');
            modal.setAttribute('aria-hidden', 'false');
        }
        if (preStep) preStep.classList.remove('is-hidden');
        if (core) core.classList.add('is-muted');
        digits.forEach((d) => {
            d.value = '';
            d.classList.remove('is-error');
        });
        const hidden = document.getElementById('regHiddenCodeInput');
        if (hidden) hidden.value = '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.remove('is-success');
            submitBtn.textContent = 'Sou eu mesmo!';
        }
    }

    function closeModal(resetPhone) {
        const modal = document.getElementById('regVerificationModal');
        stopPolling();
        if (modal) {
            modal.classList.remove('is-active');
            modal.setAttribute('aria-hidden', 'true');
        }
        if (resetPhone) {
            resetPhoneRow();
        } else {
            lockPhoneRowSuccess();
        }
    }

    function startPolling() {
        stopPolling();
        pollTimer = setInterval(async () => {
            if (!currentRequestId || !currentPhone) return stopPolling();
            try {
                const tick = await restPost('/poll-tick', {});
                const tickData = await tick.json().catch(() => ({}));
                if (tickData && tickData.mode === 'webhook') return stopPolling();

                const st = await restPost('/verification-status', {
                    phone: currentPhone,
                    request_id: currentRequestId,
                });
                const stData = await st.json().catch(() => ({}));
                if (stData && stData.status === 'verified') stopPolling();
            } catch (e) {
                /* quiet */
            }
        }, 5000);
    }

    function checkCodeCompletion(digits, submitBtn) {
        const code = Array.from(digits).map((d) => d.value).join('');
        if (submitBtn) submitBtn.disabled = code.length !== 6;
    }

    function renderCountries(countryList, selectedFlag, selectedPrefix, filter, onSelect) {
        if (!countryList) return;
        countryList.innerHTML = '';
        const lower = (filter || '').toLowerCase();
        COUNTRIES.forEach((country) => {
            if (lower && !country.name.toLowerCase().includes(lower) && !country.prefix.includes(lower)) return;
            const item = document.createElement('div');
            item.className = 'country-item';
            item.innerHTML = `<img src="https://flagpedia.net/data/flags/icon/36x27/${country.flag}.png" class="flag-icon" alt=""><span class="country-name">${country.name}</span><span class="country-prefix">+${country.prefix}</span>`;
            item.addEventListener('click', () => onSelect(country));
            countryList.appendChild(item);
        });
    }

    function init() {
        const phoneGroup = document.getElementById('regPhoneGroup');
        if (!phoneGroup) {
            // #region agent log
            dbg('D', 'apollo-auth-phone.js:init', 'regPhoneGroup_missing', {
                authPage: (window.apolloAuthConfig || {}).authPage || null,
                step1bHidden: !!document.getElementById('register-step-1b')?.hidden,
            });
            // #endregion
            return;
        }

        const flagTrigger = document.getElementById('regFlagTrigger');
        const selectedFlag = document.getElementById('regSelectedFlag');
        const selectedPrefix = document.getElementById('regSelectedPrefix');
        const phoneInput = document.getElementById('regPhoneInput');
        const validateBtn = document.getElementById('regValidateBtn');
        const validateIcon = document.getElementById('regValidateIcon');
        const countrySearch = document.getElementById('regCountrySearch');
        const countryList = document.getElementById('regCountryList');
        const modal = document.getElementById('regVerificationModal');
        const closeModalBtn = document.getElementById('regCloseModalBtn');
        const telegramPreStep = document.getElementById('regTelegramPreStep');
        const verificationCore = document.getElementById('regVerificationCore');
        const getTelegramCodeBtn = document.getElementById('regGetTelegramCodeBtn');
        const digits = document.querySelectorAll('#regCodeContainer .code-digit');
        const hiddenInput = document.getElementById('regHiddenCodeInput');
        const submitCodeBtn = document.getElementById('regSubmitCodeBtn');
        const resendLink = document.getElementById('regResendLink');
        const CONFIG = getConfig();

        // #region agent log
        (function logInitStyles() {
            const cs = phoneInput ? window.getComputedStyle(phoneInput) : null;
            const groupCs = window.getComputedStyle(phoneGroup);
            const modalCs = modal ? window.getComputedStyle(modal) : null;
            dbg('A', 'apollo-auth-phone.js:init', 'style_snapshot', {
                phoneClasses: phoneInput ? phoneInput.className : null,
                placeholder: phoneInput ? phoneInput.getAttribute('placeholder') : null,
                placeholderColor: cs ? cs.color && cs.getPropertyValue('caret-color') : null,
                inputBg: cs ? cs.backgroundColor : null,
                inputBorder: cs ? cs.border : null,
                inputRadius: cs ? cs.borderRadius : null,
                groupBg: groupCs.backgroundColor,
                groupRadius: groupCs.borderRadius,
                groupDisplay: groupCs.display,
                hasApolloInputClass: !!(phoneInput && phoneInput.classList.contains('apollo-input')),
            });
            dbg('B', 'apollo-auth-phone.js:init', 'lightbox_snapshot', {
                modalPresent: !!modal,
                modalDisplay: modalCs ? modalCs.display : null,
                modalVisibility: modalCs ? modalCs.visibility : null,
                modalOpacity: modalCs ? modalCs.opacity : null,
                prestepPresent: !!telegramPreStep,
                coreMuted: !!(verificationCore && verificationCore.classList.contains('is-muted')),
                digitCount: digits.length,
            });
            dbg('C', 'apollo-auth-phone.js:init', 'config_snapshot', {
                restUrl: CONFIG.restUrl,
                hasNonce: !!CONFIG.nonce,
                botUser: CONFIG.botUser,
                authPage: (window.apolloAuthConfig || {}).authPage || null,
                step1bHidden: !!document.getElementById('register-step-1b')?.hidden,
            });
        })();
        // #endregion

        const onCountrySelect = (country) => {
            if (selectedFlag) selectedFlag.src = `https://flagpedia.net/data/flags/icon/36x27/${country.flag}.png`;
            if (selectedPrefix) selectedPrefix.textContent = `+${country.prefix}`;
            phoneGroup.classList.remove('dropdown-open');
            if (countrySearch) countrySearch.value = '';
            renderCountries(countryList, selectedFlag, selectedPrefix, '', onCountrySelect);
            if (phoneInput) phoneInput.focus();
        };

        renderCountries(countryList, selectedFlag, selectedPrefix, '', onCountrySelect);

        if (flagTrigger) {
            flagTrigger.addEventListener('click', (e) => {
                e.stopPropagation();
                phoneGroup.classList.toggle('dropdown-open');
                if (phoneGroup.classList.contains('dropdown-open') && countrySearch) countrySearch.focus();
            });
        }

        if (countrySearch) {
            countrySearch.addEventListener('input', (e) => renderCountries(countryList, selectedFlag, selectedPrefix, e.target.value, onCountrySelect));
        }

        document.addEventListener('click', (e) => {
            if (!phoneGroup.contains(e.target)) phoneGroup.classList.remove('dropdown-open');
        });

        if (phoneInput) {
            phoneInput.addEventListener('input', function () {
                if (selectedPrefix && selectedPrefix.textContent === '+55') {
                    let val = this.value.replace(/\D/g, '').substring(0, 11);
                    let formatted = '';
                    if (val.length > 0) formatted = '(' + val.substring(0, 2);
                    if (val.length > 2) formatted += ') ' + val.substring(2, 3);
                    if (val.length > 3) formatted += ' ' + val.substring(3, 7);
                    if (val.length > 7) formatted += '-' + val.substring(7, 11);
                    this.value = formatted;
                } else {
                    this.value = this.value.replace(/[^0-9\s()-]/g, '');
                }
            });
            phoneInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    validateBtn?.click();
                }
            });
        }

        if (validateBtn) {
            validateBtn.addEventListener('click', async () => {
                if (verified) return;
                const prefix = (selectedPrefix?.textContent || '+55').replace('+', '');
                const digitsOnly = (phoneInput?.value || '').replace(/\D/g, '');
                // #region agent log
                dbg('E', 'apollo-auth-phone.js:validate', 'validate_click', {
                    prefix: prefix,
                    digitLen: digitsOnly.length,
                    rawLen: (phoneInput?.value || '').length,
                });
                // #endregion
                if (digitsOnly.length < 8) {
                    showFeedback('<i class="ri-error-warning-fill" style="color: #ef4444;"></i> <span style="color: #ef4444;">Número inválido</span>', false);
                    return;
                }

                currentPhone = `+${prefix}${digitsOnly}`;
                validateBtn.style.pointerEvents = 'none';
                if (validateIcon) validateIcon.className = 'ri-loader-4-line';
                if (phoneInput) phoneInput.disabled = true;
                if (flagTrigger) flagTrigger.style.pointerEvents = 'none';

                try {
                    const resp = await restPost('/request-support-verification', { phone: currentPhone });
                    const data = await resp.json();
                    // #region agent log
                    dbg('C', 'apollo-auth-phone.js:validate', 'request_support_result', {
                        httpOk: resp.ok,
                        status: resp.status,
                        success: !!data.success,
                        hasRequestId: !!data.request_id,
                        message: data.message || null,
                        phoneSuffix: currentPhone.slice(-4),
                    });
                    // #endregion
                    if (data.success) {
                        currentRequestId = data.request_id || null;
                        currentDeepLink = data.deep_link || (`https://t.me/${CONFIG.botUser}`);
                        setHiddenFields(currentPhone, currentRequestId, false);
                        setTimeout(() => {
                            openModal();
                            // #region agent log
                            const m = document.getElementById('regVerificationModal');
                            const mcs = m ? window.getComputedStyle(m) : null;
                            dbg('B', 'apollo-auth-phone.js:openModal', 'modal_after_open', {
                                hasActive: !!(m && m.classList.contains('is-active')),
                                display: mcs ? mcs.display : null,
                                visibility: mcs ? mcs.visibility : null,
                                opacity: mcs ? mcs.opacity : null,
                            });
                            // #endregion
                        }, 300);
                    } else {
                        showFeedback('<i class="ri-error-warning-fill" style="color: #ef4444;"></i> <span style="color: #ef4444;">' + (data.message || 'Erro ao iniciar verificação') + '</span>', false);
                        resetPhoneRow();
                    }
                } catch (err) {
                    // #region agent log
                    dbg('C', 'apollo-auth-phone.js:validate', 'request_support_network_error', {
                        error: String(err && err.message ? err.message : err),
                    });
                    // #endregion
                    showFeedback('<i class="ri-error-warning-fill" style="color: #ef4444;"></i> <span style="color: #ef4444;">Erro de rede</span>', false);
                    resetPhoneRow();
                }
            });
        }

        if (getTelegramCodeBtn) {
            getTelegramCodeBtn.addEventListener('click', () => {
                window.open(currentDeepLink || (`https://t.me/${CONFIG.botUser}`), '_blank');
                if (telegramPreStep) telegramPreStep.classList.add('is-hidden');
                if (verificationCore) verificationCore.classList.remove('is-muted');
                startPolling();
                setTimeout(() => digits[0]?.focus(), 300);
            });
        }

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => {
                if (!verified) closeModal(true);
                else closeModal(false);
            });
        }

        digits.forEach((digit, index) => {
            digit.addEventListener('input', () => {
                digit.classList.remove('is-error');
                digit.value = digit.value.replace(/[^0-9]/g, '');
                if (digit.value && index < digits.length - 1) digits[index + 1].focus();
                checkCodeCompletion(digits, submitCodeBtn);
            });
            digit.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !digit.value && index > 0) {
                    digits[index - 1].focus();
                    digits[index - 1].value = '';
                }
            });
        });

        if (modal) {
            modal.addEventListener('paste', (e) => {
                const pasteData = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                if (!pasteData) return;
                e.preventDefault();
                for (let i = 0; i < digits.length; i++) digits[i].value = pasteData[i] || '';
                checkCodeCompletion(digits, submitCodeBtn);
            });
        }

        if (submitCodeBtn) {
            submitCodeBtn.addEventListener('click', async () => {
                const code = Array.from(digits).map((d) => d.value).join('');
                if (!currentPhone || !currentRequestId || code.length !== 6) {
                    digits.forEach((d) => d.classList.add('is-error'));
                    return;
                }
                submitCodeBtn.textContent = '...';
                submitCodeBtn.style.pointerEvents = 'none';
                try {
                    const resp = await restPost('/verify-support-code', {
                        phone: currentPhone,
                        request_id: currentRequestId,
                        code,
                    });
                    const data = await resp.json();
                    if (data.success) {
                        verified = true;
                        setHiddenFields(currentPhone, currentRequestId, true);
                        stopPolling();
                        setTimeout(() => closeModal(false), 800);
                    } else {
                        digits.forEach((d) => d.classList.add('is-error'));
                        submitCodeBtn.textContent = data.message || 'Código inválido';
                        setTimeout(() => {
                            submitCodeBtn.textContent = 'Sou eu mesmo!';
                            submitCodeBtn.style.pointerEvents = 'auto';
                        }, 2000);
                    }
                } catch (err) {
                    submitCodeBtn.textContent = 'Erro de rede';
                    setTimeout(() => {
                        submitCodeBtn.textContent = 'Sou eu mesmo!';
                        submitCodeBtn.style.pointerEvents = 'auto';
                    }, 2000);
                }
            });
        }

        if (resendLink) {
            resendLink.addEventListener('click', async () => {
                if (!currentPhone) return;
                try {
                    const resp = await restPost('/request-support-verification', { phone: currentPhone });
                    const data = await resp.json();
                    if (data.success) {
                        currentRequestId = data.request_id || currentRequestId;
                        currentDeepLink = data.deep_link || currentDeepLink;
                        setHiddenFields(currentPhone, currentRequestId, false);
                        if (telegramPreStep) telegramPreStep.classList.remove('is-hidden');
                        if (verificationCore) verificationCore.classList.add('is-muted');
                        digits.forEach((d) => { d.value = ''; d.classList.remove('is-error'); });
                        checkCodeCompletion(digits, submitCodeBtn);
                    }
                } catch (e) { /* ignore */ }
            });
        }
    }

    window.ApolloPhoneVerify = {
        init,
        isVerified: () => verified || document.getElementById('phone_verified')?.value === '1',
        getPhone: () => document.getElementById('phone')?.value || currentPhone,
        getRequestId: () => document.getElementById('phone_request_id')?.value || currentRequestId,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
