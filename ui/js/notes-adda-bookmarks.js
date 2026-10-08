/**
 * Notes Adda - Bookmarks View Module
 * Handles loading saved notes, bookmark toggling without full reload, and navigation to library.
 *
 * @package Notes_Adda
 */

(function($) {
	'use strict';

	if (typeof window.NotesAddaApp === 'undefined') {
		window.NotesAddaApp = {};
	}

	const Bookmarks = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			const self = this;
			const app = window.NotesAddaApp;

			// Quick Navigation to Library
			$(document).on('click', '#na-bookmarks-browse-btn, #na-empty-browse-btn', function(e) {
				e.preventDefault();
				app.switchView('library');
			});

			// Bookmark Toggle Click Handler
			$(document).on('click', '.na-btn-bookmark', function(e) {
				e.preventDefault();
				e.stopPropagation();
				const $btn = $(this);
				const noteId = $btn.data('id');
				self.toggleBookmark(noteId, $btn, false);
			});
		},

		/**
		 * Load user's saved notes.
		 */
		loadNotes: function() {
			const app = window.NotesAddaApp;
			const $container = $('#na-bookmarks-results');
			const $loading = $('#na-bookmarks-loading');
			const $empty = $('#na-bookmarks-empty');
			const $pagination = $('#na-bookmarks-pagination');
			const $summary = $('#na-bookmarks-result-count');

			$loading.show();
			$empty.hide();
			$container.empty();
			$pagination.empty();

			const params = {
				action: 'notes_adda_query_notes',
				_ajax_nonce: NotesAdda.nonce,
				page: app.currentPage,
				per_page: 9,
				bookmarked_only: 1,
				status: 'verified'
			};

			$.get(NotesAdda.ajax_url, params, function(res) {
				$loading.hide();
				if (res.success && res.data) {
					const items = res.data.items || [];
					const total = parseInt(res.data.total, 10) || 0;
					const totalPages = parseInt(res.data.total_pages, 10) || 1;

					if (total > 0) {
						$summary.text(`Your saved notes (${total} note${total === 1 ? '' : 's'})`);
					} else {
						$summary.text('Your saved notes');
					}

					if (items.length === 0) {
						$empty.show();
					} else {
						if (app.modules.library && typeof app.modules.library.renderNoteCards === 'function') {
							app.modules.library.renderNoteCards(items, $container, 'bookmarks');
						}
						app.renderPagination(app.currentPage, totalPages, $pagination, function(newPage) {
							app.currentPage = newPage;
							Bookmarks.loadNotes();
							$('html, body').animate({ scrollTop: $('#na-view-bookmarks').offset().top - 80 }, 200);
						});
					}
				} else {
					$empty.show();
				}
			}).fail(function() {
				$loading.hide();
				$empty.show();
				app.showToast('Failed to load bookmarks.', 'error');
			});
		},

		/**
		 * Toggle note bookmark status via AJAX.
		 */
		toggleBookmark: function(noteId, $btn, isModal) {
			const app = window.NotesAddaApp;
			if ($btn.prop('disabled')) return;
			$btn.prop('disabled', true);

			$.post(NotesAdda.ajax_url, {
				action: 'notes_adda_toggle_bookmark',
				note_id: noteId,
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				$btn.prop('disabled', false);
				if (res.success && res.data) {
					const isBookmarked = !!res.data.is_bookmarked;
					$btn.toggleClass('bookmarked', isBookmarked);

					// Sync SVG fill
					$btn.find('.na-svg-bookmark').attr('fill', isBookmarked ? 'currentColor' : 'none');
					$btn.attr('title', isBookmarked ? 'Remove saved note' : 'Save note');

					// Synchronize all instances across the DOM
					$(`.na-btn-bookmark[data-id="${noteId}"]`).each(function() {
						$(this).toggleClass('bookmarked', isBookmarked);
						$(this).find('.na-svg-bookmark').attr('fill', isBookmarked ? 'currentColor' : 'none');
						$(this).attr('title', isBookmarked ? 'Remove saved note' : 'Save note');
					});

					app.showToast(res.data.message || (isBookmarked ? 'Note saved to bookmarks' : 'Note removed from bookmarks'), 'success');

					// If in bookmarks view and removed, update list
					if (app.activeView === 'bookmarks' && !isBookmarked && !isModal) {
						$(`.na-card[data-id="${noteId}"]`).fadeOut(200, function() {
							$(this).remove();
							if ($('#na-bookmarks-results .na-card').length === 0) {
								Bookmarks.loadNotes();
							}
						});
					}
				} else {
					app.showToast(res.data ? res.data.message : 'Error updating bookmark', 'error');
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				app.showToast('Network error updating bookmark', 'error');
			});
		}
	};

	window.NotesAddaApp.Bookmarks = Bookmarks;
	if (window.NotesAddaApp.registerModule) {
		window.NotesAddaApp.registerModule('bookmarks', Bookmarks);
	}

})(jQuery);
