/**
 * Notes Adda - Review Queue Module
 * Dedicated verification workflow for subject experts and administrators.
 *
 * @package Notes_Adda
 */

(function($) {
	'use strict';

	if (typeof window.NotesAddaApp === 'undefined') {
		window.NotesAddaApp = {};
	}

	const ReviewQueue = {
		init: function() {
			this.bindEvents();
			this.loadPendingCount();
		},

		bindEvents: function() {
			const self = this;
			const app = window.NotesAddaApp;

			// Status Tab Filter
			$('#na-view-review-queue .na-tab-btn').on('click', function(e) {
				e.preventDefault();
				const status = $(this).data('review-status');
				$('#na-view-review-queue .na-tab-btn').removeClass('active');
				$(this).addClass('active');
				app.reviewFilter = status;
				app.currentPage = 1;
				self.loadQueue();
			});

			// Verify Button Click
			$(document).on('click', '.na-btn-verify-note', function(e) {
				e.preventDefault();
				const noteId = $(this).data('id');
				const title = $(this).data('title');
				self.openDecisionModal(noteId, 'verify', title);
			});

			// Reject Button Click
			$(document).on('click', '.na-btn-reject-note', function(e) {
				e.preventDefault();
				const noteId = $(this).data('id');
				const title = $(this).data('title');
				self.openDecisionModal(noteId, 'reject', title);
			});

			// Submit Review Form
			$('#na-review-form').on('submit', function(e) {
				e.preventDefault();
				self.submitDecision();
			});
		},

		/**
		 * Fetch pending review count badge for sidebar.
		 */
		loadPendingCount: function() {
			if (!NotesAdda.can_review) return;
			$.get(NotesAdda.ajax_url, {
				action: 'notes_adda_get_pending_review_count'
			}, function(res) {
				if (res.success && typeof res.data.count !== 'undefined') {
					const count = parseInt(res.data.count, 10);
					const $badge = $('#na-pending-reviews-badge');
					if (count > 0) {
						$badge.text(count).show();
					} else {
						$badge.hide();
					}
				}
			});
		},

		/**
		 * Load queue items for the selected status filter.
		 */
		loadQueue: function() {
			const self = this;
			const app = window.NotesAddaApp;
			const $container = $('#na-review-results');
			const $loading = $('#na-review-loading');
			const $empty = $('#na-review-empty');
			const $pagination = $('#na-review-pagination');
			const $summary = $('#na-review-result-count');

			$loading.show();
			$empty.hide();
			$container.empty();
			$pagination.empty();

			const currentStatus = app.reviewFilter || 'pending';
			const params = {
				action: 'notes_adda_query_notes',
				_ajax_nonce: NotesAdda.nonce,
				page: app.currentPage,
				per_page: 10,
				is_review_queue: 1,
				review_status: currentStatus,
				status: currentStatus
			};

			$.get(NotesAdda.ajax_url, params, function(res) {
				$loading.hide();
				if (res.success && res.data) {
					const items = res.data.items || [];
					const total = parseInt(res.data.total, 10) || 0;
					const totalPages = parseInt(res.data.total_pages, 10) || 1;

					let statusLabel = 'pending';
					if (currentStatus === 'verified') statusLabel = 'verified';
					else if (currentStatus === 'rejected') statusLabel = 'rejected';
					else if (currentStatus === 'all') statusLabel = 'all';

					$summary.text(`Found ${total} submission${total === 1 ? '' : 's'} (${statusLabel})`);

					if (items.length === 0) {
						let emptyTitle = 'Queue is clear!';
						let emptyDesc = 'There are no unverified notes awaiting review right now.';
						if (currentStatus === 'rejected') {
							emptyTitle = 'No rejected notes';
							emptyDesc = 'There are no rejected notes in the review queue.';
						} else if (currentStatus === 'verified') {
							emptyTitle = 'No verified notes';
							emptyDesc = 'There are no verified notes matching this filter.';
						} else if (currentStatus === 'all') {
							emptyTitle = 'No notes found';
							emptyDesc = 'No submissions found in the review queue.';
						}
						$empty.find('.na-state-title').text(emptyTitle);
						$empty.find('.na-state-desc').text(emptyDesc);
						$empty.show();
					} else {
						self.renderQueue(items, $container);
						app.renderPagination(app.currentPage, totalPages, $pagination, function(newPage) {
							app.currentPage = newPage;
							self.loadQueue();
							$('html, body').animate({ scrollTop: $('#na-view-review-queue').offset().top - 80 }, 200);
						});
					}
				} else {
					$empty.show();
				}
			}).fail(function() {
				$loading.hide();
				$empty.show();
				app.showToast('Failed to load review queue.', 'error');
			});
		},

		/**
		 * Render queue items list.
		 */
		renderQueue: function(notes, $container) {
			const app = window.NotesAddaApp;
			$container.empty();

			notes.forEach(function(note) {
				const isPending = (note.review_status === 'pending');
				const isVerified = (note.review_status === 'verified');
				const isRejected = (note.review_status === 'rejected');
				const isSelfOwner = (parseInt(note.owner_id, 10) === parseInt(NotesAdda.user_id, 10));

				let badgeHtml = '';
				if (isVerified) {
					badgeHtml = '<span class="na-badge na-badge-verified">Verified</span>';
				} else if (isRejected) {
					badgeHtml = '<span class="na-badge na-badge-rejected">Rejected</span>';
				} else {
					badgeHtml = '<span class="na-badge na-badge-pending">Pending Review</span>';
				}

				const itemHtml = `
					<div class="na-review-card" data-id="${note.id}">
						<div class="na-review-card-info">
							<div class="na-review-card-top">
								<span class="na-badge na-badge-subject">${app.escapeHtml(note.subject || 'General')}</span>
								${badgeHtml}
								${note.chapter ? `<span class="na-badge na-badge-chapter">${app.escapeHtml(note.chapter)}</span>` : ''}
							</div>
							<h3 class="na-review-card-title">${app.escapeHtml(note.title)}</h3>
							<div class="na-review-card-meta">
								<span>Submitted by: <strong>${app.escapeHtml(note.uploader_name || 'Student')}</strong></span>
								<span>&bull;</span>
								<span>Date: ${app.formatDate(note.created_at)}</span>
								${note.reviewer_name ? `<span>&bull;</span><span>Reviewer: <strong>${app.escapeHtml(note.reviewer_name)}</strong></span>` : ''}
							</div>
							${note.review_note ? `
								<div class="na-card-rejection-note" style="margin-top:10px;">
									<strong>Note / Reason:</strong> ${app.escapeHtml(note.review_note)}
								</div>
							` : ''}
						</div>
						<div class="na-review-card-actions">
							${note.file_url ? `
								<a href="${app.escapeHtml(note.file_url)}" target="_blank" rel="noopener noreferrer" class="na-btn na-btn-secondary na-btn-sm">
									<span class="dashicons dashicons-pdf"></span> <span>View PDF</span>
								</a>
							` : ''}
							${isSelfOwner ? `
								<span class="na-form-hint" style="align-self:center;">(Your submission)</span>
							` : `
								<button type="button" class="na-btn na-btn-primary na-btn-sm na-btn-verify-note" data-id="${note.id}" data-title="${app.escapeHtml(note.title)}" ${isVerified ? 'title="Already verified"' : ''}>
									<span class="dashicons dashicons-yes-alt"></span> <span>${isVerified ? 'Verified' : 'Verify'}</span>
								</button>
								<button type="button" class="na-btn na-btn-danger na-btn-sm na-btn-reject-note" data-id="${note.id}" data-title="${app.escapeHtml(note.title)}" ${isRejected ? 'title="Already rejected"' : ''}>
									<span class="dashicons dashicons-dismiss"></span> <span>${isRejected ? 'Rejected' : 'Reject'}</span>
								</button>
							`}
						</div>
					</div>
				`;

				$container.append(itemHtml);
			});
		},

		/**
		 * Open the approval / rejection modal.
		 */
		openDecisionModal: function(noteId, actionType, noteTitle) {
			$('#na-review-note-id').val(noteId);
			$('#na-review-action-type').val(actionType);
			$('#na-review-target-title').text(noteTitle || 'Selected Note');
			$('#na-review-reason').val('');
			$('#na-review-modal-msg').text('').removeClass('error success');

			const $badge = $('#na-review-action-badge');
			const $btn = $('#na-submit-review-btn');
			const $btnText = $('#na-submit-review-text');

			if (actionType === 'verify') {
				$('#na-review-modal-title').text('Verify Note Submission');
				$badge.removeClass('na-badge-rejected').addClass('na-badge-verified').text('Approve as Verified');
				$btn.removeClass('na-btn-danger').addClass('na-btn-primary');
				$btnText.text('Confirm Verification');
			} else {
				$('#na-review-modal-title').text('Reject Note Submission');
				$badge.removeClass('na-badge-verified').addClass('na-badge-rejected').text('Mark Rejected');
				$btn.removeClass('na-btn-primary').addClass('na-btn-danger');
				$btnText.text('Confirm Rejection');
			}

			$('#na-review-modal').addClass('open');
		},

		/**
		 * Submit moderation decision.
		 */
		submitDecision: function() {
			const self = this;
			const app = window.NotesAddaApp;
			const noteId = $('#na-review-note-id').val();
			const actionType = $('#na-review-action-type').val();
			const reason = $('#na-review-reason').val().trim();
			const $msg = $('#na-review-modal-msg');
			const $btn = $('#na-submit-review-btn');

			if (!reason) {
				$msg.text('A review reason or explanation is strictly required.').addClass('error');
				return;
			}

			$btn.prop('disabled', true).find('.na-btn-spinner').show();
			$msg.text('').removeClass('error success');

			$.post(NotesAdda.ajax_url, {
				action: 'notes_adda_review_note',
				note_id: noteId,
				status: actionType === 'verify' ? 'verified' : 'rejected',
				review_note: reason,
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				$btn.prop('disabled', false).find('.na-btn-spinner').hide();
				if (res.success) {
					$('#na-review-modal').removeClass('open');
					app.showToast(`Note ${actionType === 'verify' ? 'verified' : 'rejected'} successfully.`, 'success');
					self.loadQueue();
					self.loadPendingCount();
				} else {
					$msg.text(res.data ? res.data.message : 'Error processing review.').addClass('error');
				}
			}).fail(function() {
				$btn.prop('disabled', false).find('.na-btn-spinner').hide();
				$msg.text('Network error while submitting decision.').addClass('error');
			});
		}
	};

	window.NotesAddaApp.ReviewQueue = ReviewQueue;
	if (window.NotesAddaApp.registerModule) {
		window.NotesAddaApp.registerModule('reviewQueue', ReviewQueue);
	}

})(jQuery);
