/**
 * Notes Adda - Core Frontend Application Script
 * Powered by jQuery & WordPress REST/AJAX
 */
(function($) {
    'use strict';

    if (typeof NotesAdda === 'undefined') {
        return;
    }

    const App = {
        currentPage: 1,
        activeView: 'library',

        init: function() {
            if ($('#notes-adda-app').length === 0) return;

            this.bindEvents();
            
            if (NotesAdda.is_logged_in) {
                this.loadNotes(this.activeView);
            }
        },

        bindEvents: function() {
            const self = this;

            // Auth View Tab Switching
            $('[data-switch]').on('click', function(e) {
                e.preventDefault();
                const target = $(this).data('switch');
                $('.na-auth-tab').removeClass('active');
                $(`.na-auth-tab[data-switch="${target}"]`).addClass('active');
                
                $('.na-auth-view').removeClass('active').hide();
                $('#na-' + target + '-view').addClass('active').show();
                $('.na-auth-message').text('').removeClass('error success');
            });

            // Login Form
            $('#na-login-form').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $form.find('button[type="submit"]');
                const $msg = $form.find('.na-auth-message');
                
                $btn.prop('disabled', true);
                $btn.find('.na-btn-text').text('Signing in...');
                $btn.find('.na-btn-spinner').show();
                $msg.text('').removeClass('error success');

                const postData = $form.serialize() + '&action=notes_adda_login&_ajax_nonce=' + NotesAdda.nonce;

                $.ajax({
                    url: NotesAdda.ajax_url,
                    type: 'POST',
                    data: postData,
                    success: function(res) {
                        if (res.success) {
                            $msg.text('Signed in successfully! Loading your dashboard...').addClass('success');
                            window.location.reload();
                        } else {
                            $btn.prop('disabled', false);
                            $btn.find('.na-btn-text').text('Sign In');
                            $btn.find('.na-btn-spinner').hide();
                            $msg.text(res.data && res.data.message ? res.data.message : 'Invalid credentials.').addClass('error');
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false);
                        $btn.find('.na-btn-text').text('Sign In');
                        $btn.find('.na-btn-spinner').hide();
                        const err = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'An error occurred during sign in.';
                        $msg.text(err).addClass('error');
                    }
                });
            });

            // Register Form
            $('#na-register-form').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $form.find('button[type="submit"]');
                const $msg = $form.find('.na-auth-message');

                $btn.prop('disabled', true);
                $btn.find('.na-btn-text').text('Creating Account...');
                $btn.find('.na-btn-spinner').show();
                $msg.text('').removeClass('error success');

                const postData = $form.serialize() + '&action=notes_adda_register&_ajax_nonce=' + NotesAdda.nonce;

                $.ajax({
                    url: NotesAdda.ajax_url,
                    type: 'POST',
                    data: postData,
                    success: function(res) {
                        if (res.success) {
                            $msg.text('Account created! Entering workspace...').addClass('success');
                            window.location.reload();
                        } else {
                            $btn.prop('disabled', false);
                            $btn.find('.na-btn-text').text('Create Account');
                            $btn.find('.na-btn-spinner').hide();
                            $msg.text(res.data && res.data.message ? res.data.message : 'Registration failed.').addClass('error');
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false);
                        $btn.find('.na-btn-text').text('Create Account');
                        $btn.find('.na-btn-spinner').hide();
                        const err = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'An error occurred during registration.';
                        $msg.text(err).addClass('error');
                    }
                });
            });

            // Logout
            $('#na-logout-btn').on('click', function(e) {
                e.preventDefault();
                const $btn = $(this);
                $btn.css('opacity', '0.5');
                $.post(NotesAdda.ajax_url, { action: 'notes_adda_logout', _ajax_nonce: NotesAdda.nonce }, function(res) {
                    window.location.reload();
                }).fail(function() {
                    window.location.reload();
                });
            });

            // Navigation Switcher
            $('.na-nav-item').on('click', function(e) {
                e.preventDefault();
                const view = $(this).data('view');
                if (!view || view === self.activeView) return;

                $('.na-nav-item').removeClass('active');
                $(this).addClass('active');

                self.activeView = view;
                $('.na-view').removeClass('active').hide();
                $('#na-view-' + view).addClass('active').show();
                
                self.currentPage = 1;
                self.loadNotes(view);
            });

            // Toolbar Filter Handlers
            $('#na-apply-filters').on('click', function() {
                self.currentPage = 1;
                $('#na-reset-filters').show();
                self.loadNotes('library');
            });

            $('#na-search-input').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    self.currentPage = 1;
                    $('#na-reset-filters').show();
                    self.loadNotes('library');
                }
            });

            $('#na-reset-filters').on('click', function() {
                $('#na-search-input').val('');
                $('#na-subject-filter').val('');
                $('#na-sort-filter').val('recent');
                $(this).hide();
                self.currentPage = 1;
                self.loadNotes('library');
            });

            // Pagination Click
            $(document).on('click', '.na-page-btn', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page) {
                    self.currentPage = page;
                    self.loadNotes(self.activeView);
                    $('.na-main-container').animate({ scrollTop: 0 }, 200);
                }
            });

            // Modal Triggers
            $('#na-new-note-btn, #na-mobile-new-note-btn, .na-open-create-btn').on('click', function(e) {
                e.preventDefault();
                self.openFormModal();
            });

            // Close Modals
            $(document).on('click', '.na-modal-close-btn', function(e) {
                e.preventDefault();
                const modalKey = $(this).data('modal');
                if (modalKey === 'form') {
                    $('#na-note-form-modal').removeClass('open');
                } else if (modalKey === 'details') {
                    $('#na-note-details-modal').removeClass('open');
                } else {
                    $('.na-modal-overlay').removeClass('open');
                }
            });

            // Close on overlay backdrop click
            $('.na-modal-overlay').on('click', function(e) {
                if ($(e.target).hasClass('na-modal-overlay')) {
                    $(this).removeClass('open');
                }
            });

            // Close on ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.na-modal-overlay').removeClass('open');
                }
            });

            // File selection change
            $('#na-note-file').on('change', function() {
                const file = this.files[0];
                if (file) {
                    $('#na-file-name-display').text(file.name + ' (' + (file.size / (1024 * 1024)).toFixed(2) + ' MB)');
                } else {
                    $('#na-file-name-display').text('Only PDF files up to 50 MB supported');
                }
            });

            // Note Form Submit
            $('#na-note-form').on('submit', function(e) {
                e.preventDefault();
                self.saveNote();
            });

            // Card Interactions
            $(document).on('click', '.na-btn-view', function() {
                const note = $(this).closest('.na-card').data('note');
                if (note) self.openDetailsModal(note);
            });

            $(document).on('click', '.na-btn-edit', function() {
                const note = $(this).closest('.na-card').data('note');
                if (note) self.openFormModal(note);
            });

            $(document).on('click', '.na-btn-delete', function() {
                const id = $(this).closest('.na-card').data('id');
                if (confirm('Are you sure you want to permanently delete this note?')) {
                    self.deleteNote(id);
                }
            });

            $(document).on('click', '.na-btn-like', function() {
                const $btn = $(this);
                const id = $btn.closest('.na-card').data('id');
                const isLiked = $btn.hasClass('liked');
                self.toggleLike(id, isLiked, $btn);
            });

            $(document).on('click', '.na-btn-report', function() {
                const id = $(this).closest('.na-card').data('id');
                const reason = prompt('Please specify the reason for reporting this note:');
                if (reason && reason.trim()) {
                    self.reportNote(id, reason.trim());
                }
            });
        },

        loadNotes: function(view) {
            const self = this;
            const $container = (view === 'my-notes') ? $('#na-my-notes-results') : $('#na-library-results');
            const $loading = (view === 'my-notes') ? $('#na-my-notes-loading') : $('#na-library-loading');
            const $empty = (view === 'my-notes') ? $('#na-my-notes-empty') : $('#na-library-empty');
            const $pagination = (view === 'my-notes') ? $('#na-my-notes-pagination') : $('#na-library-pagination');

            $container.empty();
            $empty.hide();
            $pagination.empty();
            $loading.show();

            const queryData = {
                action: 'notes_adda_query_notes',
                page: self.currentPage,
                per_page: 9
            };

            if (view === 'my-notes') {
                queryData.owner_id = NotesAdda.user_id;
            } else {
                const searchVal = $('#na-search-input').val();
                if (searchVal && searchVal.trim()) queryData.search = searchVal.trim();

                const subjectVal = $('#na-subject-filter').val();
                if (subjectVal) queryData.subject = subjectVal;

                const sortVal = $('#na-sort-filter').val();
                if (sortVal === 'popular') {
                    queryData.orderby = 'like_count';
                    queryData.order = 'DESC';
                }
            }

            $.ajax({
                url: NotesAdda.ajax_url,
                type: 'GET',
                data: queryData,
                success: function(res) {
                    $loading.hide();
                    if (res.success && res.data && res.data.items && res.data.items.length > 0) {
                        self.renderNotes(res.data.items, $container, view);
                        if (res.data.total_pages > 1) {
                            self.renderPagination(res.data.page, res.data.total_pages, $pagination);
                        }
                    } else {
                        $empty.show();
                    }
                },
                error: function() {
                    $loading.hide();
                    $container.html('<div class="na-state-box"><p style="color:var(--na-danger);">Failed to load notes. Please try again.</p></div>');
                }
            });
        },

        renderPagination: function(current, total, $container) {
            let html = '';
            if (current > 1) {
                html += `<button type="button" class="na-btn na-btn-secondary na-btn-sm na-page-btn" data-page="${current - 1}"><span class="dashicons dashicons-arrow-left-alt2"></span> Prev</button>`;
            }
            html += `<span class="na-page-indicator">Page ${current} of ${total}</span>`;
            if (current < total) {
                html += `<button type="button" class="na-btn na-btn-secondary na-btn-sm na-page-btn" data-page="${current + 1}">Next <span class="dashicons dashicons-arrow-right-alt2"></span></button>`;
            }
            $container.html(html);
        },

        renderNotes: function(notes, $container, view) {
            const self = this;

            notes.forEach(function(note) {
                const isOwner = (parseInt(note.owner_id) === parseInt(NotesAdda.user_id));

                const html = `
                    <div class="na-card" data-id="${note.id}">
                        <div class="na-card-top">
                            <div class="na-badge-group">
                                <span class="na-badge na-badge-subject">${self.escapeHtml(note.subject || 'General')}</span>
                                ${note.chapter ? `<span class="na-badge na-badge-chapter">${self.escapeHtml(note.chapter)}</span>` : ''}
                                ${parseInt(note.is_whole_notes) === 1 ? `<span class="na-badge na-badge-whole">Full Course</span>` : ''}
                            </div>
                        </div>
                        <h3 class="na-card-title">${self.escapeHtml(note.title)}</h3>
                        <p class="na-card-desc">${self.escapeHtml(note.description || 'No description provided.')}</p>
                        <div class="na-card-tags" id="na-tags-${note.id}"></div>
                        <div class="na-card-bottom">
                            <span class="na-card-date">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                ${self.formatDate(note.created_at)}
                            </span>
                            <div class="na-card-actions">
                                <button type="button" class="na-icon-btn na-btn-view" title="Preview Note">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                ${note.file_url ? `
                                <a href="${self.escapeHtml(note.file_url)}" target="_blank" class="na-icon-btn" title="Open PDF">
                                    <span class="dashicons dashicons-pdf"></span>
                                </a>` : ''}
                                <button type="button" class="na-icon-btn na-btn-like" title="Like Note">
                                    <span class="dashicons dashicons-heart"></span>
                                    <span class="like-count">${note.like_count || 0}</span>
                                </button>
                                ${isOwner ? `
                                <button type="button" class="na-icon-btn na-btn-edit" title="Edit Note">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="na-icon-btn na-danger-hover na-btn-delete" title="Delete Note">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>` : `
                                <button type="button" class="na-icon-btn na-btn-report" title="Report Note">
                                    <span class="dashicons dashicons-flag"></span>
                                </button>`}
                            </div>
                        </div>
                    </div>
                `;

                const $card = $(html);
                $card.data('note', note);
                $container.append($card);

                // Fetch tags
                self.loadCardTags(note.id);

                // Fetch like status
                self.checkLikeStatus(note.id, $card.find('.na-btn-like'));
            });
        },

        loadCardTags: function(note_id) {
            const self = this;
            $.get(NotesAdda.ajax_url, {
                action: 'notes_adda_get_note_tags',
                note_id: note_id
            }, function(res) {
                if (res.success && res.data && res.data.length > 0) {
                    const tagsHtml = res.data.map(function(t) {
                        return `<span class="na-tag">#${self.escapeHtml(t.name)}</span>`;
                    }).join('');
                    $(`#na-tags-${note_id}`).html(tagsHtml);
                }
            });
        },

        checkLikeStatus: function(note_id, $btn) {
            if (!NotesAdda.is_logged_in) return;
            $.get(NotesAdda.ajax_url, {
                action: 'notes_adda_get_like_status',
                note_id: note_id
            }, function(res) {
                if (res.success && res.data && res.data.has_liked) {
                    $btn.addClass('liked');
                }
            });
        },

        openFormModal: function(noteData) {
            const $modal = $('#na-note-form-modal');
            const $form = $('#na-note-form');
            const $msg = $('#na-form-message');
            $msg.text('').removeClass('error success');

            if (noteData) {
                $('#na-note-form-title').text('Edit Note');
                $('#na-note-id').val(noteData.id);
                $('#na-note-title').val(noteData.title);
                $('#na-note-subject').val(noteData.subject);
                $('#na-note-chapter').val(noteData.chapter || '');
                $('#na-note-description').val(noteData.description || '');
                $('#na-note-file').val('');
                $('#na-note-file-id').val(noteData.file_id || '');
                $('#na-note-file-url').val(noteData.file_url || '');
                $('#na-file-name-display').text(noteData.file_url ? 'Current PDF: ' + noteData.file_url.split('/').pop() : 'Select a new PDF if you wish to replace');
                $('#na-note-is-whole').prop('checked', parseInt(noteData.is_whole_notes) === 1);
                $('#na-save-note-btn .na-btn-text').text('Update Note');

                $.get(NotesAdda.ajax_url, {
                    action: 'notes_adda_get_note_tags',
                    note_id: noteData.id
                }, function(res) {
                    if (res.success && res.data && res.data.length > 0) {
                        const tags = res.data.map(t => t.name).join(', ');
                        $('#na-note-tags').val(tags);
                    } else {
                        $('#na-note-tags').val('');
                    }
                });
            } else {
                $('#na-note-form-title').text('Upload New Note');
                $form[0].reset();
                $('#na-note-id').val('');
                $('#na-note-file-id').val('');
                $('#na-note-file-url').val('');
                $('#na-file-name-display').text('Only PDF files up to 50 MB supported');
                $('#na-file-upload-status').text('');
                $('#na-save-note-btn .na-btn-text').text('Publish Note');
            }

            $modal.addClass('open');
        },

        saveNote: function() {
            const self = this;
            const $form = $('#na-note-form');
            const $btn = $('#na-save-note-btn');
            const $msg = $('#na-form-message');
            const fileInput = $('#na-note-file')[0];
            const isEdit = $('#na-note-id').val() !== '';

            $msg.text('').removeClass('error success');

            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                if (file.type !== 'application/pdf') {
                    $msg.text('Only PDF documents are supported.').addClass('error');
                    return;
                }
                if (file.size > 50 * 1024 * 1024) {
                    $msg.text('File size exceeds 50 MB limit.').addClass('error');
                    return;
                }

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
                            $msg.text('Upload Failed: ' + res.data.message).addClass('error');
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
            const $btn = $('#na-save-note-btn');
            const $msg = $('#na-form-message');
            const isEdit = $('#na-note-id').val() !== '';
            const action = isEdit ? 'notes_adda_update_note' : 'notes_adda_create_note';

            $btn.prop('disabled', true);
            $btn.find('.na-btn-text').text('Saving note...');
            $btn.find('.na-btn-spinner').show();

            const formData = $('#na-note-form').serializeArray();
            formData.push({ name: 'action', value: action });
            formData.push({ name: '_ajax_nonce', value: NotesAdda.nonce });

            if (!$('#na-note-is-whole').is(':checked')) {
                formData.push({ name: 'is_whole_notes', value: '0' });
            }

            $.ajax({
                url: NotesAdda.ajax_url,
                type: 'POST',
                data: formData,
                success: function(res) {
                    if (res.success) {
                        const note_id = res.data.id;
                        const tagsRaw = $('#na-note-tags').val();
                        if (tagsRaw.trim() !== '' || isEdit) {
                            const tagsArr = tagsRaw.split(',').map(t => t.trim()).filter(t => t);
                            $.post(NotesAdda.ajax_url, {
                                action: 'notes_adda_set_note_tags',
                                note_id: note_id,
                                tags: JSON.stringify(tagsArr),
                                _ajax_nonce: NotesAdda.nonce
                            }, function() {
                                self.finishSave();
                            }).fail(function() {
                                self.finishSave();
                            });
                        } else {
                            self.finishSave();
                        }
                    } else {
                        $btn.prop('disabled', false);
                        $btn.find('.na-btn-spinner').hide();
                        $btn.find('.na-btn-text').text(isEdit ? 'Update Note' : 'Publish Note');
                        $msg.text(res.data.message).addClass('error');
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false);
                    $btn.find('.na-btn-spinner').hide();
                    $btn.find('.na-btn-text').text(isEdit ? 'Update Note' : 'Publish Note');
                    const err = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'An error occurred saving the note.';
                    $msg.text(err).addClass('error');
                }
            });
        },

        finishSave: function() {
            $('#na-note-form-modal').removeClass('open');
            this.loadNotes(this.activeView);
        },

        deleteNote: function(id) {
            const self = this;
            $.post(NotesAdda.ajax_url, {
                action: 'notes_adda_delete_note',
                note_id: id,
                _ajax_nonce: NotesAdda.nonce
            }, function(res) {
                if (res.success) {
                    self.loadNotes(self.activeView);
                } else {
                    alert('Error: ' + (res.data ? res.data.message : 'Could not delete note.'));
                }
            });
        },

        toggleLike: function(id, isLiked, $btn) {
            if (!NotesAdda.is_logged_in) {
                alert('Please sign in to like notes.');
                return;
            }

            const action = isLiked ? 'notes_adda_remove_like' : 'notes_adda_add_like';
            const currentCount = parseInt($btn.find('.like-count').text()) || 0;

            // Optimistic update
            if (isLiked) {
                $btn.removeClass('liked');
                $btn.find('.like-count').text(Math.max(0, currentCount - 1));
            } else {
                $btn.addClass('liked');
                $btn.find('.like-count').text(currentCount + 1);
            }

            $.post(NotesAdda.ajax_url, {
                action: action,
                note_id: id,
                _ajax_nonce: NotesAdda.nonce
            }, function(res) {
                if (res.success) {
                    $btn.find('.like-count').text(res.data.like_count);
                } else {
                    // Revert if error
                    if (isLiked) {
                        $btn.addClass('liked');
                    } else {
                        $btn.removeClass('liked');
                    }
                    $btn.find('.like-count').text(currentCount);
                }
            }).fail(function() {
                // Revert
                if (isLiked) {
                    $btn.addClass('liked');
                } else {
                    $btn.removeClass('liked');
                }
                $btn.find('.like-count').text(currentCount);
            });
        },

        reportNote: function(id, reason) {
            if (!NotesAdda.is_logged_in) {
                alert('Please sign in to report notes.');
                return;
            }

            $.post(NotesAdda.ajax_url, {
                action: 'notes_adda_create_report',
                note_id: id,
                reason: reason,
                _ajax_nonce: NotesAdda.nonce
            }, function(res) {
                if (res.success) {
                    alert('Note report has been submitted. Thank you for keeping Notes Adda clean.');
                } else {
                    alert('Error: ' + (res.data ? res.data.message : 'Could not submit report.'));
                }
            });
        },

        openDetailsModal: function(note) {
            const self = this;
            const $body = $('#na-note-details-body');
            $body.html('<div class="na-state-box"><span class="dashicons dashicons-update na-spin na-state-icon"></span><p class="na-state-title">Loading note details...</p></div>');
            $('#na-note-details-modal').addClass('open');

            $.get(NotesAdda.ajax_url, {
                action: 'notes_adda_get_note_tags',
                note_id: note.id
            }, function(res) {
                let tagsHtml = '';
                if (res.success && res.data && res.data.length > 0) {
                    tagsHtml = '<div class="na-card-tags" style="margin: 12px 0;">' + res.data.map(function(t) {
                        return `<span class="na-tag">#${self.escapeHtml(t.name)}</span>`;
                    }).join('') + '</div>';
                }

                const html = `
                    <div class="na-preview-header">
                        <div class="na-badge-group" style="margin-bottom:8px;">
                            <span class="na-badge na-badge-subject">${self.escapeHtml(note.subject || 'General')}</span>
                            ${note.chapter ? `<span class="na-badge na-badge-chapter">${self.escapeHtml(note.chapter)}</span>` : ''}
                            ${parseInt(note.is_whole_notes) === 1 ? `<span class="na-badge na-badge-whole">Full Course</span>` : ''}
                        </div>
                        <h2 class="na-preview-title">${self.escapeHtml(note.title)}</h2>
                        <div class="na-card-date">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            Published on ${self.formatDate(note.created_at)}
                        </div>
                    </div>
                    ${tagsHtml}
                    <div class="na-preview-desc">${self.escapeHtml(note.description || 'No additional description provided.')}</div>
                    <div class="na-preview-actions">
                        ${note.file_url ? `
                        <a href="${self.escapeHtml(note.file_url)}" target="_blank" class="na-btn na-btn-primary">
                            <span class="dashicons dashicons-pdf"></span>
                            <span>Open & Download PDF</span>
                        </a>` : '<p style="color:var(--na-text-muted);">No document attached.</p>'}
                    </div>
                `;

                $body.html(html);
            }).fail(function() {
                $body.html('<p style="color:var(--na-danger);">Failed to load tags for this note.</p>');
            });
        },

        formatDate: function(dateStr) {
            if (!dateStr) return '';
            try {
                const d = new Date(dateStr);
                return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
            } catch (e) {
                return dateStr;
            }
        },

        escapeHtml: function(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };

    $(document).ready(function() {
        App.init();
    });

})(jQuery);
