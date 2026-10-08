/**
 * Notes Adda - Note Details Module
 * Handles dedicated note full-page view, quick preview modal dialog, and content reporting.
 *
 * @package Notes_Adda
 */

(function($) {
	'use strict';

	if (typeof window.NotesAddaApp === 'undefined') {
		window.NotesAddaApp = {};
	}

	const NoteDetail = {
		previousView: 'library',

		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			const self = this;
			const app = window.NotesAddaApp;

			// Back Button Navigation
			$('#na-note-back-btn, #na-notfound-browse-btn').on('click', function(e) {
				e.preventDefault();
				app.switchView(self.previousView || 'library');
			});

			// Report Note Button
			$(document).on('click', '.na-btn-report', function(e) {
				e.preventDefault();
				e.stopPropagation();
				const noteId = $(this).data('id');
				const reason = prompt('Please describe why you are reporting this note (copyright, spam, incorrect info):');
				if (reason && reason.trim()) {
					self.reportNote(noteId, reason.trim());
				}
			});
		},

		/**
		 * Open dedicated note full-page view.
		 */
		openNoteDetailsPage: function(noteId, pushState) {
			const self = this;
			const app = window.NotesAddaApp;

			if (app.activeView !== 'note-details') {
				self.previousView = app.activeView;
			}
			app.activeView = 'note-details';

			if (pushState !== false) {
				if (history.pushState) {
					history.pushState(null, null, `#note-${noteId}`);
				} else {
					window.location.hash = `#note-${noteId}`;
				}
			}

			$('.na-view').removeClass('active').hide();
			$('#na-view-note-details').addClass('active').show();

			const $loading = $('#na-note-details-loading');
			const $error = $('#na-note-details-error');
			const $container = $('#na-note-details-container');

			$loading.show();
			$error.hide();
			$container.empty();

			$.get(NotesAdda.ajax_url, {
				action: 'notes_adda_get_note_details',
				note_id: noteId
			}, function(res) {
				$loading.hide();
				if (res.success && res.data) {
					self.renderNoteDetailsPage(res.data);
				} else {
					$error.show();
				}
			}).fail(function() {
				$loading.hide();
				$error.show();
			});
		},

		/**
		 * Render the dedicated note details page DOM.
		 */
		renderNoteDetailsPage: function(note) {
			const app = window.NotesAddaApp;
			const $container = $('#na-note-details-container');

			const isOwner = (parseInt(note.owner_id, 10) === parseInt(NotesAdda.user_id, 10));
			const isVerified = (note.review_status === 'verified');
			const isRejected = (note.review_status === 'rejected');
			const isPending = (note.review_status === 'pending');
			const isLiked = !!note.is_liked;
			const isBookmarked = !!note.is_bookmarked;

			let statusBadgeHtml = '';
			if (isVerified) {
				statusBadgeHtml = '<span class="na-badge na-badge-verified"><span class="dashicons dashicons-yes-alt"></span> Expert Verified</span>';
			} else if (isRejected) {
				statusBadgeHtml = '<span class="na-badge na-badge-rejected"><span class="dashicons dashicons-dismiss"></span> Rejected</span>';
			} else {
				statusBadgeHtml = '<span class="na-badge na-badge-pending"><span class="dashicons dashicons-clock"></span> Pending Review</span>';
			}

			// Expert rating section
			let expertRatingHtml = '';
			if (isVerified && note.reviewed_by && note.reviewed_by !== parseInt(NotesAdda.user_id, 10)) {
				const currentRating = (note.reviewer_rating && note.reviewer_rating.user_rating) ? note.reviewer_rating.user_rating : 0;
				if (app.modules.expertRatings && typeof app.modules.expertRatings.renderRatingControlHtml === 'function') {
					expertRatingHtml = app.modules.expertRatings.renderRatingControlHtml(
						note.reviewed_by,
						note.reviewer_name || 'Expert Reviewer',
						note.subject,
						currentRating
					);
				}
			}

			const html = `
				<div class="na-document-card">
					<div class="na-preview-header">
						<div class="na-badge-group">
							<span class="na-badge na-badge-subject">${app.escapeHtml(note.subject || 'General')}</span>
							${note.chapter ? `<span class="na-badge na-badge-chapter">${app.escapeHtml(note.chapter)}</span>` : ''}
							${parseInt(note.is_whole_notes, 10) === 1 ? `<span class="na-badge na-badge-whole">Full Course</span>` : ''}
							${statusBadgeHtml}
						</div>
						<h2 class="na-preview-title">${app.escapeHtml(note.title)}</h2>
						<div class="na-card-meta" style="margin-top:10px; border:none; padding-top:0;">
							<span class="na-card-meta-item na-card-date">
								<span class="dashicons dashicons-calendar-alt"></span>
								<span class="na-meta-val">Published ${app.formatDate(note.created_at)}</span>
							</span>
							${note.uploader_name ? `
								<span class="na-card-meta-item na-card-uploader">
									<span class="dashicons dashicons-admin-users"></span>
									<span class="na-meta-val">by <strong>${app.escapeHtml(note.uploader_name)}</strong></span>
								</span>
							` : ''}
						</div>
					</div>

					${isPending ? `
						<div class="na-state-box" style="min-height:auto; padding:16px; margin-bottom:18px; border-color:var(--na-warning); text-align:left; place-content:start;">
							<p style="margin:0; color:var(--na-warning); font-size:13px;">
								<span class="dashicons dashicons-clock"></span> This note is awaiting expert verification and is not yet visible to other students in the library.
							</p>
						</div>
					` : (isRejected ? `
						<div class="na-state-box" style="min-height:auto; padding:16px; margin-bottom:18px; border-color:var(--na-danger); text-align:left; place-content:start;">
							<p style="margin:0; color:var(--na-danger); font-size:13px;">
								<strong>Submission Rejected:</strong> "${app.escapeHtml(note.review_note || 'Please review guidelines and resubmit.')}"
							</p>
						</div>
					` : (note.reviewer_name ? `
						<div style="margin-bottom:18px; padding:12px 16px; border-radius:var(--na-radius-sm); background:var(--na-surface-card); border:1px solid var(--na-line); font-size:13px;">
							<span class="dashicons dashicons-yes-alt" style="color:var(--na-brand);"></span>
							<span>Verified for <strong>${app.escapeHtml(note.subject || 'General')}</strong> by <strong>${app.escapeHtml(note.reviewer_name)}</strong> ${note.reviewed_at ? 'on ' + app.formatDate(note.reviewed_at) : ''}</span>
						</div>
					` : ''))}

					<div class="na-preview-desc">
						<h4 style="font-size:14px; color:var(--na-ink); margin-bottom:6px;">Description / Course Overview</h4>
						<p>${app.escapeHtml(note.description || 'No detailed overview provided.')}</p>
					</div>

					<div class="na-preview-actions">
						${note.file_url ? `
							<a href="${app.escapeHtml(note.file_url)}" target="_blank" rel="noopener noreferrer" class="na-btn na-btn-primary">
								<span class="dashicons dashicons-pdf"></span> <span>Download PDF</span>
							</a>
						` : ''}
						${isVerified ? `
							<button type="button" class="na-btn na-btn-secondary na-btn-bookmark ${isBookmarked ? 'bookmarked' : ''}" data-id="${note.id}">
								<svg viewBox="0 0 24 24" class="na-svg-bookmark" width="16" height="16" fill="${isBookmarked ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
								<span>${isBookmarked ? 'Saved to Bookmarks' : 'Save Bookmark'}</span>
							</button>
							<button type="button" class="na-btn na-btn-secondary na-btn-like ${isLiked ? 'liked' : ''}" data-id="${note.id}">
								<span class="dashicons dashicons-heart"></span>
								<span>Like</span> (<span class="like-count">${note.like_count || 0}</span>)
							</button>
						` : ''}
						${!isOwner && isVerified ? `
							<button type="button" class="na-btn na-btn-ghost na-btn-report" data-id="${note.id}">
								<span class="dashicons dashicons-flag"></span> <span>Report</span>
							</button>
						` : ''}
					</div>

					${expertRatingHtml}
				</div>
			`;

			$container.html(html);
		},

		/**
		 * Open note quick preview modal.
		 */
		openDetailsModal: function(note) {
			const app = window.NotesAddaApp;
			const $modal = $('#na-note-details-modal');
			const $body = $('#na-note-details-body');

			const isVerified = (note.review_status === 'verified');
			const isLiked = !!note.is_liked;
			const isBookmarked = !!note.is_bookmarked;

			let statusBadgeHtml = '';
			if (isVerified) {
				statusBadgeHtml = '<span class="na-badge na-badge-verified"><span class="dashicons dashicons-yes-alt"></span> Verified</span>';
			} else if (note.review_status === 'rejected') {
				statusBadgeHtml = '<span class="na-badge na-badge-rejected"><span class="dashicons dashicons-dismiss"></span> Rejected</span>';
			} else {
				statusBadgeHtml = '<span class="na-badge na-badge-pending"><span class="dashicons dashicons-clock"></span> Pending</span>';
			}

			const modalHtml = `
				<div class="na-preview-header">
					<div class="na-badge-group">
						<span class="na-badge na-badge-subject">${app.escapeHtml(note.subject || 'General')}</span>
						${note.chapter ? `<span class="na-badge na-badge-chapter">${app.escapeHtml(note.chapter)}</span>` : ''}
						${parseInt(note.is_whole_notes, 10) === 1 ? `<span class="na-badge na-badge-whole">Full Course</span>` : ''}
						${statusBadgeHtml}
					</div>
					<h3 class="na-preview-title">${app.escapeHtml(note.title)}</h3>
					<div class="na-card-meta" style="margin-top:8px; border:none; padding-top:0;">
						<span class="na-card-meta-item na-card-date">
							<span class="dashicons dashicons-calendar-alt"></span>
							<span class="na-meta-val">${app.formatDate(note.created_at, true)}</span>
						</span>
						${note.uploader_name ? `
							<span class="na-card-meta-item na-card-uploader">
								<span class="dashicons dashicons-admin-users"></span>
								<span class="na-meta-val">by ${app.escapeHtml(note.uploader_name)}</span>
							</span>
						` : ''}
					</div>
				</div>

				<div class="na-preview-desc">
					<p>${app.escapeHtml(note.description || 'No description provided.')}</p>
				</div>

				<div class="na-preview-actions">
					${note.file_url ? `
						<a href="${app.escapeHtml(note.file_url)}" target="_blank" rel="noopener noreferrer" class="na-btn na-btn-primary">
							<span class="dashicons dashicons-pdf"></span> <span>Open PDF</span>
						</a>
					` : ''}
					${isVerified ? `
						<button type="button" class="na-btn na-btn-secondary na-btn-bookmark ${isBookmarked ? 'bookmarked' : ''}" data-id="${note.id}">
							<svg viewBox="0 0 24 24" class="na-svg-bookmark" width="16" height="16" fill="${isBookmarked ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
							<span>${isBookmarked ? 'Saved' : 'Save'}</span>
						</button>
						<button type="button" class="na-btn na-btn-secondary na-btn-like ${isLiked ? 'liked' : ''}" data-id="${note.id}">
							<span class="dashicons dashicons-heart"></span>
							<span>Like</span> (<span class="like-count">${note.like_count || 0}</span>)
						</button>
					` : ''}
				</div>
			`;

			$body.html(modalHtml);
			$modal.addClass('open');
		},

		/**
		 * Report inappropriate or copyrighted note.
		 */
		reportNote: function(id, reason) {
			const app = window.NotesAddaApp;
			$.post(NotesAdda.ajax_url, {
				action: 'notes_adda_create_report',
				note_id: id,
				reason: reason,
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				if (res.success) {
					app.showToast('Report submitted for moderation. Thank you.', 'success');
				} else {
					app.showToast(res.data ? res.data.message : 'Error submitting report', 'error');
				}
			}).fail(function() {
				app.showToast('Network error submitting report', 'error');
			});
		}
	};

	window.NotesAddaApp.NoteDetail = NoteDetail;
	if (window.NotesAddaApp.registerModule) {
		window.NotesAddaApp.registerModule('noteDetail', NoteDetail);
	}

})(jQuery);
