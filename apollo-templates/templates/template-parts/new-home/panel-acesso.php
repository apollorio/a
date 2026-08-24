<?php

/**
 * Panel: Acesso — Login + Event Suggestion (Guest DOWN)
 *
 * Guest-only panel. Slides DOWN from home.
 * data-panel="acesso" data-dir="down"
 *
 * Contains:
 *   1. Login form (with AJAX support + post-login redirect via ?pra=)
 *   2. Register CTA link
 *   3. Public event suggestion form (mandatory: day, month, year, title, ticket URL)
 *
 * Logged users use panel-forms.php instead (separate panel).
 *
 * @package Apollo\Templates
 * @since   6.0.0
 * @see     _inventory/pages-layout.json → panels.acesso
 */
if (! defined('ABSPATH')) {
    exit;
}

$current_year  = (int) gmdate('Y');
$current_month = (int) gmdate('m');
?>
<section data-panel="acesso" data-glyph="▼">

    <!-- ── Header ── -->
    <header class="pnl-head">
        <button class="pnl-head__back" onclick="ApolloSlider&&ApolloSlider.back()" aria-label="Voltar">
            <i class="ri-arrow-left-s-line"></i>
        </button>
        <h2 class="pnl-head__title"><?php esc_html_e('Acesso', 'apollo-templates'); ?></h2>
    </header>

    <div class="pnl-body">

        <!-- ═══════════════════════════════════════════════════
			LOGIN FORM
		═══════════════════════════════════════════════════ -->
        <div class="pnl-form" id="acessoLogin">

            <div style="text-align:center;padding:var(--space-3) 0 var(--space-2)">
                <i class="apollo" style="font-size:32px" aria-label="Apollo"></i>
                <h3 style="font:700 var(--fs-h4)/1.2 var(--ff-mono);color:var(----txt-color-hover);margin:var(--space-3) 0 var(--space-1)"><?php esc_html_e('Entrar', 'apollo-templates'); ?></h3>
                <p style="font:400 13px/1.5 var(--ff-main);color:var(--txt-muted);margin:0"><?php esc_html_e('Acesse sua conta Apollo::Rio', 'apollo-templates'); ?></p>
            </div>

            <?php
            /**
             * Hook: apollo/login/render_panel
             * apollo-login can override the entire form.
             */
            do_action('apollo/login/render_panel');

            if (! has_action('apollo/login/render_panel')) :
            ?>
                <form id="acessoLoginForm" method="post" autocomplete="on">
                    <?php wp_nonce_field('apollo_acesso_login', 'acesso_login_nonce'); ?>

                    <div class="input-group">
                        <input type="text" id="acesso-user" name="log" class="apollo-input"
                            autocomplete="username" placeholder=" " required>
                        <label class="apollo-label" for="acesso-user">User<font style="font-family:system-ui; font-size:110%">::</font>rio ou e-mail</label>
                    </div>

                    <div class="input-group">
                        <input type="password" id="acesso-pass" name="pwd" class="apollo-input"
                            autocomplete="current-password" placeholder=" " required>
                        <label class="apollo-label" for="acesso-pass"><?php esc_html_e('Senha', 'apollo-templates'); ?></label>
                    </div>

                    <label class="custom-checkbox" style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;padding:var(--space-1) 0">
                        <input type="checkbox" name="rememberme" value="forever" style="accent-color:var(--primary)">
                        <span style="font:400 13px/1 var(--ff-main);color:var(--txt-muted)"><?php esc_html_e('Lembrar de mim', 'apollo-templates'); ?></span>
                    </label>

                    <button type="submit" class="pnl-btn pnl-btn--primary" id="acessoLoginSubmit"
                        style="width:100%;justify-content:center;padding:12px;font-size:14px;border-radius:var(--r-sm)">
                        <?php esc_html_e('Entrar', 'apollo-templates'); ?>
                    </button>

                    <div id="acessoLoginError" style="display:none;color:#ef4444;font:400 13px/1.4 var(--ff-main);text-align:center;padding:var(--space-2) 0"></div>
                </form>

                <div style="display:flex;justify-content:space-between;padding:var(--space-2) 0">
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" style="font:400 13px/1 var(--ff-main);color:var(--txt-muted);transition:var(--transition-ui)"><?php esc_html_e('Esqueci minha senha', 'apollo-templates'); ?></a>
                    <a href="<?php echo esc_url(home_url('/registre')); ?>" style="font:600 13px/1 var(--ff-main);color:var(--primary);transition:var(--transition-ui)"><?php esc_html_e('Criar conta', 'apollo-templates'); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <div class="pnl-divider"></div>

        <!-- ═══════════════════════════════════════════════════
			PUBLIC EVENT SUGGESTION FORM
			Open to all — no auth required.
			Mandatory: day, month, year, event name, ticket URL
			Optional: local, DJs
		═══════════════════════════════════════════════════ -->
        <div class="pnl-suggest" id="acessoSuggest">
            <h3 class="pnl-suggest__title">
                <i class="ri-calendar-event-fill" style="color:var(--primary);margin-right:var(--space-1)"></i>
                <?php esc_html_e('Sugerir Evento', 'apollo-templates'); ?>
            </h3>
            <p class="pnl-suggest__desc"><?php esc_html_e('Conhece um evento que não está na plataforma? Envie as informações.', 'apollo-templates'); ?></p>

            <form id="acessoSuggestForm" class="pnl-form" method="post">
                <?php wp_nonce_field('apollo_suggest_event', 'suggest_event_nonce'); ?>

                <!-- Date: Day / Month / Year -->
                <div class="input-group">
                    <div class="pnl-input__row">
                        <input type="number" name="event_day" class="apollo-input" placeholder="<?php esc_attr_e('Dia', 'apollo-templates'); ?>"
                            min="1" max="31" required>
                        <input type="number" name="event_month" class="apollo-input" placeholder="<?php esc_attr_e('Mês', 'apollo-templates'); ?>"
                            min="1" max="12" value="<?php echo esc_attr($current_month); ?>" required>
                        <input type="number" name="event_year" class="apollo-input" placeholder="<?php esc_attr_e('Ano', 'apollo-templates'); ?>"
                            min="<?php echo esc_attr($current_year); ?>" max="<?php echo esc_attr($current_year + 2); ?>"
                            value="<?php echo esc_attr($current_year); ?>" required>
                    </div>
                    <label class="apollo-label"><?php esc_html_e('Data do evento *', 'apollo-templates'); ?></label>
                </div>

                <!-- Event Name -->
                <div class="input-group">
                    <input type="text" id="suggest-name" name="event_name" class="apollo-input"
                        placeholder=" " required>
                    <label class="apollo-label" for="suggest-name"><?php esc_html_e('Nome do evento *', 'apollo-templates'); ?></label>
                </div>

                <!-- Ticket URL -->
                <div class="input-group">
                    <input type="url" id="suggest-url" name="event_ticket_url" class="apollo-input"
                        placeholder=" " required>
                    <label class="apollo-label" for="suggest-url"><?php esc_html_e('Link dos ingressos *', 'apollo-templates'); ?></label>
                </div>

                <!-- Optional: Local -->
                <div class="input-group">
                    <input type="text" id="suggest-local" name="event_local" class="apollo-input"
                        placeholder=" ">
                    <label class="apollo-label" for="suggest-local"><?php esc_html_e('Local', 'apollo-templates'); ?></label>
                </div>

                <!-- Optional: DJs -->
                <div class="input-group">
                    <input type="text" id="suggest-djs" name="event_djs" class="apollo-input"
                        placeholder=" ">
                    <label class="apollo-label" for="suggest-djs"><?php esc_html_e('DJs / Artistas', 'apollo-templates'); ?></label>
                </div>

                <button type="submit" class="pnl-btn pnl-btn--primary" id="acessoSuggestSubmit"
                    style="width:100%;justify-content:center;padding:12px;font-size:14px;border-radius:var(--r-sm)">
                    <i class="ri-send-plane-fill" style="margin-right:var(--space-1)"></i>
                    <?php esc_html_e('Enviar sugestão', 'apollo-templates'); ?>
                </button>

                <div id="acessoSuggestMsg" style="display:none;font:400 13px/1.4 var(--ff-main);text-align:center;padding:var(--space-2) 0"></div>
            </form>
        </div>

    </div>
</section>

<script>
    (function() {
        'use strict';

        /* ── AJAX Login ── */
        var loginForm = document.getElementById('acessoLoginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = document.getElementById('acessoLoginSubmit');
                var errEl = document.getElementById('acessoLoginError');
                var fd = new FormData(loginForm);
                fd.append('action', 'apollo_panel_login');

                btn.disabled = true;
                btn.textContent = '...';
                errEl.style.display = 'none';

                fetch(window.apolloNavbar ? window.apolloNavbar.ajaxUrl : '/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(res) {
                        if (res.success) {
                            /* Redirect with ?pra=chat for default post-login destination */
                            var pra = new URLSearchParams(location.search).get('pra') || 'chat';
                            location.href = location.pathname + '?pra=' + encodeURIComponent(pra);
                        } else {
                            errEl.textContent = res.data || 'Erro ao entrar';
                            errEl.style.display = '';
                            btn.disabled = false;
                            btn.textContent = 'Entrar';
                        }
                    })
                    .catch(function() {
                        errEl.textContent = 'Erro de conexão';
                        errEl.style.display = '';
                        btn.disabled = false;
                        btn.textContent = 'Entrar';
                    });
            });
        }

        /* ── Event Suggestion ── */
        var suggestForm = document.getElementById('acessoSuggestForm');
        if (suggestForm) {
            suggestForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = document.getElementById('acessoSuggestSubmit');
                var msgEl = document.getElementById('acessoSuggestMsg');
                var fd = new FormData(suggestForm);
                fd.append('action', 'apollo_suggest_event');

                btn.disabled = true;

                fetch(window.apolloNavbar ? window.apolloNavbar.ajaxUrl : '/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(res) {
                        msgEl.style.display = '';
                        if (res.success) {
                            msgEl.style.color = '#22c55e';
                            msgEl.textContent = res.data || 'Sugestão enviada com sucesso!';
                            suggestForm.reset();
                        } else {
                            msgEl.style.color = '#ef4444';
                            msgEl.textContent = res.data || 'Erro ao enviar';
                        }
                        btn.disabled = false;
                    })
                    .catch(function() {
                        msgEl.style.display = '';
                        msgEl.style.color = '#ef4444';
                        msgEl.textContent = 'Erro de conexão';
                        btn.disabled = false;
                    });
            });
        }
    })();
</script>