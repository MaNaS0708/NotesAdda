/**
 * Notes Adda - Expert Ratings Module
 * Contextual quarter-star rating controls, slider input, quick select presets, and live rating submissions.
 *
 * @package Notes_Adda
 */

(function($) {
	'use strict';

	if (typeof window.NotesAddaApp === 'undefined') {
		window.NotesAddaApp = {};
	}

	const ExpertRatings = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			const self = this;
			const app = window.NotesAddaApp;

			// Helper to update range slider background gradient track
			const updateSliderFill = function($slider, val) {
				const min = parseFloat($slider.attr('min')) || 0.25;
				const max = parseFloat($slider.attr('max')) || 5.00;
				const pct = Math.min(100, Math.max(0, Math.round(((val - min) / (max - min)) * 100)));
				$slider.css('background', `linear-gradient(to right, var(--na-brand) 0%, var(--na-brand) ${pct}%, var(--na-surface-elevated) ${pct}%, var(--na-surface-elevated) 100%)`);
			};

			// Rating Slider input / change
			$(document).on('input change', '.na-rating-slider', function() {
				const $slider = $(this);
				const $widget = $slider.closest('.na-contextual-rating-widget');
				const val = parseFloat($slider.val()) || 5.00;
				const valStr = Number(val).toFixed(2);

				updateSliderFill($slider, val);

				$widget.find('.na-rating-val-number').text(valStr);
				$widget.find('.na-rating-stars-live-preview').html(app.renderQuarterStarsHtml(val, 26));

				// Sync active preset pill
				$widget.find('.na-preset-btn').each(function() {
					const pVal = parseFloat($(this).data('val'));
					$(this).toggleClass('active', Math.abs(pVal - val) < 0.05);
				});

				const expertName = $widget.find('.na-rating-title strong').text() || 'Expert';
				$slider.attr('aria-label', `Rate ${expertName} ${valStr} out of 5 stars`);

				const hasRated = !!$widget.data('current-user-rating');
				$widget.find('.na-submit-rating-btn .na-btn-text').text((hasRated ? 'Update rating · ' : 'Submit rating · ') + valStr);
			});

			// Rating Preset Button click
			$(document).on('click', '.na-preset-btn', function(e) {
				e.preventDefault();
				e.stopPropagation();
				const val = parseFloat($(this).data('val'));
				const $widget = $(this).closest('.na-contextual-rating-widget');
				const $slider = $widget.find('.na-rating-slider');
				$slider.val(val).trigger('input');
			});

			// Submit Rating Button click
			$(document).on('click', '.na-submit-rating-btn', function(e) {
				e.preventDefault();
				e.stopPropagation();
				if (!NotesAdda.is_logged_in) {
					app.showToast('Please sign in to rate experts.', 'warning');
					return;
				}

				const $widget = $(this).closest('.na-contextual-rating-widget');
				const expertId = parseInt($widget.data('expert-id'), 10);
				const subject = String($widget.data('subject') || '');
				const rating = parseFloat($widget.find('.na-rating-slider').val()) || 5.00;

				self.submitRating(expertId, subject, rating, $widget);
			});
		},

		/**
		 * Generate HTML markup for interactive rating widget.
		 */
		renderRatingControlHtml: function(expertId, expertName, subject, userRating, summary) {
			const app = window.NotesAddaApp;
			const currentVal = (userRating !== null && userRating !== undefined && userRating > 0) ? parseFloat(userRating) : 5.00;
			const formattedVal = Number(currentVal).toFixed(2);
			const hasRated = (userRating !== null && userRating !== undefined && userRating > 0);
			const expertDisplay = expertName || 'Expert';

			const min = 0.25;
			const max = 5.00;
			const pct = Math.min(100, Math.max(0, Math.round(((currentVal - min) / (max - min)) * 100)));
			const sliderBg = `linear-gradient(to right, var(--na-brand) 0%, var(--na-brand) ${pct}%, var(--na-surface-elevated) ${pct}%, var(--na-surface-elevated) 100%)`;

			return `
				<div class="na-contextual-rating-widget" data-expert-id="${expertId}" data-subject="${app.escapeHtml(subject || '')}" data-current-user-rating="${hasRated ? currentVal : ''}">
					<div class="na-rating-ctrl-header">
						<div class="na-rating-header-left">
							<span class="na-rating-eyebrow">EXPERT EVALUATION</span>
							<h4 class="na-rating-title">Rate <strong>${app.escapeHtml(expertDisplay)}</strong> for <em>${app.escapeHtml(subject || 'this subject')}</em></h4>
						</div>
						<div class="na-rating-score-pill">
							<span class="na-score-pill-star">★</span>
							<span class="na-rating-val-number">${formattedVal}</span>
							<span class="na-score-pill-scale">/ 5.00</span>
						</div>
					</div>

					<div class="na-rating-interactive-box">
						<div class="na-rating-stars-live-preview">
							${app.renderQuarterStarsHtml(currentVal, 22)}
						</div>
						<div class="na-rating-slider-row">
							<input type="range" id="na-rating-slider-${expertId}" class="na-rating-slider" min="0.25" max="5.00" step="0.25" value="${formattedVal}" style="background:${sliderBg};" aria-label="Rate ${app.escapeHtml(expertDisplay)} ${formattedVal} out of 5 stars">
						</div>
					</div>

					<div class="na-rating-presets-row">
						<span class="na-preset-label">Quick select:</span>
						<div class="na-preset-chips">
							<button type="button" class="na-preset-btn ${formattedVal === '5.00' ? 'active' : ''}" data-val="5.00">5.00</button>
							<button type="button" class="na-preset-btn ${formattedVal === '4.75' ? 'active' : ''}" data-val="4.75">4.75</button>
							<button type="button" class="na-preset-btn ${formattedVal === '4.50' ? 'active' : ''}" data-val="4.50">4.50</button>
							<button type="button" class="na-preset-btn ${formattedVal === '4.25' ? 'active' : ''}" data-val="4.25">4.25</button>
							<button type="button" class="na-preset-btn ${formattedVal === '4.00' ? 'active' : ''}" data-val="4.00">4.00</button>
						</div>
					</div>

					<div class="na-rating-action-row">
						<button type="button" class="na-btn na-btn-primary na-submit-rating-btn">
							<span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
							<span class="na-btn-text">${hasRated ? `Update rating · ${formattedVal}` : `Submit rating · ${formattedVal}`}</span>
							<span class="na-btn-spinner dashicons dashicons-update na-spin" style="display:none;"></span>
						</button>
						${hasRated ? `<span class="na-current-rated-notice"><span class="dashicons dashicons-yes"></span> You previously rated: <strong>${formattedVal}/5</strong></span>` : ''}
					</div>
				</div>
			`;
		},

		/**
		 * Submit contextual rating to server via AJAX.
		 */
		submitRating: function(expertId, subject, rating, $container) {
			const app = window.NotesAddaApp;
			const $btn = $container.find('.na-submit-rating-btn');
			const $slider = $container.find('.na-rating-slider');
			const $presets = $container.find('.na-preset-btn');
			const $spinner = $btn.find('.na-btn-spinner');
			const $btnText = $btn.find('.na-btn-text');

			$btn.prop('disabled', true);
			$slider.prop('disabled', true);
			$presets.prop('disabled', true);
			$spinner.show();

			$.post(NotesAdda.ajax_url, {
				action: 'notes_adda_rate_expert',
				expert_id: expertId,
				subject: subject,
				rating: rating,
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				$btn.prop('disabled', false);
				$slider.prop('disabled', false);
				$presets.prop('disabled', false);
				$spinner.hide();

				if (res.success && res.data) {
					const data = res.data;
					const avgVal = parseFloat(data.average) || 0;
					const avgFormatted = data.formatted_average || Number(avgVal).toFixed(2);
					const userRatingFormatted = data.formatted_rating || Number(rating).toFixed(2);
					const count = parseInt(data.count, 10) || 0;

					$container.data('current-user-rating', rating);
					$btnText.text(`Update rating · ${userRatingFormatted}`);

					let $ratedNotice = $container.find('.na-current-rated-notice');
					if (!$ratedNotice.length) {
						$container.find('.na-rating-action-row').append('<span class="na-current-rated-notice"></span>');
						$ratedNotice = $container.find('.na-current-rated-notice');
					}
					$ratedNotice.html(`<span class="dashicons dashicons-yes"></span> You previously rated: <strong>${userRatingFormatted}/5</strong>`);

					// Update note cards on page for matching expert and subject
					$(`.na-card[data-reviewer-id="${expertId}"][data-subject="${subject}"]`).each(function() {
						const $revBox = $(this).find('.na-card-reviewer-box');
						if ($revBox.length) {
							$revBox.find('.na-card-rating-line').html(`
								${app.renderQuarterStarsHtml(avgVal, 13)}
								<span class="na-card-rating-score">${avgFormatted} / 5</span>
								<span class="na-card-rating-count">(${count})</span>
							`);
						}
					});

					// Update note details page metric stats if currently viewing
					const $metric = $(`.na-metric-rating[data-expert-id="${expertId}"][data-subject="${subject}"]`);
					if ($metric.length) {
						$metric.find('.na-metric-avg-rating-val').text(avgFormatted);
						$metric.find('.na-metric-stars-sub').html(`
							${app.renderQuarterStarsHtml(avgVal, 14)}
							<span class="na-metric-count">(${count} ${count === 1 ? 'review' : 'reviews'})</span>
						`);
					}

					app.showToast(data.message || 'Rating submitted successfully!', 'success');
				} else {
					app.showToast(res.data ? res.data.message : 'Failed to submit rating.', 'error');
				}
			}).fail(function(xhr) {
				$btn.prop('disabled', false);
				$slider.prop('disabled', false);
				$presets.prop('disabled', false);
				$spinner.hide();
				const msg = (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message)
					? xhr.responseJSON.data.message
					: 'Error submitting rating.';
				app.showToast(msg, 'error');
			});
		}
	};

	window.NotesAddaApp.ExpertRatings = ExpertRatings;
	if (window.NotesAddaApp.registerModule) {
		window.NotesAddaApp.registerModule('expertRatings', ExpertRatings);
	}

})(jQuery);
