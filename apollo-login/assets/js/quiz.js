/**
 * Apollo Quiz - JavaScript
 *
 * @package Apollo\Login
 */

(function($) {
	'use strict';

	let quizToken = '';
	let currentStage = 0;
	const stages = ['pattern', 'simon', 'ethics', 'reaction'];

	$(document).ready(function() {

		// Close quiz overlay
		$('.apollo-quiz-overlay').on('click', function(e) {
			if ($(e.target).hasClass('apollo-quiz-overlay')) {
				$(this).fadeOut();
			}
		});

		// Stage navigation
		$('.apollo-btn-next').on('click', function() {
			const nextStage = $(this).data('next');
			goToStage(nextStage);
		});

		// Complete quiz
		$('.apollo-btn-complete').on('click', function() {
			completeQuiz();
		});

		// Finish quiz and return to registration
		$('#apollo-quiz-finish').on('click', function() {
			$('#apollo-quiz-token').val(quizToken);
			$('.apollo-quiz-overlay').fadeOut();
			$('.apollo-form-actions').show();
			$('.apollo-form-actions button').prop('disabled', false);
			$('.apollo-quiz-required-notice').hide();
		});

		// Load first stage
		loadStage('pattern');
	});

	function goToStage(stageName) {
		$('.apollo-quiz-stage').hide();
		$('#quiz-stage-' + stageName).fadeIn();

		currentStage = stages.indexOf(stageName);
		updateProgress();

		loadStage(stageName);
	}

	function loadStage(stageName) {
		if (stageName === 'pattern' || stageName === 'ethics') {
			loadQuestions(stageName);
		} else if (stageName === 'simon') {
			initSimonGame();
		} else if (stageName === 'reaction') {
			initReactionTest();
		}
	}

	function loadQuestions(stage) {
		const $container = $('#quiz-stage-' + stage + ' .apollo-quiz-questions');

		$.ajax({
			url: apolloQuiz.restUrl + '/quiz/questions',
			method: 'GET',
			data: { stage: stage },
			success: function(response) {
				renderQuestions($container, response.questions, stage);
			}
		});
	}

	function renderQuestions($container, questions, stage) {
		$container.empty();

		questions.forEach(function(q) {
			const $question = $('<div class="quiz-question">');
			$question.append('<h4>' + q.question + '</h4>');

			const $options = $('<div class="quiz-options">');

			if (Array.isArray(q.options)) {
				q.options.forEach(function(opt) {
					$options.append(
						'<label><input type="radio" name="q_' + q.id + '" value="' + opt + '"> ' + opt + '</label>'
					);
				});
			} else {
				Object.keys(q.options).forEach(function(key) {
					$options.append(
						'<label><input type="radio" name="q_' + q.id + '" value="' + key + '"> ' + key + ') ' + q.options[key] + '</label>'
					);
				});
			}

			$question.append($options);
			$container.append($question);
		});
	}

	function initSimonGame() {
		// Simon game logic will be in simon.js
		// For now, just show placeholder
		$('#apollo-simon-game').html('<p>Simon game will load here</p>');

		// Simulate completion after 5 seconds
		setTimeout(function() {
			$('#quiz-stage-simon .apollo-btn-next').show();
		}, 5000);
	}

	function initReactionTest() {
		// Reaction test logic
		$('#apollo-reaction-game').html('<p>Reaction test will load here</p>');

		// Simulate completion after 5 seconds
		setTimeout(function() {
			$('.apollo-btn-complete').show();
		}, 5000);
	}

	function completeQuiz() {
		$('.apollo-quiz-stage').hide();
		$('#quiz-complete').fadeIn();
		updateProgress(100);

		// Generate token
		quizToken = 'quiz_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
	}

	function updateProgress(percent) {
		if (typeof percent === 'undefined') {
			percent = ((currentStage + 1) / stages.length) * 100;
		}
		$('.apollo-quiz-progress-bar').css('width', percent + '%');
	}

})(jQuery);
