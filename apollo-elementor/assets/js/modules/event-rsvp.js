(function() {
  "use strict";

  function init() {
    var buttons = document.querySelectorAll('[data-ae-rsvp]');
    if (!buttons.length) return;

    for (var i = 0; i < buttons.length; i++) {
      setupRsvp(buttons[i]);
    }
  }

  function setupRsvp(btn) {
    if (btn._aeRsvpBound) return;
    btn._aeRsvpBound = true;

    btn.addEventListener('click', function(e) {
      e.preventDefault();
      handleRsvp(btn);
    });
  }

  function handleRsvp(btn) {
    if (btn.classList.contains('ae-rsvp-loading')) return;

    var eventId = btn.getAttribute('data-ae-rsvp');
    var isActive = btn.classList.contains('ae-rsvp-active');
    var action = isActive ? 'cancel' : 'rsvp';

    var originalText = btn.textContent;
    btn.classList.add('ae-rsvp-loading');
    btn.setAttribute('disabled', 'disabled');

    if (action === 'rsvp') {
      btn.classList.add('ae-rsvp-active');
      btn.textContent = 'Going!';
    } else {
      btn.classList.remove('ae-rsvp-active');
      btn.textContent = 'RSVP';
    }

    var counter = btn.closest('[data-ae-rsvp-wrap]');
    var countEl = counter ? counter.querySelector('[data-ae-rsvp-count]') : null;
    if (countEl) {
      var current = parseInt(countEl.textContent, 10) || 0;
      countEl.textContent = action === 'rsvp' ? current + 1 : Math.max(0, current - 1);
    }

    var endpoint = (window.apolloElementorConfig && window.apolloElementorConfig.ajaxUrl) || '/wp-admin/admin-ajax.php';
    var nonce = (window.apolloElementorConfig && window.apolloElementorConfig.nonce) || '';

    var formData = new FormData();
    formData.append('action', 'ae_rsvp');
    formData.append('event_id', eventId);
    formData.append('rsvp_action', action);
    formData.append('_wpnonce', nonce);

    fetch(endpoint, { method: 'POST', body: formData, credentials: 'same-origin' })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        btn.classList.remove('ae-rsvp-loading');
        btn.removeAttribute('disabled');

        if (!data.success) {
          btn.classList.toggle('ae-rsvp-active');
          btn.textContent = originalText;
          if (countEl) {
            var c = parseInt(countEl.textContent, 10) || 0;
            countEl.textContent = action === 'rsvp' ? Math.max(0, c - 1) : c + 1;
          }
        } else {
          if (data.data && typeof data.data.count !== 'undefined' && countEl) {
            countEl.textContent = data.data.count;
          }
        }
      })
      .catch(function() {
        btn.classList.remove('ae-rsvp-loading');
        btn.removeAttribute('disabled');
        btn.classList.toggle('ae-rsvp-active');
        btn.textContent = originalText;
        if (countEl) {
          var c = parseInt(countEl.textContent, 10) || 0;
          countEl.textContent = action === 'rsvp' ? Math.max(0, c - 1) : c + 1;
        }
      });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
