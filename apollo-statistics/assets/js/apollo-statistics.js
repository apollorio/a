/**
 * Apollo Statistics — Admin JS
 *
 * Inicializa gráficos Chart.js com dados da REST API.
 *
 * @package Apollo\Statistics
 */

(function ($) {
	'use strict';

	if (typeof apolloStats === 'undefined') {
		return;
	}

	const API = {
		/**
		 * Fetch REST API com autenticação.
		 *
		 * @param {string} endpoint  Caminho relativo ao base URL.
		 * @param {object} params    Query params.
		 * @returns {Promise<object>}
		 */
		get: async function (endpoint, params = {}) {
			const url = new URL( apolloStats.restUrl + endpoint, window.location.origin );

			Object.keys( params ).forEach(
				function (key) {
					url.searchParams.set( key, params[key] );
				}
			);

			const response = await fetch(
				url.toString(),
				{
					method: 'GET',
					headers: {
						'X-WP-Nonce': apolloStats.nonce,
						'Content-Type': 'application/json',
					},
					credentials: 'same-origin',
				}
			);

			if ( ! response.ok) {
				throw new Error( 'Apollo Stats API Error: ' + response.statusText );
			}

			return response.json();
		},
	};

	/**
	 * Inicializa gráfico overview se canvas existir.
	 */
	async function initOverviewChart() {
		const canvas = document.getElementById( 'apollo-stats-chart-overview' );
		if ( ! canvas || typeof Chart === 'undefined') {
			return;
		}

		try {
			const result = await API.get(
				'chart',
				{
					metric: 'view',
					table: 'content',
					days: 30,
				}
			);

			if ( ! result.success || ! result.data) {
				return;
			}

			new Chart(
				canvas.getContext( '2d' ),
				{
					type: 'line',
					data: result.data,
					options: {
						responsive: true,
						maintainAspectRatio: false,
						interaction: {
							intersect: false,
							mode: 'index',
						},
						plugins: {
							legend: {
								display: true,
								position: 'top',
							},
							tooltip: {
								backgroundColor: 'rgba(26, 26, 46, 0.9)',
								titleColor: '#fff',
								bodyColor: '#e0e0e0',
								padding: 12,
								cornerRadius: 8,
							},
						},
						scales: {
							x: {
								grid: {
									display: false,
								},
								ticks: {
									maxTicksLimit: 10,
									font: { size: 11 },
								},
							},
							y: {
								beginAtZero: true,
								grid: {
									color: 'rgba(var(--rgb-d), 0.05)',
								},
								ticks: {
									font: { size: 11 },
								},
							},
						},
					},
				}
			);
		} catch (error) {
			console.warn( '[Apollo Statistics]', error.message );
		}
	}

	/**
	 * Inicializa quando DOM estiver pronto.
	 */
	$(
		function () {
			initOverviewChart();
		}
	);

})( jQuery );
