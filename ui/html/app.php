<div id="notes-adda-app" class="notes-adda-app-container">
    <?php if ( ! is_user_logged_in() ) : ?>
        <div class="na-auth-container">
            <div class="na-auth-card">
                <div class="na-brand-header">
                    <div class="na-brand-icon">
                        <span class="dashicons dashicons-book-alt"></span>
                    </div>
                    <h1 class="na-brand-title">Notes Adda</h1>
                    <p class="na-brand-tagline">Your collaborative student notes hub</p>
                </div>

                <div class="na-auth-nav">
                    <button type="button" class="na-auth-tab active" data-switch="login">Sign In</button>
                    <button type="button" class="na-auth-tab" data-switch="register">Create Account</button>
                </div>

                <div id="na-login-view" class="na-auth-view active">
                    <form id="na-login-form" class="na-form">
                        <div class="na-form-group">
                            <label for="na-login-username">Username or Email</label>
                            <div class="na-input-wrapper">
                                <span class="na-input-icon dashicons dashicons-admin-users"></span>
                                <input type="text" id="na-login-username" name="username" class="na-input" placeholder="Enter username or email" required autocomplete="username">
                            </div>
                        </div>
                        <div class="na-form-group">
                            <label for="na-login-password">Password</label>
                            <div class="na-input-wrapper">
                                <span class="na-input-icon dashicons dashicons-lock"></span>
                                <input type="password" id="na-login-password" name="password" class="na-input" placeholder="Enter password" required autocomplete="current-password">
                            </div>
                        </div>
                        <button type="submit" class="na-btn na-btn-primary na-btn-block">
                            <span class="na-btn-text">Sign In</span>
                            <span class="na-btn-spinner dashicons dashicons-update na-spin" style="display:none;"></span>
                        </button>
                        <div class="na-auth-message"></div>
                    </form>
                </div>
                
                <div id="na-register-view" class="na-auth-view" style="display:none;">
                    <form id="na-register-form" class="na-form">
                        <div class="na-form-row">
                            <div class="na-form-group na-col">
                                <label for="na-reg-username">Username</label>
                                <input type="text" id="na-reg-username" name="username" class="na-input" placeholder="Unique username" required autocomplete="username">
                            </div>
                            <div class="na-form-group na-col">
                                <label for="na-reg-email">Email</label>
                                <input type="email" id="na-reg-email" name="email" class="na-input" placeholder="student@college.edu" required autocomplete="email">
                            </div>
                        </div>
                        <div class="na-form-group">
                            <label for="na-reg-college">College / University</label>
                            <input type="text" id="na-reg-college" name="college" class="na-input" placeholder="e.g. Stanford University" required>
                        </div>
                        <div class="na-form-group">
                            <label for="na-reg-bio">Bio (Optional)</label>
                            <textarea id="na-reg-bio" name="bio" class="na-input na-textarea" rows="2" placeholder="Major, year, interests..."></textarea>
                        </div>
                        <div class="na-form-row">
                            <div class="na-form-group na-col">
                                <label for="na-reg-password">Password</label>
                                <input type="password" id="na-reg-password" name="password" class="na-input" placeholder="Min. 6 chars" required autocomplete="new-password">
                            </div>
                            <div class="na-form-group na-col">
                                <label for="na-reg-confirm">Confirm Password</label>
                                <input type="password" id="na-reg-confirm" name="confirm_password" class="na-input" placeholder="Re-enter password" required autocomplete="new-password">
                            </div>
                        </div>
                        <button type="submit" class="na-btn na-btn-primary na-btn-block">
                            <span class="na-btn-text">Create Account</span>
                            <span class="na-btn-spinner dashicons dashicons-update na-spin" style="display:none;"></span>
                        </button>
                        <div class="na-auth-message"></div>
                    </form>
                </div>
            </div>
        </div>
    <?php else : 
        $current_user = wp_get_current_user();
    ?>
        <div class="na-app-layout">
            <!-- Sidebar -->
            <aside class="na-sidebar">
                <div class="na-sidebar-header">
                    <div class="na-sidebar-brand">
                        <div class="na-brand-badge"><span class="dashicons dashicons-welcome-learn-more"></span></div>
                        <div>
                            <span class="na-brand-name">Notes Adda</span>
                            <span class="na-brand-caption">Study library</span>
                        </div>
                    </div>
                </div>
                
                <div class="na-user-card">
                    <div class="na-user-avatar">
                        <?php echo get_avatar( $current_user->ID, 44 ); ?>
                    </div>
                    <div class="na-user-details">
                        <span class="na-user-name"><?php echo esc_html( $current_user->display_name ); ?></span>
                        <span class="na-user-handle">@<?php echo esc_html( $current_user->user_login ); ?></span>
                    </div>
                </div>

                <div class="na-sidebar-action">
                    <button type="button" class="na-btn na-btn-primary na-btn-block" id="na-new-note-btn">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <span>Upload Note</span>
                    </button>
                </div>

                <nav class="na-sidebar-nav">
                    <div class="na-nav-label">Navigation</div>
                    <ul class="na-nav-list">
                        <li>
                            <a href="#library" class="na-nav-item active" data-view="library">
                                <span class="dashicons dashicons-books"></span>
                                <span class="na-nav-text">Browse library</span>
                            </a>
                        </li>
                        <li>
                            <a href="#my-notes" class="na-nav-item" data-view="my-notes">
                                <span class="dashicons dashicons-category"></span>
                                <span class="na-nav-text">My Notes</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="na-sidebar-footer">
                    <button type="button" id="na-logout-btn" class="na-logout-btn">
                        <span class="dashicons dashicons-migrate"></span>
                        <span>Sign Out</span>
                    </button>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="na-main-container">
                <!-- Mobile Top Bar -->
                <header class="na-mobile-header">
                    <div class="na-sidebar-brand">
                        <div class="na-brand-badge"><span class="dashicons dashicons-welcome-learn-more"></span></div>
                        <span class="na-brand-name">Notes Adda</span>
                    </div>
                    <button type="button" class="na-btn na-btn-primary na-btn-sm" id="na-mobile-new-note-btn">
                        <span class="dashicons dashicons-plus"></span> New
                    </button>
                </header>

                <!-- Central Library View -->
                <section id="na-view-library" class="na-view active">
                    <div class="na-view-header">
                        <div>
                            <span class="na-eyebrow">Community knowledge base</span>
                            <h2 class="na-view-title">Your study library, in one place.</h2>
                            <p class="na-view-subtitle">Search reliable notes, study guides, and complete course material shared by your community.</p>
                        </div>
                        <button type="button" class="na-btn na-btn-primary na-open-create-btn">
                            <span class="dashicons dashicons-upload"></span> Share notes
                        </button>
                    </div>

                    <!-- Toolbar / Filters -->
                    <div class="na-toolbar">
                        <div class="na-search-box">
                            <span class="dashicons dashicons-search na-search-icon"></span>
                            <input type="search" id="na-search-input" placeholder="Search by subject, topic, or title" class="na-input" aria-label="Search notes">
                        </div>
                        <div class="na-filters-row">
                            <select id="na-subject-filter" class="na-select">
                                <option value="">All Subjects</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Mathematics">Mathematics</option>
                                <option value="Physics">Physics</option>
                                <option value="Chemistry">Chemistry</option>
                                <option value="Biology">Biology</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Economics">Economics</option>
                                <option value="History">History</option>
                                <option value="Literature">Literature</option>
                                <option value="Other">Other</option>
                            </select>
                            <select id="na-sort-filter" class="na-select">
                                <option value="recent">Sort: Most Recent</option>
                                <option value="popular">Sort: Most Liked</option>
                            </select>
                            <button type="button" id="na-apply-filters" class="na-btn na-btn-secondary">
                                <span class="dashicons dashicons-search"></span>
                                <span>Search</span>
                            </button>
                            <button type="button" id="na-reset-filters" class="na-btn na-btn-ghost" style="display:none;">
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <div class="na-results-meta">
                        <span id="na-library-result-count">Explore recently shared notes</span>
                        <span class="na-results-meta-hint">Upload yours to help another student.</span>
                    </div>

                    <!-- Loading State -->
                    <div id="na-library-loading" class="na-state-box na-loading-box" style="display:none;">
                        <span class="dashicons dashicons-update na-spin na-state-icon"></span>
                        <p class="na-state-title">Loading notes...</p>
                    </div>

                    <!-- Empty State -->
                    <div id="na-library-empty" class="na-state-box na-empty-box" style="display:none;">
                        <div class="na-state-icon-wrap"><span class="dashicons dashicons-search na-state-icon"></span></div>
                        <h3 class="na-state-title">No notes found</h3>
                        <p class="na-state-desc">No notes match your filter criteria or the library is empty.</p>
                        <button type="button" class="na-btn na-btn-primary na-open-create-btn">
                            <span class="dashicons dashicons-plus"></span> Upload First Note
                        </button>
                    </div>

                    <!-- Note Grid -->
                    <div id="na-library-results" class="na-cards-grid"></div>

                    <!-- Pagination -->
                    <div id="na-library-pagination" class="na-pagination-container"></div>
                </section>

                <!-- My Notes View -->
                <section id="na-view-my-notes" class="na-view" style="display:none;">
                    <div class="na-view-header">
                        <div>
                            <span class="na-eyebrow">Your contribution</span>
                            <h2 class="na-view-title">My Notes</h2>
                            <p class="na-view-subtitle">Manage the material you have shared with the student community.</p>
                        </div>
                        <button type="button" class="na-btn na-btn-primary na-open-create-btn">
                            <span class="dashicons dashicons-plus"></span> Upload Note
                        </button>
                    </div>

                    <div class="na-results-meta na-results-meta-my">
                        <span id="na-my-notes-result-count">Your published material</span>
                        <span class="na-results-meta-hint">Keep titles and descriptions clear so students can find them.</span>
                    </div>

                    <!-- Loading State -->
                    <div id="na-my-notes-loading" class="na-state-box na-loading-box" style="display:none;">
                        <span class="dashicons dashicons-update na-spin na-state-icon"></span>
                        <p class="na-state-title">Loading your notes...</p>
                    </div>

                    <!-- Empty State -->
                    <div id="na-my-notes-empty" class="na-state-box na-empty-box" style="display:none;">
                        <div class="na-state-icon-wrap"><span class="dashicons dashicons-portfolio na-state-icon"></span></div>
                        <h3 class="na-state-title">You haven't uploaded any notes yet</h3>
                        <p class="na-state-desc">Share your lecture notes, summaries, or study guides with other students.</p>
                        <button type="button" class="na-btn na-btn-primary na-open-create-btn">
                            <span class="dashicons dashicons-plus"></span> Upload Your First Note
                        </button>
                    </div>

                    <!-- Note Grid -->
                    <div id="na-my-notes-results" class="na-cards-grid"></div>

                    <!-- Pagination -->
                    <div id="na-my-notes-pagination" class="na-pagination-container"></div>
                </section>
            </main>
        </div>

        <!-- Note Details Modal -->
        <div id="na-note-details-modal" class="na-modal-overlay">
            <div class="na-modal-dialog">
                <div class="na-modal-header">
                    <h3 class="na-modal-title">Note Preview</h3>
                    <button type="button" class="na-modal-close-btn" data-modal="details" aria-label="Close modal">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div id="na-note-details-body" class="na-modal-body">
                    <!-- Loaded dynamically via AJAX -->
                </div>
            </div>
        </div>

        <!-- Create / Edit Note Modal -->
        <div id="na-note-form-modal" class="na-modal-overlay">
            <div class="na-modal-dialog na-modal-lg">
                <div class="na-modal-header">
                    <h3 id="na-note-form-title" class="na-modal-title">Upload New Note</h3>
                    <button type="button" class="na-modal-close-btn" data-modal="form" aria-label="Close modal">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="na-modal-body">
                    <form id="na-note-form" class="na-form">
                        <input type="hidden" id="na-note-id" name="note_id" value="">

                        <div class="na-form-group">
                            <label for="na-note-title">Title <span class="na-required">*</span></label>
                            <input type="text" id="na-note-title" name="title" class="na-input" placeholder="e.g. Operating Systems: Process Scheduling" required>
                        </div>

                        <div class="na-form-row">
                            <div class="na-form-group na-col">
                                <label for="na-note-subject">Subject <span class="na-required">*</span></label>
                                <input type="text" id="na-note-subject" name="subject" class="na-input" placeholder="e.g. Computer Science" required>
                            </div>
                            <div class="na-form-group na-col">
                                <label for="na-note-chapter">Chapter / Unit (Optional)</label>
                                <input type="text" id="na-note-chapter" name="chapter" class="na-input" placeholder="e.g. Chapter 4">
                            </div>
                        </div>

                        <div class="na-form-group">
                            <label for="na-note-description">Description / Overview</label>
                            <textarea id="na-note-description" name="description" class="na-input na-textarea" rows="3" placeholder="Brief summary of what this note covers..."></textarea>
                        </div>

                        <!-- PDF Upload Area -->
                        <div class="na-form-group">
                            <label>PDF Document <span class="na-required">*</span> <span class="na-label-hint">(Max 50 MB)</span></label>
                            <div class="na-file-dropzone" id="na-file-dropzone">
                                <input type="file" id="na-note-file" accept="application/pdf" class="na-file-input">
                                <input type="hidden" id="na-note-file-url" name="file_url" value="">
                                <input type="hidden" id="na-note-file-id" name="file_id" value="">
                                
                                <div class="na-dropzone-content">
                                    <div class="na-dropzone-icon"><span class="dashicons dashicons-pdf"></span></div>
                                    <div class="na-dropzone-text">
                                        <span class="na-dropzone-primary">Click to select PDF or drag & drop</span>
                                        <span class="na-dropzone-secondary" id="na-file-name-display">Only PDF files up to 50 MB supported</span>
                                    </div>
                                </div>
                            </div>
                            <div id="na-file-upload-status" class="na-file-status"></div>
                        </div>

                        <div class="na-form-group na-checkbox-wrapper">
                            <label class="na-checkbox-label">
                                <input type="checkbox" id="na-note-is-whole" name="is_whole_notes">
                                <span class="na-checkbox-custom"></span>
                                <span class="na-checkbox-text">This note covers the complete course / whole syllabus</span>
                            </label>
                        </div>

                        <div class="na-form-group">
                            <label for="na-note-tags">Tags <span class="na-label-hint">(Comma separated)</span></label>
                            <input type="text" id="na-note-tags" name="tags" class="na-input" placeholder="e.g. Midterms, Sem 4, Cheatsheet">
                        </div>

                        <div id="na-form-message" class="na-form-message"></div>

                        <div class="na-modal-footer">
                            <button type="button" class="na-btn na-btn-ghost na-modal-close-btn" data-modal="form">Cancel</button>
                            <button type="submit" class="na-btn na-btn-primary" id="na-save-note-btn">
                                <span class="na-btn-text">Publish Note</span>
                                <span class="na-btn-spinner dashicons dashicons-update na-spin" style="display:none;"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>
