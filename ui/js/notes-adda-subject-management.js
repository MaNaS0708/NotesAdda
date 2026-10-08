/**
 * Notes Adda - Subject Management Module
 * Administrator tools for creating and organizing standard course catalog subjects.
 *
 * @package Notes_Adda
 */

(function($) {
	'use strict';

	if (typeof window.NotesAddaApp === 'undefined') {
		window.NotesAddaApp = {};
	}

	const SubjectManagement = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			const self = this;

			// Add Subject Form
			$('#na-add-subject-form').on('submit', function(e) {
				e.preventDefault();
				self.addSubject();
			});

			// Delete Subject Action
			$(document).on('click', '.na-btn-delete-subject', function(e) {
				e.preventDefault();
				const id = $(this).data('id');
				const name = $(this).data('name');
				if (confirm(`Are you sure you want to delete subject "${name}"? This is only allowed if no notes use it.`)) {
					self.deleteSubject(id);
				}
			});
		},

		render: function() {
			const app = window.NotesAddaApp;
			const $container = $('#na-subjects-results');
			const $loading = $('#na-subjects-loading');
			const $empty = $('#na-subjects-empty');
			const $summary = $('#na-subjects-count');

			$loading.hide();
			$container.empty();

			const subjects = app.subjects || [];
			$summary.text(`Current Subjects (${subjects.length} total)`);

			if (subjects.length === 0) {
				$empty.show();
			} else {
				$empty.hide();
				subjects.forEach(function(s) {
					const cardHtml = `
						<div class="na-subject-card" data-id="${s.id}">
							<div class="na-subject-card-info">
								<span class="na-subject-card-title">${app.escapeHtml(s.name)}</span>
								<span class="na-subject-card-count">Added ${app.formatDate(s.created_at)}</span>
							</div>
							<button type="button" class="na-icon-btn na-danger-hover na-btn-delete-subject" data-id="${s.id}" data-name="${app.escapeHtml(s.name)}" title="Delete Subject">
								<span class="dashicons dashicons-trash"></span>
							</button>
						</div>
					`;
					$container.append(cardHtml);
				});
			}
		},

		addSubject: function() {
			const self = this;
			const app = window.NotesAddaApp;
			const $nameInput = $('#na-new-subject-name');
			const $btn = $('#na-add-subject-btn');
			const $msg = $('#na-subject-form-msg');
			const name = $nameInput.val().trim();

			if (!name) return;

			$btn.prop('disabled', true).find('.na-btn-spinner').show();
			$msg.text('').removeClass('error success');

			$.post(NotesAdda.ajax_url, {
				action: 'notes_adda_create_subject',
				name: name,
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				$btn.prop('disabled', false).find('.na-btn-spinner').hide();
				if (res.success) {
					$msg.text('Subject created successfully!').addClass('success');
					$nameInput.val('');
					app.showToast('Subject created!', 'success');
					app.loadSubjects(function() {
						self.render();
					});
				} else {
					$msg.text(res.data ? res.data.message : 'Error creating subject.').addClass('error');
				}
			}).fail(function() {
				$btn.prop('disabled', false).find('.na-btn-spinner').hide();
				$msg.text('Network error adding subject.').addClass('error');
			});
		},

		deleteSubject: function(id) {
			const self = this;
			const app = window.NotesAddaApp;

			$.post(NotesAdda.ajax_url, {
				action: 'notes_adda_delete_subject',
				id: id,
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				if (res.success) {
					app.showToast('Subject deleted successfully', 'success');
					app.loadSubjects(function() {
						self.render();
					});
				} else {
					app.showToast(res.data ? res.data.message : 'Cannot delete subject', 'error');
				}
			}).fail(function() {
				app.showToast('Network error deleting subject', 'error');
			});
		}
	};

	window.NotesAddaApp.SubjectManagement = SubjectManagement;
	if (window.NotesAddaApp.registerModule) {
		window.NotesAddaApp.registerModule('subjectManagement', SubjectManagement);
	}

})(jQuery);
