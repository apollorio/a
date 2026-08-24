(function() {
  "use strict";

  var LOADED = {};
  var BASE_URL = (window.apolloElementorConfig && window.apolloElementorConfig.baseUrl) || '';

  function initAll(root) {
    root = root || document;
    var modules = root.querySelectorAll('[data-a-module]');
    for (var i = 0; i < modules.length; i++) {
      var name = modules[i].getAttribute('data-a-module');
      if (!name || LOADED[name]) continue;
      LOADED[name] = true;
      loadModule(name);
    }
  }

  function loadModule(name) {
    var script = document.createElement('script');
    script.src = BASE_URL + 'modules/' + name + '.js';
    script.async = true;
    script.onerror = function() {
      console.warn('[apollo-elementor] Failed to load module: ' + name);
    };
    document.body.appendChild(script);
  }

  function boot() {
    initAll(document);
  }

  // Elementor editor re-hydration
  if (window.elementorFrontend && window.elementorFrontend.hooks) {
    elementorFrontend.hooks.addAction('frontend/element_ready/widget', function($el) {
      LOADED = {};
      initAll($el[0] || $el);
    });
  }

  // Apollo boot pattern
  window.addEventListener('apollo:ready', boot);
  if (window.Apollo && window.Apollo.isReady) boot();
  setTimeout(function() {
    if (!LOADED._booted) { LOADED._booted = true; boot(); }
  }, 1200);
})();
