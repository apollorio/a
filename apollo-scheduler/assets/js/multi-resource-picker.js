/**
 * Apollo Scheduler — multi-resource picker with heat indicator.
 */
(function () {
	'use strict';

	window.ApolloSchedulerPicker = {
		init: function (root, config) {
			if (!root || !config) return;
			root.addEventListener('click', function (e) {
				var card = e.target.closest('.apollo-scheduler-card');
				if (!card || !window.gsap) return;
				gsap.to(card, { rotateY: 2, rotateX: -2, duration: 0.2, yoyo: true, repeat: 1, transformPerspective: 600 });
			});
		}
	};

	document.addEventListener('DOMContentLoaded', function () {
		var root = document.querySelector('.apollo-scheduler-canvas');
		var configEl = document.querySelector('[data-apollo-scheduler-config]');
		if (root && configEl) {
			try {
				window.ApolloSchedulerPicker.init(root, JSON.parse(configEl.getAttribute('data-apollo-scheduler-config')));
			} catch (e) { /* noop */ }
		}
	});
})();
