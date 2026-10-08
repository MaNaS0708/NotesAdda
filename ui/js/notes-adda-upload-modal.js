/**
 * Notes Adda - Upload & Edit Note Modal Module
 * Handles file dropzone, client-side PDF verification, multipart AJAX upload, and metadata saving.
 *
 * @package Notes_Adda
 */

(function($) {
	'use strict';

	if (typeof window.NotesAddaApp === 'undefined') {
		window.NotesAddaApp = {};
	}

	const UploadModal = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			const self = this;
			const app = window.NotesAddaApp;

			// Modal Open Buttons
			$(document).on('click', '#na-new-note-btn, #na-mobile-new-note-btn, .na-open-create-btn', function(e) {
				e.preventDefault();
				self.openFormModal();
			});

			// Edit Note Click
			$(document).on('click', '.na-btn-edit', function(e) {
				e.preventDefault();
				e.stopPropagation();
				const noteId = $(this).data('id');
				const $card = $(this).closest('.na-card');
				const noteData = $card.data('note');
				if (noteData) {
					self.openFormModal(noteData);
				} else if (noteId) {
					$.get(NotesAdda.ajax_url, {
						action: 'notes_adda_get_note_details',
						note_id: noteId
					}, function(res) {
						if (res.success && res.data) {
							self.openFormModal(res.data);
						}
					});
				}
			});

			// Request Subject Link Trigger
			$(document).on('click', '.na-request-subject-trigger', function(e) {
				e.preventDefault();
				if (app.modules.subjectRequests && typeof app.modules.subjectRequests.openModal === 'function') {
					app.modules.subjectRequests.openModal();
				} else {
					$('#na-subject-request-modal').addClass('open');
				}
			});

			// File Selection Change
			$('#na-note-file').on('change', function(e) {
				const file = e.target.files[0];
				self.handleSelectedFile(file);
			});

			// Drag and Drop Zone
			const $dropzone = $('#na-file-dropzone');
			$dropzone.on('dragover dragenter', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$dropzone.addClass('dragover');
			});

			$dropzone.on('dragleave dragend drop', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$dropzone.removeClass('dragover');
			});

			$dropzone.on('drop', function(e) {
				const dt = e.originalEvent.dataTransfer;
				if (dt && dt.files && dt.files.length > 0) {
					$('#na-note-file')[0].files = dt.files;
					self.handleSelectedFile(dt.files[0]);
				}
			});

			// Form Submit
			$('#na-note-form').on('submit', function(e) {
				e.preventDefault();
				self.saveNote();
			});
		},

		handleSelectedFile: function(file) {
			const app = window.NotesAddaApp;
			const $msg = $('#na-form-message');
			if (!file) {
				$('#na-file-name-display').text('Only PDF files up to 50 MB supported');
				return;
			}

			if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
				$msg.text('Invalid format: Only PDF documents are allowed.').addClass('error');
				$('#na-note-file').val('');
				$('#na-file-name-display').text('Only PDF files up to 50 MB supported');
				return;
			}

			if (file.size > 50 * 1024 * 1024) {
				$msg.text('File exceeds maximum size limit of 50 MB.').addClass('error');
				$('#na-note-file').val('');
				$('#na-file-name-display').text('Only PDF files up to 50 MB supported');
				return;
			}

			$msg.text('').removeClass('error success');
			const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
			$('#na-file-name-display').text(`${file.name} (${sizeMb} MB)`);
		},

		openFormModal: function(noteData) {
			const self = this;
			const app = window.NotesAddaApp;
			self.reset();

			const isEdit = !!noteData;
			$('#na-note-form-title').text(isEdit ? 'Edit & Update Note' : 'Upload New Note');
			$('#na-save-note-btn .na-btn-text').text(isEdit ? 'Update Note' : 'Publish Note');

			if (isEdit) {
				$('#na-note-id').val(noteData.id);
				$('#na-note-title').val(noteData.title);
				$('#na-note-subject').val(noteData.subject);
				$('#na-note-chapter').val(noteData.chapter || '');
				$('#na-note-description').val(noteData.description || '');
				$('#na-note-is-whole').prop('checked', parseInt(noteData.is_whole_notes, 10) === 1);
				$('#na-note-file-url').val(noteData.file_url || '');
				$('#na-note-file-id').val(noteData.file_id || 0);

				if (noteData.file_url) {
					const filename = noteData.file_url.split('/').pop() || 'Attached Document';
					$('#na-file-name-display').text(`Current file: ${filename}`);
				}

				// Load tags
				$.get(NotesAdda.ajax_url, {
					action: 'notes_adda_get_note_tags',
					note_id: noteData.id
				}, function(res) {
					if (res.success && res.data && res.data.length > 0) {
						const tagNames = res.data.map(function(t) { return t.name; }).join(', ');
						$('#na-note-tags').val(tagNames);
					}
				});
			}

			$('#na-note-form-modal').addClass('open');
		},

		saveNote: function() {
			const self = this;
			const app = window.NotesAddaApp;
			const $btn = $('#na-save-note-btn');
			const $msg = $('#na-form-message');
			const isEdit = $('#na-note-id').val() !== '';
			const file = $('#na-note-file')[0].files[0];

			$msg.text('').removeClass('error success');

			// If a new file is attached, upload file first
			if (file) {
				$btn.prop('disabled', true);
				$btn.find('.na-btn-text').text('Uploading PDF...');
				$btn.find('.na-btn-spinner').show();
				$('#na-file-upload-status').text('Uploading file to server...');

				const uploadData = new FormData();
				uploadData.append('action', 'notes_adda_upload_note_file');
				uploadData.append('file', file);
				uploadData.append('_ajax_nonce', NotesAdda.nonce);

				$.ajax({
					url: NotesAdda.ajax_url,
					type: 'POST',
					data: uploadData,
					processData: false,
					contentType: false,
					success: function(res) {
						if (res.success) {
							$('#na-note-file-id').val(res.data.file_id);
							$('#na-note-file-url').val(res.data.file_url);
							$('#na-file-upload-status').text('PDF uploaded successfully.');
							self.submitNoteForm();
						} else {
							$btn.prop('disabled', false);
							$btn.find('.na-btn-spinner').hide();
							$btn.find('.na-btn-text').text(isEdit ? 'Update Note' : 'Publish Note');
							$('#na-file-upload-status').text('');
							$msg.text('Upload Failed: ' + (res.data ? res.data.message : 'Unknown error')).addClass('error');
						}
					},
					error: function(xhr) {
						$btn.prop('disabled', false);
						$btn.find('.na-btn-spinner').hide();
						$btn.find('.na-btn-text').text(isEdit ? 'Update Note' : 'Publish Note');
						$('#na-file-upload-status').text('');
						const err = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Error uploading PDF.';
						$msg.text('Upload Error: ' + err).addClass('error');
					}
				});
			} else {
				if (!isEdit || !$('#na-note-file-url').val()) {
					$msg.text('Please attach a PDF document.').addClass('error');
					return;
				}
				self.submitNoteForm();
			}
		},

		submitNoteForm: function() {
			const self = this;
			const app = window.NotesAddaApp;
			const $btn = $('#na-save-note-btn');
			const $msg = $('#na-form-message');
			const isEdit = $('#na-note-id').val() !== '';
			const action = isEdit ? 'notes_adda_update_note' : 'notes_adda_create_note';

			$btn.prop('disabled', true);
			$btn.find('.na-btn-text').text('Saving note...');
			$btn.find('.na-btn-spinner').show();

			const postData = $('#na-note-form').serialize() + '&action=' + action + '&_ajax_nonce=' + NotesAdda.nonce;

			$.ajax({
				url: NotesAdda.ajax_url,
				type: 'POST',
				data: postData,
				success: function(res) {
					if (res.success && res.data) {
						const noteId = res.data.note_id || $('#na-note-id').val();
						const tags = $('#na-note-tags').val().trim();

						// Save tags if provided
						if (noteId && tags) {
							$.post(NotesAdda.ajax_url, {
								action: 'notes_adda_set_note_tags',
								note_id: noteId,
								tags: tags,
								_ajax_nonce: NotesAdda.nonce
							}, function() {
								self.finishSave(isEdit);
							});
						} else {
							self.finishSave(isEdit);
						}
					} else {
						$btn.prop('disabled', false);
						$btn.find('.na-btn-spinner').hide();
						$btn.find('.na-btn-text').text(isEdit ? 'Update Note' : 'Publish Note');
						$msg.text(res.data ? res.data.message : 'Error saving note.').addClass('error');
					}
				},
				error: function(xhr) {
					$btn.prop('disabled', false);
					$btn.find('.na-btn-spinner').hide();
					$btn.find('.na-btn-text').text(isEdit ? 'Update Note' : 'Publish Note');
					const err = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Error communicating with server.';
					$msg.text(err).addClass('error');
				}
			});
		},

		finishSave: function(isEdit) {
			const self = this;
			const app = window.NotesAddaApp;
			$('#na-note-form-modal').removeClass('open');
			self.reset();
			app.showToast(isEdit ? 'Note updated successfully!' : 'Note submitted for expert review!', 'success');

			// Refresh active views
			if (app.activeView === 'my-notes' && app.modules.myNotes) {
				app.modules.myNotes.loadNotes();
			} else if (app.activeView === 'library' && app.modules.library) {
				app.modules.library.loadNotes();
			} else if (app.modules.library) {
				app.modules.library.loadNotes();
			}
		},

		reset: function() {
			$('#na-note-form')[0].reset();
			$('#na-note-id').val('');
			$('#na-note-file-url').val('');
			$('#na-note-file-id').val('');
			$('#na-file-name-display').text('Only PDF files up to 50 MB supported');
			$('#na-file-upload-status').text('');
			$('#na-form-message').text('').removeClass('error success');
			$('#na-save-note-btn').prop('disabled', false).find('.na-btn-spinner').hide();
			$('#na-save-note-btn .na-btn-text').text('Publish Note');
		}
	};

	window.NotesAddaApp.UploadModal = UploadModal;
	if (window.NotesAddaApp.registerModule) {
		window.NotesAddaApp.registerModule('uploadModal', UploadModal);
	}

})(jQuery);
