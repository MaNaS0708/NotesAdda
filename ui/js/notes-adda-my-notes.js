/**
 * Notes Adda - My Notes Module
 * Displays student author contributions, review states, edit/delete actions, and rejected note resubmission.
 *
 * @package Notes_Adda
 */

(function($) {
	'use strict';

	if (typeof window.NotesAddaApp === 'undefined') {
		window.NotesAddaApp = {};
	}

	const MyNotes = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			const self = this;
			const app = window.NotesAddaApp;

			// Delete Note Button
			$(document).on('click', '.na-btn-delete', function(e) {
				e.preventDefault();
				e.stopPropagation();
				const noteId = $(this).data('id');
				if (confirm('Are you sure you want to delete this note? This action cannot be undone.')) {
					self.deleteNote(noteId);
				}
			});

			// Resubmit / Edit Note Button
			$(document).on('click', '.na-btn-resubmit-note', function(e) {
				e.preventDefault();
				e.stopPropagation();
				const noteId = $(this).data('id');
				const $card = $(this).closest('.na-card');
				const noteData = $card.data('note');
				if (noteData && app.modules.uploadModal && typeof app.modules.uploadModal.openFormModal === 'function') {
					app.modules.uploadModal.openFormModal(noteData);
				} else if (noteId) {
					$.get(NotesAdda.ajax_url, {
						action: 'notes_adda_get_note_details',
						note_id: noteId
					}, function(res) {
						if (res.success && res.data && app.modules.uploadModal) {
							app.modules.uploadModal.openFormModal(res.data);
						}
					});
				}
			});
		},

		/**
		 * Load student contributions from the backend.
		 */
		loadNotes: function() {
			const app = window.NotesAddaApp;
			const $container = $('#na-my-notes-results');
			const $loading = $('#na-my-notes-loading');
			const $empty = $('#na-my-notes-empty');
			const $pagination = $('#na-my-notes-pagination');
			const $summary = $('#na-my-notes-result-count');

			$loading.show();
			$empty.hide();
			$container.empty();
			$pagination.empty();

			const params = {
				action: 'notes_adda_query_notes',
				_ajax_nonce: NotesAdda.nonce,
				page: app.currentPage,
				per_page: 9,
				owner_id: NotesAdda.user_id,
				status: 'all'
			};

			$.get(NotesAdda.ajax_url, params, function(res) {
				$loading.hide();
				if (res.success && res.data) {
					const items = res.data.items || [];
					const total = parseInt(res.data.total, 10) || 0;
					const totalPages = parseInt(res.data.total_pages, 10) || 1;

					if (total > 0) {
						$summary.text(`Your published material (${total} note${total === 1 ? '' : 's'})`);
					} else {
						$summary.text('Your published material');
					}

					if (items.length === 0) {
						$empty.show();
					} else {
						if (app.modules.library && typeof app.modules.library.renderNoteCards === 'function') {
							app.modules.library.renderNoteCards(items, $container, 'my-notes');
						}
						app.renderPagination(app.currentPage, totalPages, $pagination, function(newPage) {
							app.currentPage = newPage;
							MyNotes.loadNotes();
							$('html, body').animate({ scrollTop: $('#na-view-my-notes').offset().top - 80 }, 200);
						});
					}
				} else {
					$empty.show();
				}
			}).fail(function() {
				$loading.hide();
				$empty.show();
				app.showToast('Failed to load your notes.', 'error');
			});
		},

		/**
		 * Delete note handler.
		 */
		deleteNote: function(id) {
			const app = window.NotesAddaApp;
			$.post(NotesAdda.ajax_url, {
				action: 'notes_adda_delete_note',
				note_id: id,
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				if (res.success) {
					app.showToast('Note deleted successfully', 'success');
					$(`.na-card[data-id="${id}"]`).fadeOut(200, function() {
						$(this).remove();
					});
					if (app.activeView === 'my-notes') {
						MyNotes.loadNotes();
					} else if (app.modules.library) {
						app.modules.library.loadNotes();
					}
				} else {
					app.showToast(res.data ? res.data.message : 'Error deleting note', 'error');
				}
			}).fail(function() {
				app.showToast('Network error while deleting note', 'error');
			});
		}
	};

	window.NotesAddaApp.MyNotes = MyNotes;
	if (window.NotesAddaApp.registerModule) {
		window.NotesAddaApp.registerModule('myNotes', MyNotes);
	}

})(jQuery);
