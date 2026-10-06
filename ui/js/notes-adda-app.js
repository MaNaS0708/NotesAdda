/**
 * Notes Adda - Core Frontend Application Script
 * Powered by jQuery & WordPress AJAX
 */
(function($) {
    'use strict';

    if (typeof NotesAdda === 'undefined') {
        return;
    }

    const App = {
        currentPage: 1,
        activeView: 'library',
        subjects: [],
        reviewFilter: 'unverified',
        userSearchTerm: '',
        userPage: 1,

        init: function() {
            if ($('#notes-adda-app').length === 0) return;

            this.bindEvents();

            if (NotesAdda.is_logged_in) {
                this.loadSubjects();
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
                $.post(NotesAdda.ajax_url, { action: 'notes_adda_logout', _ajax_nonce: NotesAdda.nonce }, function() {
                    window.location.reload();
                }).fail(function() {
                    window.location.reload();
                });
            });

            // Navigation Switcher
            $(document).on('click', '.na-nav-item', function(e) {
                e.preventDefault();
                const view = $(this).data('view');
                if (!view || view === self.activeView) return;

                $('.na-nav-item').removeClass('active');
                $(`.na-nav-item[data-view="${view}"]`).addClass('active');

                self.switchView(view);
            });

            // Browse button in empty/bookmarks header
            $('#na-bookmarks-browse-btn, #na-empty-browse-btn').on('click', function(e) {
                e.preventDefault();
                self.switchView('library');
                $('.na-nav-item').removeClass('active');
                $('.na-nav-item[data-view="library"]').addClass('active');
            });

            // Toolbar Filter Handlers (Library)
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

            // Pagination Click (Generic)
            $(document).on('click', '.na-page-btn', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                const paginationType = $(this).closest('.na-pagination-container').attr('id');

                if (paginationType === 'na-users-pagination') {
                    self.userPage = page;
                    self.loadUsers();
                } else if (paginationType === 'na-review-pagination') {
                    self.currentPage = page;
                    self.loadReviewQueue();
                } else {
                    self.currentPage = page;
                    self.loadNotes(self.activeView);
                }
                $('.na-main-container').animate({ scrollTop: 0 }, 200);
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
                const note = $(this).closest('.na-card, .na-review-card').data('note');
                if (note) self.openDetailsModal(note);
            });

            $(document).on('click', '.na-btn-edit', function() {
                const note = $(this).closest('.na-card, .na-review-card').data('note');
                if (note) self.openFormModal(note);
            });

            $(document).on('click', '.na-btn-delete', function() {
                const id = $(this).closest('.na-card, .na-review-card').data('id');
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

            $(document).on('click', '.na-btn-bookmark', function() {
                const $btn = $(this);
                const id = $btn.closest('.na-card, .na-review-card').data('id');
                self.toggleBookmark(id, $btn);
            });

            $(document).on('click', '#na-modal-bookmark-btn', function() {
                const $btn = $(this);
                const id = $btn.data('id');
                self.toggleBookmark(id, $btn, true);
            });

            $(document).on('click', '.na-btn-report', function() {
                const id = $(this).closest('.na-card').data('id');
                const reason = prompt('Please specify the reason for reporting this note:');
                if (reason && reason.trim()) {
                    self.reportNote(id, reason.trim());
                }
            });

            // Review Queue Tabs
            $('.na-review-filter-tabs .na-tab-btn').on('click', function(e) {
                e.preventDefault();
                $('.na-review-filter-tabs .na-tab-btn').removeClass('active');
                $(this).addClass('active');
                self.reviewFilter = $(this).data('review-status');
                self.currentPage = 1;
                self.loadReviewQueue();
            });

            // Review Actions (Verify / Mark Unverified)
            $(document).on('click', '.na-btn-verify', function() {
                const $btn = $(this);
                const id = $btn.closest('.na-review-card').data('id');
                self.submitReview(id, 'verified', $btn);
            });

            $(document).on('click', '.na-btn-unverify', function() {
                const $btn = $(this);
                const id = $btn.closest('.na-review-card').data('id');
                self.submitReview(id, 'unverified', $btn);
            });

            // Subject Management Add Form
            $('#na-add-subject-form').on('submit', function(e) {
                e.preventDefault();
                self.addSubject();
            });

            // Subject Delete Button
            $(document).on('click', '.na-btn-delete-subject', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                if (confirm(`Are you sure you want to delete the subject "${name}"?`)) {
                    self.deleteSubject(id);
                }
            });

            // User Management Search
            $('#na-search-users-btn').on('click', function() {
                self.userPage = 1;
                self.userSearchTerm = $('#na-user-search-input').val().trim();
                self.loadUsers();
            });

            $('#na-user-search-input').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    self.userPage = 1;
                    self.userSearchTerm = $(this).val().trim();
                    self.loadUsers();
                }
            });

            // User Management Role Change
            $(document).on('change', '.na-user-role-select', function() {
                const $select = $(this);
                const userId = $select.data('user-id');
                const newRole = $select.val();
                const currentRole = $select.data('current-role');
                const userName = $select.data('user-name');

                if (newRole === currentRole) return;

                const roleLabel = $select.find('option:selected').text();
                if (confirm(`Change role for ${userName} to ${roleLabel}?`)) {
                    self.updateUserRole(userId, newRole, $select);
                } else {
                    $select.val(currentRole);
                }
            });
        },

        switchView: function(view) {
            this.activeView = view;
            this.currentPage = 1;

            $('.na-view').removeClass('active').hide();
            $('#na-view-' + view).addClass('active').show();

            if (view === 'library' || view === 'my-notes' || view === 'bookmarks') {
                this.loadNotes(view);
            } else if (view === 'review-queue') {
                this.loadReviewQueue();
            } else if (view === 'subjects') {
                this.renderSubjectsView();
            } else if (view === 'users') {
                this.userPage = 1;
                this.loadUsers();
            }
        },

        loadSubjects: function(callback) {
            const self = this;
            $.get(NotesAdda.ajax_url, { action: 'notes_adda_get_subjects' }, function(res) {
                if (res.success && Array.isArray(res.data)) {
                    self.subjects = res.data;
                    self.populateSubjectDropdowns();
                    if (self.activeView === 'subjects') {
                        self.renderSubjectsView();
                    }
                    if (typeof callback === 'function') callback(self.subjects);
                }
            });
        },

        populateSubjectDropdowns: function() {
            const $filter = $('#na-subject-filter');
            const $formSelect = $('#na-note-subject');
            const $noSubjectsWarning = $('#na-no-subjects-warning');

            const currentFilterVal = $filter.val();
            const currentFormVal = $formSelect.val();

            // Library Filter Dropdown
            $filter.html('<option value="">All Subjects</option>');
            this.subjects.forEach(function(s) {
                $filter.append(`<option value="${App.escapeHtml(s.name)}">${App.escapeHtml(s.name)}</option>`);
            });
            if (currentFilterVal) $filter.val(currentFilterVal);

            // Note Form Subject Dropdown
            $formSelect.html('<option value="">Select a Subject *</option>');
            if (this.subjects.length === 0) {
                $formSelect.prop('disabled', true);
                if ($noSubjectsWarning.length) $noSubjectsWarning.show();
            } else {
                $formSelect.prop('disabled', false);
                if ($noSubjectsWarning.length) $noSubjectsWarning.hide();
                this.subjects.forEach(function(s) {
                    $formSelect.append(`<option value="${App.escapeHtml(s.name)}">${App.escapeHtml(s.name)}</option>`);
                });
                if (currentFormVal) $formSelect.val(currentFormVal);
            }
        },

        loadNotes: function(view) {
            const self = this;
            let $container, $loading, $empty, $pagination;

            if (view === 'my-notes') {
                $container = $('#na-my-notes-results');
                $loading = $('#na-my-notes-loading');
                $empty = $('#na-my-notes-empty');
                $pagination = $('#na-my-notes-pagination');
            } else if (view === 'bookmarks') {
                $container = $('#na-bookmarks-results');
                $loading = $('#na-bookmarks-loading');
                $empty = $('#na-bookmarks-empty');
                $pagination = $('#na-bookmarks-pagination');
            } else {
                $container = $('#na-library-results');
                $loading = $('#na-library-loading');
                $empty = $('#na-library-empty');
                $pagination = $('#na-library-pagination');
            }

            $container.empty();
            $empty.hide();
            $pagination.empty();
            $loading.show();
            this.setResultSummary(view, null);

            let action = 'notes_adda_query_notes';
            const queryData = {
                page: self.currentPage,
                per_page: 9
            };

            if (view === 'my-notes') {
                queryData.owner_id = NotesAdda.user_id;
            } else if (view === 'bookmarks') {
                action = 'notes_adda_query_bookmarks';
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
            queryData.action = action;

            $.ajax({
                url: NotesAdda.ajax_url,
                type: 'GET',
                data: queryData,
                success: function(res) {
                    $loading.hide();
                    if (res.success && res.data && res.data.items && res.data.items.length > 0) {
                        self.renderNotes(res.data.items, $container, view);
                        self.setResultSummary(view, res.data.total);
                        if (res.data.total_pages > 1) {
                            self.renderPagination(res.data.page, res.data.total_pages, $pagination);
                        }
                    } else {
                        self.setResultSummary(view, 0);
                        $empty.show();
                    }
                },
                error: function() {
                    $loading.hide();
                    self.setResultSummary(view, 'error');
                    $container.html('<div class="na-state-box"><p style="color:var(--na-danger);">Failed to load notes. Please try again.</p></div>');
                }
            });
        },

        setResultSummary: function(view, total) {
            let $summary;
            if (view === 'my-notes') {
                $summary = $('#na-my-notes-result-count');
            } else if (view === 'bookmarks') {
                $summary = $('#na-bookmarks-result-count');
            } else if (view === 'review-queue') {
                $summary = $('#na-review-result-count');
            } else {
                $summary = $('#na-library-result-count');
            }

            if (!$summary.length) return;

            if (total === null) {
                $summary.text('Loading study material...');
            } else if (total === 'error') {
                $summary.text('Unable to load notes right now');
            } else if (view === 'my-notes') {
                $summary.text(total === 1 ? '1 note published by you' : total + ' notes published by you');
            } else if (view === 'bookmarks') {
                $summary.text(total === 1 ? '1 saved note' : total + ' saved notes');
            } else if (view === 'review-queue') {
                $summary.text(total === 1 ? '1 note found in queue' : total + ' notes found in queue');
            } else {
                $summary.text(total === 1 ? '1 note found' : total + ' notes found');
            }
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
                const isVerified = (note.review_status === 'verified');
                const isBookmarked = !!note.is_bookmarked;

                const html = `
                    <article class="na-card ${isVerified ? 'na-card-verified' : ''}" data-id="${note.id}">
                        <div class="na-card-top">
                            <div class="na-badge-group">
                                <span class="na-badge na-badge-subject">${self.escapeHtml(note.subject || 'General')}</span>
                                ${note.chapter ? `<span class="na-badge na-badge-chapter">${self.escapeHtml(note.chapter)}</span>` : ''}
                                ${parseInt(note.is_whole_notes) === 1 ? `<span class="na-badge na-badge-whole">Full Course</span>` : ''}
                                ${isVerified ? `
                                    <span class="na-badge na-badge-verified" title="Verified by subject expert">
                                        <span class="dashicons dashicons-yes-alt"></span> Expert Verified
                                    </span>
                                ` : `
                                    <span class="na-badge na-badge-unverified" title="Community upload (unverified)">
                                        <span class="dashicons dashicons-warning"></span> Unverified
                                    </span>
                                `}
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
                                <button type="button" class="na-icon-btn na-btn-view" title="Preview note" aria-label="Preview ${self.escapeHtml(note.title)}">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                ${note.file_url ? `
                                <a href="${self.escapeHtml(note.file_url)}" target="_blank" rel="noopener noreferrer" class="na-icon-btn" title="Open PDF" aria-label="Open PDF for ${self.escapeHtml(note.title)}">
                                    <span class="dashicons dashicons-pdf"></span>
                                </a>` : ''}
                                <button type="button" class="na-icon-btn na-btn-bookmark ${isBookmarked ? 'bookmarked' : ''}" title="${isBookmarked ? 'Remove bookmark' : 'Bookmark note'}" aria-label="Bookmark ${self.escapeHtml(note.title)}">
                                    <span class="dashicons dashicons-bookmark"></span>
                                </button>
                                <button type="button" class="na-icon-btn na-btn-like" title="Like note" aria-label="Like ${self.escapeHtml(note.title)}">
                                    <span class="dashicons dashicons-heart"></span>
                                    <span class="like-count">${note.like_count || 0}</span>
                                </button>
                                ${isOwner || NotesAdda.can_manage_all_notes ? `
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
                    </article>
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
            const self = this;
            const $modal = $('#na-note-form-modal');
            const $form = $('#na-note-form');
            const $msg = $('#na-form-message');
            $msg.text('').removeClass('error success');

            // Refresh subjects dropdown to guarantee freshness
            this.populateSubjectDropdowns();

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
            const selectedSubject = $('#na-note-subject').val();

            $msg.text('').removeClass('error success');

            if (!selectedSubject) {
                if (self.subjects.length === 0) {
                    $msg.text('No subjects are available yet. Ask an expert or admin to add one.').addClass('error');
                } else {
                    $msg.text('Please select a subject from the list.').addClass('error');
                }
                return;
            }

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
                        $msg.text(res.data && res.data.message ? res.data.message : 'Failed to save note.').addClass('error');
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
            this.loadSubjects();
        },

        deleteNote: function(id) {
            const self = this;
            $.post(NotesAdda.ajax_url, {
                action: 'notes_adda_delete_note',
                note_id: id,
                _ajax_nonce: NotesAdda.nonce
            }, function(res) {
                if (res.success) {
                    if (self.activeView === 'review-queue') {
                        self.loadReviewQueue();
                    } else {
                        self.loadNotes(self.activeView);
                    }
                    self.loadSubjects();
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
                if (isLiked) {
                    $btn.addClass('liked');
                } else {
                    $btn.removeClass('liked');
                }
                $btn.find('.like-count').text(currentCount);
            });
        },

        toggleBookmark: function(noteId, $btn, isModal) {
            const self = this;
            if (!NotesAdda.is_logged_in) {
                alert('Please sign in to bookmark notes.');
                return;
            }

            const wasBookmarked = $btn.hasClass('bookmarked');
            // Optimistic toggle
            if (wasBookmarked) {
                $btn.removeClass('bookmarked');
                if (isModal) $btn.find('.na-btn-text').text('Bookmark');
            } else {
                $btn.addClass('bookmarked');
                if (isModal) $btn.find('.na-btn-text').text('Bookmarked');
            }

            // Sync other card button on screen
            $(`.na-card[data-id="${noteId}"] .na-btn-bookmark`).toggleClass('bookmarked', !wasBookmarked);

            $.post(NotesAdda.ajax_url, {
                action: 'notes_adda_toggle_bookmark',
                note_id: noteId,
                _ajax_nonce: NotesAdda.nonce
            }, function(res) {
                if (res.success) {
                    const isBookmarked = res.data.is_bookmarked;
                    $btn.toggleClass('bookmarked', isBookmarked);
                    $(`.na-card[data-id="${noteId}"] .na-btn-bookmark`).toggleClass('bookmarked', isBookmarked);
                    if (isModal) {
                        $btn.find('.na-btn-text').text(isBookmarked ? 'Bookmarked' : 'Bookmark');
                    }
                    // If we're on bookmarks view and removed, reload
                    if (self.activeView === 'bookmarks' && !isBookmarked) {
                        self.loadNotes('bookmarks');
                    }
                } else {
                    // Revert
                    $btn.toggleClass('bookmarked', wasBookmarked);
                    $(`.na-card[data-id="${noteId}"] .na-btn-bookmark`).toggleClass('bookmarked', wasBookmarked);
                    if (isModal) {
                        $btn.find('.na-btn-text').text(wasBookmarked ? 'Bookmarked' : 'Bookmark');
                    }
                    alert('Could not update bookmark.');
                }
            }).fail(function() {
                $btn.toggleClass('bookmarked', wasBookmarked);
                $(`.na-card[data-id="${noteId}"] .na-btn-bookmark`).toggleClass('bookmarked', wasBookmarked);
                if (isModal) {
                    $btn.find('.na-btn-text').text(wasBookmarked ? 'Bookmarked' : 'Bookmark');
                }
                alert('An error occurred updating bookmark.');
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

            const isVerified = (note.review_status === 'verified');
            const isBookmarked = !!note.is_bookmarked;

            $.get(NotesAdda.ajax_url, {
                action: 'notes_adda_get_note_tags',
                note_id: note.id
            }, function(res) {
                let tagsHtml = '';
                if (res.success && res.data && res.data.length > 0) {
                    tagsHtml = '<div class="na-card-tags" style="margin: 14px 0;">' + res.data.map(function(t) {
                        return `<span class="na-tag">#${self.escapeHtml(t.name)}</span>`;
                    }).join('') + '</div>';
                }

                const html = `
                    <div class="na-preview-header">
                        <div class="na-badge-group" style="margin-bottom:10px;">
                            <span class="na-badge na-badge-subject">${self.escapeHtml(note.subject || 'General')}</span>
                            ${note.chapter ? `<span class="na-badge na-badge-chapter">${self.escapeHtml(note.chapter)}</span>` : ''}
                            ${parseInt(note.is_whole_notes) === 1 ? `<span class="na-badge na-badge-whole">Full Course</span>` : ''}
                            ${isVerified ? `
                                <span class="na-badge na-badge-verified">
                                    <span class="dashicons dashicons-yes-alt"></span> Expert Verified
                                </span>
                            ` : `
                                <span class="na-badge na-badge-unverified">
                                    <span class="dashicons dashicons-warning"></span> Unverified
                                </span>
                            `}
                        </div>
                        <h2 class="na-preview-title">${self.escapeHtml(note.title)}</h2>
                        <div class="na-card-date" style="margin-top:6px;">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <span>Published on ${self.formatDate(note.created_at)}</span>
                            ${note.uploader_name ? `<span style="margin-left:8px;">by <strong>${self.escapeHtml(note.uploader_name)}</strong></span>` : ''}
                        </div>
                    </div>

                    ${!isVerified ? `
                        <div class="na-unverified-warning">
                            <div class="na-warning-icon"><span class="dashicons dashicons-warning"></span></div>
                            <div class="na-warning-text">
                                This community note has not been verified by an expert. Please check the material independently before relying on it.
                            </div>
                        </div>
                    ` : (note.reviewer_name ? `
                        <div class="na-verified-info">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <span>Verified by <strong>${self.escapeHtml(note.reviewer_name)}</strong> ${note.reviewed_at ? 'on ' + self.formatDate(note.reviewed_at) : ''}</span>
                            ${note.review_note ? `<p class="na-reviewer-note-text">"${self.escapeHtml(note.review_note)}"</p>` : ''}
                        </div>
                    ` : '')}

                    ${tagsHtml}
                    <div class="na-preview-desc">${self.escapeHtml(note.description || 'No additional description provided.')}</div>
                    
                    <div class="na-preview-actions">
                        ${note.file_url ? `
                        <a href="${self.escapeHtml(note.file_url)}" target="_blank" rel="noopener noreferrer" class="na-btn na-btn-primary">
                            <span class="dashicons dashicons-pdf"></span>
                            <span>Open & Download PDF</span>
                        </a>` : '<p style="color:var(--na-muted);">No document attached.</p>'}
                        
                        <button type="button" class="na-btn na-btn-secondary ${isBookmarked ? 'bookmarked' : ''}" id="na-modal-bookmark-btn" data-id="${note.id}">
                            <span class="dashicons dashicons-bookmark"></span>
                            <span class="na-btn-text">${isBookmarked ? 'Bookmarked' : 'Bookmark'}</span>
                        </button>
                    </div>
                `;

                $body.html(html);
            }).fail(function() {
                $body.html('<p style="color:var(--na-danger);">Failed to load note details.</p>');
            });
        },

        /* ==================================================
           REVIEW QUEUE WORKFLOW
           ================================================== */
        loadReviewQueue: function() {
            const self = this;
            const $container = $('#na-review-results');
            const $loading = $('#na-review-loading');
            const $empty = $('#na-review-empty');
            const $pagination = $('#na-review-pagination');

            $container.empty();
            $empty.hide();
            $pagination.empty();
            $loading.show();
            this.setResultSummary('review-queue', null);

            const queryData = {
                action: 'notes_adda_query_notes',
                is_review_queue: 1,
                page: self.currentPage,
                per_page: 10
            };

            if (self.reviewFilter !== 'all') {
                queryData.review_status = self.reviewFilter;
            }

            $.ajax({
                url: NotesAdda.ajax_url,
                type: 'GET',
                data: queryData,
                success: function(res) {
                    $loading.hide();
                    if (res.success && res.data && res.data.items && res.data.items.length > 0) {
                        self.renderReviewQueue(res.data.items, $container);
                        self.setResultSummary('review-queue', res.data.total);
                        if (res.data.total_pages > 1) {
                            self.renderPagination(res.data.page, res.data.total_pages, $pagination);
                        }
                    } else {
                        self.setResultSummary('review-queue', 0);
                        $empty.show();
                    }
                },
                error: function() {
                    $loading.hide();
                    self.setResultSummary('review-queue', 'error');
                    $container.html('<div class="na-state-box"><p style="color:var(--na-danger);">Failed to load review queue.</p></div>');
                }
            });
        },

        renderReviewQueue: function(notes, $container) {
            const self = this;

            notes.forEach(function(note) {
                const isVerified = (note.review_status === 'verified');

                const html = `
                    <div class="na-review-card ${isVerified ? 'verified-card' : 'unverified-card'}" data-id="${note.id}">
                        <div class="na-review-card-main">
                            <div class="na-badge-group">
                                <span class="na-badge na-badge-subject">${self.escapeHtml(note.subject || 'General')}</span>
                                ${note.chapter ? `<span class="na-badge na-badge-chapter">${self.escapeHtml(note.chapter)}</span>` : ''}
                                ${isVerified ? `
                                    <span class="na-badge na-badge-verified"><span class="dashicons dashicons-yes-alt"></span> Verified</span>
                                ` : `
                                    <span class="na-badge na-badge-unverified"><span class="dashicons dashicons-warning"></span> Unverified</span>
                                `}
                            </div>
                            <h3 class="na-review-card-title">${self.escapeHtml(note.title)}</h3>
                            <p class="na-review-card-desc">${self.escapeHtml(note.description || 'No description provided.')}</p>
                            
                            <div class="na-review-card-meta">
                                <span>Uploaded by: <strong>${self.escapeHtml(note.uploader_name || 'Student')}</strong></span>
                                <span>•</span>
                                <span>${self.formatDate(note.created_at)}</span>
                                ${note.reviewer_name ? `<span>•</span> <span>Reviewed by: <strong>${self.escapeHtml(note.reviewer_name)}</strong></span>` : ''}
                            </div>
                        </div>

                        <div class="na-review-card-actions">
                            <button type="button" class="na-btn na-btn-secondary na-btn-sm na-btn-view" title="Preview note details">
                                <span class="dashicons dashicons-visibility"></span> Preview
                            </button>
                            ${note.file_url ? `
                            <a href="${self.escapeHtml(note.file_url)}" target="_blank" rel="noopener noreferrer" class="na-btn na-btn-secondary na-btn-sm" title="Open PDF">
                                <span class="dashicons dashicons-pdf"></span> Open PDF
                            </a>` : ''}
                            
                            ${isVerified ? `
                                <button type="button" class="na-btn na-btn-ghost na-btn-sm na-btn-unverify">
                                    <span class="dashicons dashicons-dismiss"></span> Mark Unverified
                                </button>
                            ` : `
                                <button type="button" class="na-btn na-btn-primary na-btn-sm na-btn-verify">
                                    <span class="dashicons dashicons-yes-alt"></span> Verify Note
                                </button>
                            `}

                            <button type="button" class="na-btn na-btn-danger na-btn-sm na-btn-delete" title="Delete Note">
                                <span class="dashicons dashicons-trash"></span> Delete
                            </button>
                        </div>
                    </div>
                `;

                const $card = $(html);
                $card.data('note', note);
                $container.append($card);
            });
        },

        submitReview: function(noteId, status, $btn) {
            const self = this;
            $btn.prop('disabled', true).css('opacity', '0.6');

            let reviewNote = '';
            if (status === 'verified') {
                const notePrompt = prompt('Optional reviewer note (leave empty if none):');
                if (notePrompt !== null) {
                    reviewNote = notePrompt.trim();
                }
            }

            $.post(NotesAdda.ajax_url, {
                action: 'notes_adda_review_note',
                note_id: noteId,
                status: status,
                review_note: reviewNote,
                _ajax_nonce: NotesAdda.nonce
            }, function(res) {
                if (res.success) {
                    self.loadReviewQueue();
                } else {
                    $btn.prop('disabled', false).css('opacity', '1');
                    alert('Error: ' + (res.data ? res.data.message : 'Could not update review status.'));
                }
            }).fail(function(xhr) {
                $btn.prop('disabled', false).css('opacity', '1');
                const err = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Failed to submit review.';
                alert(err);
            });
        },

        /* ==================================================
           SUBJECT MANAGEMENT
           ================================================== */
        renderSubjectsView: function() {
            const self = this;
            const $container = $('#na-subjects-results');
            const $loading = $('#na-subjects-loading');
            const $empty = $('#na-subjects-empty');
            const $count = $('#na-subjects-count');

            $loading.hide();
            $container.empty();

            if (self.subjects.length === 0) {
                $count.text('0 Subjects');
                $empty.show();
                return;
            }

            $empty.hide();
            $count.text(self.subjects.length === 1 ? '1 Subject Defined' : self.subjects.length + ' Subjects Defined');

            const html = `
                <div class="na-subjects-table-wrapper">
                    <table class="na-admin-table">
                        <thead>
                            <tr>
                                <th>Subject Name</th>
                                <th>Slug</th>
                                <th>Associated Notes</th>
                                <th>Created Date</th>
                                ${NotesAdda.can_manage_users ? '<th>Actions</th>' : ''}
                            </tr>
                        </thead>
                        <tbody>
                            ${self.subjects.map(function(s) {
                                const noteCount = parseInt(s.note_count) || 0;
                                return `
                                    <tr>
                                        <td><strong>${self.escapeHtml(s.name)}</strong></td>
                                        <td><code class="na-code-slug">${self.escapeHtml(s.slug)}</code></td>
                                        <td>
                                            <span class="na-count-badge ${noteCount > 0 ? 'active' : ''}">
                                                ${noteCount} note${noteCount === 1 ? '' : 's'}
                                            </span>
                                        </td>
                                        <td>${self.formatDate(s.created_at)}</td>
                                        ${NotesAdda.can_manage_users ? `
                                            <td>
                                                <button type="button" class="na-btn na-btn-danger na-btn-sm na-btn-delete-subject" data-id="${s.id}" data-name="${self.escapeHtml(s.name)}">
                                                    <span class="dashicons dashicons-trash"></span> Delete
                                                </button>
                                            </td>
                                        ` : ''}
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            `;

            $container.html(html);
        },

        addSubject: function() {
            const self = this;
            const $input = $('#na-new-subject-name');
            const $btn = $('#na-add-subject-btn');
            const $msg = $('#na-subject-form-msg');
            const name = $input.val().trim();

            $msg.text('').removeClass('error success');

            if (!name) {
                $msg.text('Please enter a subject name.').addClass('error');
                return;
            }

            $btn.prop('disabled', true);
            $btn.find('.na-btn-spinner').show();
            $btn.find('.na-btn-text').text('Adding...');

            $.post(NotesAdda.ajax_url, {
                action: 'notes_adda_create_subject',
                name: name,
                _ajax_nonce: NotesAdda.nonce
            }, function(res) {
                $btn.prop('disabled', false);
                $btn.find('.na-btn-spinner').hide();
                $btn.find('.na-btn-text').text('Add Subject');

                if (res.success) {
                    $input.val('');
                    $msg.text(`Subject "${res.data.name}" added successfully!`).addClass('success');
                    self.loadSubjects(function() {
                        self.renderSubjectsView();
                    });
                } else {
                    $msg.text(res.data ? res.data.message : 'Could not add subject.').addClass('error');
                }
            }).fail(function(xhr) {
                $btn.prop('disabled', false);
                $btn.find('.na-btn-spinner').hide();
                $btn.find('.na-btn-text').text('Add Subject');
                const err = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Error adding subject.';
                $msg.text(err).addClass('error');
            });
        },

        deleteSubject: function(id) {
            const self = this;
            $.post(NotesAdda.ajax_url, {
                action: 'notes_adda_delete_subject',
                id: id,
                _ajax_nonce: NotesAdda.nonce
            }, function(res) {
                if (res.success) {
                    self.loadSubjects(function() {
                        self.renderSubjectsView();
                    });
                } else {
                    alert(res.data ? res.data.message : 'Could not delete subject.');
                }
            }).fail(function(xhr) {
                const err = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Error deleting subject.';
                alert(err);
            });
        },

        /* ==================================================
           USER MANAGEMENT (ADMIN ONLY)
           ================================================== */
        loadUsers: function() {
            const self = this;
            const $container = $('#na-users-results');
            const $loading = $('#na-users-loading');
            const $empty = $('#na-users-empty');
            const $pagination = $('#na-users-pagination');
            const $count = $('#na-users-count');

            $container.empty();
            $empty.hide();
            $pagination.empty();
            $loading.show();
            $count.text('Loading community members...');

            $.ajax({
                url: NotesAdda.ajax_url,
                type: 'GET',
                data: {
                    action: 'notes_adda_get_users',
                    search: self.userSearchTerm,
                    page: self.userPage,
                    per_page: 15,
                    _ajax_nonce: NotesAdda.nonce
                },
                success: function(res) {
                    $loading.hide();
                    if (res.success && res.data && res.data.users && res.data.users.length > 0) {
                        self.renderUsers(res.data.users, $container);
                        $count.text(res.data.total === 1 ? '1 Community Member' : res.data.total + ' Community Members');
                        if (res.data.total_pages > 1) {
                            self.renderPagination(res.data.page, res.data.total_pages, $pagination);
                        }
                    } else {
                        $count.text('0 Community Members');
                        $empty.show();
                    }
                },
                error: function() {
                    $loading.hide();
                    $count.text('Error loading members');
                    $container.html('<div class="na-state-box"><p style="color:var(--na-danger);">Failed to load users.</p></div>');
                }
            });
        },

        renderUsers: function(users, $container) {
            const self = this;

            const html = `
                <div class="na-users-table-wrapper">
                    <table class="na-admin-table na-users-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>College</th>
                                <th>Joined</th>
                                <th>Notes Adda Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${users.map(function(u) {
                                return `
                                    <tr class="${u.is_self ? 'na-self-row' : ''}">
                                        <td>
                                            <div class="na-user-row-info">
                                                <img src="${self.escapeHtml(u.avatar_url)}" class="na-row-avatar" alt="${self.escapeHtml(u.display_name)}">
                                                <div>
                                                    <div class="na-row-name">${self.escapeHtml(u.display_name)} ${u.is_self ? '<span class="na-self-badge">(You)</span>' : ''}</div>
                                                    <div class="na-row-handle">@${self.escapeHtml(u.username)}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>${self.escapeHtml(u.email)}</td>
                                        <td>${self.escapeHtml(u.college || '—')}</td>
                                        <td>${self.formatDate(u.registered)}</td>
                                        <td>
                                            ${u.is_self ? `
                                                <span class="na-role-badge na-role-admin">Notes Adda Admin</span>
                                            ` : `
                                                <select class="na-select na-user-role-select" data-user-id="${u.id}" data-current-role="${u.role}" data-user-name="${self.escapeHtml(u.display_name)}" aria-label="Change role for ${self.escapeHtml(u.display_name)}">
                                                    <option value="notes_adda_student" ${u.role === 'notes_adda_student' ? 'selected' : ''}>Student</option>
                                                    <option value="notes_adda_expert" ${u.role === 'notes_adda_expert' ? 'selected' : ''}>Expert</option>
                                                    <option value="notes_adda_admin" ${u.role === 'notes_adda_admin' ? 'selected' : ''}>Notes Adda Admin</option>
                                                </select>
                                            `}
                                        </td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>

                <!-- Responsive Stacked Mobile Cards -->
                <div class="na-users-mobile-cards">
                    ${users.map(function(u) {
                        return `
                            <div class="na-user-mobile-card ${u.is_self ? 'na-self-row' : ''}">
                                <div class="na-user-mobile-header">
                                    <img src="${self.escapeHtml(u.avatar_url)}" class="na-row-avatar" alt="${self.escapeHtml(u.display_name)}">
                                    <div>
                                        <div class="na-row-name">${self.escapeHtml(u.display_name)} ${u.is_self ? '<span class="na-self-badge">(You)</span>' : ''}</div>
                                        <div class="na-row-handle">@${self.escapeHtml(u.username)}</div>
                                    </div>
                                </div>
                                <div class="na-user-mobile-meta">
                                    <div><strong>Email:</strong> ${self.escapeHtml(u.email)}</div>
                                    <div><strong>College:</strong> ${self.escapeHtml(u.college || '—')}</div>
                                    <div><strong>Joined:</strong> ${self.formatDate(u.registered)}</div>
                                </div>
                                <div class="na-user-mobile-role">
                                    <label>Role:</label>
                                    ${u.is_self ? `
                                        <span class="na-role-badge na-role-admin">Notes Adda Admin</span>
                                    ` : `
                                        <select class="na-select na-user-role-select" data-user-id="${u.id}" data-current-role="${u.role}" data-user-name="${self.escapeHtml(u.display_name)}">
                                            <option value="notes_adda_student" ${u.role === 'notes_adda_student' ? 'selected' : ''}>Student</option>
                                            <option value="notes_adda_expert" ${u.role === 'notes_adda_expert' ? 'selected' : ''}>Expert</option>
                                            <option value="notes_adda_admin" ${u.role === 'notes_adda_admin' ? 'selected' : ''}>Notes Adda Admin</option>
                                        </select>
                                    `}
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;

            $container.html(html);
        },

        updateUserRole: function(userId, newRole, $select) {
            const self = this;
            $select.prop('disabled', true);

            $.post(NotesAdda.ajax_url, {
                action: 'notes_adda_update_user_role',
                user_id: userId,
                role: newRole,
                _ajax_nonce: NotesAdda.nonce
            }, function(res) {
                $select.prop('disabled', false);
                if (res.success) {
                    $select.data('current-role', newRole);
                    self.showToast('Role updated successfully.');
                } else {
                    $select.val($select.data('current-role'));
                    alert('Error: ' + (res.data ? res.data.message : 'Could not update role.'));
                }
            }).fail(function(xhr) {
                $select.prop('disabled', false);
                $select.val($select.data('current-role'));
                const err = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Error updating user role.';
                alert(err);
            });
        },

        showToast: function(msg) {
            let $toast = $('#na-toast');
            if (!$toast.length) {
                $toast = $('<div id="na-toast" class="na-toast"></div>').appendTo('body');
            }
            $toast.text(msg).addClass('show');
            setTimeout(function() {
                $toast.removeClass('show');
            }, 3000);
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
