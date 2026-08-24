/**
 * Apollo Sign — Placement JS
 * State-machine driven PDF viewer + interactive signature placement.
 *
 * Dependencies (loaded before this script):
 *   - PDF.js (window.pdfjsLib via ESM module)
 *   - interact.js (window.interact via UMD)
 *   - window.apolloSignConfig (inline from scripts.php)
 *
 * States: IDLE → PLACING → PLACED → DRAGGING/RESIZING
 *
 * Coordinate system:
 *   Frontend: canvas pixels, origin top-left
 *   Storage:  fractions 0.0–1.0 (x/canvasW, y/canvasH)
 *   Backend:  PDF points (72 pt/in), origin bottom-left (Y inverted)
 *
 * @package Apollo\Sign
 * @version 1.1.0
 */
(function () {
    'use strict';

    /* ───── Guard ───── */
    var cfg = window.apolloSignConfig;
    if (!cfg || !cfg.pdfUrl) return;

    /* ───── State ───── */
    var STATE = {
        IDLE:     'idle',
        PLACING:  'placing',
        PLACED:   'placed',
        DRAGGING: 'dragging',
        RESIZING: 'resizing'
    };

    var state = STATE.IDLE;

    /* ───── Defaults (fractions 0-1) ───── */
    var placement = {
        x:    cfg.defaults.x,
        y:    cfg.defaults.y,
        w:    cfg.defaults.w,
        h:    cfg.defaults.h,
        page: 1,
        mode: 'auto_footer'
    };

    /* ───── DOM refs ───── */
    var section    = document.getElementById('apollo-pdf-section');
    var wrap       = document.getElementById('apollo-pdf-wrap');
    var canvas     = document.getElementById('apollo-pdf-canvas');
    var overlay    = document.getElementById('apollo-sign-overlay');
    var rect       = document.getElementById('apollo-sign-rect');
    var placeBtn   = document.getElementById('apollo-place-btn');
    var resetBtn   = document.getElementById('apollo-reset-btn');
    var toast      = document.getElementById('apollo-placement-toast');
    var modal      = document.getElementById('apollo-placement-modal');
    var modalClose = document.getElementById('apollo-modal-close');
    var prevBtn    = document.getElementById('apollo-pdf-prev');
    var nextBtn    = document.getElementById('apollo-pdf-next');
    var pageNum    = document.getElementById('apollo-pdf-page-num');
    var pageTotal  = document.getElementById('apollo-pdf-page-total');

    /* Hidden form fields */
    var fX    = document.getElementById('sign-placement-x');
    var fY    = document.getElementById('sign-placement-y');
    var fW    = document.getElementById('sign-placement-w');
    var fH    = document.getElementById('sign-placement-h');
    var fPage = document.getElementById('sign-placement-page');
    var fMode = document.getElementById('sign-placement-mode');

    if (!section || !canvas || !wrap) return;

    /* ───── PDF.js vars ───── */
    var pdfDoc    = null;
    var currentPg = 1;
    var totalPgs  = 1;
    var rendering = false;
    var scale     = 1;

    /* Min rect size in CSS px */
    var MIN_W = 80;
    var MIN_H = 30;

    /* ───── Add has-pdf class ───── */
    document.body.classList.add('has-pdf');

    /* ───── Utility ───── */
    function showToast(msg, type, duration) {
        if (!toast) return;
        toast.textContent = msg;
        toast.className = 'sign-toast' + (type ? ' ' + type : '');
        toast.style.display = 'block';
        clearTimeout(toast._timer);
        toast._timer = setTimeout(function () {
            toast.style.display = 'none';
        }, duration || 3000);
    }

    function syncHiddenFields() {
        if (fX)    fX.value    = placement.x.toFixed(4);
        if (fY)    fY.value    = placement.y.toFixed(4);
        if (fW)    fW.value    = placement.w.toFixed(4);
        if (fH)    fH.value    = placement.h.toFixed(4);
        if (fPage) fPage.value = placement.page;
        if (fMode) fMode.value = placement.mode;
    }

    function clamp(val, min, max) {
        return Math.max(min, Math.min(max, val));
    }

    /* ───── PDF Rendering ───── */
    function renderPage(num) {
        if (rendering) return;
        rendering = true;
        currentPg = num;

        pdfDoc.getPage(num).then(function (page) {
            var viewport = page.getViewport({ scale: 1 });
            var containerW = wrap.clientWidth;
            scale = containerW / viewport.width;
            var scaled = page.getViewport({ scale: scale });

            canvas.width  = scaled.width;
            canvas.height = scaled.height;

            var ctx = canvas.getContext('2d');
            var renderCtx = { canvasContext: ctx, viewport: scaled };

            page.render(renderCtx).promise.then(function () {
                rendering = false;
                if (pageNum)   pageNum.textContent   = num;
                if (pageTotal) pageTotal.textContent  = totalPgs;
                if (prevBtn)   prevBtn.disabled = (num <= 1);
                if (nextBtn)   nextBtn.disabled = (num >= totalPgs);

                /* Update overlay size to match canvas */
                overlay.style.width  = canvas.width  + 'px';
                overlay.style.height = canvas.height + 'px';

                /* If rect is visible and on this page, re-position */
                if (state !== STATE.IDLE && placement.page === num) {
                    positionRectFromFractions();
                }
            });
        });
    }

    function initPdf() {
        var ready = function () {
            if (!window.pdfjsLib) {
                showToast('PDF.js não carregado.', 'warning');
                return;
            }

            var loadingTask = window.pdfjsLib.getDocument(cfg.pdfUrl);
            loadingTask.promise.then(function (pdf) {
                pdfDoc   = pdf;
                totalPgs = pdf.numPages;
                renderPage(1);
            }).catch(function (err) {
                console.error('Apollo Sign PDF error:', err);
                showToast('Erro ao carregar PDF.', 'warning');
            });
        };

        if (window.pdfjsLib) {
            ready();
        } else {
            window.addEventListener('pdfjsReady', ready);
        }
    }

    /* ───── Page navigation ───── */
    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            if (currentPg > 1) renderPage(currentPg - 1);
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            if (currentPg < totalPgs) renderPage(currentPg + 1);
        });
    }

    /* ───── Rect positioning ───── */
    function positionRectFromFractions() {
        var cw = canvas.width;
        var ch = canvas.height;

        var left   = placement.x * cw;
        var top    = placement.y * ch;
        var width  = placement.w * cw;
        var height = placement.h * ch;

        rect.style.left   = left   + 'px';
        rect.style.top    = top    + 'px';
        rect.style.width  = width  + 'px';
        rect.style.height = height + 'px';
        rect.style.display = 'block';

        /* Reset transform (interact.js uses data-x/data-y) */
        rect.setAttribute('data-x', 0);
        rect.setAttribute('data-y', 0);
        rect.style.transform = 'translate(0px, 0px)';
    }

    function updateFractionsFromRect() {
        var cw = canvas.width;
        var ch = canvas.height;

        var dx = parseFloat(rect.getAttribute('data-x')) || 0;
        var dy = parseFloat(rect.getAttribute('data-y')) || 0;
        var left   = parseFloat(rect.style.left)   + dx;
        var top    = parseFloat(rect.style.top)    + dy;
        var width  = parseFloat(rect.style.width);
        var height = parseFloat(rect.style.height);

        placement.x = clamp(left   / cw, 0, 1 - width / cw);
        placement.y = clamp(top    / ch, 0, 1 - height / ch);
        placement.w = clamp(width  / cw, MIN_W / cw, 1);
        placement.h = clamp(height / ch, MIN_H / ch, 1);
        placement.page = currentPg;

        syncHiddenFields();
    }

    /* ───── interact.js: Drag + Resize ───── */
    function initInteract() {
        if (typeof interact === 'undefined') {
            showToast('interact.js não carregado.', 'warning');
            return;
        }

        interact(rect)
            .draggable({
                inertia: true,
                modifiers: [
                    interact.modifiers.restrictRect({
                        restriction: overlay,
                        endOnly: false
                    })
                ],
                listeners: {
                    start: function () {
                        state = STATE.DRAGGING;
                        rect.classList.add('active');
                    },
                    move: function (event) {
                        var x = (parseFloat(rect.getAttribute('data-x')) || 0) + event.dx;
                        var y = (parseFloat(rect.getAttribute('data-y')) || 0) + event.dy;
                        rect.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
                        rect.setAttribute('data-x', x);
                        rect.setAttribute('data-y', y);
                    },
                    end: function () {
                        updateFractionsFromRect();
                        /* Re-position cleanly (reset data-x/y) */
                        positionRectFromFractions();
                        state = STATE.PLACED;
                        rect.classList.remove('active');
                        showToast('Posição atualizada', 'success', 1500);
                    }
                }
            })
            .resizable({
                edges: { left: true, right: true, bottom: true, top: true },
                inertia: true,
                modifiers: [
                    interact.modifiers.restrictEdges({
                        outer: overlay,
                        endOnly: false
                    }),
                    interact.modifiers.restrictSize({
                        min: { width: MIN_W, height: MIN_H }
                    })
                ],
                listeners: {
                    start: function () {
                        state = STATE.RESIZING;
                        rect.classList.add('active');
                    },
                    move: function (event) {
                        var x = (parseFloat(rect.getAttribute('data-x')) || 0) + event.deltaRect.left;
                        var y = (parseFloat(rect.getAttribute('data-y')) || 0) + event.deltaRect.top;

                        rect.style.width  = event.rect.width  + 'px';
                        rect.style.height = event.rect.height + 'px';
                        rect.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
                        rect.setAttribute('data-x', x);
                        rect.setAttribute('data-y', y);
                    },
                    end: function () {
                        updateFractionsFromRect();
                        positionRectFromFractions();
                        state = STATE.PLACED;
                        rect.classList.remove('active');
                        showToast('Tamanho atualizado', 'success', 1500);
                    }
                }
            });
    }

    /* ───── Modal: placement choice ───── */
    function openModal() {
        if (modal) modal.style.display = 'flex';
    }

    function closeModal() {
        if (modal) modal.style.display = 'none';
    }

    if (modal) {
        var options = modal.querySelectorAll('.sign-modal-option');
        for (var i = 0; i < options.length; i++) {
            options[i].addEventListener('click', function () {
                var mode = this.getAttribute('data-placement');
                placement.mode = mode;

                if (mode === 'manual') {
                    state = STATE.PLACING;
                    placement.page = currentPg;
                    positionRectFromFractions();
                    syncHiddenFields();
                    state = STATE.PLACED;
                    showToast('Arraste e redimensione a assinatura no PDF', 'warning', 4000);
                    if (resetBtn) resetBtn.style.display = 'flex';
                    if (placeBtn) placeBtn.innerHTML = '<i class="ri-check-line"></i> Posição Definida';
                } else {
                    /* auto_footer — reset to defaults */
                    placement.x = cfg.defaults.x;
                    placement.y = cfg.defaults.y;
                    placement.w = cfg.defaults.w;
                    placement.h = cfg.defaults.h;
                    placement.page = totalPgs;
                    rect.style.display = 'none';
                    state = STATE.IDLE;
                    syncHiddenFields();
                    showToast('Assinatura no rodapé automático', 'success', 2500);
                    if (resetBtn) resetBtn.style.display = 'none';
                    if (placeBtn) placeBtn.innerHTML = '<i class="ri-drag-move-2-fill"></i> Posicionar Assinatura';
                }

                closeModal();
            });
        }

        if (modalClose) {
            modalClose.addEventListener('click', closeModal);
        }

        /* Close on backdrop click */
        var backdrop = modal.querySelector('.sign-modal-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', closeModal);
        }

        /* Close on ESC key */
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal && modal.style.display === 'flex') closeModal();
        });
    }

    /* ───── Place button ───── */
    if (placeBtn) {
        placeBtn.addEventListener('click', function () {
            if (state === STATE.PLACED) {
                /* Already placed — re-open modal to change */
                openModal();
            } else {
                openModal();
            }
        });
    }

    /* ───── Reset button ───── */
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            placement.x = cfg.defaults.x;
            placement.y = cfg.defaults.y;
            placement.w = cfg.defaults.w;
            placement.h = cfg.defaults.h;
            placement.page = currentPg;
            positionRectFromFractions();
            syncHiddenFields();
            showToast('Posição resetada', 'success', 1500);
        });
    }

    /* ───── Window resize ───── */
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (pdfDoc) {
                renderPage(currentPg);
            }
        }, 200);
    });

    /* ───── Init ───── */
    syncHiddenFields();
    initInteract();
    initPdf();

})();
