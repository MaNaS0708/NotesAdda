(function($) {
    'use strict';

    if (typeof NotesAdda === 'undefined') return;

    const App = {
        currentPage: 1,

        init: function() {
            if ($('#notes-adda-app').length === 0) return;

            this.bindEvents();
            if (NotesAdda.is_logged_in) {
                this.loadNotes('library');
            }
        },

        bindEvents: function() {
            const self = this;

            // Auth Switch
            $('[data-switch]').on('click', function(e) {
                e.preventDefault();
                $('.na-auth-view').removeClass('active').hide();
                $('#na-' + $(this).data('switch') + '-view').addClass('active').show();
                $('.na-auth-message').text('').removeClass('error success');
            });

            // Login
            $('#na-login-form').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $msg = $form.find('.na-auth-message');
                const data = $form.serialize() + '&action=notes_adda_login&_ajax_nonce=' + NotesAdda.nonce;
                $msg.text('Logging in...').removeClass('error success');
                $.post(NotesAdda.ajax_url, data, function(res) {
                    if (res.success) {
                        $msg.text(res.data.message).addClass('success');
                        window.location.reload();
                    } else {
                        $msg.text(res.data.message).addClass('error');
                    }
                });
            });

            // Register
            $('#na-register-form').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $msg = $form.find('.na-auth-message');
                const data = $form.serialize() + '&action=notes_adda_register&_ajax_nonce=' + NotesAdda.nonce;
                $msg.text('Creating account...').removeClass('error success');
                $.post(NotesAdda.ajax_url, data, function(res) {
                    if (res.success) {
                        $msg.text(res.data.message).addClass('success');
                        window.location.reload();
                    } else {
                        $msg.text(res.data.message).addClass('error');
                    }
                });
            });

            // Logout
            $('#na-logout-btn').on('click', function(e) {
                e.preventDefault();
                $.post(NotesAdda.ajax_url, { action: 'notes_adda_logout', _ajax_nonce: NotesAdda.nonce }, function(res) {
                    if (res.success) window.location.reload();
                });
            });

            // Navigation
            $('.na-nav-link').on('click', function(e) {
                e.preventDefault();
                $('.na-nav-link').removeClass('active');
                $(this).addClass('active');
                const view = $(this).data('view');
                $('.na-view').removeClass('active').hide();
                $('#na-view-' + view).addClass('active').show();
                self.currentPage = 1;
                self.loadNotes(view);
            });

            // Filters
            $('#na-apply-filters').on('click', function() {
                self.currentPage = 1;
                self.loadNotes('library');
            });

            // Pagination
            $(document).on('click', '.na-page-btn', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page) {
                    self.currentPage = page;
                    const view = $('.na-nav-link.active').data('view');
                    self.loadNotes(view);
                }
            });

            // Modals
            $('#na-new-note-btn').on('click', function(e) {
                e.preventDefault();
                self.openFormModal();
            });

            $('.na-modal-close').on('click', function() {
                $(this).closest('.na-modal').removeClass('open');
            });

            // Form Submit
            $('#na-note-form').on('submit', function(e) {
                e.preventDefault();
                self.saveNote();
            });

            // Actions on cards
            $(document).on('click', '.na-btn-view', function() {
                const noteData = $(this).closest('.na-card').data('note');
                self.openDetailsModal(noteData);
            });

            $(document).on('click', '.na-btn-edit', function() {
                const noteData = $(this).closest('.na-card').data('note');
                self.openFormModal(noteData);
            });

            $(document).on('click', '.na-btn-delete', function() {
                if (confirm('Are you sure you want to delete this note?')) {
                    const id = $(this).closest('.na-card').data('id');
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
                const reason = prompt('Please enter a reason for reporting this note:');
                if (reason) {
                    self.reportNote(id, reason);
                }
            });
        },

        loadNotes: function(view) {
            const self = this;
            const $container = (view === 'my-notes') ? $('#na-my-notes-results') : $('#na-library-results');
            const $loading = (view === 'my-notes') ? $('#na-my-notes-loading') : $('#na-library-loading');
            const $pagination = (view === 'my-notes') ? $('#na-my-notes-pagination') : $('#na-library-pagination');
            
            $container.empty();
            if ($pagination.length) $pagination.empty();
            $loading.show();

            let data = {
                action: 'notes_adda_query_notes',
                page: self.currentPage,
                per_page: 12
            };

            if (view === 'my-notes') {
                data.owner_id = NotesAdda.user_id;
            } else {
                data.search = $('#na-search-input').val();
                data.subject = $('#na-subject-filter').val();
                
                const sort = $('#na-sort-filter').val();
                if (sort === 'popular') {
                    data.orderby = 'like_count';
                    data.order = 'DESC';
                }
            }

            $.ajax({
                url: NotesAdda.ajax_url,
                type: 'GET',
                data: data,
                success: function(res) {
                    $loading.hide();
                    if (res.success && res.data.items && res.data.items.length > 0) {
                        self.renderNotes(res.data.items, $container, view);
                        if ($pagination.length && res.data.total_pages > 1) {
                            self.renderPagination(res.data.page, res.data.total_pages, $pagination);
                        }
                    } else {
                        $container.html('<div class="na-empty-state"><span class="dashicons dashicons-search" style="font-size:48px; width:48px; height:48px; opacity:0.5;"></span><p>No notes found.</p></div>');
                    }
                },
                error: function() {
                    $loading.hide();
                    $container.html('<p>Error loading notes.</p>');
                }
            });
        },

        renderPagination: function(current, total, $container) {
            let html = '<div style="margin-top:20px; display:flex; gap:10px; justify-content:center;">';
            if (current > 1) {
                html += `<button class="na-btn na-btn-secondary na-page-btn" data-page="${current - 1}">Previous</button>`;
            }
            html += `<span style="display:flex; align-items:center; padding:0 10px;">Page ${current} of ${total}</span>`;
            if (current < total) {
                html += `<button class="na-btn na-btn-secondary na-page-btn" data-page="${current + 1}">Next</button>`;
            }
            html += '</div>';
            $container.html(html);
        },

        renderNotes: function(notes, $container, view) {
            const self = this;

            notes.forEach(function(note) {
                const isOwner = (parseInt(note.owner_id) === parseInt(NotesAdda.user_id));
                let actionsHtml = `<button class="na-btn-view" title="View"><span class="dashicons dashicons-visibility"></span></button>`;
                
                actionsHtml += `<button class="na-btn-like" title="Like"><span class="dashicons dashicons-heart"></span> <span class="like-count">${note.like_count}</span></button>`;
                actionsHtml += `<button class="na-btn-report" title="Report"><span class="dashicons dashicons-flag"></span></button>`;

                if (isOwner) {
                    actionsHtml += `<button class="na-btn-edit" title="Edit"><span class="dashicons dashicons-edit"></span></button>`;
                    actionsHtml += `<button class="na-btn-delete" title="Delete"><span class="dashicons dashicons-trash"></span></button>`;
                }

                const html = `
                    <div class="na-card" data-id="${note.id}">
                        <h3 class="na-card-title">${self.escapeHtml(note.title)}</h3>
                        <div class="na-card-subject">${self.escapeHtml(note.subject)} ${note.chapter ? '- ' + self.escapeHtml(note.chapter) : ''}</div>
                        <div class="na-card-desc">${self.escapeHtml(note.description || '')}</div>
                        <div class="na-card-meta">
                            <span>${new Date(note.created_at).toLocaleDateString()}</span>
                            <div class="na-card-actions">${actionsHtml}</div>
                        </div>
                    </div>
                `;
                const $card = $(html);
                $card.data('note', note);
                $container.append($card);
                
                self.checkLikeStatus(note.id, $card.find('.na-btn-like'));
            });
        },

        checkLikeStatus: function(note_id, $btn) {
            $.get(NotesAdda.ajax_url, {
                action: 'notes_adda_get_like_status',
                note_id: note_id
            }, function(res) {
                if (res.success && res.data.has_liked) {
                    $btn.addClass('liked');
                }
            });
        },

        openFormModal: function(noteData) {
            $('#na-form-message').html('');
            if (noteData) {
                $('#na-note-form-title').text('Edit Note');
                $('#na-note-id').val(noteData.id);
                $('#na-note-title').val(noteData.title);
                $('#na-note-subject').val(noteData.subject);
                $('#na-note-chapter').val(noteData.chapter);
                $('#na-note-description').val(noteData.description);
                $('#na-note-file').val('');
                $('#na-note-file-id').val(noteData.file_id || '');
                $('#na-note-file-url').val(noteData.file_url);
                $('#na-file-upload-status').text('Current file: ' + (noteData.file_url ? noteData.file_url.split('/').pop() : ''));
                $('#na-note-is-whole').prop('checked', parseInt(noteData.is_whole_notes) === 1);
                
                $.get(NotesAdda.ajax_url, {
                    action: 'notes_adda_get_note_tags',
                    note_id: noteData.id
                }, function(res) {
                    if (res.success && res.data.length > 0) {
                        const tags = res.data.map(t => t.name).join(', ');
                        $('#na-note-tags').val(tags);
                    } else {
                        $('#na-note-tags').val('');
                    }
                });

            } else {
                $('#na-note-form-title').text('Create Note');
                $('#na-note-form')[0].reset();
                $('#na-note-id').val('');
                $('#na-note-tags').val('');
                $('#na-note-file').val('');
                $('#na-note-file-id').val('');
                $('#na-note-file-url').val('');
                $('#na-file-upload-status').text('');
            }
            $('#na-note-form-modal').addClass('open');
        },

        saveNote: function() {
            const self = this;
            const $submitBtn = $('#na-note-form button[type="submit"]');
            const fileInput = $('#na-note-file')[0];
            const isEdit = $('#na-note-id').val() !== '';

            $('#na-form-message').html('');

            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                if (file.type !== 'application/pdf') {
                    $('#na-form-message').html('<p style="color:#f87171;">Only PDF files are allowed.</p>');
                    return;
                }
                if (file.size > 50 * 1024 * 1024) {
                    $('#na-form-message').html('<p style="color:#f87171;">File exceeds 50 MB limit.</p>');
                    return;
                }

                $submitBtn.prop('disabled', true);
                $('#na-file-upload-status').text('Uploading PDF, please wait...');

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
                            $('#na-file-upload-status').text('Upload complete.');
                            self.submitNoteForm();
                        } else {
                            $submitBtn.prop('disabled', false);
                            $('#na-file-upload-status').text('');
                            $('#na-form-message').html('<p style="color:#f87171;">Upload Error: ' + res.data.message + '</p>');
                        }
                    },
                    error: function(xhr) {
                        $submitBtn.prop('disabled', false);
                        $('#na-file-upload-status').text('');
                        const msg = xhr.responseJSON ? xhr.responseJSON.data.message : 'An error occurred during upload.';
                        $('#na-form-message').html('<p style="color:#f87171;">Upload Error: ' + msg + '</p>');
                    }
                });
            } else {
                if (!isEdit || $('#na-note-file-url').val() === '') {
                    $('#na-form-message').html('<p style="color:#f87171;">Please select a PDF file.</p>');
                    return;
                }
                self.submitNoteForm();
            }
        },

        submitNoteForm: function() {
            const self = this;
            const isEdit = $('#na-note-id').val() !== '';
            const action = isEdit ? 'notes_adda_update_note' : 'notes_adda_create_note';
            const $submitBtn = $('#na-note-form button[type="submit"]');
            
            $submitBtn.prop('disabled', true);
            const formData = $('#na-note-form').serializeArray();
            formData.push({name: 'action', value: action});
            formData.push({name: '_ajax_nonce', value: NotesAdda.nonce});
            
            if (!$('#na-note-is-whole').is(':checked')) {
                formData.push({name: 'is_whole_notes', value: '0'});
            }

            $.ajax({
                url: NotesAdda.ajax_url,
                type: 'POST',
                data: formData,
                success: function(res) {
                    $submitBtn.prop('disabled', false);
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
                            }, function(tagRes) {
                                if (tagRes.success) {
                                    self.finishSave();
                                } else {
                                    $('#na-form-message').html('<p style="color:#f87171;">Note saved, but tag assignment failed: ' + tagRes.data.message + '</p>');
                                }
                            }).fail(function(xhr) {
                                const msg = xhr.responseJSON ? xhr.responseJSON.data.message : 'An error occurred setting tags';
                                $('#na-form-message').html('<p style="color:#f87171;">Note saved, but tag assignment failed: ' + msg + '</p>');
                            });
                        } else {
                            self.finishSave();
                        }
                    } else {
                        $('#na-form-message').html('<p style="color:#f87171;">' + res.data.message + '</p>');
                    }
                },
                error: function(xhr) {
                    $submitBtn.prop('disabled', false);
                    const msg = xhr.responseJSON ? xhr.responseJSON.data.message : 'An error occurred';
                    $('#na-form-message').html('<p style="color:#f87171;">' + msg + '</p>');
                }
            });
        },

        finishSave: function() {
            $('#na-note-form-modal').removeClass('open');
            const view = $('.na-nav-link.active').data('view');
            this.loadNotes(view);
        },

        deleteNote: function(id) {
            const self = this;
            $.post(NotesAdda.ajax_url, {
                action: 'notes_adda_delete_note',
                note_id: id,
                _ajax_nonce: NotesAdda.nonce
            }, function(res) {
                if (res.success) {
                    const view = $('.na-nav-link.active').data('view');
                    self.loadNotes(view);
                } else {
                    alert('Error: ' + res.data.message);
                }
            });
        },

        toggleLike: function(id, isLiked, $btn) {
            if (!NotesAdda.is_logged_in) {
                alert('Please sign in to like notes.');
                return;
            }
            const action = isLiked ? 'notes_adda_remove_like' : 'notes_adda_add_like';
            $.post(NotesAdda.ajax_url, {
                action: action,
                note_id: id,
                _ajax_nonce: NotesAdda.nonce
            }, function(res) {
                if (res.success) {
                    if (isLiked) {
                        $btn.removeClass('liked');
                    } else {
                        $btn.addClass('liked');
                    }
                    $btn.find('.like-count').text(res.data.like_count);
                } else {
                    alert(res.data.message);
                }
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
                    alert('Report submitted successfully.');
                } else {
                    alert('Error: ' + res.data.message);
                }
            });
        },

        openDetailsModal: function(note) {
            const self = this;
            $('#na-note-details-body').html('<div class="na-loading"><span class="dashicons dashicons-update na-spin"></span> Loading...</div>');
            $('#na-note-details-modal').addClass('open');

            $.get(NotesAdda.ajax_url, {
                action: 'notes_adda_get_note_tags',
                note_id: note.id
            }, function(res) {
                let tagsHtml = '';
                if (res.success && res.data.length > 0) {
                    tagsHtml = '<div class="na-tags-wrap">' + res.data.map(t => `<span class="na-tag">${self.escapeHtml(t.name)}</span>`).join('') + '</div>';
                }

                const html = `
                    <h2 style="margin-top:0;">${self.escapeHtml(note.title)}</h2>
                    <p style="color:var(--na-accent-sage); font-weight:500;">
                        ${self.escapeHtml(note.subject)} ${note.chapter ? '- ' + self.escapeHtml(note.chapter) : ''}
                    </p>
                    ${tagsHtml}
                    <div style="margin:20px 0; white-space:pre-wrap; color:var(--na-text-main);">${self.escapeHtml(note.description || '')}</div>
                    <div style="margin-top:20px;">
                        <a href="${self.escapeHtml(note.file_url)}" target="_blank" class="na-btn na-btn-primary">
                            <span class="dashicons dashicons-external"></span> Open File
                        </a>
                    </div>
                `;
                $('#na-note-details-body').html(html);
            });
        },

        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };

    $(document).ready(function() {
        App.init();
    });

})(jQuery);
