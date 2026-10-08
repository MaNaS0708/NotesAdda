/**
 * Notes Adda - Library View Module
 * Handles community note browsing, filters, search toolbar, responsive card rendering with clean dates,
 * likes, bookmarking, and card actions.
 *
 * @package Notes_Adda
 */

(function($) {
	'use strict';

	if (typeof window.NotesAddaApp === 'undefined') {
		window.NotesAddaApp = {};
	}

	const Library = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			const self = this;
			const app = window.NotesAddaApp;

			// Search on Enter Key
			$('#na-search-input').on('keypress', function(e) {
				if (e.which === 13) {
					e.preventDefault();
					app.currentPage = 1;
					self.loadNotes();
				}
			});

			// Filter Change Events
			$('#na-subject-filter, #na-sort-filter').on('change', function() {
				app.currentPage = 1;
				self.loadNotes();
			});

			// Apply Search Filters Button
			$('#na-apply-filters').on('click', function(e) {
				e.preventDefault();
				app.currentPage = 1;
				self.loadNotes();
			});

			// Reset Search Filters Button
			$('#na-reset-filters').on('click', function(e) {
				e.preventDefault();
				$('#na-search-input').val('');
				$('#na-subject-filter').val('');
				$('#na-sort-filter').val('recent');
				$(this).hide();
				app.currentPage = 1;
				self.loadNotes();
			});

			// Note Card Click Delegation (except buttons and links)
			$(document).on('click', '.na-card', function(e) {
				if ($(e.target).closest('button, a, input, select').length > 0) {
					return;
				}
				const noteId = $(this).data('id');
				if (noteId && app.modules.noteDetail && typeof app.modules.noteDetail.openNoteDetailsPage === 'function') {
					app.modules.noteDetail.openNoteDetailsPage(noteId, true);
				}
			});

			// Preview Note Modal Click
			$(document).on('click', '.na-btn-view', function(e) {
				e.preventDefault();
				e.stopPropagation();
				const noteId = $(this).data('id');
				const $card = $(this).closest('.na-card');
				const noteData = $card.data('note');
				if (noteData && app.modules.noteDetail && typeof app.modules.noteDetail.openDetailsModal === 'function') {
					app.modules.noteDetail.openDetailsModal(noteData);
				} else if (noteId && app.modules.noteDetail && typeof app.modules.noteDetail.openNoteDetailsPage === 'function') {
					app.modules.noteDetail.openNoteDetailsPage(noteId, true);
				}
			});

			// Like Note Click
			$(document).on('click', '.na-btn-like', function(e) {
				e.preventDefault();
				e.stopPropagation();
				const $btn = $(this);
				const noteId = $btn.data('id');
				const isLiked = $btn.hasClass('liked');
				self.toggleLike(noteId, isLiked, $btn);
			});
		},

		/**
		 * Fetch and display library notes via WordPress AJAX.
		 */
		loadNotes: function() {
			const self = this;
			const app = window.NotesAddaApp;
			const $container = $('#na-library-results');
			const $loading = $('#na-library-loading');
			const $empty = $('#na-library-empty');
			const $pagination = $('#na-library-pagination');
			const $summary = $('#na-library-result-count');

			const search = $('#na-search-input').val().trim();
			const subject = $('#na-subject-filter').val();
			const sort = $('#na-sort-filter').val();

			if (search || subject) {
				$('#na-reset-filters').show();
			} else {
				$('#na-reset-filters').hide();
			}

			$loading.show();
			$empty.hide();
			$container.empty();
			$pagination.empty();

			const params = {
				action: 'notes_adda_query_notes',
				_ajax_nonce: NotesAdda.nonce,
				page: app.currentPage,
				per_page: 9,
				search: search,
				subject: subject,
				sort: sort,
				status: 'verified'
			};

			$.get(NotesAdda.ajax_url, params, function(res) {
				$loading.hide();
				if (res.success && res.data) {
					const items = res.data.items || [];
					const total = parseInt(res.data.total, 10) || 0;
					const totalPages = parseInt(res.data.total_pages, 10) || 1;

					if (total > 0) {
						$summary.text(`Showing ${items.length} of ${total} notes`);
					} else {
						$summary.text('Explore recently shared notes');
					}

					if (items.length === 0) {
						$empty.show();
					} else {
						self.renderNoteCards(items, $container, 'library');
						app.renderPagination(app.currentPage, totalPages, $pagination, function(newPage) {
							app.currentPage = newPage;
							self.loadNotes();
							$('html, body').animate({ scrollTop: $('#na-view-library').offset().top - 80 }, 200);
						});
					}
				} else {
					$empty.show();
					$summary.text('0 notes found');
				}
			}).fail(function() {
				$loading.hide();
				$empty.show();
				app.showToast('Failed to load notes. Please check connection.', 'error');
			});
		},

		/**
		 * Render cards with enhanced responsive metadata (uploader & readable date).
		 */
		renderNoteCards: function(notes, $container, view) {
			const self = this;
			const app = window.NotesAddaApp;
			$container.empty();

			notes.forEach(function(note) {
				const isOwner = (parseInt(note.owner_id, 10) === parseInt(NotesAdda.user_id, 10));
				const canEdit = isOwner || NotesAdda.can_manage_all_notes;
				const canDelete = isOwner || NotesAdda.can_manage_all_notes;
				const isVerified = (note.review_status === 'verified');
				const isRejected = (note.review_status === 'rejected');
				const isLiked = !!note.is_liked;
				const isBookmarked = !!note.is_bookmarked;

				let statusBadgeHtml = '';
				if (isVerified) {
					statusBadgeHtml = `
						<span class="na-badge na-badge-verified" title="Verified by subject expert">
							<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> Expert Verified
						</span>
					`;
				} else if (isRejected) {
					statusBadgeHtml = `
						<span class="na-badge na-badge-rejected" title="Submission rejected by reviewer">
							<span class="dashicons dashicons-dismiss" aria-hidden="true"></span> Rejected
						</span>
					`;
				} else {
					statusBadgeHtml = `
						<span class="na-badge na-badge-pending" title="Awaiting expert verification">
							<span class="dashicons dashicons-clock" aria-hidden="true"></span> Pending Review
						</span>
					`;
				}

				// Reviewer Card Info
				let reviewerCardHtml = '';
				if (isVerified && note.reviewed_by) {
					const rr = note.reviewer_rating;
					const avgScore = (rr && rr.has_ratings) ? parseFloat(rr.average) : 0;
					const formattedScore = (rr && rr.has_ratings) ? rr.formatted_average : '';
					const ratingCount = (rr && rr.has_ratings) ? parseInt(rr.count, 10) : 0;

					reviewerCardHtml = `
						<div class="na-card-reviewer-box" data-reviewer-id="${note.reviewed_by}">
							<div class="na-card-reviewer-line">
								<span class="dashicons dashicons-awards na-card-reviewer-icon" aria-hidden="true"></span>
								<span class="na-card-reviewer-text">Reviewed by <strong>${app.escapeHtml(note.reviewer_name || 'Subject Expert')}</strong></span>
							</div>
							<div class="na-card-rating-line">
								${app.renderQuarterStarsHtml(avgScore, 13)}
								<span class="na-card-rating-score">${formattedScore ? `${formattedScore} / 5` : 'Verified'}</span>
								${ratingCount > 0 ? `<span class="na-card-rating-count">(${ratingCount})</span>` : ''}
							</div>
						</div>
					`;
				}

				const cardHtml = `
					<article class="na-card ${isVerified ? 'na-card-verified' : (isRejected ? 'na-card-rejected' : 'na-card-pending')}" data-id="${note.id}" data-subject="${app.escapeHtml(note.subject || '')}">
						<div class="na-card-top">
							<div class="na-badge-group">
								<span class="na-badge na-badge-subject">${app.escapeHtml(note.subject || 'General')}</span>
								${note.chapter ? `<span class="na-badge na-badge-chapter">${app.escapeHtml(note.chapter)}</span>` : ''}
								${parseInt(note.is_whole_notes, 10) === 1 ? `<span class="na-badge na-badge-whole">Full Course</span>` : ''}
								${statusBadgeHtml}
							</div>
						</div>

						<h3 class="na-card-title">${app.escapeHtml(note.title)}</h3>
						<p class="na-card-desc">${app.escapeHtml(note.description || 'No description provided.')}</p>

						${isRejected && isOwner && note.review_note ? `
							<div class="na-card-rejection-note">
								<strong>Reviewer Feedback:</strong> ${app.escapeHtml(note.review_note)}
							</div>
						` : ''}

						${reviewerCardHtml}

						<div class="na-card-tags" id="na-tags-${note.id}"></div>

						<!-- Clean, Responsive Note Metadata (Uploader & Formatted Date) -->
						<div class="na-card-meta">
							${note.uploader_name ? `
								<span class="na-card-meta-item na-card-uploader" title="Uploaded by ${app.escapeHtml(note.uploader_name)}">
									<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
									<span class="na-meta-val">${app.escapeHtml(note.uploader_name)}</span>
								</span>
							` : ''}
							<span class="na-card-meta-item na-card-date" title="${app.formatDate(note.created_at, true)}">
								<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
								<span class="na-meta-val">${app.formatDate(note.created_at, true)}</span>
							</span>
						</div>

						<!-- Card Action Bar -->
						<div class="na-card-bottom">
							<div class="na-card-actions">
								<button type="button" class="na-icon-btn na-btn-view" data-id="${note.id}" title="Preview note" aria-label="Preview note">
									<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
								</button>
								${note.file_url ? `
									<a href="${app.escapeHtml(note.file_url)}" target="_blank" rel="noopener noreferrer" class="na-icon-btn" title="Open PDF" aria-label="Open PDF">
										<span class="dashicons dashicons-pdf" aria-hidden="true"></span>
									</a>
								` : ''}
								${isVerified ? `
									<button type="button" class="na-icon-btn na-btn-bookmark ${isBookmarked ? 'bookmarked' : ''}" data-id="${note.id}" title="${isBookmarked ? 'Remove saved note' : 'Save note'}" aria-label="Save note">
										<svg viewBox="0 0 24 24" class="na-svg-bookmark" width="16" height="16" fill="${isBookmarked ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
									</button>
									<button type="button" class="na-icon-btn na-btn-like ${isLiked ? 'liked' : ''}" data-id="${note.id}" title="Like note" aria-label="Like note">
										<span class="dashicons dashicons-heart" aria-hidden="true"></span>
										<span class="like-count">${note.like_count || 0}</span>
									</button>
								` : ''}
								${isRejected && isOwner ? `
									<button type="button" class="na-btn na-btn-secondary na-btn-sm na-btn-resubmit-note" data-id="${note.id}" title="Edit and resubmit note">
										<span class="dashicons dashicons-update" aria-hidden="true"></span> <span>Edit & Resubmit</span>
									</button>
								` : ''}
								${canEdit && !isRejected ? `
									<button type="button" class="na-icon-btn na-btn-edit" data-id="${note.id}" title="Edit Note" aria-label="Edit Note">
										<span class="dashicons dashicons-edit" aria-hidden="true"></span>
									</button>
								` : ''}
								${canDelete ? `
									<button type="button" class="na-icon-btn na-danger-hover na-btn-delete" data-id="${note.id}" title="Delete Note" aria-label="Delete Note">
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
									</button>
								` : ''}
								${!isOwner && isVerified ? `
									<button type="button" class="na-icon-btn na-btn-report" data-id="${note.id}" title="Report Note" aria-label="Report Note">
										<span class="dashicons dashicons-flag" aria-hidden="true"></span>
									</button>
								` : ''}
							</div>
						</div>
					</article>
				`;

				const $card = $(cardHtml);
				$card.data('note', note);
				$container.append($card);

				// Load tags asynchronously
				self.loadCardTags(note.id);

				// Check like status if verified
				if (isVerified) {
					self.checkLikeStatus(note.id, $card.find('.na-btn-like'));
				}
			});
		},

		loadCardTags: function(note_id) {
			const app = window.NotesAddaApp;
			$.get(NotesAdda.ajax_url, {
				action: 'notes_adda_get_note_tags',
				note_id: note_id
			}, function(res) {
				if (res.success && res.data && res.data.length > 0) {
					const tagsHtml = res.data.map(function(t) {
						return `<span class="na-tag">#${app.escapeHtml(t.name)}</span>`;
					}).join(' ');
					$(`#na-tags-${note_id}`).html(tagsHtml);
				}
			});
		},

		checkLikeStatus: function(note_id, $btn) {
			$.get(NotesAdda.ajax_url, {
				action: 'notes_adda_get_like_status',
				note_id: note_id
			}, function(res) {
				if (res.success && res.data) {
					if (res.data.is_liked) {
						$btn.addClass('liked');
					} else {
						$btn.removeClass('liked');
					}
					$btn.find('.like-count').text(res.data.like_count || 0);
				}
			});
		},

		toggleLike: function(id, isLiked, $btn) {
			const app = window.NotesAddaApp;
			if ($btn.prop('disabled')) return;
			$btn.prop('disabled', true);

			const action = isLiked ? 'notes_adda_remove_like' : 'notes_adda_add_like';
			$.post(NotesAdda.ajax_url, {
				action: action,
				note_id: id,
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				$btn.prop('disabled', false);
				if (res.success && res.data) {
					const newLiked = !!res.data.has_liked;
					const newCount = parseInt(res.data.like_count, 10) || 0;
					$btn.toggleClass('liked', newLiked);
					$btn.find('.like-count').text(newCount);
					// Synchronize other buttons matching this note ID
					$(`.na-btn-like[data-id="${id}"]`).toggleClass('liked', newLiked);
					$(`.na-btn-like[data-id="${id}"] .like-count`).text(newCount);
				} else {
					app.showToast(res.data ? res.data.message : 'Unable to update like', 'error');
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				app.showToast('Network error updating like', 'error');
			});
		}
	};

	window.NotesAddaApp.Library = Library;
	if (window.NotesAddaApp.registerModule) {
		window.NotesAddaApp.registerModule('library', Library);
	}

})(jQuery);
