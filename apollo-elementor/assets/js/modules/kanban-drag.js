(function() {
  "use strict";

  var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var EDGE_ZONE = 60;
  var SCROLL_SPEED = 8;
  var activeCard = null;
  var ghost = null;
  var dropLine = null;
  var originCol = null;
  var startX = 0;
  var startY = 0;
  var offsetX = 0;
  var offsetY = 0;
  var scrollRAF = null;

  function init() {
    var boards = document.querySelectorAll('[data-ae-kanban]');
    if (!boards.length) return;

    for (var i = 0; i < boards.length; i++) {
      setupBoard(boards[i]);
    }
  }

  function applyDragStyles(el) {
    el.style.cssText += '-webkit-user-select:none!important;user-select:none!important;-webkit-touch-callout:none!important;touch-action:none!important;';
  }

  function setupBoard(board) {
    var cards = board.querySelectorAll('[data-ae-card]');
    for (var i = 0; i < cards.length; i++) {
      applyDragStyles(cards[i]);
      cards[i].classList.add('ae-card-drag');
    }

    board.addEventListener('pointerdown', onPointerDown, { passive: false });
  }

  function onPointerDown(e) {
    var card = e.target.closest('[data-ae-card]');
    if (!card) return;

    e.preventDefault();
    activeCard = card;
    originCol = card.closest('[data-ae-column]');
    var rect = card.getBoundingClientRect();
    startX = e.clientX;
    startY = e.clientY;
    offsetX = e.clientX - rect.left;
    offsetY = e.clientY - rect.top;

    ghost = card.cloneNode(true);
    ghost.classList.add('ae-drag-ghost');
    ghost.style.cssText = 'position:fixed;z-index:99999;pointer-events:none;width:' + rect.width + 'px;opacity:0.85;left:' + (e.clientX - offsetX) + 'px;top:' + (e.clientY - offsetY) + 'px;';
    if (!REDUCED) ghost.style.transition = 'transform 0.12s ease, opacity 0.12s ease';
    document.body.appendChild(ghost);

    card.style.opacity = '0.3';

    dropLine = document.createElement('div');
    dropLine.className = 'ae-drop-line';
    dropLine.style.cssText = 'height:3px;background:var(--ae-accent,#FF5C00);border-radius:2px;pointer-events:none;margin:4px 0;';

    document.addEventListener('pointermove', onPointerMove, { passive: false });
    document.addEventListener('pointerup', onPointerUp);
    document.addEventListener('pointercancel', onPointerUp);
  }

  function onPointerMove(e) {
    if (!ghost) return;
    e.preventDefault();

    ghost.style.left = (e.clientX - offsetX) + 'px';
    ghost.style.top = (e.clientY - offsetY) + 'px';

    edgeScroll(e);

    var col = getColumnUnder(e.clientX, e.clientY);
    if (!col) return;

    var cardsList = col.querySelectorAll('[data-ae-card]:not(.ae-drag-ghost)');
    var inserted = false;

    for (var i = 0; i < cardsList.length; i++) {
      var r = cardsList[i].getBoundingClientRect();
      var mid = r.top + r.height / 2;
      if (e.clientY < mid && cardsList[i] !== activeCard) {
        if (dropLine.parentNode) dropLine.parentNode.removeChild(dropLine);
        cardsList[i].parentNode.insertBefore(dropLine, cardsList[i]);
        inserted = true;
        break;
      }
    }

    if (!inserted) {
      var container = col.querySelector('[data-ae-card-list]') || col;
      if (dropLine.parentNode) dropLine.parentNode.removeChild(dropLine);
      container.appendChild(dropLine);
    }
  }

  function onPointerUp() {
    document.removeEventListener('pointermove', onPointerMove);
    document.removeEventListener('pointerup', onPointerUp);
    document.removeEventListener('pointercancel', onPointerUp);

    if (scrollRAF) { cancelAnimationFrame(scrollRAF); scrollRAF = null; }

    if (activeCard && dropLine && dropLine.parentNode) {
      dropLine.parentNode.insertBefore(activeCard, dropLine);
    }

    if (activeCard) activeCard.style.opacity = '';
    if (ghost && ghost.parentNode) ghost.parentNode.removeChild(ghost);
    if (dropLine && dropLine.parentNode) dropLine.parentNode.removeChild(dropLine);

    activeCard = null;
    ghost = null;
    dropLine = null;
    originCol = null;
  }

  function getColumnUnder(x, y) {
    var cols = document.querySelectorAll('[data-ae-column]');
    for (var i = 0; i < cols.length; i++) {
      var r = cols[i].getBoundingClientRect();
      if (x >= r.left && x <= r.right && y >= r.top && y <= r.bottom) {
        return cols[i];
      }
    }
    return null;
  }

  function edgeScroll(e) {
    if (scrollRAF) { cancelAnimationFrame(scrollRAF); scrollRAF = null; }

    var vw = window.innerWidth;
    var vh = window.innerHeight;
    var dx = 0;
    var dy = 0;

    if (e.clientX < EDGE_ZONE) dx = -SCROLL_SPEED;
    else if (e.clientX > vw - EDGE_ZONE) dx = SCROLL_SPEED;
    if (e.clientY < EDGE_ZONE) dy = -SCROLL_SPEED;
    else if (e.clientY > vh - EDGE_ZONE) dy = SCROLL_SPEED;

    if (dx || dy) {
      (function tick() {
        window.scrollBy(dx, dy);
        scrollRAF = requestAnimationFrame(tick);
      })();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
