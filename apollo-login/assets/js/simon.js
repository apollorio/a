/**
 * Apollo Simon Game - JavaScript
 *
 * @package Apollo\Login
 */

(function($) {
	'use strict';

	let sequence = [];
	let playerSequence = [];
	let level = 1;
	let isPlaying = false;

	const colors = ['red', 'green', 'blue', 'yellow'];

	$(document).ready(function() {
		if ($('#apollo-simon-game').length) {
			renderSimonGame();
		}
	});

	function renderSimonGame() {
		const $game = $('#apollo-simon-game');

		$game.html(`
			<div class="simon-button simon-red" data-color="red"></div>
			<div class="simon-button simon-green" data-color="green"></div>
			<div class="simon-button simon-blue" data-color="blue"></div>
			<div class="simon-button simon-yellow" data-color="yellow"></div>
			<div class="simon-center">
				<span class="simon-level">Level 1</span>
			</div>
		`);

		// Start button
		$('.simon-center').on('click', function() {
			if (!isPlaying) {
				startGame();
			}
		});

		// Color buttons
		$('.simon-button').on('click', function() {
			if (isPlaying) {
				const color = $(this).data('color');
				playerClick(color);
			}
		});
	}

	function startGame() {
		isPlaying = true;
		sequence = [];
		playerSequence = [];
		level = 1;
		nextRound();
	}

	function nextRound() {
		playerSequence = [];

		// Add random color to sequence
		const randomColor = colors[Math.floor(Math.random() * colors.length)];
		sequence.push(randomColor);

		// Update level display
		$('.simon-level').text('Level ' + level);

		// Play sequence
		playSequence();
	}

	function playSequence() {
		isPlaying = false;
		let i = 0;

		const interval = setInterval(function() {
			activateButton(sequence[i]);
			i++;

			if (i >= sequence.length) {
				clearInterval(interval);
				isPlaying = true;
			}
		}, 800);
	}

	function activateButton(color) {
		const $button = $('.simon-' + color);
		$button.addClass('active');

		// Play sound (if implemented)

		setTimeout(function() {
			$button.removeClass('active');
		}, 400);
	}

	function playerClick(color) {
		activateButton(color);
		playerSequence.push(color);

		const index = playerSequence.length - 1;

		if (playerSequence[index] !== sequence[index]) {
			// Wrong!
			gameOver();
			return;
		}

		if (playerSequence.length === sequence.length) {
			// Round complete!
			if (level >= 4) {
				// Game won!
				gameWon();
			} else {
				level++;
				setTimeout(nextRound, 1000);
			}
		}
	}

	function gameOver() {
		isPlaying = false;
		$('.simon-level').text('Game Over!');

		// Submit score
		submitSimonScore(level, false);

		// Restart after 2 seconds
		setTimeout(startGame, 2000);
	}

	function gameWon() {
		isPlaying = false;
		$('.simon-level').text('You Won!');

		// Submit score
		submitSimonScore(4, true);

		// Show next button
		$('#quiz-stage-simon .apollo-btn-next').show();
	}

	function submitSimonScore(level, success) {
		$.ajax({
			url: apolloSimon.restUrl + '/simon/submit',
			method: 'POST',
			data: {
				level: level,
				sequence: sequence,
				success: success,
				token: $('#apollo-quiz-token').val() || 'guest'
			},
			success: function() {}
		});
	}

})(jQuery);
