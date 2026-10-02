<div id="notes-adda-app" class="notes-adda-app-container na-theme-dark">
    <?php if ( ! is_user_logged_in() ) : ?>
        <div class="na-logged-out-state">
            <span class="dashicons dashicons-lock na-icon-large"></span>
            <h2>Welcome to Notes Adda</h2>
            <p>Please sign in to access the notes library and manage your notes.</p>
            <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="na-btn na-btn-primary">Sign In to Continue</a>
        </div>
    <?php else : ?>
        <div class="na-app-layout">
            <!-- Sidebar / Nav -->
            <nav class="na-sidebar">
                <div class="na-brand">
                    <span class="dashicons dashicons-book"></span> Notes Adda
                </div>
                <ul class="na-nav-menu">
                    <li><a href="#" class="na-nav-link active" data-view="library"><span class="dashicons dashicons-portfolio"></span> Central Library</a></li>
                    <li><a href="#" class="na-nav-link" data-view="my-notes"><span class="dashicons dashicons-category"></span> My Notes</a></li>
                    <li><a href="#" class="na-btn na-btn-primary na-btn-full" id="na-new-note-btn"><span class="dashicons dashicons-plus"></span> New Note</a></li>
                </ul>
            </nav>

            <!-- Main Content -->
            <main class="na-main-content">
                <!-- View: Central Library -->
                <section id="na-view-library" class="na-view active">
                    <header class="na-view-header">
                        <h2>Central Library</h2>
                    </header>
                    <div class="na-filters">
                        <input type="text" id="na-search-input" placeholder="Search notes..." class="na-input">
                        <select id="na-subject-filter" class="na-select">
                            <option value="">All Subjects</option>
                            <option value="Math">Math</option>
                            <option value="Science">Science</option>
                            <option value="History">History</option>
                            <option value="Computer Science">Computer Science</option>
                        </select>
                        <select id="na-sort-filter" class="na-select">
                            <option value="recent">Recent First</option>
                            <option value="popular">Most Liked</option>
                        </select>
                        <button id="na-apply-filters" class="na-btn na-btn-secondary">Apply Filters</button>
                    </div>
                    
                    <div id="na-library-loading" class="na-loading" style="display:none;"><span class="dashicons dashicons-update na-spin"></span> Loading...</div>
                    <div id="na-library-results" class="na-grid"></div>
                    <div id="na-library-pagination" class="na-pagination"></div>
                </section>

                <!-- View: My Notes -->
                <section id="na-view-my-notes" class="na-view" style="display:none;">
                    <header class="na-view-header">
                        <h2>My Notes</h2>
                    </header>
                    <div id="na-my-notes-loading" class="na-loading" style="display:none;"><span class="dashicons dashicons-update na-spin"></span> Loading...</div>
                    <div id="na-my-notes-results" class="na-grid"></div>
                    <div id="na-my-notes-pagination" class="na-pagination"></div>
                </section>
            </main>
        </div>

        <!-- Modal: Note Details -->
        <div id="na-note-details-modal" class="na-modal">
            <div class="na-modal-content">
                <span class="na-modal-close" data-modal="details">&times;</span>
                <div id="na-note-details-body"></div>
            </div>
        </div>

        <!-- Modal: Create / Edit Note -->
        <div id="na-note-form-modal" class="na-modal">
            <div class="na-modal-content">
                <span class="na-modal-close" data-modal="form">&times;</span>
                <h3 id="na-note-form-title">Create Note</h3>
                <form id="na-note-form">
                    <input type="hidden" id="na-note-id" name="note_id" value="">
                    
                    <div class="na-form-group">
                        <label>Title</label>
                        <input type="text" id="na-note-title" name="title" class="na-input" required>
                    </div>
                    
                    <div class="na-form-group">
                        <label>Subject</label>
                        <input type="text" id="na-note-subject" name="subject" class="na-input" required>
                    </div>

                    <div class="na-form-group">
                        <label>Chapter</label>
                        <input type="text" id="na-note-chapter" name="chapter" class="na-input">
                    </div>

                    <div class="na-form-group">
                        <label>Description</label>
                        <textarea id="na-note-description" name="description" class="na-input"></textarea>
                    </div>

                    <div class="na-form-group">
                        <label>PDF File</label>
                        <input type="file" id="na-note-file" accept="application/pdf" class="na-input">
                        <input type="hidden" id="na-note-file-url" name="file_url" value="">
                        <input type="hidden" id="na-note-file-id" name="file_id" value="">
                        <div id="na-file-upload-status" style="margin-top: 5px; font-size: 0.85em; color: var(--na-text-muted);"></div>
                    </div>

                    <div class="na-form-group na-checkbox-group">
                        <input type="checkbox" id="na-note-is-whole" name="is_whole_notes">
                        <label for="na-note-is-whole">Is Whole Notes?</label>
                    </div>

                    <div class="na-form-group">
                        <label>Tags (Comma separated)</label>
                        <input type="text" id="na-note-tags" name="tags" class="na-input" placeholder="e.g. Sem 7, Midterms">
                    </div>

                    <div class="na-form-actions">
                        <button type="submit" class="na-btn na-btn-primary">Save Note</button>
                    </div>
                    <div id="na-form-message"></div>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>
