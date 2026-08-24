/**
 * Apollo Event About Editor — contenteditable for #ev-about (post_content).
 * Adapted from Apollo Editor: no nested form; WP media preferred for images;
 * exposes window.ApolloEventAboutEditor { getHTML, setHTML, syncHidden }.
 */
(function () {
  "use strict";
  var editorRoot = document.getElementById("apEditor");
  var body       = document.getElementById("apBody");
  var toolbar    = document.getElementById("apToolbar");
  var counterEl  = document.getElementById("apCounter");
var toast      = document.getElementById("apToast");
  var toastMsg   = document.getElementById("apToastMsg");
  var counterMode = "chars";
  var selectedImg = null;
  /* ─── tiny animation helper — GSAP if present, CSS fallback otherwise ─── */
  function gs() { return window.gsap || null; }
  function squeeze(el) {
    if (!el) return;
    var g = gs();
    if (g) {
      g.killTweensOf(el);
      g.timeline()
        .to(el, { scaleX: 1.24, scaleY: .8, duration: .1, ease: "power2.out" })
        .to(el, { scaleX: .9, scaleY: 1.12, duration: .12, ease: "power2.out" })
        .to(el, { scaleX: 1.04, scaleY: .97, duration: .1, ease: "power2.out" })
        .to(el, { scaleX: 1, scaleY: 1, duration: .18, ease: "power2.out" });
    } else {
      el.style.transition = "transform .35s cubic-bezier(.34,1.56,.64,1)";
      el.style.transform = "scale(1.18)";
      requestAnimationFrame(function () { el.style.transform = "scale(1)"; });
    }
  }
  function popIn(els, opts) {
    opts = opts || {};
    var g = gs();
    if (g && els.length) {
      g.fromTo(els, { scale: .4, opacity: 0 }, { scale: 1, opacity: 1, duration: .45, ease: "back.out(2.2)", stagger: .045, delay: opts.delay || 0 });
    }
  }
  function armEditor() {
    var g = gs();
    if (g) {
      g.timeline()
        .to(editorRoot, { opacity: 1, duration: .5, ease: "power2.out" })
        .from(Array.prototype.slice.call(toolbar.querySelectorAll(".ap-tgroup > *, .ap-tdiv")), { y: -6, opacity: 0, duration: .4, stagger: .015, ease: "power2.out" }, "-=.3")
        .from(body, { y: 8, opacity: 0, duration: .4, ease: "power2.out" }, "-=.3");
    } else {
      editorRoot.style.transition = "opacity .5s ease";
      editorRoot.classList.add("is-armed");
    }
  }
  editorRoot.classList.add("is-armed");
  if (window.Apollo && typeof Apollo.whenGsapReady === "function") {
    Apollo.whenGsapReady(armEditor);
  } else {
    window.addEventListener("load", armEditor);
  }
  /* ─── snap the toolbar's own scroll to reveal newly-appeared buttons ── */
  function scrollToolbarTo(x) {
    var g = gs();
    if (g) g.to(toolbar, { scrollLeft: x, duration: .6, ease: "power2.inOut" });
    else toolbar.scrollTo({ left: x, behavior: "smooth" });
  }
  /* ─── toolbar rail: click-vs-drag scrolls-x from icons too ─────────── */
  var toolbarDrag = null;
  var TOOLBAR_DRAG_THRESHOLD = 8;
  function isToolbarDragExcluded(el) {
    return !!(el && el.closest && el.closest(
      "input[type=range], .fader-widget, .fader-container, .ap-pop.is-open"
    ));
  }
  function suppressToolbarClickOnce() {
    function once(ev) {
      ev.preventDefault();
      ev.stopImmediatePropagation();
      document.removeEventListener("click", once, true);
    }
    document.addEventListener("click", once, true);
    setTimeout(function () { document.removeEventListener("click", once, true); }, 400);
  }
  function onToolbarDragMove(e) {
    if (!toolbarDrag || e.pointerId !== toolbarDrag.pointerId) return;
    var dx = e.clientX - toolbarDrag.startX;
    if (!toolbarDrag.dragging && Math.abs(dx) > TOOLBAR_DRAG_THRESHOLD) {
      toolbarDrag.dragging = true;
      toolbar.classList.add("is-dragging");
      try { toolbar.setPointerCapture(e.pointerId); } catch (_) {}
      if (typeof closeAllPops === "function") closeAllPops();
    }
    if (toolbarDrag.dragging) {
      if (e.cancelable) e.preventDefault();
      toolbar.scrollLeft = toolbarDrag.startScroll - dx;
    }
  }
  function endToolbarDrag(e) {
    if (!toolbarDrag || (e && e.pointerId !== toolbarDrag.pointerId)) return;
    var wasDragging = toolbarDrag.dragging;
    toolbar.classList.remove("is-dragging");
    try { if (e) toolbar.releasePointerCapture(e.pointerId); } catch (_) {}
    toolbarDrag = null;
    document.removeEventListener("pointermove", onToolbarDragMove, true);
    document.removeEventListener("pointerup", endToolbarDrag, true);
    document.removeEventListener("pointercancel", endToolbarDrag, true);
    if (wasDragging) suppressToolbarClickOnce();
  }
  toolbar.addEventListener("pointerdown", function (e) {
    if (e.button !== 0) return;
    if (isToolbarDragExcluded(e.target)) return;
    toolbarDrag = {
      pointerId: e.pointerId,
      startX: e.clientX,
      startScroll: toolbar.scrollLeft,
      dragging: false
    };
    /* document-level until threshold — keeps icon clicks intact (no early capture) */
    document.addEventListener("pointermove", onToolbarDragMove, { capture: true, passive: false });
    document.addEventListener("pointerup", endToolbarDrag, true);
    document.addEventListener("pointercancel", endToolbarDrag, true);
  });
  /* ─── popovers — portaled to body so overflow-x:auto on the rail never clips them ─── */
  var popPortal = document.createElement("div");
  popPortal.id = "ap-pop-portal";
  document.body.appendChild(popPortal);
  var popWraps = Array.prototype.slice.call(document.querySelectorAll(".ap-pop-wrap"));
  popWraps.forEach(function (wrap) {
    var trig = wrap.querySelector("[data-pop-trigger]");
    var pop = wrap.querySelector(".ap-pop");
    if (!trig || !pop) return;
    pop._wrap = wrap;
    pop._trigger = trig;
    wrap._pop = pop;
    wrap._trigger = trig;
    popPortal.appendChild(pop);
  });
  function closeAllPops(except) {
    popWraps.forEach(function (w) {
      if (w === except) return;
      w.classList.remove("is-open");
      if (w._pop) w._pop.classList.remove("is-open");
      if (w._trigger) {
        w._trigger.setAttribute("aria-expanded", "false");
        w._trigger.classList.remove("is-open");
      }
    });
  }
  function positionPop(trig, pop) {
    var r = trig.getBoundingClientRect();
    var margin = 12;
    pop.style.left = r.left + "px";
    pop.style.top = (r.bottom + 10) + "px";
    var w = pop.offsetWidth;
    if (r.left + w + margin > window.innerWidth) {
      pop.style.left = "auto";
      pop.style.right = Math.max(margin, window.innerWidth - r.right) + "px";
    } else {
      pop.style.right = "auto";
    }
    var h = pop.offsetHeight;
    if (r.bottom + 10 + h + margin > window.innerHeight) {
      pop.style.top = Math.max(margin, r.top - h - 10) + "px";
    }
  }
  popWraps.forEach(function (wrap) {
    var trig = wrap._trigger;
    var pop = wrap._pop;
    if (!trig || !pop) return;
    trig.addEventListener("mousedown", function () { saveSelection(); }, true);
    trig.addEventListener("click", function (e) {
      e.stopPropagation();
      var willOpen = !wrap.classList.contains("is-open");
      closeAllPops(willOpen ? wrap : null);
      if (willOpen) positionPop(trig, pop);
      wrap.classList.toggle("is-open", willOpen);
      pop.classList.toggle("is-open", willOpen);
      trig.classList.toggle("is-open", willOpen);
      trig.setAttribute("aria-expanded", String(willOpen));
      if (willOpen) {
        var popItems = pop.querySelectorAll(".ap-pop-item, .ap-sw, .ap-size-opt");
        popIn(Array.prototype.slice.call(popItems), { delay: .05 });
      }
    });
  });
  document.addEventListener("click", function (e) {
    if (e.target.closest(".ap-pop, [data-pop-trigger], .ap-pop-wrap")) return;
    closeAllPops();
  });
  document.addEventListener("keydown", function (e) { if (e.key === "Escape") closeAllPops(); });
  toolbar.addEventListener("scroll", function () { closeAllPops(); }, { passive: true });
  window.addEventListener("scroll", function () { closeAllPops(); }, { passive: true, capture: true });
  window.addEventListener("resize", function () { closeAllPops(); });
  /* ─── sticky selection — holds highlight longer on mobile + desktop ─── */
  var savedRange = null;
  var stickyUntil = 0;
  var chromeHold = false;
  var chromeHoldTimer = null;
  var restoringSel = false;
  var STICKY_MS = 12000; /* keep last non-empty selection ~12s while using chrome */
  var sizeMarkEl = null;       /* span wrapped for live type=range sizing */
  var sizeFaderDragging = false;
  var lhMarkEl = null;         /* span wrapped for live line-height */
  var leadFaderDragging = false;
  function isEditorChrome(el) {
    return !!(el && el.closest && el.closest(
      ".ap-ed-toolbar-wrap, .ap-ed-toolbar, .ap-ed-bottom, .ap-ed-actions, #ap-pop-portal, .ap-pop, .ap-pop-wrap, .fader-widget, .fader-container"
    ));
  }
  function beginChromeHold() {
    chromeHold = true;
    clearTimeout(chromeHoldTimer);
    chromeHoldTimer = setTimeout(function () { chromeHold = false; }, STICKY_MS);
  }
  function bumpSticky() {
    if (savedRange && !savedRange.collapsed) stickyUntil = Date.now() + STICKY_MS;
  }
  function saveSelection(opts) {
    opts = opts || {};
    var sel = window.getSelection();
    if (!sel || !sel.rangeCount) return;
    if (!body.contains(sel.anchorNode)) return;
    var range = sel.getRangeAt(0).cloneRange();
    /* never overwrite a useful sticky range with a collapsed caret (toolbar focus steal) */
    if (range.collapsed && !opts.force) {
      if (savedRange && !savedRange.collapsed) return;
      return;
    }
    savedRange = range;
    bumpSticky();
  }
  function restoreSelection() {
    if (!savedRange) return;
    try {
      if (!body.contains(savedRange.commonAncestorContainer)) return;
    } catch (_) { return; }
    restoringSel = true;
    try {
      if (document.activeElement !== body) {
        try { body.focus({ preventScroll: true }); } catch (_) { body.focus(); }
      }
      var sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(savedRange.cloneRange());
    } catch (_) {}
    restoringSel = false;
  }
  function clearStickySelection() {
    savedRange = null;
    stickyUntil = 0;
    chromeHold = false;
    clearTimeout(chromeHoldTimer);
  }
  /* capture non-empty selections from the writing surface */
  body.addEventListener("keyup", function () { saveSelection(); });
  body.addEventListener("mouseup", function () { saveSelection(); });
  body.addEventListener("touchend", function () { setTimeout(function () { saveSelection(); }, 0); });
  body.addEventListener("blur", function () {
    /* keep sticky; only refresh if live selection is still a real range */
    saveSelection();
  });
  /* pointer into chrome: freeze selection (preventDefault stops browser from collapsing it) */
  function onChromePointerDown(e) {
    if (!isEditorChrome(e.target)) return;
    saveSelection();
    beginChromeHold();
    bumpSticky();
    /* NEVER preventDefault on range/inputs — that freezes type=range thumbs */
    var tag = (e.target.tagName || "").toUpperCase();
    if (tag === "INPUT" || tag === "TEXTAREA" || tag === "SELECT"
        || e.target.closest("input, textarea, select, .fader-container, .fader-widget")) {
      return;
    }
    /* keep focus/selection from collapsing on icon buttons — click still fires afterward */
    if (e.cancelable) e.preventDefault();
  }
  document.addEventListener("mousedown", onChromePointerDown, true);
  document.addEventListener("touchstart", onChromePointerDown, { capture: true, passive: false });
  toolbar.addEventListener("mousedown", function () { saveSelection(); beginChromeHold(); }, true);
  if (popPortal) {
    popPortal.addEventListener("mousedown", function () { saveSelection(); beginChromeHold(); }, true);
    popPortal.addEventListener("touchstart", function () { saveSelection(); beginChromeHold(); }, { capture: true, passive: true });
  }
  /* if selection collapses while chrome-hold is active, put it back */
  document.addEventListener("selectionchange", function () {
    if (restoringSel) return;
    /* don't steal focus from editor faders mid-drag */
    var ae = document.activeElement;
    if (sizeFaderDragging || leadFaderDragging
        || (ae && (ae.id === "apSizeFader" || ae.id === "apLeadFader" || ae.type === "range"
            || (ae.closest && ae.closest(".fader-container"))))) {
      return;
    }
    var sel = window.getSelection();
    if (sel && sel.rangeCount && body.contains(sel.anchorNode) && !sel.isCollapsed) {
      savedRange = sel.getRangeAt(0).cloneRange();
      bumpSticky();
      return;
    }
    var holdActive = chromeHold || Date.now() < stickyUntil;
    if (!holdActive || !savedRange || savedRange.collapsed) return;
    restoreSelection();
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") clearStickySelection();
  });
  function focusBody() { restoreSelection(); }
  function hasEditorSelection() {
    if (savedRange && !savedRange.collapsed) {
      try {
        if (body.contains(savedRange.commonAncestorContainer)) return true;
      } catch (_) {}
    }
    var sel = window.getSelection();
    return !!(sel && sel.rangeCount && !sel.isCollapsed
      && body.contains(sel.anchorNode) && body.contains(sel.focusNode));
  }
  function showToast(msg) {
    toastMsg.textContent = msg;
    var g = gs();
    if (g) {
      g.killTweensOf(toast);
      g.timeline()
        .set(toast, { pointerEvents: "auto" })
        .to(toast, { opacity: 1, y: 0, duration: .35, ease: "back.out(1.8)" })
        .to(toast, { opacity: 0, y: 12, duration: .3, ease: "power2.in", delay: 1.4 })
        .set(toast, { pointerEvents: "none" });
    } else {
      toast.style.transition = "opacity .3s ease, transform .3s ease";
      toast.style.opacity = "1"; toast.style.transform = "translate(-50%, 0)";
      setTimeout(function () { toast.style.opacity = "0"; toast.style.transform = "translate(-50%, 12px)"; }, 1800);
    }
  }
  function requireSelection() {
    if (!hasEditorSelection()) {
      showToast("Selecione o texto primeiro");
      return false;
    }
    return true;
  }
  function execOnSelection(cmd, val) {
    saveSelection();
    if (!requireSelection()) return false;
    beginChromeHold();
    restoreSelection();
    document.execCommand(cmd, false, val);
    /* re-save after command so sticky stays on the formatted range */
    saveSelection({ force: false });
    bumpSticky();
    refreshToolbarState();
    updateEmptyState();
    updateCounter();
    return true;
  }
  /* ─── wrap ONLY the selected characters (even mid-word) — never formatBlock the whole <p> ─── */
  var INLINE_MARK_TAGS = { H1: 1, H2: 1, H3: 1, H4: 1, H5: 1, H6: 1, BLOCKQUOTE: 1 };
  function unwrapNode(el) {
    var parent = el.parentNode;
    if (!parent) return;
    while (el.firstChild) parent.insertBefore(el.firstChild, el);
    parent.removeChild(el);
  }
  function unwrapMarksInRange(range) {
    var root = range.commonAncestorContainer;
    if (root.nodeType === 3) root = root.parentNode;
    if (!root || !body.contains(root)) return;
    var marks = root.querySelectorAll ? root.querySelectorAll(".ap-inline-mark") : [];
    var list = Array.prototype.slice.call(marks);
    /* also unwrap if the selection is wholly inside one mark */
    var anc = range.startContainer;
    while (anc && anc !== body) {
      if (anc.nodeType === 1 && anc.classList && anc.classList.contains("ap-inline-mark")) {
        list.push(anc);
        break;
      }
      anc = anc.parentNode;
    }
    list.forEach(function (el) {
      try {
        if (range.intersectsNode && !range.intersectsNode(el)) return;
      } catch (_) {}
      unwrapNode(el);
    });
    if (root.normalize) root.normalize();
  }
  function wrapSelectionInTag(tagName) {
    saveSelection();
    if (!requireSelection()) return false;
    beginChromeHold();
    restoreSelection();
    var sel = window.getSelection();
    if (!sel || !sel.rangeCount || sel.isCollapsed) return false;
    var tag = String(tagName || "P").toUpperCase();
    var range = sel.getRangeAt(0).cloneRange();
    /* Normal — strip heading/quote marks from the exact selection */
    if (tag === "P" || tag === "DIV") {
      unwrapMarksInRange(range);
      restoreSelection();
      saveSelection({ force: false });
      bumpSticky();
      refreshToolbarState();
      updateEmptyState();
      updateCounter();
      return true;
    }
    if (!INLINE_MARK_TAGS[tag]) {
      return execOnSelection("formatBlock", tag);
    }
    try {
      /* extract ONLY selected letters/nodes, wrap, put back — splits text nodes mid-word */
      var contents = range.extractContents();
      var el = document.createElement(tag.toLowerCase());
      el.className = "ap-inline-mark";
      el.appendChild(contents);
      range.insertNode(el);
      /* if already nested same mark, flatten one level of empty wrappers */
      if (el.parentNode && el.parentNode.classList && el.parentNode.classList.contains("ap-inline-mark")
          && el.parentNode.tagName === el.tagName && el.parentNode.childNodes.length === 1) {
        unwrapNode(el.parentNode);
      }
      var next = document.createRange();
      next.selectNodeContents(el);
      sel.removeAllRanges();
      sel.addRange(next);
      savedRange = next.cloneRange();
      bumpSticky();
    } catch (err) {
      /* fallback: surroundContents when extract fails on complex trees */
      try {
        restoreSelection();
        range = sel.getRangeAt(0);
        var wrap = document.createElement(tag.toLowerCase());
        wrap.className = "ap-inline-mark";
        range.surroundContents(wrap);
        var nr = document.createRange();
        nr.selectNodeContents(wrap);
        sel.removeAllRanges();
        sel.addRange(nr);
        savedRange = nr.cloneRange();
        bumpSticky();
      } catch (err2) {
        showToast("Não foi possível aplicar no trecho selecionado");
        return false;
      }
    }
    refreshToolbarState();
    updateEmptyState();
    updateCounter();
    return true;
  }
  /* undo/redo — no selection gate */
  function execFree(cmd, val) {
    focusBody();
    document.execCommand(cmd, false, val);
    refreshToolbarState();
    updateEmptyState();
    updateCounter();
  }
  /* ─── placeholder / counter ───────────────────────────────────────── */
  function updateEmptyState() {
    var txt = body.innerText.replace(/\u200b/g, "").trim();
    body.classList.toggle("is-empty", txt.length === 0);
  }
  function updateCounter() {
    var text = body.innerText.replace(/\u200b/g, "");
    var chars = text.replace(/\s+$/,"").length;
    var words = text.trim() ? text.trim().split(/\s+/).length : 0;
    counterEl.textContent = counterMode === "chars" ? (chars + " caracteres") : (words + " palavras");
  }
  counterEl.addEventListener("click", function () {
    counterMode = counterMode === "chars" ? "words" : "chars";
    updateCounter();
    squeeze(counterEl);
  });
  body.addEventListener("input", function () { updateEmptyState(); updateCounter(); });
  updateEmptyState(); updateCounter();
  /* ─── plain toggle / list / indent commands (selection-only except undo/redo) ─── */
  var FREE_CMDS = { undo: 1, redo: 1 };
  toolbar.querySelectorAll("[data-cmd]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var cmd = btn.getAttribute("data-cmd");
      if (FREE_CMDS[cmd]) {
        execFree(cmd);
        return;
      }
      if (cmd === "blockquote") {
        /* toggle inline quote mark on the exact selection — not the whole block */
        saveSelection();
        if (!requireSelection()) return;
        restoreSelection();
        var selQ = window.getSelection();
        var ancQ = selQ && selQ.anchorNode;
        var inQuote = false;
        while (ancQ && ancQ !== body) {
          if (ancQ.nodeType === 1 && ancQ.tagName === "BLOCKQUOTE" && ancQ.classList.contains("ap-inline-mark")) {
            inQuote = true;
            unwrapNode(ancQ);
            break;
          }
          ancQ = ancQ.parentNode;
        }
        if (!inQuote) wrapSelectionInTag("BLOCKQUOTE");
        else {
          saveSelection({ force: false });
          bumpSticky();
          refreshToolbarState(); updateEmptyState(); updateCounter();
        }
      } else if (cmd === "clean") {
        if (!requireSelection()) return;
        restoreSelection();
        var selC = window.getSelection();
        if (selC && selC.rangeCount) unwrapMarksInRange(selC.getRangeAt(0));
        document.execCommand("removeFormat");
        saveSelection({ force: false });
        bumpSticky();
        refreshToolbarState(); updateEmptyState(); updateCounter();
      } else {
        if (!execOnSelection(cmd)) return;
      }
      if (["bold","italic","underline","insertOrderedList","insertUnorderedList","blockquote"].indexOf(cmd) > -1) squeeze(btn);
    });
  });
  /* ─── block format popover — wrap selection letters only ───────────── */
  var blockLabel = toolbar.querySelector("[data-block-label]");
  var blockIcon  = toolbar.querySelector("[data-block-icon]");
  var blockMap = { P: ["Normal","ri-paragraph"], H1: ["Título 1","ri-h-1"], H2: ["Título 2","ri-h-2"], H3: ["Título 3","ri-h-3"], H4: ["Título 4","ri-h-4"], H5: ["Título 5","ri-h-5"], H6: ["Título 6","ri-h-6"] };
  document.querySelectorAll("#ap-pop-portal [data-block], .ap-ed-toolbar [data-block]").forEach(function (item) {
    item.addEventListener("click", function () {
      var tag = item.getAttribute("data-block");
      if (!wrapSelectionInTag(tag)) return;
      var info = blockMap[tag] || ["Normal","ri-paragraph"];
      blockLabel.textContent = info[0];
      blockIcon.className = info[1];
      document.querySelectorAll("#ap-pop-portal [data-block]").forEach(function (i) { i.classList.toggle("is-selected", i === item); });
      closeAllPops();
      squeeze(toolbar.querySelector('[data-pop-trigger="block"]'));
    });
  });
  /* ─── font size — theme fader-widget range (selection only) ─────────── */
  var sizeFader = document.getElementById("apSizeFader");
  var sizeFrVal = document.getElementById("apSizeFrVal");
  function sizeFaderToPx(val) {
    /* same scale as Master fader readout: value 400 → 40.0 (here → px) */
    return Math.max(8, Math.min(100, (Number(val) || 0) / 10));
  }
  function syncSizeFaderUI(val) {
    var n = Math.max(0, Math.min(1000, Number(val) || 0));
    var pct = (n / 1000) * 100;
    sizeFader.style.setProperty("--val", pct + "%");
    sizeFrVal.textContent = sizeFaderToPx(n).toFixed(1);
  }
  function lockSavedRangeToEl(el) {
    if (!el) return;
    try {
      var nr = document.createRange();
      nr.selectNodeContents(el);
      savedRange = nr.cloneRange();
      bumpSticky();
    } catch (_) {}
  }
  function reselectMarkEl(el, adjustClass) {
    if (!el || !body.contains(el)) return;
    restoringSel = true;
    try {
      if (adjustClass) el.classList.remove(adjustClass);
      el.classList.remove("is-sizing", "is-adjusting");
      try { body.focus({ preventScroll: true }); } catch (_) { body.focus(); }
      var nr = document.createRange();
      nr.selectNodeContents(el);
      var sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(nr);
      savedRange = nr.cloneRange();
      bumpSticky();
      beginChromeHold();
    } catch (_) {}
    restoringSel = false;
  }
  function reselectSizeMark() { reselectMarkEl(sizeMarkEl); }
  function reselectLhMark() { reselectMarkEl(lhMarkEl); }
  /**
   * Live font-size on the sticky selection:
   * - first tick wraps exact letters in <span class="ap-size-mark">
   * - further ticks only update that span's style (no re-wrap, no unselect)
   * - never focuses body while dragging the range thumb
   */
  function applyFontSizePxLive(px, opts) {
    opts = opts || {};
    beginChromeHold();
    if (sizeMarkEl && body.contains(sizeMarkEl)) {
      sizeMarkEl.style.fontSize = px + "px";
      if (opts.dragging) sizeMarkEl.classList.add("is-adjusting");
      lockSavedRangeToEl(sizeMarkEl);
      updateCounter();
      return true;
    }
    if (!savedRange || savedRange.collapsed) {
      if (!opts.silent) requireSelection();
      return false;
    }
    try {
      var range = savedRange.cloneRange();
      var anc = range.commonAncestorContainer;
      if (anc.nodeType === 3) anc = anc.parentNode;
      var existing = anc && anc.closest ? anc.closest(".ap-size-mark") : null;
      if (existing && body.contains(existing)
          && range.startContainer && existing.contains(range.startContainer)
          && existing.contains(range.endContainer)) {
        sizeMarkEl = existing;
        sizeMarkEl.style.fontSize = px + "px";
        if (opts.dragging) sizeMarkEl.classList.add("is-adjusting");
        lockSavedRangeToEl(sizeMarkEl);
        updateCounter();
        return true;
      }
      var contents = range.extractContents();
      var span = document.createElement("span");
      span.className = "ap-size-mark" + (opts.dragging ? " is-adjusting" : "");
      span.style.fontSize = px + "px";
      span.appendChild(contents);
      range.insertNode(span);
      if (span.parentNode && span.parentNode.normalize) span.parentNode.normalize();
      sizeMarkEl = span;
      lockSavedRangeToEl(span);
      updateEmptyState();
      updateCounter();
      return true;
    } catch (err) {
      if (!opts.silent) showToast("Não foi possível aplicar o tamanho");
      return false;
    }
  }
  if (sizeFader && sizeFrVal) {
    syncSizeFaderUI(sizeFader.value);
    var sizeGestureEnded = false;
    sizeFader.addEventListener("pointerdown", function () {
      sizeGestureEnded = false;
      saveSelection();
      beginChromeHold();
      sizeFaderDragging = true;
      sizeMarkEl = null;
      if (savedRange && !savedRange.collapsed) {
        try {
          var anc = savedRange.commonAncestorContainer;
          if (anc.nodeType === 3) anc = anc.parentNode;
          var existing = anc && anc.closest ? anc.closest(".ap-size-mark") : null;
          if (existing && body.contains(existing)) sizeMarkEl = existing;
        } catch (_) {}
      }
    });
    sizeFader.addEventListener("input", function () {
      syncSizeFaderUI(sizeFader.value);
      sizeFaderDragging = true;
      beginChromeHold();
      applyFontSizePxLive(sizeFaderToPx(sizeFader.value), { dragging: true, silent: true });
    });
    function endSizeFaderGesture() {
      if (sizeGestureEnded) return;
      sizeGestureEnded = true;
      syncSizeFaderUI(sizeFader.value);
      applyFontSizePxLive(sizeFaderToPx(sizeFader.value), { dragging: false, silent: false });
      sizeFaderDragging = false;
      /* restore real text selection on the sized fragment — never leave unselected */
      reselectSizeMark();
    }
    sizeFader.addEventListener("change", endSizeFaderGesture);
    sizeFader.addEventListener("pointerup", endSizeFaderGesture);
    sizeFader.addEventListener("pointercancel", function () {
      sizeFaderDragging = false;
      sizeGestureEnded = true;
      reselectSizeMark();
    });
  }
  /* ─── line-height — theme fader-widget range (selection only) ──────── */
  var leadFader = document.getElementById("apLeadFader");
  var leadFrVal = document.getElementById("apLeadFrVal");
  function leadFaderToLh(val) {
    return Math.max(1, Math.min(3, (Number(val) || 100) / 100));
  }
  function syncLeadFaderUI(val) {
    var n = Math.max(100, Math.min(300, Number(val) || 100));
    var pct = ((n - 100) / 200) * 100;
    leadFader.style.setProperty("--val", pct + "%");
    leadFrVal.textContent = leadFaderToLh(n).toFixed(2);
  }
  function applyLineHeightLive(lh, opts) {
    opts = opts || {};
    beginChromeHold();
    if (lhMarkEl && body.contains(lhMarkEl)) {
      lhMarkEl.style.lineHeight = String(lh);
      if (opts.dragging) lhMarkEl.classList.add("is-adjusting");
      lockSavedRangeToEl(lhMarkEl);
      updateCounter();
      return true;
    }
    if (!savedRange || savedRange.collapsed) {
      if (!opts.silent) requireSelection();
      return false;
    }
    try {
      var range = savedRange.cloneRange();
      var anc = range.commonAncestorContainer;
      if (anc.nodeType === 3) anc = anc.parentNode;
      var existing = anc && anc.closest ? anc.closest(".ap-lh-mark") : null;
      if (existing && body.contains(existing)
          && range.startContainer && existing.contains(range.startContainer)
          && existing.contains(range.endContainer)) {
        lhMarkEl = existing;
        lhMarkEl.style.lineHeight = String(lh);
        if (opts.dragging) lhMarkEl.classList.add("is-adjusting");
        lockSavedRangeToEl(lhMarkEl);
        updateCounter();
        return true;
      }
      var contents = range.extractContents();
      var span = document.createElement("span");
      span.className = "ap-lh-mark" + (opts.dragging ? " is-adjusting" : "");
      span.style.lineHeight = String(lh);
      span.appendChild(contents);
      range.insertNode(span);
      if (span.parentNode && span.parentNode.normalize) span.parentNode.normalize();
      lhMarkEl = span;
      lockSavedRangeToEl(span);
      updateEmptyState();
      updateCounter();
      return true;
    } catch (err) {
      if (!opts.silent) showToast("Não foi possível aplicar a entrelinha");
      return false;
    }
  }
  if (leadFader && leadFrVal) {
    syncLeadFaderUI(leadFader.value);
    var leadGestureEnded = false;
    leadFader.addEventListener("pointerdown", function () {
      leadGestureEnded = false;
      saveSelection();
      beginChromeHold();
      leadFaderDragging = true;
      lhMarkEl = null;
      if (savedRange && !savedRange.collapsed) {
        try {
          var anc = savedRange.commonAncestorContainer;
          if (anc.nodeType === 3) anc = anc.parentNode;
          var existing = anc && anc.closest ? anc.closest(".ap-lh-mark") : null;
          if (existing && body.contains(existing)) lhMarkEl = existing;
        } catch (_) {}
      }
    });
    leadFader.addEventListener("input", function () {
      syncLeadFaderUI(leadFader.value);
      leadFaderDragging = true;
      beginChromeHold();
      applyLineHeightLive(leadFaderToLh(leadFader.value), { dragging: true, silent: true });
    });
    function endLeadFaderGesture() {
      if (leadGestureEnded) return;
      leadGestureEnded = true;
      syncLeadFaderUI(leadFader.value);
      applyLineHeightLive(leadFaderToLh(leadFader.value), { dragging: false, silent: false });
      leadFaderDragging = false;
      reselectLhMark();
    }
    leadFader.addEventListener("change", endLeadFaderGesture);
    leadFader.addEventListener("pointerup", endLeadFaderGesture);
    leadFader.addEventListener("pointercancel", function () {
      leadFaderDragging = false;
      leadGestureEnded = true;
      reselectLhMark();
    });
  }
  /* ─── align popover ───────────────────────────────────────────────── */
  var alignIcon = toolbar.querySelector("[data-align-icon]");
  var alignIconMap = { Left: "ri-align-left", Center: "ri-align-center", Right: "ri-align-right", Full: "ri-align-justify" };
  document.querySelectorAll("#ap-pop-portal [data-align]").forEach(function (item) {
    item.addEventListener("click", function () {
      var val = item.getAttribute("data-align");
      if (!execOnSelection("justify" + val)) return;
      alignIcon.className = alignIconMap[val];
      document.querySelectorAll("#ap-pop-portal [data-align]").forEach(function (i) { i.classList.toggle("is-selected", i === item); });
      closeAllPops();
      squeeze(toolbar.querySelector('[data-pop-trigger="align"]'));
    });
  });
  /* ─── color / highlight swatches (selection-only) ─────────────────── */
  function applySwatchTarget(target, color, gridEl, sourceEl) {
    saveSelection();
    if (!requireSelection()) return;
    focusBody(); restoreSelection();
    if (target === "color") {
      document.execCommand("foreColor", false, color || "inherit");
      var preview = toolbar.querySelector("[data-color-preview]");
      preview.style.color = color || "";
    } else {
      var hl = color === "transparent" ? "inherit" : color;
      if (!document.execCommand("hiliteColor", false, hl)) document.execCommand("backColor", false, hl);
    }
    gridEl.querySelectorAll(".ap-sw").forEach(function (s) { s.classList.toggle("is-selected", s === sourceEl); });
    squeeze(sourceEl);
    var pop = gridEl.closest(".ap-pop");
    var wrap = pop && pop._wrap;
    var trig = wrap && wrap._trigger;
    if (trig) squeeze(trig);
    closeAllPops();
    refreshToolbarState(); updateEmptyState(); updateCounter();
  }
  document.querySelectorAll("#ap-pop-portal .ap-swgrid[data-swatch-target]").forEach(function (grid) {
    var target = grid.getAttribute("data-swatch-target");
    grid.querySelectorAll(".ap-sw[data-color]").forEach(function (sw) {
      sw.addEventListener("click", function () { applySwatchTarget(target, sw.getAttribute("data-color"), grid, sw); });
    });
    var colorInput = grid.querySelector('input[type="color"]');
    if (colorInput) colorInput.addEventListener("input", function () { applySwatchTarget(target, colorInput.value, grid, colorInput.parentElement); });
  });
  /* ─── link popover (selection-only for create; unlink exempt of empty check if selection was link) ─── */
  var linkInput = document.getElementById("apLinkInput");
  var linkApply = document.getElementById("apLinkApply");
  var linkRemove = document.getElementById("apLinkRemove");
  linkApply.addEventListener("click", function () {
    var url = linkInput.value.trim();
    if (!url) return;
    if (!execOnSelection("createLink", url)) return;
    linkInput.value = "";
    closeAllPops();
    squeeze(toolbar.querySelector('[data-pop-trigger="link"]'));
  });
  linkRemove.addEventListener("click", function () {
    saveSelection();
    focusBody(); restoreSelection();
    document.execCommand("unlink");
    closeAllPops();
    refreshToolbarState(); updateEmptyState(); updateCounter();
  });
  /* ─── image insertion ─────────────────────────────────────────────── */
  
  /* ─── image: prefer WP media frame; FileReader fallback ───────────── */
  function openMedia(doReplace) {
    replaceMode = !!doReplace;
    try {
      if (window.wp && wp.media) {
        var frame = wp.media({
          title: replaceMode ? "Substituir imagem" : "Inserir imagem",
          button: { text: "Usar imagem" },
          multiple: false,
          library: { type: "image" }
        });
        frame.on("select", function () {
          var att = frame.state().get("selection").first().toJSON();
          var url = att.url || (att.sizes && att.sizes.large && att.sizes.large.url) || "";
          if (!url) return;
          if (replaceMode && selectedImg) {
            selectedImg.querySelector("img").src = url;
            squeeze(selectedImg);
            syncHidden();
          } else {
            insertImage(url);
          }
        });
        frame.open();
        return;
      }
    } catch (err) { /* fall through to file input */ }
    imgFile.click();
  }

  var imgFile = document.getElementById("apImageFile");
  var imgBtn = document.getElementById("apImageBtn");
  var replaceMode = false;
  imgBtn.addEventListener("click", function () { openMedia(false); });
  document.getElementById("apImgReplace").addEventListener("click", function () { openMedia(true); });
  imgFile.addEventListener("change", function () {
    var file = imgFile.files && imgFile.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      if (replaceMode && selectedImg) {
        selectedImg.querySelector("img").src = e.target.result;
        squeeze(selectedImg);
      } else {
        insertImage(e.target.result);
      }
      imgFile.value = "";
    };
    reader.readAsDataURL(file);
  });
  function insertImage(src) {
    focusBody(); restoreSelection();
    var wrap = document.createElement("span");
    wrap.className = "ap-ed-img-wrap size-m align-center";
    wrap.setAttribute("contenteditable", "false");
    var img = document.createElement("img");
    img.src = src; img.alt = "";
    var handle = document.createElement("span");
    handle.className = "ap-img-handle";
    handle.setAttribute("contenteditable", "false");
    handle.innerHTML = '<i class="ri-drag-move-2-line"></i>';
    var pill = document.createElement("span");
    pill.className = "ap-img-h-pill";
    pill.setAttribute("contenteditable", "false");
    wrap.appendChild(img); wrap.appendChild(handle); wrap.appendChild(pill);
    document.execCommand("insertHTML", false, wrap.outerHTML + "<p><br></p>");
    updateEmptyState(); updateCounter();
    // re-bind the freshly inserted node (insertHTML clones it)
    var inserted = Array.prototype.slice.call(body.querySelectorAll(".ap-ed-img-wrap")).pop();
    if (inserted) selectImage(inserted, true);
  }
  /* ─── contextual "on image selected" toolbar ──────────────────────── */
  var imgTools = document.getElementById("apImgTools");
  function portalQ(sel) {
    return popPortal.querySelectorAll(sel);
  }
  function syncFullSizeTools(wrap) {
    var isFull = wrap.classList.contains("size-full");
    imgTools.classList.toggle("is-full-size", isFull);
    var img = wrap.querySelector("img");
    var curFit = img.style.objectFit || "cover";
    var curPos = (img.style.objectPosition || "center top").indexOf("bottom") > -1 ? "bottom" : (img.style.objectPosition || "center top").indexOf("top") > -1 ? "top" : "center";
    portalQ("[data-fit]").forEach(function (o) { o.classList.toggle("is-selected", o.getAttribute("data-fit") === curFit); });
    portalQ("[data-pos]").forEach(function (o) { o.classList.toggle("is-selected", o.getAttribute("data-pos") === curPos); });
    var fillBtn = document.getElementById("apImgFillHeight");
    if (fillBtn) fillBtn.classList.toggle("is-active", wrap.dataset.fillMode === "viewport");
  }
  function selectImage(wrap, animateIn) {
    if (selectedImg && selectedImg !== wrap) selectedImg.classList.remove("is-selected");
    selectedImg = wrap;
    wrap.classList.add("is-selected");
    toolbar.classList.add("has-img-selection");
    var align = ["left","center","right"].filter(function (a) { return wrap.classList.contains("align-" + a); })[0] || "center";
    imgTools.querySelectorAll("[data-img-align]").forEach(function (b) { b.classList.toggle("is-active", b.getAttribute("data-img-align") === align); });
    var size = ["s","m","l","full"].filter(function (s) { return wrap.classList.contains("size-" + s); })[0] || "m";
    portalQ("[data-imgsize]").forEach(function (b) { b.classList.toggle("is-selected", b.getAttribute("data-imgsize") === size); });
    document.getElementById("apAltInput").value = wrap.querySelector("img").alt || "";
    syncFullSizeTools(wrap);
    if (animateIn) {
      popIn(Array.prototype.slice.call(imgTools.querySelectorAll(".ap-tbtn, .ap-pop-wrap")));
      requestAnimationFrame(function () { scrollToolbarTo(toolbar.scrollWidth); });
    }
  }
  function deselectImage() {
    if (!selectedImg) return;
    selectedImg.classList.remove("is-selected");
    selectedImg = null;
    toolbar.classList.remove("has-img-selection");
  }
  body.addEventListener("click", function (e) {
    var wrap = e.target.closest && e.target.closest(".ap-ed-img-wrap");
    if (wrap) { selectImage(wrap, true); } else { deselectImage(); }
  });
  document.addEventListener("click", function (e) {
    if (!editorRoot.contains(e.target) && !e.target.closest("#ap-pop-portal")) deselectImage();
  });
  imgTools.querySelectorAll("[data-img-align]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      if (!selectedImg) return;
      selectedImg.classList.remove("align-left","align-center","align-right");
      selectedImg.classList.add("align-" + btn.getAttribute("data-img-align"));
      imgTools.querySelectorAll("[data-img-align]").forEach(function (b) { b.classList.toggle("is-active", b === btn); });
      squeeze(btn);
    });
  });
  portalQ("[data-imgsize]").forEach(function (opt) {
    opt.addEventListener("click", function () {
      if (!selectedImg) return;
      var nextSize = opt.getAttribute("data-imgsize");
      selectedImg.classList.remove("size-s","size-m","size-l","size-full");
      selectedImg.classList.add("size-" + nextSize);
      portalQ("[data-imgsize]").forEach(function (o) { o.classList.toggle("is-selected", o === opt); });
      if (nextSize !== "full") {
        selectedImg.classList.remove("h-custom", "is-dragging");
        selectedImg.style.removeProperty("--ap-img-h");
        delete selectedImg.dataset.fillMode;
        var img = selectedImg.querySelector("img");
        img.style.objectFit = ""; img.style.objectPosition = "";
      }
      syncFullSizeTools(selectedImg);
      squeeze(selectedImg);
      closeAllPops();
      squeeze(toolbar.querySelector('[data-pop-trigger="imgsize"]'));
    });
  });
  /* ─── full-bleed only: crop fit + vertical position ───────────────── */
  portalQ("[data-fit]").forEach(function (opt) {
    opt.addEventListener("click", function () {
      if (!selectedImg) return;
      selectedImg.querySelector("img").style.objectFit = opt.getAttribute("data-fit");
      portalQ("[data-fit]").forEach(function (o) { o.classList.toggle("is-selected", o === opt); });
      squeeze(opt);
    });
  });
  var posMap = { top: "center top", center: "center center", bottom: "center bottom" };
  portalQ("[data-pos]").forEach(function (opt) {
    opt.addEventListener("click", function () {
      if (!selectedImg) return;
      selectedImg.querySelector("img").style.objectPosition = posMap[opt.getAttribute("data-pos")];
      portalQ("[data-pos]").forEach(function (o) { o.classList.toggle("is-selected", o === opt); });
      squeeze(opt);
    });
  });
  /* ─── one-click: fill the composer's current visible height ───────── */
  var fillHeightBtn = document.getElementById("apImgFillHeight");
  fillHeightBtn.addEventListener("click", function () {
    if (!selectedImg) return;
    if (selectedImg.dataset.fillMode === "viewport") {
      selectedImg.classList.remove("h-custom");
      selectedImg.style.removeProperty("--ap-img-h");
      delete selectedImg.dataset.fillMode;
      fillHeightBtn.classList.remove("is-active");
    } else {
      var h = Math.max(200, body.clientHeight - 24);
      selectedImg.style.setProperty("--ap-img-h", h + "px");
      selectedImg.classList.add("h-custom");
      selectedImg.dataset.fillMode = "viewport";
      fillHeightBtn.classList.add("is-active");
    }
    squeeze(selectedImg);
    squeeze(fillHeightBtn);
  });
  /* ─── body pointerdown: sticky clear (body only) + image height handle ── */
  var dragState = null;
  body.addEventListener("pointerdown", function (e) {
    /* clear sticky only for intentional edits inside the writing surface */
    if (e.target.closest && e.target.closest("#apBody") && !isEditorChrome(e.target)) {
      chromeHold = false;
      clearTimeout(chromeHoldTimer);
      stickyUntil = 0;
      sizeMarkEl = null;
      lhMarkEl = null;
    }
    var handle = e.target.closest && e.target.closest(".ap-img-handle");
    if (!handle) return;
    var wrap = handle.closest(".ap-ed-img-wrap");
    if (!wrap || !wrap.classList.contains("size-full")) return;
    e.preventDefault(); e.stopPropagation();
    var pill = wrap.querySelector(".ap-img-h-pill");
    dragState = { wrap: wrap, startY: e.clientY, startH: wrap.getBoundingClientRect().height, pill: pill };
    wrap.classList.add("is-dragging");
    if (handle.setPointerCapture) { try { handle.setPointerCapture(e.pointerId); } catch (err) {} }
  });
  document.addEventListener("pointermove", function (e) {
    if (!dragState) return;
    var dy = e.clientY - dragState.startY;
    var h = Math.max(120, Math.min(window.innerHeight * .85, dragState.startH + dy));
    dragState.wrap.style.setProperty("--ap-img-h", Math.round(h) + "px");
    dragState.wrap.classList.add("h-custom");
    if (dragState.pill) dragState.pill.textContent = Math.round(h) + "px";
  });
  function endDrag() {
    if (!dragState) return;
    var wrap = dragState.wrap;
    wrap.classList.remove("is-dragging");
    wrap.dataset.fillMode = "custom";
    if (selectedImg === wrap) fillHeightBtn.classList.remove("is-active");
    squeeze(wrap);
    dragState = null;
  }
  document.addEventListener("pointerup", endDrag);
  document.addEventListener("pointercancel", endDrag);
  body.addEventListener("dblclick", function (e) {
    var handle = e.target.closest && e.target.closest(".ap-img-handle");
    if (!handle) return;
    var wrap = handle.closest(".ap-ed-img-wrap");
    if (!wrap) return;
    wrap.classList.remove("h-custom");
    wrap.style.removeProperty("--ap-img-h");
    delete wrap.dataset.fillMode;
    if (selectedImg === wrap) fillHeightBtn.classList.remove("is-active");
    squeeze(wrap);
  });
  document.getElementById("apAltApply").addEventListener("click", function () {
    if (!selectedImg) return;
    selectedImg.querySelector("img").alt = document.getElementById("apAltInput").value.trim();
    closeAllPops();
    squeeze(toolbar.querySelector('[data-pop-trigger="alt"]'));
  });
  document.getElementById("apImgDelete").addEventListener("click", function () {
    if (!selectedImg) return;
    var g = gs(), target = selectedImg;
    deselectImage();
    if (g) g.to(target, { scale: .5, opacity: 0, duration: .25, ease: "power2.in", onComplete: function () { target.remove(); updateEmptyState(); updateCounter(); } });
    else { target.remove(); updateEmptyState(); updateCounter(); }
  });
  /* ─── live active-state sync (bold/italic/underline/list/align) ──── */
  function refreshToolbarState() {
    ["bold","italic","underline","insertOrderedList","insertUnorderedList"].forEach(function (cmd) {
      var btn = toolbar.querySelector('[data-cmd="' + cmd + '"]');
      if (btn) { var on = document.queryCommandState(cmd); btn.classList.toggle("is-active", on); btn.setAttribute("aria-pressed", String(on)); }
    });
    var quoteBtn = toolbar.querySelector('[data-cmd="blockquote"]');
    if (quoteBtn) {
      var inInlineQuote = false;
      var sel = window.getSelection();
      var n = sel && sel.anchorNode;
      while (n && n !== body) {
        if (n.nodeType === 1 && n.tagName === "BLOCKQUOTE" && n.classList && n.classList.contains("ap-inline-mark")) {
          inInlineQuote = true;
          break;
        }
        n = n.parentNode;
      }
      quoteBtn.classList.toggle("is-active", inInlineQuote || document.queryCommandValue("formatBlock").toLowerCase() === "blockquote");
    }
  }
  document.addEventListener("selectionchange", function () {
    if (document.activeElement === body) refreshToolbarState();
  });
  /* ─── content structuring: turn the live contenteditable DOM (full of
     editor-only chrome — drag handles, height pills, selection state) into
     clean, portable, self-contained HTML. Editor CSS classes like
     "size-full"/"align-center" mean nothing outside this page, so every
     size/align/crop decision gets baked into inline styles instead. This is
     what both the Preview/Copy modal and the hidden submit field use. ──── */
  function buildPortableHTML() {
    var clone = body.cloneNode(true);
    clone.querySelectorAll(".ap-img-handle, .ap-img-h-pill").forEach(function (el) { el.remove(); });
    var sizeMap = { s: "34%", m: "58%", l: "84%", full: "100%" };
    clone.querySelectorAll(".ap-ed-img-wrap").forEach(function (wrap) {
      var width = "58%";
      Object.keys(sizeMap).forEach(function (s) { if (wrap.classList.contains("size-" + s)) width = sizeMap[s]; });
      var margin = "1.1em auto";
      if (wrap.classList.contains("align-left")) margin = "1.1em auto 1.1em 0";
      if (wrap.classList.contains("align-right")) margin = "1.1em 0 1.1em auto";
      var img = wrap.querySelector("img");
      var wrapStyle = "display:block;width:" + width + ";margin:" + margin + ";border-radius:10px;overflow:hidden;";
      var imgStyle = "display:block;width:100%;border-radius:10px;";
      if (wrap.classList.contains("h-custom")) {
        var h = wrap.style.getPropertyValue("--ap-img-h") || "320px";
        wrapStyle += "height:" + h + ";";
        imgStyle += "height:100%;object-fit:" + (img.style.objectFit || "cover") + ";object-position:" + (img.style.objectPosition || "center top") + ";";
      }
      wrap.removeAttribute("class");
      wrap.removeAttribute("contenteditable");
      wrap.setAttribute("style", wrapStyle);
      if (img) img.setAttribute("style", imgStyle);
    });
    // strip any remaining editor-only attributes so the fragment is inert HTML
    clone.querySelectorAll("[contenteditable]").forEach(function (el) { el.removeAttribute("contenteditable"); });
    return clone.innerHTML.trim();
  }
  /* ─── preview modal: rendered "as published" view + copyable source ──── */
  var previewOverlay = document.getElementById("apPreviewOverlay");
  var previewRender = document.getElementById("apPreviewRender");
  var previewCode = document.getElementById("apPreviewCode");
  var previewCodeText = document.getElementById("apPreviewCodeText");
  var previewToggleBtn = document.getElementById("apPreviewToggleCode");
  var previewCopyBtn = document.getElementById("apPreviewCopy");
  var previewCloseBtn = document.getElementById("apPreviewClose");
  var previewBtn = document.getElementById("apPreviewBtn");
  function openPreview() {
    var html = buildPortableHTML();
    previewRender.innerHTML = html || '<p class="ap-preview-empty">Nada escrito ainda — comece a digitar para ver a pré-visualização.</p>';
    previewCodeText.textContent = html;
    previewRender.hidden = false;
    previewCode.hidden = true;
    previewToggleBtn.innerHTML = '<i class="ri-code-s-slash-line"></i><span>Ver HTML</span>';
    previewOverlay.classList.add("is-open");
    document.documentElement.style.overflow = "hidden";
    var g = gs();
    if (g) {
      g.killTweensOf(previewOverlay.querySelector(".ap-preview-modal"));
      g.fromTo(previewOverlay.querySelector(".ap-preview-modal"), { y: 32, opacity: 0, scale: .97 }, { y: 0, opacity: 1, scale: 1, duration: .45, ease: "power2.out" });
    }
  }
  function closePreview() {
    previewOverlay.classList.remove("is-open");
    document.documentElement.style.overflow = "";
  }
  previewBtn.addEventListener("click", openPreview);
  previewCloseBtn.addEventListener("click", closePreview);
  previewOverlay.addEventListener("click", function (e) { if (e.target === previewOverlay) closePreview(); });
  document.addEventListener("keydown", function (e) { if (e.key === "Escape" && previewOverlay.classList.contains("is-open")) closePreview(); });
  previewToggleBtn.addEventListener("click", function () {
    var showCode = previewCode.hidden;
    previewCode.hidden = !showCode;
    previewRender.hidden = showCode;
    previewToggleBtn.innerHTML = showCode ? '<i class="ri-eye-line"></i><span>Ver pré-visualização</span>' : '<i class="ri-code-s-slash-line"></i><span>Ver HTML</span>';
    squeeze(previewToggleBtn);
  });
  function fallbackCopy(text) {
    var ta = document.createElement("textarea");
    ta.value = text; ta.style.position = "fixed"; ta.style.opacity = "0";
    document.body.appendChild(ta); ta.focus(); ta.select();
    var ok = false;
    try { ok = document.execCommand("copy"); } catch (err) { ok = false; }
    document.body.removeChild(ta);
    return ok;
  }
  function flashCopyState(ok) {
    squeeze(previewCopyBtn);
    previewCopyBtn.innerHTML = ok ? '<i class="ri-check-line"></i><span>Copiado</span>' : '<i class="ri-error-warning-line"></i><span>Falha ao copiar</span>';
    setTimeout(function () { previewCopyBtn.innerHTML = '<i class="ri-clipboard-line"></i><span>Copiar HTML</span>'; }, 1700);
  }
  previewCopyBtn.addEventListener("click", function () {
    var text = buildPortableHTML();
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () { flashCopyState(true); }, function () { flashCopyState(fallbackCopy(text)); });
    } else {
      flashCopyState(fallbackCopy(text));
    }
  });
  /* submit handled by apollo-events create form — not this editor */

  function syncHidden() {
    var hidden = document.getElementById("ev-about");
    if (!hidden) return;
    hidden.value = buildPortableHTML();
  }
  var _origUpdateEmpty = updateEmptyState;
  updateEmptyState = function () {
    _origUpdateEmpty();
    syncHidden();
  };
  /* also sync after preview opens / format ops already call updateEmptyState */
  window.ApolloEventAboutEditor = {
    getHTML: function () { return buildPortableHTML(); },
    setHTML: function (html) {
      body.innerHTML = html || "";
      selectedImg = null;
      if (toolbar) toolbar.classList.remove("has-img-selection");
      updateEmptyState();
      updateCounter();
      syncHidden();
    },
    syncHidden: syncHidden
  };
  syncHidden();

})();
