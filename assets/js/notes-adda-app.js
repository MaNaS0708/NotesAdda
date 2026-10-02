(function($) {
    'use strict';

    if (typeof NotesAdda === 'undefined') return;

    const App = {
        init: function() {
            if ($('#notes-adda-app').length === 0) return;

            // Init icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            this.bindEvents();
            if (NotesAdda.is_logged_in) {
                this.loadNotes('library');
            }
        },

        bindEvents: function() {
            const self = this;

            // Navigation
            $('.na-nav-link').on('click', function(e) {
                e.preventDefault();
                $('.na-nav-link').removeClass('active');
                $(this).addClass('active');
                const view = $(this).data('view');
                $('.na-view').removeClass('active').hide();
                $('#na-view-' + view).addClass('active').show();
                self.loadNotes(view);
            });

            // Filters
            $('#na-apply-filters').on('click', function() {
                self.loadNotes('library');
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
            
            $container.empty();
            $loading.show();

            let data = {
                action: 'notes_adda_query_notes'
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
                    if (res.success && res.data.notes) {
                        self.renderNotes(res.data.notes, $container, view);
                    } else {
                        $container.html('<p>No notes found.</p>');
                    }
                },
                error: function() {
                    $loading.hide();
                    $container.html('<p>Error loading notes.</p>');
                }
            });
        },

        renderNotes: function(notes, $container, view) {
            const self = this;
            if (notes.length === 0) {
                $container.html('<p>No notes found.</p>');
                return;
            }

            notes.forEach(function(note) {
                const isOwner = (parseInt(note.owner_id) === parseInt(NotesAdda.user_id));
                let actionsHtml = `<button class="na-btn-view" title="View"><i data-lucide="eye"></i></button>`;
                
                // Likes functionality available for all logged-in
                actionsHtml += `<button class="na-btn-like" title="Like"><i data-lucide="heart"></i> <span class="like-count">${note.like_count}</span></button>`;
                actionsHtml += `<button class="na-btn-report" title="Report"><i data-lucide="flag"></i></button>`;

                if (isOwner) {
                    actionsHtml += `<button class="na-btn-edit" title="Edit"><i data-lucide="edit-2"></i></button>`;
                    actionsHtml += `<button class="na-btn-delete" title="Delete"><i data-lucide="trash-2"></i></button>`;
                }

                // Check tags if we can, but since query doesn't bring tags we might need to fetch them. For now, empty or fetch per note.
                // We'll leave tags blank on the card to avoid N+1 requests, or fetch them inside details.

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
                
                // Check if user liked it
                self.checkLikeStatus(note.id, $card.find('.na-btn-like'));
            });

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        },

        checkLikeStatus: function(note_id, $btn) {
            $.get(NotesAdda.ajax_url, {
                action: 'notes_adda_get_like_status',
                note_id: note_id,
                user_id: NotesAdda.user_id
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
                $('#na-note-file-url').val(noteData.file_url);
                $('#na-note-is-whole').prop('checked', parseInt(noteData.is_whole_notes) === 1);
                
                // Fetch tags for edit
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
            }
            $('#na-note-form-modal').addClass('open');
        },

        saveNote: function() {
            const self = this;
            const isEdit = $('#na-note-id').val() !== '';
            const action = isEdit ? 'notes_adda_update_note' : 'notes_adda_create_note';
            
            const formData = $('#na-note-form').serializeArray();
            formData.push({name: 'action', value: action});
            formData.push({name: '_ajax_nonce', value: NotesAdda.nonce});
            
            // Checkbox value
            if (!$('#na-note-is-whole').is(':checked')) {
                formData.push({name: 'is_whole_notes', value: '0'});
            }

            $.ajax({
                url: NotesAdda.ajax_url,
                type: 'POST',
                data: formData,
                success: function(res) {
                    if (res.success) {
                        const note_id = res.data.id;
                        // Save tags
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
                            });
                        } else {
                            self.finishSave();
                        }
                    } else {
                        $('#na-form-message').html('<p style="color:#f87171;">' + res.data.message + '</p>');
                    }
                },
                error: function(xhr) {
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
            $('#na-note-details-body').html('<div class="na-loading"><i data-lucide="loader" class="na-spin"></i> Loading...</div>');
            $('#na-note-details-modal').addClass('open');
            if (typeof lucide !== 'undefined') lucide.createIcons();

            // Fetch tags
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
                            <i data-lucide="external-link"></i> Open File
                        </a>
                    </div>
                `;
                $('#na-note-details-body').html(html);
                if (typeof lucide !== 'undefined') lucide.createIcons();
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
