<?php
/**
 * Single Event — RSVP + Warm-up Mural
 *
 * Two-step presence system:
 *   1. RSVP widget  — "Vou!" (going) / "Quero.." (interested)
 *   2. Vibe popup   — A) dançar 👯  B) entrar na pixta 😈
 *   3. Warm-up mural — timeline auto-populada ao fazer RSVP
 *
 * RSVP REST  : POST /eventos/{id}/participantes  { status: 'going'|'interested' }
 * Warmup GET : GET  /depoimentos?post_id=X&limit=20
 * Warmup POST: POST /depoimentos             { post_id, content }
 *
 * @package Apollo\Event
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

/* ─── Current user data ──────────────────────────────────── */
$uid       = get_current_user_id();
$is_logged = (bool) $uid;
$ud        = $is_logged ? get_userdata( $uid ) : false;
$uname     = $ud ? $ud->user_login        : '';
$dname     = $ud ? $ud->display_name      : '';

/* ─── Existing RSVP? ─────────────────────────────────────── */
$user_rsvp = null;
if ( $is_logged && $post_id ) {
    $user_rsvp = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT status
               FROM {$wpdb->prefix}apollo_event_rsvp
              WHERE event_id = %d
                AND user_id  = %d
              LIMIT 1",
            $post_id,
            $uid
        )
    );
}

$warmup_open  = ! empty( $user_rsvp );
$going_active = ( 'going'      === $user_rsvp ) ? ' is-active' : '';
$maybe_active = ( 'interested' === $user_rsvp ) ? ' is-active' : '';

/* ─── Warmup muted? ───────────────────────────────────────── */
$warmup_muted = false;
if ( $is_logged ) {
    $muted_list   = get_user_meta( $uid, '_apollo_warmup_muted', true );
    $warmup_muted = is_array( $muted_list ) && in_array( $post_id, $muted_list, true );
}

/* ─── RSVP counts + avatar stack ──────────────────────────── */
$rsvp_going      = 0;
$rsvp_interested = 0;
$rsvp_avatars    = array();
$rsvp_total      = 0;
if ( $post_id ) {
    $rsvp_counts = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT status, COUNT(*) AS cnt
               FROM {$wpdb->prefix}apollo_event_rsvp
              WHERE event_id = %d AND status IN ('going','interested')
              GROUP BY status",
            $post_id
        )
    );
    foreach ( $rsvp_counts as $rc ) {
        if ( 'going' === $rc->status )      { $rsvp_going      = (int) $rc->cnt; }
        if ( 'interested' === $rc->status ) { $rsvp_interested = (int) $rc->cnt; }
    }
    $rsvp_total = $rsvp_going + $rsvp_interested;

    $stack_uids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT user_id
               FROM {$wpdb->prefix}apollo_event_rsvp
              WHERE event_id = %d AND status IN ('going','interested')
              ORDER BY created_at DESC LIMIT 5",
            $post_id
        )
    );
    foreach ( $stack_uids as $suid ) {
        $rsvp_avatars[] = get_avatar_url( (int) $suid, array( 'size' => 64 ) );
    }
}

/* ─── Security tokens ────────────────────────────────────── */
$nonce     = wp_create_nonce( 'wp_rest' );
$rest_base = rest_url( 'apollo/v1' );
?>

<!-- ─── RSVP Widget ──────────────────────────────────────── -->
<div class="a-eve-rsvp-widget"
     data-a-component="rsvp"
     data-event-id="<?php echo esc_attr( (string) $post_id ); ?>"
     data-a-type="event-rsvp">

    <div class="a-eve-rsvp-actions">
        <?php if ( $is_logged ) : ?>

            <button
                class="a-eve-rsvp-btn a-eve-rsvp-btn--going<?php echo esc_attr( $going_active ); ?>"
                data-intent="going"
                type="button"
                aria-pressed="<?php echo $going_active ? 'true' : 'false'; ?>">
                <span class="a-eve-rsvp-btn__label">Vou! 🎉</span>
                <?php if ( $rsvp_going ) : ?>
                <span class="a-eve-rsvp-btn__count"><?php echo esc_html( (string) $rsvp_going ); ?></span>
                <?php endif; ?>
                <span class="a-eve-rsvp-btn__spinner" aria-hidden="true"></span>
            </button>

            <button
                class="a-eve-rsvp-btn a-eve-rsvp-btn--maybe<?php echo esc_attr( $maybe_active ); ?>"
                data-intent="interested"
                type="button"
                aria-pressed="<?php echo $maybe_active ? 'true' : 'false'; ?>">
                <span class="a-eve-rsvp-btn__label">Quero.. 🔥</span>
                <?php if ( $rsvp_interested ) : ?>
                <span class="a-eve-rsvp-btn__count"><?php echo esc_html( (string) $rsvp_interested ); ?></span>
                <?php endif; ?>
                <span class="a-eve-rsvp-btn__spinner" aria-hidden="true"></span>
            </button>

        <?php else : ?>

            <a href="<?php echo esc_url( home_url( '/acesso' ) ); ?>"
               class="a-eve-rsvp-btn a-eve-rsvp-btn--going">
                <span class="a-eve-rsvp-btn__label">Vou! 🎉</span>
                <?php if ( $rsvp_going ) : ?>
                <span class="a-eve-rsvp-btn__count"><?php echo esc_html( (string) $rsvp_going ); ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo esc_url( home_url( '/acesso' ) ); ?>"
               class="a-eve-rsvp-btn a-eve-rsvp-btn--maybe">
                <span class="a-eve-rsvp-btn__label">Quero.. 🔥</span>
                <?php if ( $rsvp_interested ) : ?>
                <span class="a-eve-rsvp-btn__count"><?php echo esc_html( (string) $rsvp_interested ); ?></span>
                <?php endif; ?>
            </a>

        <?php endif; ?>
    </div>

    <?php if ( $rsvp_avatars ) : ?>
    <div class="a-eve-rsvp-social">
        <div class="a-eve-rsvp-social__stack">
            <?php foreach ( $rsvp_avatars as $av_url ) : ?>
            <img class="a-eve-rsvp-social__avatar" src="<?php echo esc_url( $av_url ); ?>" alt="" loading="lazy">
            <?php endforeach; ?>
        </div>
        <span class="a-eve-rsvp-social__label">
            <?php echo esc_html( $rsvp_total . ( 1 === $rsvp_total ? ' pessoa confirmada' : ' pessoas confirmadas' ) ); ?>
        </span>
    </div>
    <?php endif; ?>

</div>

<?php if ( $is_logged ) : ?>

<!-- ─── Vibe Popup Modal ──────────────────────────────────── -->
<div class="a-eve-vibe-modal"
     id="a-eve-vibe-modal"
     data-a-component="vibe-popup"
     hidden>

    <div class="a-eve-vibe-modal__overlay" id="a-eve-vibe-overlay"></div>

    <div class="a-eve-vibe-modal__box"
         role="dialog"
         aria-modal="true"
         aria-labelledby="a-eve-vibe-title">

        <button class="a-eve-vibe-modal__close" id="a-eve-vibe-close" type="button" aria-label="Fechar">
            <i class="ri-close-line" aria-hidden="true"></i>
        </button>

        <div class="a-eve-vibe-modal__drag" aria-hidden="true"></div>

        <h3 class="a-eve-vibe-modal__title" id="a-eve-vibe-title">
            Nessa gig eu quero...
        </h3>
        <p class="a-eve-vibe-modal__sub">sem compromisso, só vibes ✨</p>

        <button class="a-eve-vibe-modal__opt" data-vibe="dance" type="button">
            A) curtir com amigos, dançar e música boa.. 👯
        </button>

        <button class="a-eve-vibe-modal__opt" data-vibe="pixta" type="button">
            B) curtir música, amigos e aberto para ser curtido tambem 😈
        </button>

    </div>
</div>

<?php endif; ?>

<!-- ─── Warm-up Mural ─────────────────────────────────────── -->
<section class="a-eve-warmup"
         id="a-eve-warmup"
         data-a-component="warmup"
         data-post-id="<?php echo esc_attr( (string) $post_id ); ?>"
         <?php echo $warmup_open ? '' : 'hidden'; ?>>

    <div class="a-eve-warmup__header">
        <h3 class="a-eve-warmup__title">
            <i class="ri-fire-line" aria-hidden="true"></i>
            Warm-up
            <span class="a-eve-warmup__count" id="a-eve-warmup-count"></span>
        </h3>

        <?php if ( $is_logged ) : ?>
        <button
            class="a-eve-warmup__notif-toggle<?php echo $warmup_muted ? ' is-muted' : ''; ?>"
            id="a-eve-warmup-notif-toggle"
            type="button"
            title="<?php echo $warmup_muted ? esc_attr__( 'Notificações desativadas', 'apollo-events' ) : esc_attr__( 'Notificações ativadas', 'apollo-events' ); ?>"
            aria-pressed="<?php echo $warmup_muted ? 'false' : 'true'; ?>"
            data-muted="<?php echo $warmup_muted ? '1' : '0'; ?>">
            <i class="<?php echo $warmup_muted ? 'ri-notification-off-line' : 'ri-notification-3-line'; ?>" aria-hidden="true"></i>
        </button>
        <?php endif; ?>
    </div>

    <?php if ( $is_logged && $warmup_open ) : ?>
    <div class="a-eve-warmup__composer" id="a-eve-warmup-composer">
        <img class="a-eve-warmup__composer-avatar"
             src="<?php echo esc_url( get_avatar_url( $uid, array( 'size' => 64 ) ) ); ?>"
             alt="" loading="lazy">
        <input
            class="a-eve-warmup__composer-input"
            id="a-eve-warmup-input"
            type="text"
            placeholder="Diz pro warm-up..."
            maxlength="280"
            autocomplete="off">
        <button class="a-eve-warmup__composer-send" id="a-eve-warmup-send" type="button" disabled
                aria-label="<?php esc_attr_e( 'Enviar', 'apollo-events' ); ?>">
            <i class="ri-send-plane-fill" aria-hidden="true"></i>
        </button>
    </div>
    <?php endif; ?>

    <div class="a-eve-warmup__feed"
         id="a-eve-warmup-feed"
         aria-live="polite"
         aria-label="<?php esc_attr_e( 'Feed do Warm-up', 'apollo-events' ); ?>">
        <!-- posts carregados via JS -->
    </div>

    <?php if ( ! $is_logged ) : ?>
    <p class="a-eve-warmup__login-cta">
        <i class="ri-fire-line" aria-hidden="true"></i>
        <span>
            <a href="<?php echo esc_url( home_url( '/acesso' ) ); ?>">
                <?php esc_html_e( 'Faça login', 'apollo-events' ); ?>
            </a>
            <?php esc_html_e( ' e confirme presença para ver o Warm-up', 'apollo-events' ); ?>
        </span>
    </p>
    <?php endif; ?>

</section>

<?php if ( $is_logged ) : ?>
<script>
(function () {
    'use strict';

    /* ═══ CONFIG ═══ */
    var CFG = {
        eventId:      <?php echo (int) $post_id; ?>,
        postId:       <?php echo (int) $post_id; ?>,
        restBase:     <?php echo wp_json_encode( untrailingslashit( $rest_base ) ); ?>,
        nonce:        <?php echo wp_json_encode( $nonce ); ?>,
        displayName:  <?php echo wp_json_encode( $dname ); ?>,
        profileUrl:   <?php echo wp_json_encode( $uname ? home_url( '/id/' . $uname ) : '' ); ?>,
        avatarUrl:    <?php echo wp_json_encode( get_avatar_url( $uid, array( 'size' => 88 ) ) ); ?>,
        currentRsvp:  <?php echo wp_json_encode( $user_rsvp ); ?>,
        acesso:       <?php echo wp_json_encode( home_url( '/acesso' ) ); ?>,
        warmupMuted:  <?php echo $warmup_muted ? 'true' : 'false'; ?>,
        goingCount:   <?php echo (int) $rsvp_going; ?>,
        intCount:     <?php echo (int) $rsvp_interested; ?>,
    };

    /* ═══ DOM REFS ═══ */
    var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
    var $$ = function (sel, ctx) { return (ctx || document).querySelectorAll(sel); };

    var modal      = $('#a-eve-vibe-modal');
    var overlay    = $('#a-eve-vibe-overlay');
    var closeBtn   = $('#a-eve-vibe-close');
    var warmup     = $('#a-eve-warmup');
    var feed       = $('#a-eve-warmup-feed');
    var countEl    = $('#a-eve-warmup-count');
    var notifBtn   = $('#a-eve-warmup-notif-toggle');
    var compInput  = $('#a-eve-warmup-input');
    var compSend   = $('#a-eve-warmup-send');
    var rsvpBtns   = $$('.a-eve-rsvp-btn[data-intent]');
    var vibeBtns   = $$('.a-eve-vibe-modal__opt');

    var pendingIntent = null;
    var isSubmitting  = false;
    var refreshTimer  = null;
    var G = typeof gsap !== 'undefined' ? gsap : null;

    /* ═══ API HELPERS ═══ */
    function apiPost(url, data) {
        return fetch(url, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce },
            body: JSON.stringify(data),
        });
    }

    /* ═══ MODAL CONTROL ═══ */
    function openModal() {
        if (!modal) return;
        modal.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
        var box = $('.a-eve-vibe-modal__box', modal);
        if (G && box) {
            G.fromTo(box, { y: '100%', opacity: 0 }, { y: 0, opacity: 1, duration: 0.38, ease: 'back.out(1.4)' });
            G.fromTo($$('.a-eve-vibe-modal__opt', modal), { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.3, stagger: 0.08, delay: 0.18, ease: 'power2.out' });
        }
    }

    function closeModal() {
        if (!modal) return;
        var box = $('.a-eve-vibe-modal__box', modal);
        var finish = function () {
            modal.setAttribute('hidden', '');
            document.body.style.overflow = '';
            pendingIntent = null;
        };
        if (G && box) {
            G.to(box, { y: '100%', opacity: 0, duration: 0.22, ease: 'power2.in', onComplete: finish });
        } else {
            finish();
        }
    }

    /* ═══ WARMUP REVEAL ═══ */
    function revealWarmup() {
        if (!warmup) return;
        warmup.removeAttribute('hidden');
        if (G) {
            G.fromTo(warmup, { opacity: 0, y: 30 }, { opacity: 1, y: 0, duration: 0.5, ease: 'power2.out' });
        }
        setTimeout(function () {
            warmup.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 260);
    }

    /* ═══ BUTTON STATE ═══ */
    function setActiveBtn(intent) {
        rsvpBtns.forEach(function (btn) {
            var isTarget = btn.dataset.intent === intent;
            btn.classList.toggle('is-active', isTarget);
            btn.setAttribute('aria-pressed', isTarget ? 'true' : 'false');
        });
    }

    function setBtnLoading(loading) {
        rsvpBtns.forEach(function (b) {
            b.disabled = loading;
            b.classList.toggle('is-loading', loading);
        });
    }

    function updateBtnCount(intent, delta) {
        rsvpBtns.forEach(function (btn) {
            if (btn.dataset.intent !== intent) return;
            var el = $('.a-eve-rsvp-btn__count', btn);
            if (intent === 'going') { CFG.goingCount = Math.max(0, CFG.goingCount + delta); }
            else { CFG.intCount = Math.max(0, CFG.intCount + delta); }
            var val = intent === 'going' ? CFG.goingCount : CFG.intCount;
            if (val > 0) {
                if (!el) {
                    el = document.createElement('span');
                    el.className = 'a-eve-rsvp-btn__count';
                    var spinner = $('.a-eve-rsvp-btn__spinner', btn);
                    btn.insertBefore(el, spinner);
                }
                el.textContent = val;
            } else if (el) {
                el.remove();
            }
        });
    }

    /* ═══ TOAST ═══ */
    function showToast(msg, isError) {
        var existing = document.getElementById('a-eve-toast');
        if (existing) existing.remove();
        var toast = document.createElement('div');
        toast.id = 'a-eve-toast';
        toast.className = 'a-eve-toast' + (isError ? ' is-error' : '');

        var icon = document.createElement('i');
        icon.className = isError ? 'ri-error-warning-line' : 'ri-check-line';
        icon.setAttribute('aria-hidden', 'true');
        toast.appendChild(icon);

        var txt = document.createElement('span');
        txt.textContent = msg;
        toast.appendChild(txt);

        document.body.appendChild(toast);
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { toast.classList.add('is-visible'); });
        });
        setTimeout(function () {
            toast.classList.remove('is-visible');
            setTimeout(function () { toast.remove(); }, 320);
        }, 3200);
    }

    /* ═══ TIME HELPERS ═══ */
    function humanTime(dateStr) {
        var secs = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
        if (secs < 60)    return 'agora';
        if (secs < 3600)  return Math.floor(secs / 60) + 'min';
        if (secs < 86400) return Math.floor(secs / 3600) + 'h';
        return Math.floor(secs / 86400) + 'd';
    }

    /* ═══ SKELETON ═══ */
    function showSkeleton() {
        if (!feed) return;
        var html = '';
        for (var i = 0; i < 3; i++) {
            html += '<div class="a-eve-warmup-card a-eve-skeleton">' +
                '<div class="a-eve-skeleton__circle"></div>' +
                '<div class="a-eve-skeleton__body">' +
                    '<div class="a-eve-skeleton__line" style="width:40%"></div>' +
                    '<div class="a-eve-skeleton__line" style="width:80%"></div>' +
                    '<div class="a-eve-skeleton__line" style="width:25%"></div>' +
                '</div></div>';
        }
        feed.innerHTML = html;
    }

    /* ═══ RENDER CARD (DOM-safe) ═══ */
    function renderCard(item, isLatest) {
        var div = document.createElement('div');
        div.className = 'a-eve-warmup-card' + (isLatest ? ' is-latest' : '');

        var img  = document.createElement('img');
        img.className = 'a-eve-warmup-card__avatar';
        img.src       = (item.author && item.author.avatar) || '';
        img.alt       = (item.author && item.author.name) || '';
        img.loading   = 'lazy';

        var body = document.createElement('div');
        body.className = 'a-eve-warmup-card__body';

        var link = document.createElement('a');
        link.className   = 'a-eve-warmup-card__author';
        link.href        = (item.author && item.author.profile_url) || '#';
        link.textContent = (item.author && item.author.name) || 'Usuário';

        var p = document.createElement('p');
        p.className   = 'a-eve-warmup-card__text';
        p.textContent = item.content || '';

        var span = document.createElement('span');
        span.className   = 'a-eve-warmup-card__time';
        span.textContent = item.date ? humanTime(item.date) : '';

        body.appendChild(link);
        body.appendChild(p);
        body.appendChild(span);
        div.appendChild(img);
        div.appendChild(body);
        return div;
    }

    /* ═══ RENDER OPTIMISTIC CARD ═══ */
    function renderOptimistic(text) {
        var item = {
            author: { avatar: CFG.avatarUrl, name: CFG.displayName, profile_url: CFG.profileUrl },
            content: text,
            date: new Date().toISOString(),
        };
        var card = renderCard(item, true);
        card.classList.add('is-optimistic');
        if (feed) {
            var firstChild = feed.firstChild;
            if (firstChild) { feed.insertBefore(card, firstChild); }
            else { feed.appendChild(card); }
        }
        if (G) { G.fromTo(card, { opacity: 0, y: -16, scale: 0.97 }, { opacity: 1, y: 0, scale: 1, duration: 0.4, ease: 'back.out(1.6)' }); }
        return card;
    }

    /* ═══ LOAD WARMUP FEED ═══ */
    function loadWarmup(animate) {
        if (!feed) return;
        if (animate) showSkeleton();

        fetch(CFG.restBase + '/depoimentos?post_id=' + CFG.postId + '&limit=20', { credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            if (!data) return;
            var items = data.depoimentos || [];

            if (countEl) { countEl.textContent = data.total ? '(' + data.total + ')' : ''; }

            feed.innerHTML = '';

            if (!items.length) {
                var empty = document.createElement('p');
                empty.className = 'a-eve-warmup__empty';
                empty.textContent = 'Ninguém postou ainda — seja o primeiro ao confirmar presença!';
                feed.appendChild(empty);
                return;
            }

            items.forEach(function (item, idx) {
                feed.appendChild(renderCard(item, idx === 0));
            });

            /* GSAP stagger reveal */
            if (animate && G) {
                G.fromTo(
                    feed.querySelectorAll('.a-eve-warmup-card'),
                    { opacity: 0, y: 18 },
                    { opacity: 1, y: 0, duration: 0.35, stagger: 0.06, ease: 'power2.out', delay: 0.08 }
                );
            }
        })
        .catch(function () {});
    }

    /* ═══ AUTO-REFRESH (IntersectionObserver-gated) ═══ */
    function startAutoRefresh() {
        if (!warmup || !('IntersectionObserver' in window)) return;
        var isVisible = false;
        var obs = new IntersectionObserver(function (entries) {
            isVisible = entries[0] && entries[0].isIntersecting;
        }, { threshold: 0.1 });
        obs.observe(warmup);

        refreshTimer = setInterval(function () {
            if (isVisible && !document.hidden) loadWarmup(false);
        }, 30000);
    }

    /* ═══ COMPOSER ═══ */
    if (compInput && compSend) {
        compInput.addEventListener('input', function () {
            compSend.disabled = !this.value.trim();
        });
        compInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !compSend.disabled) { compSend.click(); }
        });
        compSend.addEventListener('click', function () {
            var text = compInput.value.trim();
            if (!text || isSubmitting) return;
            isSubmitting = true;
            compSend.disabled = true;
            compInput.value = '';

            var optimisticCard = renderOptimistic(text);

            apiPost(CFG.restBase + '/depoimentos', { post_id: CFG.postId, content: text })
            .then(function (r) {
                if (!r.ok) throw new Error('post');
                showToast('Postado no warm-up!');
                /* Reload to get authoritative data */
                setTimeout(function () { loadWarmup(false); }, 800);
            })
            .catch(function () {
                if (optimisticCard) optimisticCard.remove();
                showToast('Erro ao postar. Tente novamente.', true);
                compInput.value = text;
            })
            .finally(function () {
                isSubmitting = false;
                compSend.disabled = !compInput.value.trim();
            });
        });
    }

    /* ═══ RSVP CLICK ═══ */
    rsvpBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (isSubmitting) return;
            var intent = this.dataset.intent;

            if (CFG.currentRsvp) {
                if (CFG.currentRsvp === intent) {
                    revealWarmup();
                    loadWarmup(false);
                    return;
                }
                /* Switch RSVP going ↔ interested */
                isSubmitting = true;
                setBtnLoading(true);

                var oldIntent = CFG.currentRsvp;
                /* Optimistic UI */
                CFG.currentRsvp = intent;
                setActiveBtn(intent);
                updateBtnCount(intent, 1);
                updateBtnCount(oldIntent, -1);

                apiPost(CFG.restBase + '/eventos/' + CFG.eventId + '/participantes', { status: intent })
                .then(function (r) {
                    if (!r.ok) throw new Error('rsvp');
                    showToast(intent === 'going' ? 'Você agora vai! 🎉' : 'Marcado como interessade 🔥');
                })
                .catch(function () {
                    /* Rollback optimistic */
                    CFG.currentRsvp = oldIntent;
                    setActiveBtn(oldIntent);
                    updateBtnCount(intent, -1);
                    updateBtnCount(oldIntent, 1);
                    showToast('Erro ao atualizar. Tente novamente.', true);
                })
                .finally(function () {
                    isSubmitting = false;
                    setBtnLoading(false);
                });
                return;
            }

            pendingIntent = intent;
            openModal();
        });
    });

    /* ═══ VIBE CLICK ═══ */
    vibeBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (isSubmitting) return;
            isSubmitting = true;

            var vibe   = this.dataset.vibe;
            var intent = pendingIntent || 'going';
            closeModal();

            var vibeMsg = (vibe === 'dance')
                ? CFG.displayName + ' veio dançar no warm-up 👯'
                : CFG.displayName + ' entrou na pixta 😈';

            setBtnLoading(true);

            /* Optimistic: update button immediately */
            CFG.currentRsvp = intent;
            setActiveBtn(intent);
            updateBtnCount(intent, 1);

            apiPost(CFG.restBase + '/eventos/' + CFG.eventId + '/participantes', { status: intent })
            .then(function (r) {
                if (!r.ok) throw new Error('rsvp');
                return apiPost(CFG.restBase + '/depoimentos', { post_id: CFG.postId, content: vibeMsg });
            })
            .then(function (r) {
                if (!r.ok) throw new Error('depoimento');
                revealWarmup();
                loadWarmup(true);
                showToast(intent === 'going' ? 'Presença confirmada! 🎉' : 'Marcado como interessade 🔥');
                startAutoRefresh();
            })
            .catch(function () {
                /* Rollback */
                CFG.currentRsvp = null;
                setActiveBtn('');
                updateBtnCount(intent, -1);
                showToast('Erro ao confirmar. Tente novamente.', true);
            })
            .finally(function () {
                isSubmitting = false;
                setBtnLoading(false);
            });
        });
    });

    /* ═══ NOTIFICATION TOGGLE ═══ */
    if (notifBtn) {
        notifBtn.addEventListener('click', function () {
            var isMuted = notifBtn.dataset.muted === '1';
            var newMuted = !isMuted;

            /* Optimistic */
            var icon = notifBtn.querySelector('i');
            if (icon) icon.className = newMuted ? 'ri-notification-off-line' : 'ri-notification-3-line';
            notifBtn.classList.toggle('is-muted', newMuted);
            notifBtn.dataset.muted = newMuted ? '1' : '0';
            notifBtn.setAttribute('aria-pressed', newMuted ? 'false' : 'true');
            notifBtn.title = newMuted ? 'Notificações desativadas' : 'Notificações ativadas';

            apiPost(CFG.restBase + '/eventos/' + CFG.eventId + '/notificar-warmup', { muted: newMuted })
            .then(function (r) {
                if (!r.ok) throw new Error('notify');
                showToast(newMuted ? 'Warm-up silenciado' : 'Notificações ativadas 🔔');
            })
            .catch(function () {
                /* Rollback */
                if (icon) icon.className = isMuted ? 'ri-notification-off-line' : 'ri-notification-3-line';
                notifBtn.classList.toggle('is-muted', isMuted);
                notifBtn.dataset.muted = isMuted ? '1' : '0';
                showToast('Erro ao alterar notificações.', true);
            });
        });
    }

    /* ═══ MODAL DISMISS (overlay, close btn, Escape) ═══ */
    if (overlay) overlay.addEventListener('click', closeModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && !modal.hasAttribute('hidden')) closeModal();
    });

    /* ═══ INIT ═══ */
    if (CFG.currentRsvp) {
        loadWarmup(true);
        startAutoRefresh();
    }

}());
</script>
<?php endif; ?>
