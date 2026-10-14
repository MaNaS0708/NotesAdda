/**
 * Notes Adda - Review Queue Module
 * Dedicated verification and report moderation workflow for subject experts and administrators.
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
			this.loadReportedCount();
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

			// Accept Report Button Click (rejects note & marks report resolved)
			$(document).on('click', '.na-btn-accept-report', function(e) {
				e.preventDefault();
				const reportId = $(this).data('id');
				const noteId = $(this).data('note-id');
				const title = $(this).data('title');
				const reporter = $(this).data('reporter');
				const reason = $(this).data('reason');
				self.openReportDecisionModal(reportId, noteId, 'accept-report', title, reporter, reason);
			});

			// Dismiss Report Button Click (keeps note verified & marks report dismissed)
			$(document).on('click', '.na-btn-dismiss-report', function(e) {
				e.preventDefault();
				const reportId = $(this).data('id');
				const noteId = $(this).data('note-id');
				const title = $(this).data('title');
				const reporter = $(this).data('reporter');
				const reason = $(this).data('reason');
				self.openReportDecisionModal(reportId, noteId, 'dismiss-report', title, reporter, reason);
			});

			// Submit Review / Moderation Form
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
				if (res.success && res.data) {
					const count = parseInt(res.data.count, 10) || 0;
					const $badge = $('#na-sidebar-review-badge');
					if (count > 0) {
						$badge.text(count).show();
					} else {
						$badge.hide();
					}
				}
			});
		},

		/**
		 * Fetch open reported count badge for tab and header.
		 */
		loadReportedCount: function() {
			if (!NotesAdda.can_review) return;
			$.get(NotesAdda.ajax_url, {
				action: 'notes_adda_get_reports_count'
			}, function(res) {
				if (res.success && res.data) {
					const count = parseInt(res.data.open_count, 10) || 0;
					const $badge = $('#na-review-reported-badge');
					if (count > 0) {
						$badge.text(count).show();
					} else {
						$badge.hide();
					}
				}
			});
		},

		/**
		 * Load Review Queue items based on selected tab filter.
		 */
		loadQueue: function() {
			const self = this;
			const app = window.NotesAddaApp;
			const $container = $('#na-review-results');
			const $loading = $('#na-review-loading');
			const $empty = $('#na-review-empty');
			const $summary = $('#na-review-result-count');
			const $pagination = $('#na-review-pagination');

			$container.empty();
			$loading.show();
			$empty.hide();
			$pagination.empty();

			const currentStatus = app.reviewFilter || 'pending';

			// If reported notes tab is active, query reports
			if (currentStatus === 'reported') {
				$.get(NotesAdda.ajax_url, {
					action: 'notes_adda_get_reports',
					status: 'all',
					page: app.currentPage,
					per_page: 10,
					_ajax_nonce: NotesAdda.nonce
				}, function(res) {
					$loading.hide();
					if (res.success && res.data) {
						const items = res.data.items || [];
						const total = parseInt(res.data.total, 10) || 0;
						const totalPages = parseInt(res.data.total_pages, 10) || 1;

						$summary.text(`Found ${total} reported note submission${total === 1 ? '' : 's'}`);

						if (items.length === 0) {
							$empty.find('.na-state-title').text('No reported notes');
							$empty.find('.na-state-desc').text('There are currently no community reports awaiting moderation.');
							$empty.show();
						} else {
							self.renderReportQueue(items, $container);
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
					app.showToast('Failed to load reported notes.', 'error');
				});
				return;
			}

			// Standard Note Review Queue
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
		 * Render standard review queue items list.
		 */
		renderQueue: function(notes, $container) {
			const app = window.NotesAddaApp;
			$container.empty();

			notes.forEach(function(note) {
				const isPending = (note.review_status === 'pending');
				const isVerified = (note.review_status === 'verified');
				const isRejected = (note.review_status === 'rejected');
				const isReported = (note.is_reported || parseInt(note.open_reports_count, 10) > 0);
				const isSelfOwner = (parseInt(note.owner_id, 10) === parseInt(NotesAdda.user_id, 10));

				let badgeHtml = '';
				if (isVerified) {
					badgeHtml = '<span class="na-badge na-badge-verified">Verified</span>';
				} else if (isRejected) {
					badgeHtml = '<span class="na-badge na-badge-rejected">Rejected</span>';
				} else {
					badgeHtml = '<span class="na-badge na-badge-pending">Pending Review</span>';
				}

				if (isReported) {
					badgeHtml += ' <span class="na-badge na-badge-reported"><span class="dashicons dashicons-flag"></span> Reported</span>';
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
		 * Render reported queue items list.
		 */
		renderReportQueue: function(reports, $container) {
			const app = window.NotesAddaApp;
			$container.empty();

			reports.forEach(function(report) {
				const isOpen = (report.status === 'open');
				const isResolved = (report.status === 'resolved');
				const isDismissed = (report.status === 'dismissed');

				let statusBadge = '';
				if (isOpen) {
					statusBadge = '<span class="na-badge na-badge-reported"><span class="dashicons dashicons-flag"></span> Open Report</span>';
				} else if (isResolved) {
					statusBadge = '<span class="na-badge na-badge-rejected"><span class="dashicons dashicons-yes-alt"></span> Report Accepted (Note Rejected)</span>';
				} else {
					statusBadge = '<span class="na-badge na-badge-verified"><span class="dashicons dashicons-dismiss"></span> Report Dismissed (Note Active)</span>';
				}

				const itemHtml = `
					<div class="na-review-card na-reported-card" data-report-id="${report.id}" data-note-id="${report.note_id}">
						<div class="na-review-card-info">
							<div class="na-review-card-top">
								<span class="na-badge na-badge-subject">${app.escapeHtml(report.note_subject || 'General')}</span>
								${statusBadge}
								${report.note_chapter ? `<span class="na-badge na-badge-chapter">${app.escapeHtml(report.note_chapter)}</span>` : ''}
							</div>
							<h3 class="na-review-card-title">${app.escapeHtml(report.note_title)}</h3>
							<div class="na-review-card-meta">
								<span>Author: <strong>${app.escapeHtml(report.note_owner_name || 'Author')}</strong></span>
								<span>&bull;</span>
								<span>Reported by: <strong>${app.escapeHtml(report.reporter_name || 'Student')}</strong></span>
								<span>&bull;</span>
								<span>Date: ${app.formatDate(report.created_at)}</span>
							</div>

							<!-- Report Reason Box -->
							<div class="na-card-report-box">
								<strong>Student's Report Reason:</strong>
								<span>${app.escapeHtml(report.reason)}</span>
							</div>

							${report.resolution_note ? `
								<div class="na-card-rejection-note" style="margin-top:10px;">
									<strong>Resolution (${app.escapeHtml(report.status)}):</strong> ${app.escapeHtml(report.resolution_note)}
									${report.resolver_name ? ` <em>&mdash; by ${app.escapeHtml(report.resolver_name)}</em>` : ''}
								</div>
							` : ''}
						</div>

						<div class="na-review-card-actions">
							${report.note_file_url ? `
								<a href="${app.escapeHtml(report.note_file_url)}" target="_blank" rel="noopener noreferrer" class="na-btn na-btn-secondary na-btn-sm">
									<span class="dashicons dashicons-pdf"></span> <span>View Note PDF</span>
								</a>
							` : ''}

							${isOpen ? `
								<button type="button" class="na-btn na-btn-danger na-btn-sm na-btn-accept-report" data-id="${report.id}" data-note-id="${report.note_id}" data-title="${app.escapeHtml(report.note_title)}" data-reporter="${app.escapeHtml(report.reporter_name)}" data-reason="${app.escapeHtml(report.reason)}">
									<span class="dashicons dashicons-yes-alt"></span> <span>Accept Report (Reject Note)</span>
								</button>
								<button type="button" class="na-btn na-btn-ghost na-btn-sm na-btn-dismiss-report" data-id="${report.id}" data-note-id="${report.note_id}" data-title="${app.escapeHtml(report.note_title)}" data-reporter="${app.escapeHtml(report.reporter_name)}" data-reason="${app.escapeHtml(report.reason)}">
									<span class="dashicons dashicons-dismiss"></span> <span>Dismiss Report (Keep Verified)</span>
								</button>
							` : `
								<span class="na-form-hint" style="align-self:center;">Moderation Complete</span>
							`}
						</div>
					</div>
				`;

				$container.append(itemHtml);
			});
		},

		/**
		 * Open the approval / rejection modal for a standard submission.
		 */
		openDecisionModal: function(noteId, actionType, noteTitle) {
			$('#na-review-note-id').val(noteId);
			$('#na-review-report-id').val('');
			$('#na-review-action-type').val(actionType);
			$('#na-review-target-title').text(noteTitle || 'Selected Note');
			$('#na-review-meta-reporter-row').hide();
			$('#na-review-meta-reason-row').hide();
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
		 * Open the report moderation modal (Accept Report vs Dismiss Report).
		 */
		openReportDecisionModal: function(reportId, noteId, actionType, noteTitle, reporterName, reason) {
			$('#na-review-note-id').val(noteId);
			$('#na-review-report-id').val(reportId);
			$('#na-review-action-type').val(actionType);
			$('#na-review-target-title').text(noteTitle || 'Reported Note');

			$('#na-review-target-reporter').text(reporterName || 'Student');
			$('#na-review-meta-reporter-row').show();

			$('#na-review-target-reported-reason').text(reason || '');
			$('#na-review-meta-reason-row').show();

			$('#na-review-reason').val('');
			$('#na-review-modal-msg').text('').removeClass('error success');

			const $badge = $('#na-review-action-badge');
			const $btn = $('#na-submit-review-btn');
			const $btnText = $('#na-submit-review-text');

			if (actionType === 'accept-report') {
				$('#na-review-modal-title').text('Review Report: Accept & Reject Note');
				$badge.removeClass('na-badge-verified').addClass('na-badge-rejected').text('Accept Report (Reject Note)');
				$btn.removeClass('na-btn-primary').addClass('na-btn-danger');
				$btnText.text('Confirm Accept & Reject Note');
				$('#na-review-reason').attr('placeholder', 'Explain why this note violates guidelines. This decision is sent to the reporter, note owner, and moderators...');
			} else {
				$('#na-review-modal-title').text('Review Report: Dismiss Report');
				$badge.removeClass('na-badge-rejected').addClass('na-badge-verified').text('Dismiss Report (Keep Note Active)');
				$btn.removeClass('na-btn-danger').addClass('na-btn-primary');
				$btnText.text('Confirm Dismiss Report');
				$('#na-review-reason').attr('placeholder', 'Explain why the report is dismissed. This decision is sent to the reporter, note owner, and moderators...');
			}

			$('#na-review-modal').addClass('open');
		},

		/**
		 * Submit moderation decision (handles both note review and report moderation).
		 */
		submitDecision: function() {
			const self = this;
			const app = window.NotesAddaApp;
			const noteId = $('#na-review-note-id').val();
			const reportId = $('#na-review-report-id').val();
			const actionType = $('#na-review-action-type').val();
			const reason = $('#na-review-reason').val().trim();
			const $msg = $('#na-review-modal-msg');
			const $btn = $('#na-submit-review-btn');

			if (!reason) {
				$msg.text('A moderation reason or explanation is strictly required.').addClass('error');
				return;
			}

			$btn.prop('disabled', true).find('.na-btn-spinner').show();
			$msg.text('').removeClass('error success');

			// If this is a report moderation decision:
			if (reportId && (actionType === 'accept-report' || actionType === 'dismiss-report')) {
				const decision = (actionType === 'accept-report') ? 'accept' : 'dismiss';
				$.post(NotesAdda.ajax_url, {
					action: 'notes_adda_resolve_report',
					report_id: reportId,
					decision: decision,
					resolution_note: reason,
					_ajax_nonce: NotesAdda.nonce
				}, function(res) {
					$btn.prop('disabled', false).find('.na-btn-spinner').hide();
					if (res.success) {
						$('#na-review-modal').removeClass('open');
						const msg = (decision === 'accept') ? 'Report accepted and note rejected successfully.' : 'Report dismissed. Note remains verified.';
						app.showToast(msg, 'success');
						self.loadQueue();
						self.loadPendingCount();
						self.loadReportedCount();
					} else {
						$msg.text(res.data ? res.data.message : 'Error processing report moderation.').addClass('error');
					}
				}).fail(function(xhr) {
					$btn.prop('disabled', false).find('.na-btn-spinner').hide();
					const err = (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message)
						? xhr.responseJSON.data.message
						: (xhr && xhr.responseJSON && xhr.responseJSON.message
							? xhr.responseJSON.message
							: 'Error processing moderation decision. Please check your session or permissions.');
					$msg.text(err).addClass('error');
				});
				return;
			}

			// Standard Note Review (verify/reject)
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
					self.loadReportedCount();
				} else {
					$msg.text(res.data ? res.data.message : 'Error processing review.').addClass('error');
				}
			}).fail(function(xhr) {
				$btn.prop('disabled', false).find('.na-btn-spinner').hide();
				const err = (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message)
					? xhr.responseJSON.data.message
					: (xhr && xhr.responseJSON && xhr.responseJSON.message
						? xhr.responseJSON.message
						: 'Error processing review decision. Please check your session or permissions.');
				$msg.text(err).addClass('error');
			});
		}
	};

	window.NotesAddaApp.ReviewQueue = ReviewQueue;
	if (window.NotesAddaApp.registerModule) {
		window.NotesAddaApp.registerModule('reviewQueue', ReviewQueue);
	}

})(jQuery);
