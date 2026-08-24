(function() {
  "use strict";

  var TICK_MS = 60000;

  function init() {
    var containers = document.querySelectorAll('[data-ae-now-line]');
    if (!containers.length) return;

    for (var i = 0; i < containers.length; i++) {
      setupNowLine(containers[i]);
    }
  }

  function setupNowLine(container) {
    var line = container.querySelector('.ae-now-line');
    if (!line) {
      line = document.createElement('div');
      line.className = 'ae-now-line';
      line.style.cssText = 'position:absolute;left:0;right:0;height:2px;background:var(--ae-accent,#FF5C00);pointer-events:none;z-index:10;';

      var dot = document.createElement('div');
      dot.className = 'ae-now-dot';
      dot.style.cssText = 'position:absolute;left:-4px;top:-3px;width:8px;height:8px;border-radius:50%;background:var(--ae-accent,#FF5C00);';
      line.appendChild(dot);

      container.style.position = container.style.position || 'relative';
      container.appendChild(line);
    }

    positionLine(container, line);
    setInterval(function() { positionLine(container, line); }, TICK_MS);
  }

  function positionLine(container, line) {
    var now = new Date();
    var startHour = parseInt(container.getAttribute('data-ae-start-hour'), 10);
    var endHour = parseInt(container.getAttribute('data-ae-end-hour'), 10);

    if (isNaN(startHour)) startHour = 0;
    if (isNaN(endHour)) endHour = 24;

    var totalMinutes = (endHour - startHour) * 60;
    if (totalMinutes <= 0) return;

    var currentMinutes = (now.getHours() * 60 + now.getMinutes()) - (startHour * 60);

    if (currentMinutes < 0 || currentMinutes > totalMinutes) {
      line.style.display = 'none';
      return;
    }

    var pct = (currentMinutes / totalMinutes) * 100;
    line.style.display = '';
    line.style.top = pct + '%';

    var label = line.querySelector('.ae-now-label');
    if (!label) {
      label = document.createElement('span');
      label.className = 'ae-now-label ae-mono';
      label.style.cssText = 'position:absolute;right:0;top:-14px;font-size:9px;color:var(--ae-accent,#FF5C00);';
      line.appendChild(label);
    }

    var h = now.getHours();
    var m = now.getMinutes();
    var ampm = h >= 12 ? 'PM' : 'AM';
    var h12 = h % 12 || 12;
    label.textContent = h12 + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
